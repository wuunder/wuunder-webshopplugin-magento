<?php

class Wuunder_WuunderConnector_Block_Adminhtml_Order_Renderer_WuunderIcons extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $orderId    = $row->getData('entity_id');
        $order = Mage::getModel('sales/order')->load($orderId);
        $shipping_method = $order->getShippingMethod();
        $icons = '';
        if (in_array($shipping_method, explode(",", Mage::getStoreConfig('wuunderconnector/connect/wuunder_enabled_shipping_methods'))) ||
            in_array("wuunder_default_all_selected", explode(",", Mage::getStoreConfig('wuunderconnector/connect/wuunder_enabled_shipping_methods')))) {
            if (!empty($row->getData('label_id'))) {
                $icons = '<li class="wuunder-label-download"><a href="' . $row->getData('label_url') . '"  target="_blank" title="Print verzendlabel"></a></li>';
            } else if (!empty($row->getData('booking_url'))) {
                $booking_url = $row->getData('booking_url');

                $icons = '<li class="wuunder-label-draft"><a href="' . $booking_url . '" title="Draft verzenden"></a></li>';
            } else {
                $icons = '<li class="wuunder-label-create"><a href="' . $this->getUrl('adminhtml/wuunder/processLabel', array('id' => $orderId)) . '" title="Verzendlabel aanmaken"></a></li>';
            }
        }

        if ($icons != '') {
            $icons = '<div class="wuunder-icons"><ul>' . $icons . '</ul></div>';
        }

        return $icons;
    }
}