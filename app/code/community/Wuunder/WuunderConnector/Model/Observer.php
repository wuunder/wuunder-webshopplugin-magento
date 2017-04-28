<?php
class Wuunder_WuunderConnector_Model_Observer extends Varien_Event_Observer {

	public function salesOrderGridCollectionLoadBefore($observer)
	{
		$collection = $observer->getOrderGridCollection();
        $select = $collection->getSelect();
        $select->joinLeft(
        	array(
        		'wuunder' => $collection->getTable('wuunderconnector/shipments')
        	),
			'wuunder.order_id = main_table.entity_id',
			array('label_id','label_url','label_tt_url','retour_id','retour_url','retour_tt_url')
		);
	}

//    public function adminhtmlWidgetContainerHtmlBefore($event)
//    {
//        $block = $event->getBlock();
//        $orderId = $block->getOrderId();
//        $linkurl  = Mage::helper('adminhtml')->getUrl('adminhtml/wuunder/create/id/', array('id' => $orderId));
//
//        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_View) {
////            $message = Mage::helper('wuunderconnector')->__('Are you sure you want to do this?');
//            $block->addButton('order_ship', array(
//                'label'     => Mage::helper('sales')->__('Ship'),
//                'onclick'   => 'setLocation(\'' . $linkurl . '\')',
//                'class'     => 'go'
//            ), 0, 40);
//        }
//    }
}
