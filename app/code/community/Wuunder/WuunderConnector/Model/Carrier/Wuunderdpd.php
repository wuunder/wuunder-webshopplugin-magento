<?php

/**
 * Class DPD_Shipping_Model_Carrier_Dpdparcelshops
 */
class Wuunder_WuunderConnector_Model_Carrier_Wuunderdpd extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface
{
    /**
     * Shipping method code.
     *
     * @var string
     */
    protected $_code = 'wuunderdpd';

    /**
     * Collect shipping method price and set all data selected in config.
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return bool|false|Mage_Core_Model_Abstract
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        return null;
    }

    /**
     * Add this shipping method to list of allowed methods so Magento can display it.
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return array('dpdparcelshops' => $this->getConfigData('name'));
    }

    /**
     * Get tracking result object.
     *
     * @param string $tracking_number
     * @return Mage_Shipping_Model_Tracking_Result $tracking_result
     */
    public function getTrackingInfo($tracking_number)
    {
        $tracking_result = $this->getTracking($tracking_number);

        if ($tracking_result instanceof Mage_Shipping_Model_Tracking_Result) {
            $trackings = $tracking_result->getAllTrackings();
            if (is_array($trackings) && count($trackings) > 0) {
                return $trackings[0];
            }
        }
        return false;
    }

    /**
     * Get tracking Url.
     *
     * @param string $tracking_number
     * @return Mage_Shipping_Model_Tracking_Result
     */
    public function getTracking($tracking_number)
    {
        $tracking_numberExploded = explode('-', $tracking_number);
        $tracking_result = Mage::getModel('shipping/tracking_result');
        $tracking_status = Mage::getModel('shipping/tracking_result_status');
        $localeExploded = explode('_', Mage::app()->getLocale()->getLocaleCode());
        $tracking_status->setCarrier($this->_code);
        $tracking_status->setCarrierTitle($this->getConfigData('title'));
        $tracking_status->setTracking($tracking_number);
        $tracking_status->addData(
            array(
                'status' => '<a target="_blank" href="' . "http://tracking.dpd.de/cgi-bin/delistrack?typ=32&lang=" . $localeExploded[0] . "&pknr=" . $tracking_numberExploded[1] . "&var=" . Mage::getStoreConfig('shipping/dpdclassic/userid') . '">' . Mage::helper('dpd')->__('Track this shipment') . '</a>'
            )
        );
        $tracking_result->append($tracking_status);

        return $tracking_result;
    }

    /**
     * Make tracking available for dpd shippingmethods.
     *
     * @return bool
     */
    public function isTrackingAvailable()
    {
        return true;
    }

    /**
     * Make shippinglabels not available as we provided our own method.
     *
     * @return bool
     */
    public function isShippingLabelsAvailable()
    {
        return false;
    }

    /**
     * Get the rateobject from our resource model.
     *
     * @param $request
     * @return mixed
     */
    public function getRate($request)
    {
        return Mage::getResourceModel('dpd/dpdparcelshops_tablerate')->getRate($request);
    }
}