<?php

class Wuunder_WuunderConnector_Block_Adminhtml_Sales_Order_View extends Mage_Adminhtml_Block_Sales_Order_View
{
    public function __construct()
    {
        parent::__construct();
        $this->_removeButton('order_ship');
    }
    public function getShipUrl()
    {
        //add your custom url
        return $this->getUrl('*/sales_order_shipment/start');
    }
}
