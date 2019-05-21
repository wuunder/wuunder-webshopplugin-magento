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
                Mage::helper('wuunderconnector/data')->log('Controller: processLabelAction - Data', null, 'wuunder.log');

                $order = Mage::getModel('sales/order')->load($orderId);
                $shipmentInfo = Mage::helper('wuunderconnector/data')->getShipmentInfo($orderId);
                $infoOrder = Mage::helper('wuunderconnector/data')->getInfoFromOrder($orderId);
                $shippingAdr = $order->getShippingAddress();

                if (array_key_exists("shipment_id", $shipmentInfo)) {
                    $phonenumber = (!empty($shipmentInfo['phone_number']) && strlen($shipmentInfo['phone_number']) >= 10) ? trim($shipmentInfo['phone_number']) : trim($shippingAdr->telephone);
                } else {
                    $phonenumber = trim($shippingAdr->telephone);
                }

                $storeId = $order->getStoreId();
                $unitConverter = floatval((!empty(Mage::getStoreConfig('wuunderconnector/magentoconfig/dimensions_units', $storeId)) ? Mage::getStoreConfig('wuunderconnector/magentoconfig/dimensions_units', $storeId) : 1));

                $length = ($infoOrder['length'] == 0) ? null : $infoOrder['length'] * $unitConverter;
                $width = ($infoOrder['width'] == 0) ? null : $infoOrder['width'] * $unitConverter;
                $height = ($infoOrder['height'] == 0) ? null : $infoOrder['height'] * $unitConverter;
                $weight = ($infoOrder['total_weight'] == 0) ? null : $infoOrder['total_weight'];

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

                $result = Mage::helper('wuunderconnector/data')->processLabelInfo($infoArray);

                if ($result['error'] === true) {
                    Mage::getSingleton('adminhtml/session')->addError($result['message']);
                }

                $booking_url = $result['booking_url'];

                $infoArray['booking_url'] = $booking_url;
                Mage::helper('wuunderconnector/data')->saveWuunderShipment($infoArray);

                !empty($result['booking_url']) ? $this->_redirectUrl($booking_url) : $this->_redirect('*/sales_order/index');
            } catch (Exception $e) {
                $this->_getSession()->addError(Mage::helper('wuunderconnector/data')->__('An error occurred while saving the data, please check the wuunder extension logging.'));
                Mage::logException($e);
                $this->_redirect('*/sales_order/index');
                return $this;
            }
        }
    }

    public function processMultiselectedOrdersAction()
    {
        $orderIds = $this->getRequest()->getPost('order_ids', array());
        $draftConfig = new \Wuunder\Api\Config\DraftConfig();
        $storeId = Mage::app()->getStore()->getStoreId();
        $apiKey = Mage::helper('wuunderconnector/data')->getAPIKey($storeId);

        $count = 0;
        foreach ($orderIds as $orderId) {
            // Limit to 100 orders
            if ($count > 100) {
                continue;
            }
            $count++;

            $shipment = Mage::helper('wuunderconnector/data')->getWuunderShipmentByOrderId($orderId);
            if (!is_null($shipment->getBookingUrl())) {
                continue; //Ignore orders that already have a bookingurl
            }

            $order = Mage::getModel('sales/order')->load($orderId);
            $shipmentInfo = Mage::helper('wuunderconnector/data')->getShipmentInfo($orderId);
            $infoOrder = Mage::helper('wuunderconnector/data')->getInfoFromOrder($orderId);
            $shippingAdr = $order->getShippingAddress();

            $weight = ($infoOrder['total_weight'] == 0) ? null : $infoOrder['total_weight'];

            $shipmentDescription = "";
            foreach ($order->getAllItems() as $item) {
                $product = Mage::getModel('catalog/product')->load($item->getProductId());
                $shipmentDescription .= $product->getShortDescription() . " ";
            }


            if (array_key_exists("shipment_id", $shipmentInfo)) {
                $phonenumber = (!empty($shipmentInfo['phone_number']) && strlen($shipmentInfo['phone_number']) >= 10) ? trim($shipmentInfo['phone_number']) : trim($shippingAdr->telephone);
            } else {
                $phonenumber = trim($shippingAdr->telephone);
            }

            // Set default values
            if ((substr($phonenumber, 0, 1) == '0') && ($shippingAdr->country_id == 'NL')) {
                // If NL and phonenumber starting with 0, replace it with +31
                $phonenumber = '+31' . substr($phonenumber, 1);
            }

            $infoArray = array(
                'order_id' => $orderId,
                'packing_type' => array_key_exists("package_type", $shipmentInfo) ? $shipmentInfo['package_type'] : "",
                'length' => null,
                'width' => null,
                'height' => null,
                'weight' => $weight,
                'description' => $shipmentDescription,
                'phone_number' => $phonenumber,
            );

            $bookingConfig = $this->createBookingConfigForOrder($infoArray);

            $draftConfig->addBookingConfig($orderId, $bookingConfig, true);
        }

        $connector = new Wuunder\Connector($apiKey, Mage::getStoreConfig('wuunderconnector/connect/testmode', $storeId) == 1);
        $draftsRequest = $connector->createBulkDrafts();

        if ($draftConfig->validate()) {
            $draftsRequest->setConfig($draftConfig);

            if ($draftsRequest->fire()) {
                $response = $draftsRequest->getDraftsResponse()->getBody();
            } else {
                Mage::helper('wuunderconnector/data')->log('ERROR saveWuunderShipment : ' . json_encode($draftsRequest->getDraftsResponse()->getError()));
            }
        } else {
            print("DraftsConfig not valid");
        }

        $orderResponses = json_decode($response);

        $correctlyBookedCounter = 0;

        foreach ($orderResponses as $orderResponse) {
            if (count($orderResponse->errors)) {
                Mage::helper('wuunderconnector/data')->log('Error Booking order ' . $orderResponse->id);
                continue;
            }

            $shipment = Mage::helper('wuunderconnector/data')->getWuunderShipmentByOrderId($orderResponse->id);
            if (empty($shipment)) {
                continue;
                // TODO maybe make new shipment?
            } else {
                $correctlyBookedCounter++;
                $shipment->setBookingUrl($orderResponse->url);
                try {
                    $shipment->save();
                } catch (Mage_Core_Exception $e) {
                    $this->log('ERROR saveWuunderShipment : ' . $e);
                    return false;
                }
            }
        }
        $this->_redirect('*/sales_order/index', array('_query' => array('wuunder_bulk_booked' => $correctlyBookedCounter)));
    }

    private function createBookingConfigForOrder($infoArray)
    {
        // Fetch order
        $order = Mage::getModel('sales/order')->load($infoArray['order_id']);

        // Get configuration
        $booking_token = uniqid();
        $infoArray['booking_token'] = $booking_token;

        // Combine wuunder info and order data
        $bookingConfig = Mage::helper('wuunderconnector/data')->buildWuunderData($infoArray, $order, $booking_token);
        $bookingConfig->setRedirectUrl(null); //For bulk booking the user should not be redirected(?)

        Mage::helper('wuunderconnector/data')->saveWuunderShipment($infoArray);

        return $bookingConfig;
    }
}