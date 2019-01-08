<?php

class Wuunder_WuunderConnector_Block_CountryRateTable extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $_itemRenderer;

    public function _prepareToRender()
    {
        $this->addColumn('country', array(
            'label' => Mage::helper('wuunderconnector/data')->__('Country'),
            'renderer' => $this->_getRenderer()
        ));
        $this->addColumn('cost', array(
            'label' => Mage::helper('wuunderconnector/data')->__('Cost'),
            'style' => 'width:100px',
        ));
        $this->addColumn('free_from', array(
            'label' => Mage::helper('wuunderconnector/data')->__('Free from'),
            'style' => 'width:100px',
        ));

        $this->_addAfter = false;
    }

    protected function _getRenderer()
    {
        if (!$this->_itemRenderer) {
            $this->_itemRenderer = $this->getLayout()->createBlock(
                'wuunderconnector/adminhtml_config_countryTable', '',
                array('is_render_to_js_template' => true)
            );
        }
        return $this->_itemRenderer;
    }

    protected function _prepareArrayRow(Varien_Object $row)
    {
        $row->setData(
            'option_extra_attr_' . $this->_getRenderer()
                ->calcOptionHash($row->getData('country')),
            'selected="selected"'
        );
    }

}
