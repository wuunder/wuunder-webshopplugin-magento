<?php

class Wuunder_WuunderConnector_ParcelshopController extends Mage_Core_Controller_Front_Action
{

    public function shopsAction()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        $response = array(
            "error" => "Something went wrong"
        );
        $response_code = 500;

        try {
            $address = null;
            if ($this->getRequest()->isPost()) {
                $postData = json_decode(file_get_contents('php://input'));
                if (isset($postData->address))
                    $address = $postData->address;
            }

            if (is_null($address))
                $address = Mage::helper('wuunderconnector')->getAddressFromQuote();

            $parcelshopData = Mage::helper('wuunderconnector')->getParcelshops($address);

            // Code to implement Limit client side for the time being, will be implemented in backend asap
            $parcelShops = $parcelshopData->parcelshops;
            usort($parcelShops, function($a, $b){
                return $a->distance > $b->distance;
            });
//            $parcelShops = array_slice($parcelShops, 0 , intval(Mage::getStoreConfig('carriers/wuunderparcelshop/limit')));

            $response = array(
                "error" => "",
                "image_dir" => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN),
                "lat" => $parcelshopData->location->lat,
                "long" => $parcelshopData->location->lng,
                "formatted_address" => $this->formatAddress($parcelshopData->address),
                "limit" => intval(Mage::getStoreConfig('carriers/wuunderparcelshop/limit')),
                "parcelshops" => json_encode($parcelShops)
            );
            $response_code = 200;
        } catch (Exception $e) {
            Mage::helper('wuunderconnector')->log("Something went wrong when fetching parcelshops: ");
            Mage::helper('wuunderconnector')->log($e);
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

    /**
     * Takes address parts from the result of the api and paste them in a string, in a certain order
     *
     * @param $parts
     * @return string
     */
    private function formatAddress($parts) {
        $parts = (array) $parts;
        $keys = array("street_name", "house_number", "city", "zip_code", "state", "alpha2");
        $formatted_parts = array();
        foreach ($keys as $key) {
            if (!isset($parts[$key]) || !$parts[$key])
                continue;

            array_push($formatted_parts, $parts[$key]);
        }
        return implode(" ", $formatted_parts);
    }
}