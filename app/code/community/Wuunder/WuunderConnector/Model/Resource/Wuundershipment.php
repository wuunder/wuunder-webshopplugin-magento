<?php

class Wuunder_WuunderConnector_Model_Resource_Wuundershipment extends Mage_Core_Model_Resource_Db_Abstract{
    protected function _construct()
    {
        $this->_init('wuunderconnector/wuundershipment', 'shipment_id');
    }
}