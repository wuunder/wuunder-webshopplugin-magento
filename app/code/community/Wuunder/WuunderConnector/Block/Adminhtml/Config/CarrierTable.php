<?php

class Wuunder_WuunderConnector_Block_Adminhtml_Config_CarrierTable extends Mage_Core_Block_Html_Select
{
  public function _toHtml()
  {
       $options = Mage::helper('wuunderconnector/parcelshophelper')->getParcelshopCarriers();
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
