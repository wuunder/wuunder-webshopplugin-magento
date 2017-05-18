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

    public function adminhtmlWidgetContainerHtmlBefore($observer)
    {
        $block = $observer->getBlock();

        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_View) {
            $order = $block->getOrder();
            if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/ship') && $order->canShip()
                && !$order->getForcedDoShipmentWithInvoice()
            ) {
                $orderId = $block->getOrderId();
                $block->removeButton('order_ship');
                $shipmentInfo = Mage::helper('wuunderconnector')->getShipmentInfo($orderId);
                $storeId = $order->getStoreId();
                $testMode = Mage::getStoreConfig('wuunderconnector/connect/testmode', $storeId);
                if ($testMode == 1) {
                    $booking_url = 'https://api-staging.wuunder.co' . $shipmentInfo['booking_url'];
                } else {
                    $booking_url = 'https://api.wuunder.co' . $shipmentInfo['booking_url'];
                }
                $linkurl = (!is_null($shipmentInfo['booking_url']) && !empty($shipmentInfo['booking_url']) ? $booking_url : Mage::helper('adminhtml')->getUrl('adminhtml/wuunder/processLabel', array('id' => $orderId)));

                $block->addButton('order_ship', array(
                    'label' => Mage::helper('sales')->__('Ship'),
                    'onclick' => 'setLocation(\''. $linkurl . '\')',
                    'class' => 'go'
                ), 0, 40);
            }
        } else if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Shipment_View) {
            $shipmentId = $block->getRequest()->getParam('shipment_id');
            if (empty($shipmentId)) {
                return;
            }
            $shipment = Mage::getModel('sales/order_shipment')->load((int)$shipmentId);
            $orderId = $shipment->getOrder()->getId();

            $block->removeButton('print');
            $shipmentInfo = Mage::helper('wuunderconnector')->getShipmentInfo($orderId);

            $block->addButton('print', array(
                'label' => Mage::helper('sales')->__('Print'),
                'onclick' => 'setLocation(\''. $shipmentInfo['label_url'] . '\')',
                'class' => 'save'
            ), 0, 40);
        }
    }
}
