<?php
class Wuunder_WuunderConnector_Model_System_Config_Source_Carriers extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    public function getAllOptions()
    {
        $methods = Mage::getSingleton('shipping/config')->getActiveCarriers();

        $options = array();

        foreach($methods as $_code => $_method)
        {
            if(!$_title = Mage::getStoreConfig("carriers/$_code/title"))
                $_title = $_code;

            $options[] = array('value' => $_code."_".$_code, 'label' => $_title . " ($_code)");
        }

        if(false)
        {
            array_unshift($options, array('value'=>'', 'label'=> Mage::helper('adminhtml')->__('--Please Select--')));
        }

        return $options;
    }

    public function toOptionArray()
    {
        $methods = Mage::getSingleton('shipping/config')->getActiveCarriers();

        $options = array();
        foreach($methods as $_code => $_method)
        {
            if(!$_title = Mage::getStoreConfig("carriers/$_code/title"))
                $_title = $_code;

            $options[] = array('value' => $_code."_".$_code, 'label' => $_title . " ($_code)");
        }

        if(false)
        {
            array_unshift($options, array('value'=>'', 'label'=> Mage::helper('adminhtml')->__('--Please Select--')));
        }

        return $options;
    }
}


