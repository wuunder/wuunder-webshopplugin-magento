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
            if (in_array($shipping_method, explode(",", Mage::getStoreConfig('wuunderconnector/connect/wuunder_enabled_shipping_methods'))) ||
                in_array("wuunder_default_all_selected", explode(",", Mage::getStoreConfig('wuunderconnector/connect/wuunder_enabled_shipping_methods')))
            ) {
                if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/ship') && $order->canShip()
                    && !$order->getForcedDoShipmentWithInvoice()
                ) {
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
            if (in_array($shipping_method, explode(",", Mage::getStoreConfig('wuunderconnector/connect/wuunder_enabled_shipping_methods'))) ||
                in_array("wuunder_default_all_selected", explode(",", Mage::getStoreConfig('wuunderconnector/connect/wuunder_enabled_shipping_methods')))
            ) {
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

    /**
     * Observes html load, this will add html to the Parcelshop shipping method and set default shipping method.
     *
     * @param $observer
     */
    public function coreBlockAbstractToHtmlAfter($observer)
    {
        if ($observer->getBlock() instanceof Mage_Checkout_Block_Onepage_Shipping_Method_Available) {
            //get HTML
            $html = $observer->getTransport()->getHtml();
            //set default if in config
            $html = Mage::helper('wuunderconnector')->checkShippingDefault($html);
            //replace label by html
            $html = Mage::helper('wuunderconnector')->addHTML($html);

            //set HTML
            $observer->getTransport()->setHtml($html);
        }
    }

    /**
     * Triggered when saving a shipping method, this saves dpd data to the customer address without saving it on customer.
     *
     * @param $observer
     */
    public function checkout_controller_onepage_save_shipping_method($observer)
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $address = $quote->getShippingAddress();
        if ($address->getShippingMethod() == "wuunderdpd_wuunderdpd") {
            $address->unsetAddressId()
                ->unsetTelephone()
                ->setSaveInAddressBook(0)
                ->setFirstname('DPD ParcelShop: ')
                ->setLastname($quote->getDpdCompany())
                ->setStreet($quote->getDpdStreet())
                ->setCity($quote->getDpdCity())
                ->setPostcode($quote->getDpdZipcode())
                ->setCountryId($quote->getDpdCountry())
                ->save();
        }
    }

    /**
     * Sets generate return label button on order detail view in the admin.
     * Sets download dpdlabel button on shipment order detail.
     *
     * @param $observer
     */
    public function core_block_abstract_to_html_before($observer)
    {
        $block = $observer->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_View && $block->getRequest()->getControllerName() == 'sales_order') {
            $orderId = Mage::app()->getRequest()->getParam('order_id');
            $block->addButton('print_retour_label', array(
                'label' => Mage::helper('dpd')->__('DPD Return Label'),
                'onclick' => 'setLocation(\'' . Mage::helper("adminhtml")->getUrl('adminhtml/dpdorder/generateRetourLabel/order_id/' . $orderId) . '\')',
                'class' => 'go'
            ));

        }
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Shipment_View && $block->getRequest()->getControllerName() == "sales_order_shipment") {
            $shipment = Mage::registry('current_shipment');
            $shipmentId = $shipment->getId();
            $order = Mage::getModel('sales/order')->load($shipment->getOrderId());
            if (strpos($order->getShippingMethod(), 'dpd') !== false) {
                $block->addButton('download_dpd_label', array(
                    'label' => Mage::helper('dpd')->__('Download DPD Label'),
                    'onclick' => 'setLocation(\'' . Mage::helper("adminhtml")->getUrl('adminhtml/dpdorder/downloadDpdLabel/shipment_id/' . $shipmentId) . '\')',
                    'class' => 'scalable save'
                ));
            }
        }
    }

    /**
     * Calculate and set the weight on the shipping to pass it to the webservice after a standard shipment save.
     *
     * @param $observer
     */
    public function sales_order_shipment_save_before($observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        if (!$shipment->hasId() && !$shipment->getTotalWeight()) {
            $weight = Mage::helper('wuunderconnector')->calculateTotalShippingWeight($shipment);
            $shipment->setTotalWeight($weight);
        }
    }

    /**
     * If the checkout is a Onestepcheckout and dpdselected is true, we need to copy the address on submitting
     *
     * @param $observer
     */
    public function checkout_submit_all_after($observer)
    {
        if (Mage::helper('wuunderconnector')->getIsOnestepCheckout()) {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $address = $quote->getShippingAddress();
            if ($address->getShippingMethod() == "wuunderdpd_wuunderdpd" && (bool)$quote->getDpdSelected()) {
                $address->unsetAddressId()
                    ->unsetTelephone()
                    ->setSaveInAddressBook(0)
                    ->setFirstname('DPD ParcelShop: ')
                    ->setLastname($quote->getDpdCompany())
                    ->setStreet($quote->getDpdStreet())
                    ->setCity($quote->getDpdCity())
                    ->setPostcode($quote->getDpdZipcode())
                    ->setCountryId($quote->getDpdCountry())
                    ->save();
            }
            $quote->setDpdSelected(0);
        }
    }

    /**
     * If Billing/Shipping address was changed, reset the DPD shipping Method.
     *
     * @param $observer
     */
    public function controller_action_predispatch_onestepcheckout_ajax_save_billing($observer)
    {
        if (Mage::helper('wuunderconnector')->getIsOnestepCheckout()) {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            if ($quote->getDpdSelected()) {
                $quote->setDpdSelected(0);
            }
        }
    }
}
