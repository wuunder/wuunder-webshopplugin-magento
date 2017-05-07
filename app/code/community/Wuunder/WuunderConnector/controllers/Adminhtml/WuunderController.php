<?php

class Wuunder_WuunderConnector_Adminhtml_WuunderController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config/wuunderconnector');
    }

    public function indexAction()
    {
    }

    public function createAction()
    {
        try {

            $wuunderEnabled = Mage::getStoreConfig('wuunderconnector/connect/enabled');

            if ($wuunderEnabled == 0) {

                Mage::getSingleton('adminhtml/session')->addError('Error: WuunderConnector disabled');

            } else {

                $orderId = $this->getRequest()->getParam('id', null);
                Mage::register('wuuder_order_id', $orderId);
                Mage::helper('wuunderconnector')->log('Controller: createAction - Order ID = ' . $orderId);

                $this->loadLayout();
                $this->renderLayout();
            }
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
    }

    /*
     * Ship order items if order->canShip() is true, otherwise only add extra tracking info to existing shipment
     */
    public function ship($orderId, $trackAndTraceURL)
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

    public function processLabelAction()
    {

        $orderId = $this->getRequest()->getParam('id', null);
        if ($orderId) {

            try {

//                $data = $this->getRequest()->getPost();

                Mage::helper('wuunderconnector')->log('Controller: processLabelAction - Data', null, 'wuunder.log');

                $messageField = ($infoArray['label_type'] == 'retour') ? 'retour_message' : 'personal_message';

                $order          = Mage::getModel('sales/order')->load($orderId);
                $shipmentInfo  = Mage::helper('wuunderconnector')->getShipmentInfo($orderId);
                $infoOrder      = Mage::helper('wuunderconnector')->getInfoFromOrder($orderId);
                $shippingAdr    = $order->getShippingAddress();

                $phoneNumber = ($shipmentInfo['phone_number'] != '') ? trim($shipmentInfo['phone_number']) : trim($shippingAdr->telephone);
                // Set default values
                if ($phoneNumber == '') {
                    $phoneNumber = '+31';
                } else if ((substr($phoneNumber,0,1) == '0') && ($shippingAdr->country_id == 'NL')) {
                    // If NL and phonenumber starting with 06, replace it with +316
                    $phoneNumber = '+31'.substr($phoneNumber,1);
                }

                $infoArray = array(
                    'order_id' => $orderId,
                    'label_id' => $shipmentInfo['label_id'],
                    'label_type' => $shipmentInfo['label_type'],
                    'packing_type' => $shipmentInfo['package_type'],
                    'length' => ($shipmentInfo['wuunder_length'] > 0) ? $shipmentInfo['wuunder_length'] : $infoOrder['wuunder_length'],
                    'width' => ($shipmentInfo['wuunder_width'] > 0) ? $shipmentInfo['wuunder_width'] : $infoOrder['wuunder_width'],
                    'height' => ($shipmentInfo['wuunder_height'] > 0) ? $shipmentInfo['wuunder_height'] : $infoOrder['wuunder_height'],
                    'weight' => ($shipmentInfo['wuunder_weight'] > 0) ? $shipmentInfo['wuunder_weight'] : $infoOrder['total_weight'],
                    'reference' => ($shipmentInfo['reference'] != '') ? $shipmentInfo['reference'] : $infoOrder['product_names'],
                    $messageField => ($shipmentInfo['retour_message'] != '') ? $shipmentInfo['retour_message'] : '',
                    'phone_number' => $phoneNumber,
                );

                Mage::helper('wuunderconnector')->log($infoArray);

                $result = Mage::helper('wuunderconnector')->processLabelInfo($infoArray);

                if ($result['error'] === true) {
                    Mage::getSingleton('adminhtml/session')->addError($result['message']);
                } else {
                    Mage::getSingleton('adminhtml/session')->addSuccess($result['message']);
                }

                $this->_redirect('*/sales_order/index');

//                $this->ship($orderId, $result['original_result']->id);
            } catch (Exception $e) {

                $this->_getSession()->addError(Mage::helper('wuunderconnector')->__('An error occurred while saving the data'));
                Mage::logException($e);
                $this->_redirect('*/*/create');
                return $this;
            }
        }
    }

    public function wuunderWebhookAction()
    {
        echo "hi";
    }
}