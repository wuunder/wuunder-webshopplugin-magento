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

    public function processLabelAction()
    {

        $orderId = $this->getRequest()->getParam('id', null);
        if ($orderId) {

            try {

//                $data = $this->getRequest()->getPost();

                Mage::helper('wuunderconnector')->log('Controller: processLabelAction - Data', null, 'wuunder.log');

                $messageField = ($infoArray['label_type'] == 'retour') ? 'retour_message' : 'personal_message';

                $order = Mage::getModel('sales/order')->load($orderId);
                $shipmentInfo = Mage::helper('wuunderconnector')->getShipmentInfo($orderId);
                $infoOrder = Mage::helper('wuunderconnector')->getInfoFromOrder($orderId);
                $shippingAdr = $order->getShippingAddress();

                $defLength = 80;
                $defWidth = 50;
                $defHeight = 35;
                $defWeight = 20000;

                $length = ($shipmentInfo['wuunder_length'] > 0) ? $shipmentInfo['wuunder_length'] : $infoOrder['wuunder_length'];
                $width = ($shipmentInfo['wuunder_width'] > 0) ? $shipmentInfo['wuunder_width'] : $infoOrder['wuunder_width'];
                $height = ($shipmentInfo['wuunder_height'] > 0) ? $shipmentInfo['wuunder_height'] : $infoOrder['wuunder_height'];
                $weight = ($shipmentInfo['wuunder_weight'] > 0) ? $shipmentInfo['wuunder_weight'] : $infoOrder['total_weight'];
                $reference = ($shipmentInfo['reference'] != '') ? $shipmentInfo['reference'] : $infoOrder['product_names'];
                $phonenumber = ($shipmentInfo['phone_number'] != '') ? trim($shipmentInfo['phone_number']) : trim($shippingAdr->telephone);
                $length = (trim($length) == '') ? $defLength : $length;
                $width = (trim($width) == '') ? $defWidth : $width;
                $height = (trim($height) == '') ? $defHeight : $height;
                $weight = (trim($weight) == '' || $weight == 0) ? $defWeight : $weight;

                // Set default values
                if ($phonenumber == '') {
                    $phonenumber = '+31';
                } else if ((substr($phonenumber, 0, 1) == '0') && ($shippingAdr->country_id == 'NL')) {
                    // If NL and phonenumber starting with 06, replace it with +316
                    $phonenumber = '+31' . substr($phonenumber, 1);
                }

                $infoArray = array(
                    'order_id' => $orderId,
                    'label_id' => $shipmentInfo['label_id'],
                    'label_type' => $shipmentInfo['label_type'],
                    'packing_type' => $shipmentInfo['package_type'],
                    'length' => $length,
                    'width' => $width,
                    'height' => $height,
                    'weight' => $weight,
                    'reference' => $reference,
                    $messageField => ($shipmentInfo['retour_message'] != '') ? $shipmentInfo['retour_message'] : '',
                    'phone_number' => $phonenumber,
                );

                Mage::helper('wuunderconnector')->log($infoArray);

                $result = Mage::helper('wuunderconnector')->processLabelInfo($infoArray);

                if ($result['error'] === true) {
                    Mage::getSingleton('adminhtml/session')->addError($result['message']);
                } else {
                    Mage::getSingleton('adminhtml/session')->addSuccess($result['message']);
                }
                $booking_url = "";
                $order = Mage::getModel('sales/order')->load($orderId);
                $storeId = $order->getStoreId();
                if (strpos($result['booking_url'], 'http:') === 0 || strpos($result['booking_url'], 'https:') === 0) {
                    $booking_url = $result['booking_url'];
                } else {
                    $testMode = Mage::getStoreConfig('wuunderconnector/connect/testmode', $storeId);
                    if ($testMode == 1) {
                        $booking_url = 'https://api-staging.wuunder.co' . $result['booking_url'];
                    } else {
                        $booking_url = 'https://api.wuunder.co' . $result['booking_url'];
                    }
                }

                !empty($booking_url) ? $this->_redirectUrl($booking_url) : $this->_redirect('*/sales_order/index');
            } catch (Exception $e) {

                $this->_getSession()->addError(Mage::helper('wuunderconnector')->__('An error occurred while saving the data'));
                Mage::logException($e);
                $this->_redirect('*/sales_order/index');
                return $this;
            }
        }
    }
}