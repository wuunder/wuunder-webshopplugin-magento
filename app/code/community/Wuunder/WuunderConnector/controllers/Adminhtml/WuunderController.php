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
                $infoArray = array();

                Mage::helper('wuunderconnector')->log('Controller: processLabelAction - Data', null, 'wuunder.log');

                $order = Mage::getModel('sales/order')->load($orderId);
                $shipmentInfo = Mage::helper('wuunderconnector')->getShipmentInfo($orderId);
                $infoOrder = Mage::helper('wuunderconnector')->getInfoFromOrder($orderId);
                $shippingAdr = $order->getShippingAddress();

                if (array_key_exists("shipment_id", $shipmentInfo)) {
                    $phonenumber = (!empty($shipmentInfo['phone_number']) && strlen($shipmentInfo['phone_number']) >= 10) ? trim($shipmentInfo['phone_number']) : trim($shippingAdr->telephone);
                } else {
                    $phonenumber = trim($shippingAdr->telephone);
                }

                $storeId = $order->getStoreId();
                $unitConverter = floatval((!empty(Mage::getStoreConfig('wuunderconnector/magentoconfig/dimensions_units', $storeId)) ? Mage::getStoreConfig('wuunderconnector/magentoconfig/dimensions_units', $storeId) : 1));

                $length = ($infoOrder['length'] == 0 ) ? "" : $infoOrder['length'] * $unitConverter;
                $width = ($infoOrder['width'] == 0 ) ? "" : $infoOrder['width'] * $unitConverter;
                $height = ($infoOrder['height'] == 0 ) ? "" : $infoOrder['height'] * $unitConverter;
                $weight = ($infoOrder['total_weight'] == 0) ? "" : $infoOrder['total_weight'];

                $shipmentDescription = "";
                foreach ($order->getAllItems() as $item) {
                    $product = Mage::getModel('catalog/product')->load($item->getProductId());
                    $shipmentDescription .= $product->getShortDescription() . " ";
                }

                // Set default values
                if ((substr($phonenumber, 0, 1) == '0') && ($shippingAdr->country_id == 'NL')) {
                    // If NL and phonenumber starting with 0, replace it with +31
                    $phonenumber = '+31' . substr($phonenumber, 1);
                }

                $infoArray = array(
                    'order_id' => $orderId,
                    'packing_type' => array_key_exists("package_type", $shipmentInfo) ? $shipmentInfo['package_type'] : "",
                    'length' => $length,
                    'width' => $width,
                    'height' => $height,
                    'weight' => $weight,
                    'description' => $shipmentDescription,
                    'phone_number' => $phonenumber,
                );

                $result = Mage::helper('wuunderconnector')->processLabelInfo($infoArray);

                if ($result['error'] === true) {
                    Mage::getSingleton('adminhtml/session')->addError($result['message']);
                }
                $order = Mage::getModel('sales/order')->load($orderId);
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