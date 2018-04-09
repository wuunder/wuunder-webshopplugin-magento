<?php

class Wuunder_WuunderConnector_ParcelshopController extends Mage_Core_Controller_Front_Action
{

    public function shopsAction()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        $response = null;
        $response_code = null;

        try {

            $address = null;
            if ($this->getRequest()->isPost()) {
                $postData = json_decode(file_get_contents('php://input'));
                if (isset($postData->address)) {
                    $address = $postData->address;
                }
            } else {
                $address = Mage::helper('wuunderconnector')->getAddressFromQuote();
            }
            $latAndLong = Mage::helper('wuunderconnector')->getLatitudeAndLongitude($address);



            $response = array(
                "error" => $latAndLong["error"],
                "image_dir" => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN),
                "lat" => $latAndLong["lat"],
                "long" => $latAndLong["long"],
                "formatted_address" => $latAndLong["formatted_address"],
//                "parcelshops" => Mage::helper('wuunderconnector')->getParcelshops(round($latAndLong["lat"], 2), round($latAndLong["long"], 2))
                "parcelshops" => json_encode(json_decode(Mage::helper('wuunderconnector')->getParcelshops($address))->parcelshops)
            );
            $response_code = 200;
        } catch (Exception $e) {
            Mage::helper('wuunderconnector')->log("Something went wrong when fetching parcelshops: ");
            Mage::helper('wuunderconnector')->log($e);
            $response = array(
                "error" => "Something went wrong"
            );
            $response_code = 500;
        } finally {

            $this->getResponse()
                ->clearHeaders()
                ->setHeader('HTTP/1.0', $response_code, true)
                ->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode($response))
                ->sendResponse();
            exit;
        }
    }

    public function setshopAction() {
        $parcelshop_id = Mage::app()->getRequest()->getParam('id');
        $quote_id = Mage::getSingleton('checkout/session')->getQuote()->getEntityId();
        Mage::helper('wuunderconnector')->setParcelshopIdForQuote($quote_id, $parcelshop_id);
    }
}