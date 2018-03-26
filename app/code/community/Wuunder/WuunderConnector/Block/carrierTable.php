<?php

class Wuunder_WuunderConnector_Block_carrierTable extends Mage_Core_Block_Template
{
  public function _toHtml()
  {
    // $options = Mage::getSingleton('adminhtml/system_config_source_country')
    //     ->toOptionArray();
    // foreach ($options as $option) {
    //     $this->addOption($option['value'], $option['label']);
    // }
    $this->addOption('DPD', 'DPD');
    $this->addOption('DHL_PARCEL', 'DHL');

    return parent::_toHtml();
  }

  public function setInputName($value)
  {
    return $this->setName($value);
  }
}
