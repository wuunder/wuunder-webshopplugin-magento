<?php

class Wuunder_WuunderConnector_Block_Adminhtml_Config_CountryTable extends Mage_Core_Block_Html_Select
{
    public function _toHtml()
    {
        $options = Mage::getModel('directory/country')->getResourceCollection()
            ->loadByStore()
            ->toOptionArray(true);
        $options = array_slice($options, 1);
        foreach ($options as $option) {
            $this->addOption($option['value'], $option['label']);
        }

        return parent::_toHtml();
    }

    public function setInputName($value)
    {
        return $this->setName($value);
    }
}
