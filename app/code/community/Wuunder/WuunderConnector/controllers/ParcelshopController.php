<?php

class Wuunder_WuunderConnector_ParcelshopController extends Mage_Core_Controller_Front_Action
{

    public $tblPrfx;


    function __construct()
    {
        $this->tblPrfx = (string)Mage::getConfig()->getTablePrefix();
    }

    public function addressAction()
    {
//        error_reporting(E_ALL);
//        ini_set('display_errors', 1);
//        $response = array(
//            "error" => "Something went wrong"
//        );
        $response_code = 500;

        try {
            $address = Mage::helper('wuunderconnector/data')->getAddressFromQuote();
            $response = array(
                "address" => $address
            );
            $response_code = 200;
        } catch (Exception $e) {
            Mage::helper('wuunderconnector/data')->log($e);
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
        $this->setParcelshopIdForQuote($quote_id, $parcelshop_id);
        $currentParcelshopInfo = Mage::helper('wuunderconnector/parcelshophelper')->getCurrentSetParcelshopInfo();

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
    
    public function setParcelshopIdForQuote($quote_id, $parcelshop_id)
    {
        $mageDbW = Mage::getSingleton('core/resource')->getConnection('core_write');
        $sqlQuery = "SELECT `id` FROM `" . $this->tblPrfx . "wuunder_quote_data` WHERE `quote_id` = ? LIMIT 1";
        $id = $mageDbW->fetchOne($sqlQuery, array($quote_id));

        if ($id > 0) {
            $sqlQuery = "UPDATE `" . $this->tblPrfx . "wuunder_quote_data` SET
                        `quote_id`          = ?,
                        `parcelshop_id`       = ?
                    WHERE
                        `id` = ?";

            $sqlValues = array($quote_id, $parcelshop_id, $id);
        } else {
            $sqlQuery = "INSERT INTO `" . $this->tblPrfx . "wuunder_quote_data` SET
                        `quote_id`          = ?,
                        `parcelshop_id`       = ?";

            $sqlValues = array($quote_id, $parcelshop_id);
        }

        try {
            $mageDbW->query($sqlQuery, $sqlValues);
            return true;
        } catch (Mage_Core_Exception $e) {
            $this->log('ERROR saveWuunderShipment : ' . $e);
            return false;
        }
    }





}