<?php

class Wuunder_WuunderConnector_Block_ParcelshopsTable extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
  protected $_itemRenderer;

  public function _prepareToRender()
  {
      $this->addColumn('carriers', array(
          'label' => 'Carrier',
          'renderer' => $this->_getRenderer(),
          // 'style' => 'width:100px',
      ));
      $this->addColumn('cost', array(
          'label' => 'Cost to ship',
          'style' => 'width:100px',
      ));

      $this->_addAfter = false;
      // $this->_addButtonLabel = Mage::helper('namespace_module')->__('Add');
  }

  protected function _getRenderer()
  {
      if (!$this->_itemRenderer) {
          $this->_itemRenderer = $this->getLayout()->createBlock(
              'wuunderconnector/carrierTable', '',
              array('is_render_to_js_template' => true)
          );
      }
      return $this->_itemRenderer;
  }

  // protected function _prepareArrayRow(Varien_Object $row)
  // {
  //     $row->setData(
  //         'option_extra_attr_' . $this->_getRenderer()
  //             ->calcOptionHash($row->getData('carriers')),
  //         'selected="selected"'
  //     );
  // }

}
