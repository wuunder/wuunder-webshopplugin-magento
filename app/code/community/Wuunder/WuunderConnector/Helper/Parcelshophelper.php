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
            $this->log('ERROR getWuunderShipment : ' . $e);
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
            $this->log('ERROR getWuunderShipment : ' . $e);
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
            return $this->doParcelshopRequest("parcelshops/" . $id);
        }

        return false;
    }

    private function doParcelshopRequest($uriPath)
    {
        $storeId = Mage::app()->getStore();
        $apiUrl = Mage::helper('wuunderconnector/data')->getAPIHost($storeId) . $uriPath;

        $cc = curl_init($apiUrl);

        curl_setopt($cc, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cc, CURLOPT_VERBOSE, 1);

        // Execute the cURL, fetch the XML
        $result = curl_exec($cc);
        curl_close($cc);
        return $result;
    }
}

