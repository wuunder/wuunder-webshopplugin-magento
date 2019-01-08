<?php

class Wuunder_WuunderConnector_Block_ParcelshopsTable extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
  protected $_itemRenderer;

  public function _prepareToRender()
  {
      $this->addColumn('carrier', array(
          'label' => Mage::helper('wuunderconnector/data')->__('Carrier'),
          'renderer' => $this->_getRenderer()
      ));
      $this->addColumn('name', array(
          'label' => Mage::helper('wuunderconnector/data')->__('Name in checkout'),
          'style' => 'width:100px',
      ));

      $this->_addAfter = false;
  }

  protected function _getRenderer()
  {
      if (!$this->_itemRenderer) {
          $this->_itemRenderer = $this->getLayout()->createBlock(
              'wuunderconnector/adminhtml_config_carrierTable', '',
              array('is_render_to_js_template' => true)
          );
      }
      return $this->_itemRenderer;
  }

   protected function _prepareArrayRow(Varien_Object $row)
   {
       $row->setData(
           'option_extra_attr_' . $this->_getRenderer()
               ->calcOptionHash($row->getData('carrier')),
           'selected="selected"'
       );
   }

}
