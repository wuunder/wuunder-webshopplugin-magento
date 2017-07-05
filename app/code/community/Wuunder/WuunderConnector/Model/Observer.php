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
            array('label_id', 'label_url', 'label_tt_url', 'booking_url')
        );

    }

    public function adminhtmlBlockHtmlBefore($observer)
    {
        if (isset($_REQUEST['label_order'])) {
            Mage::getSingleton('adminhtml/session')->addSuccess('Label met succes aangemaakt');
            Mage::app()->getFrontController()->getResponse()->setRedirect((isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . explode('?', $_SERVER['REQUEST_URI'], 2)[0]);
            Mage::app()->getResponse()->sendResponse();
            exit;
        }
    }

    public function adminhtmlWidgetContainerHtmlBefore($observer)
    {
        $block = $observer->getBlock();

        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_View) {
            $order = Mage::getModel('sales/order')->load($block->getOrderId());
            $shipping_method = $order->getShippingMethod();
            if (in_array($shipping_method, explode(",", Mage::getStoreConfig('wuunderconnector/connect/wuunder_enabled_shipping_methods')))) {
                if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/ship') && $order->canShip()
                    && !$order->getForcedDoShipmentWithInvoice()) {
                    $orderId = $block->getOrderId();
                    $block->removeButton('order_ship');
                    $shipmentInfo = Mage::helper('wuunderconnector')->getShipmentInfo($orderId);
                    $storeId = $order->getStoreId();
                    $testMode = Mage::getStoreConfig('wuunderconnector/connect/testmode', $storeId);

                    if (isset($shipmentInfo["booking_url"])) {
                        if (strpos($shipmentInfo['booking_url'], 'http:') === 0 || strpos($shipmentInfo['booking_url'], 'https:') === 0) {
                            $booking_url = $shipmentInfo['booking_url'];
                        } else {
                            if ($testMode == 1) {
                                $booking_url = 'https://api-staging.wuunder.co' . $shipmentInfo['booking_url'];
                            } else {
                                $booking_url = 'https://api.wuunder.co' . $shipmentInfo['booking_url'];
                            }
                        }
                        $linkurl = (!is_null($shipmentInfo['booking_url']) && !empty($shipmentInfo['booking_url']) ? $booking_url : Mage::helper('adminhtml')->getUrl('adminhtml/wuunder/processLabel', array('id' => $orderId)));
                    } else {
                        $linkurl = Mage::helper('adminhtml')->getUrl('adminhtml/wuunder/processLabel', array('id' => $orderId));
                    }


                    $block->addButton('order_ship', array(
                        'label' => Mage::helper('sales')->__('Ship'),
                        'onclick' => 'setLocation(\'' . $linkurl . '\')',
                        'class' => 'go'
                    ), 0, 40);
                }
            }
        } else if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Shipment_View) {
            $shipment = Mage::registry('current_shipment');
            $shipping_method = Mage::getModel('sales/order')->load($shipment->getOrderId())->getShippingMethod();
            if (in_array($shipping_method, explode(",", Mage::getStoreConfig('wuunderconnector/connect/wuunder_enabled_shipping_methods')))) {
                $shipmentId = $block->getRequest()->getParam('shipment_id');
                if (empty($shipmentId)) {
                    return;
                }
                $orderId = $shipment->getOrderId();
                $block->removeButton('print');
                $shipmentInfo = Mage::helper('wuunderconnector')->getShipmentInfo($orderId);

                $block->addButton('print', array(
                    'label' => Mage::helper('sales')->__('Print'),
                    'onclick' => 'setLocation(\'' . $shipmentInfo['label_url'] . '\')',
                    'class' => 'save'
                ), 0, 40);
            }
        }
    }
}
