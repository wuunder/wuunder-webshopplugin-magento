<?php

class Wuunder_WuunderConnector_ParcelshopController extends Mage_Core_Controller_Front_Action
{

    public function addressAction()
    {
//        error_reporting(E_ALL);
//        ini_set('display_errors', 1);
//        $response = array(
//            "error" => "Something went wrong"
//        );
        $response_code = 500;

        try {
            $address = Mage::helper('wuunderconnector')->getAddressFromQuote();
            $response = array(
                "address" => $address
            );
            $response_code = 200;
        } catch (Exception $e) {
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

    public function setshopAction()
    {
        $parcelshop_id = Mage::app()->getRequest()->getParam('id');
        $quote_id = Mage::getSingleton('checkout/session')->getQuote()->getEntityId();
        Mage::helper('wuunderconnector')->setParcelshopIdForQuote($quote_id, $parcelshop_id);
        $currentParcelshopInfo = Mage::helper('wuunderconnector')->getCurrentSetParcelshopInfo();

        $this->getResponse()
            ->clearHeaders()
            ->setHeader('HTTP/1.0', '200', true)
            ->setHeader('Content-Type', 'application/json', true)
            ->setBody(json_encode($currentParcelshopInfo))
            ->sendResponse();
        exit;
    }

    /**
     * Takes address parts from the result of the api and paste them in a string, in a certain order
     *
     * @param $parts
     * @return string
     */
    private function formatAddress($parts)
    {
        $parts = (array)$parts;
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