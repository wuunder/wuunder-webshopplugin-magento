<?php
class Wuunder_WuunderConnector_WebhookController extends Mage_Core_Controller_Front_Action
{
    public function callAction()
    {
        echo "hi";
        Mage::log("BEGIN webhook");
        Mage::log($this->getRequest());
        Mage::log("END webhook");
    }

    /*
     * Ship order items if order->canShip() is true, otherwise only add extra tracking info to existing shipment
     */
    private function ship($orderId, $trackAndTraceURL)
    {
        $email = true;
        $carrier = 'wuunder';
        $includeComment = false;
        $comment = "Order Completed And Shipped";

        $order = Mage::getModel('sales/order')->load($orderId);

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

            $data = array();
            $data['carrier_code'] = $carrier;
            $data['title'] = 'Wuunder';
            $data['number'] = $trackAndTraceURL;

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
        } else {
            foreach ($order->getShipmentsCollection() as $shipment) {
                $shipmentId = $shipment->getId();
                $shipment = Mage::getModel('sales/order_shipment')->load($shipmentId);

                $data = array();
                $data['carrier_code'] = $carrier;
                $data['title'] = 'Wuunder return shipment';
                $data['number'] = $trackAndTraceURL;

                $track = Mage::getModel('sales/order_shipment_track')->addData($data);
                $shipment->addTrack($track);

                $shipment->addComment($comment, $email && $includeComment);
                $shipment->setEmailSent(true);

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
}