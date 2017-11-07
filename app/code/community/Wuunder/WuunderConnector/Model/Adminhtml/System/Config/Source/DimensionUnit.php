<?php
class Wuunder_WuunderConnector_Model_Adminhtml_System_Config_Source_DimensionUnit
{

    public function toOptionArray()
    {
        $type = array();
        $type[] = array('value' => '0.1', 'label' => Mage::helper('adminhtml')->__('Milimeter'));
        $type[] = array('value' => '1', 'label' => Mage::helper('adminhtml')->__('Centimeter'));
        $type[] = array('value' => '10', 'label' => Mage::helper('adminhtml')->__('Decimeter'));
        $type[] = array('value' => '100', 'label' => Mage::helper('adminhtml')->__('Meter'));

        return $type;
    }

}