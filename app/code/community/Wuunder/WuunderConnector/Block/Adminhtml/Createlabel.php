<?php
class Wuunder_WuunderConnector_Block_Adminhtml_Createlabel extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('wuunder/createlabel.phtml');
    }
}