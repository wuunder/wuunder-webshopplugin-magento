<?php
class Wuunder_WuunderConnector_Adminhtml_WuunderConnectorController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config/wuunderconnector');
    }
}