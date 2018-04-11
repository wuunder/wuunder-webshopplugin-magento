<?php

/**
 * Class DPD_Shipping_Model_Carrier_Dpdparcelshops
 */
class Wuunder_WuunderConnector_Model_Carrier_Wuunderparcelshop extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface
{
    protected $_code = 'wuunderparcelshop';

    public function isTrackingAvailable()
    {
        return true;
    }

    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!Mage::getStoreConfig('carriers/' . $this->_code . '/active')) {
            return false;
        }

        $result = Mage::getModel('shipping/rate_result');
        if (!empty($free_from_value) && $request->getPackageValueWithDiscount() >= floatval($free_from_value)) {
            $method = Mage::getModel('shipping/rate_result_method');
            $method->setCarrier($this->_code);
            $method->setMethod($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));
            $method->setMethodTitle($this->getConfigData('name'));
            $method->setPrice('0.00');
            $method->setCost('0.00');
            $result->append($method);
        } else {
            $method = Mage::getModel('shipping/rate_result_method');
            $method->setCarrier($this->_code);
            $method->setMethod($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));
            $method->setMethodTitle($this->getConfigData('name'));
            $method->setPrice($this->getConfigData('price'));
            $method->setCost($this->getConfigData('price'));
            $result->append($method);
        }
        return $result;
    }

    public function getAllowedMethods()
    {
        return array('wuunder' => $this->getConfigData('name'));
    }

    public function getTrackingInfo($label_id)
    {
        $result = Mage::helper('wuunderconnector')->getWuunderShipment($label_id);
        $track = Mage::getModel('shipping/tracking_result_status');
        $track->setUrl($result)
            ->setTracking($label_id)
            ->setCarrierTitle($this->getConfigData('name'));
        return $track;
    }
}
