<?php

class Wuunder_WuunderConnector_Model_Observer extends Varien_Event_Observer
{

    public function salesOrderGridCollectionLoadBefore($observer)
    {
        $collection = $observer->getOrderGridCollection();
        $select = $collection->getSelect();
        $select->joinLeft(
            array(
                'wuunder' => $collection->getTable('wuunderconnector/shipments')
            ),
            'wuunder.order_id = main_table.entity_id',
            array('label_id', 'label_url', 'label_tt_url', 'retour_id', 'retour_url', 'retour_tt_url')
        );
    }

    public function adminhtmlWidgetContainerHtmlBefore($event)
    {
        $block = $event->getBlock();

        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_View) {
            $order = $block->getOrder();
            if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/ship') && $order->canShip()
                && !$order->getForcedDoShipmentWithInvoice()
            ) {
                $orderId = $block->getOrderId();
                $linkurl = Mage::helper('adminhtml')->getUrl('adminhtml/wuunder/create/', array('id' => $orderId));
                $block->removeButton('order_ship');
                $block->addButton('order_ship', array(
                    'label' => Mage::helper('sales')->__('Ship'),
                    'onclick' => '(function(e) {
                    var $j = jQuery.noConflict();
                    $j.fancybox(
                        {
                            href : \'' . $linkurl . '\',
                            type: \'ajax\',
                            width: \'600\',
                            openEffect: \'elastic\',
                            afterClose: function () {
                                parent.location.reload(true);
                            }
                        });
                })(event)',
                    'class' => 'go'
                ), 0, 40);
            }
        }
    }
}
