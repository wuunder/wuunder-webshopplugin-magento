<?php

class Wuunder_WuunderConnector_Helper_Parcelshophelper extends Mage_Core_Helper_Abstract
{
        public function getParcelshopCarriers()
    {
        return array(
            array(
                "value" => "dpd",
                "label" => "DPD"
            ),
            array(
                "value" => "dhl",
                "label" => "DHL"
            ),
            array(
                "value" => "postnl",
                "label" => "PostNL"
            )
        );
    }

    public function removeParcelshopIdForQuote($quoteId)
    {
        $mageDbW = Mage::getSingleton('core/resource')->getConnection('core_write');
        $sqlQuery = "DELETE FROM `" . $this->tblPrfx . "wuunder_quote_data` WHERE `quote_id` = " . $quoteId;

        try {
            $mageDbW->query($sqlQuery);
        } catch (Exception $e) {
            Mage::helper('wuunderconnector/data')->log('ERROR getWuunderShipment : ' . $e);
            return false;
        }

        return true;
    }

    public function checkIfParcelShippingIsSelected($html)
    {
        preg_match('(<input[^>]+id="s_method_wuunderparcelshop_wuunderparcelshop"[^>]+checked="checked"[^>]+>)s', $html,
            $matches);
        return isset($matches[0]);
    }

    public function getParcelshopIdForQuote($id)
    {
        $mageDbW = Mage::getSingleton('core/resource')->getConnection('core_write');

        //check for a label id
        $sqlQuery = "SELECT `parcelshop_id` FROM `" . $this->tblPrfx . "wuunder_quote_data` WHERE `quote_id` = " . $id . " LIMIT 1";

        try {
            $parcelshopId = $mageDbW->fetchOne($sqlQuery);
        } catch (Exception $e) {
            Mage::helper('wuunderconnector/data')->log('ERROR getWuunderShipment : ' . $e);
            return null;
        }

        return ($parcelshopId ? $parcelshopId : null);
    }

    public function addParcelshopsHTML($originalHtml)
    {
        preg_match('!<label for="(.*?)wuunderparcelshop">(.*?)<\/label>!s', $originalHtml, $matches);
        if (!isset($matches[0])) {
            return $originalHtml;
        }

        //get allowed carriers
        $carriers = array();
        $carrierData = Mage::getStoreConfig('carriers/wuunderparcelshop/parcelshop_carriers');
        if (!empty($carrierData)) {
            $carrierData = unserialize($carrierData);
            foreach ($carrierData as $carrier) {
                array_push($carriers, $carrier['carrier']);
            }
        }

        $parcelshopHtml = Mage::app()
            ->getLayout()
            ->createBlock('core/template')
            ->setOneStepCheckoutHtml(Mage::helper('wuunderconnector/data')->getOneStepValidationField($html))
            ->setCurrentParcelshopInfo($this->getCurrentSetParcelshopInfo())
            ->setWebshopBaseUrl(Mage::getUrl('', array('_secure' => Mage::app()->getStore()->isFrontUrlSecure())))
            ->setApiBaseUrl(str_replace("api/", "", Mage::helper('wuunderconnector/data')->getAPIHost(Mage::app()->getStore()->getStoreId())))
            ->setAllowedCarriers(implode(",", $carriers))
            ->setTemplate('wuunder/parcelshopsContainer.phtml')
            ->toHtml();

        $html = str_replace($matches[0],
            $matches[0] . $parcelshopHtml,
            $originalHtml);
        $html = str_replace("name=\"shipping_method\"",
            "name=\"shipping_method\" onclick=\"switchShippingMethod(event);" . (Mage::helper('wuunderconnector/data')->getIsOnestepCheckout() ? "switchShippingMethodValidation(event);" : "") . "\"", $html);

        return $html;
    }

    public function getCurrentSetParcelshopInfo()
    {
        $quote_id = Mage::getSingleton('checkout/session')->getQuote()->getEntityId();
        $parcelshop_id = $this->getParcelshopIdForQuote($quote_id);
        
        if (is_null($parcelshop_id)) {
            return "<div id='parcelShopsSelected'></div>";
        }
        $parcelshop_info = json_decode($this->getParcelshopById($parcelshop_id));
        Mage::helper('wuunderconnector/data')->log($parcelshop_info);
        $selectedParcelshopHtml = Mage::app()
            ->getLayout()
            ->createBlock('core/template')
            ->setParcelshopInfo($parcelshop_info)
            ->setTemplate('wuunder/selectedParcelshop.phtml')
            ->toHtml();
        return $selectedParcelshopHtml;
    }

    private function getParcelshopById($id)
    {
        if (!empty($id)) {
            return $this->doParcelshopRequest($id);
        }

        return false;
    }

    private function doParcelshopRequest($id)
    {
        $apiKey = Mage::helper('wuunderconnector/data')->getAPIKey(Mage::app()->getStore());
        $connector = new Wuunder\Connector($apiKey);
        $connector->setLanguage("NL");
        $parcelshopRequest = $connector->getParcelshopById();
        $parcelshopConfig = new \Wuunder\Api\Config\ParcelshopConfig();
        $parcelshopConfig->setId($id);

        if ($parcelshopConfig->validate()) {
            $parcelshopRequest->setConfig($parcelshopConfig);
            if ($parcelshopRequest->fire()) {
                $parcelshop = json_encode($parcelshopRequest->getParcelshopResponse()->getParcelshopData());
            } else {
                echo 'error';
                var_dump($parcelshopRequest->getParcelshopResponse()->getError());
            }
        } else {
            $parcelshop = null;
        }
        return $parcelshop;
    }
    
}


