<?php

class Wuunder_WuunderConnector_WebhookController extends Mage_Core_Controller_Front_Action
{

    private $carrier_code_mapping = array(
        "PNL" => "PostNL",
        "DES" => "DHL",
        "POST_NL" => "PostNL",
        "TRA" => "TransMission",
        "DPS" => "DPD",
        "UPS" => "UPS",
        "EEX" => "DHL",
        "DHF" => "DHL",
        "DPD_DE" => "DPD",
        "TNT" => "TNT Express",
        "DPA" => "DPD",
        "DHL" => "DHL",
        "DPD" => "DPD",
        "D4U" => "DHL",
        "TEF" => "TNT",
        "GLS" => "GLS",
        "DEP" => "DHL"
    );

    /*
     * Webhook; called by wuunder api
     */
    public function callAction()
    {
        if (!empty($this->getRequest()->getParam('order_id')) && !empty($this->getRequest()->getParam('token'))) {
            $result = json_decode(file_get_contents('php://input'), true);
            if ($result['action'] === "shipment_booked") {
                Mage::helper('wuunderconnector')->log("Webhook - Shipment for order: " . $this->getRequest()->getParam('order_id'));
                $processDataSuccess = Mage::helper('wuunderconnector')->processDataFromApi($result['shipment'], "no_retour", $this->getRequest()->getParam('order_id'), $this->getRequest()->getParam('token'));
                if (!$processDataSuccess) {
                    Mage::helper('wuunderconnector')->log("Cannot update wuunder_shipment data");
                }
            } else if ($result['action'] === "track_and_trace_updated") {
                Mage::helper('wuunderconnector')->log("Webhook - Track and trace for order: " . $this->getRequest()->getParam('order_id'));
                $processTrackingDataSuccess = Mage::helper('wuunderconnector')->processTrackingDataFromApi($result['carrier_code'], $result['track_and_trace_code'], $this->getRequest()->getParam('order_id'), $this->getRequest()->getParam('token'));
                if ($processTrackingDataSuccess) {
                    $shipmentInfo = Mage::helper('wuunderconnector')->getShipmentInfo($this->getRequest()->getParam('order_id'));
                    if (!empty($shipmentInfo['label_id'])) {
                        $this->ship($this->getRequest()->getParam('order_id'), $result['carrier_code'], $result['track_and_trace_code']);
                    }
                }
            }
        } else {
            Mage::helper('wuunderconnector')->log("Invalid order_id for webhook");
        }

    }

    /*
     * Ship order items if order->canShip() is true, otherwise only add extra tracking info to existing shipment
     */
    private function ship($orderId, $carrier, $label_id)
    {
        $email = true;
        $includeComment = false;
        $comment = "Order Completed And Shipped";

        $order = Mage::getModel('sales/order')->load($orderId);

        if (array_key_exists($carrier, $this->carrier_code_mapping)) {
            $carrier = $this->carrier_code_mapping[$carrier];
        }

        if ($order->canShip()) {
            $convertor = Mage::getModel('sales/convert_order');
            $shipment = $convertor->toShipment($order);
            foreach ($order->getAllItems() as $orderItem) {

                if (!$orderItem->getQtyToShip()) {
                    continue;
                }
                if ($orderItem->getIsVirtual()) {
                    continue;
                }
                $item = $convertor->itemToShipmentItem($orderItem);
                $qty = $orderItem->getQtyToShip();
                $item->setQty($qty);
                $shipment->addItem($item);
            }

            $data = array(
                'carrier_code' => 'wuunder',
                'title' => $carrier,
                'number' => $label_id
            );
            $track = Mage::getModel('sales/order_shipment_track')->addData($data);
            $shipment->addTrack($track);

            $shipment->register();
            $shipment->addComment($comment, $email && $includeComment);
            $shipment->setEmailSent(true);
            $shipment->getOrder()->setIsInProcess(true);

            try {
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($shipment)
                    ->addObject($shipment->getOrder())
                    ->save();
            } catch (Mage_Core_Exception $e) {
                Mage::log($e->getMessage(), Zend_Log::ERR);
            }

            $shipment->sendEmail($email, ($includeComment ? $comment : ''));
            $order->setStatus('Complete');
            $order->addStatusToHistory($order->getStatus(), $comment, false);

            $shipment->save();
        }
    }
}