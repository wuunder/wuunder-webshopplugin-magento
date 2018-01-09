<?php

class Wuunder_WuunderConnector_ParcelshopController extends Mage_Core_Controller_Front_Action
{

    public function shopsAction()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $latAndLong = Mage::helper('wuunderconnector')->getLatitudeAndLongitude();

        $response = array(
            "error" => $latAndLong["error"],
            "image_dir" => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN),
            "lat" => $latAndLong["lat"],
            "long" => $latAndLong["long"],
            "parcelshops" => Mage::helper('wuunderconnector')->getParcelshops(round($latAndLong["lat"], 2), round($latAndLong["long"], 2))
        );

//        var_dump($response);
// exit;
        $this->getResponse()
            ->clearHeaders()
            ->setHeader('Content-Type', 'application/json', true)
            ->setBody(json_encode($response))
            ->sendResponse();
        exit;
    }
}