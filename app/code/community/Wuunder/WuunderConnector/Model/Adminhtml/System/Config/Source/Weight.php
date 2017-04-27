<?php
class Wuunder_WuunderConnector_Model_Adminhtml_System_Config_Source_Weight
{

    public function toOptionArray()
    {
        $type = array();
        $type[] = array('value' => 'g', 'label' => Mage::helper('adminhtml')->__('Grams (g)'));
        $type[] = array('value' => 'kg', 'label' => Mage::helper('adminhtml')->__('Kilograms (kg)'));
        //$type[] = array('value' => 'lb', 'label' => Mage::helper('adminhtml')->__('Pounds (lb)'));
        //$type[] = array('value' => 'oz', 'label' => Mage::helper('adminhtml')->__('Ounces (oz)'));

        return $type;
    }

}