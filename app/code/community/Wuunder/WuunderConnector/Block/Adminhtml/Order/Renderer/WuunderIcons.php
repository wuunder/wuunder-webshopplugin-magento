<?php

class Wuunder_WuunderConnector_Block_Adminhtml_Order_Renderer_WuunderIcons extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $orderId = $row->getData('entity_id');

        $shipmentInfo = Mage::helper('wuunderconnector')->getShipmentInfo($orderId);
        $order = Mage::getModel('sales/order')->load($orderId);
        $storeId = $order->getStoreId();
        $testMode = Mage::getStoreConfig('wuunderconnector/connect/testmode', $storeId);
        if (!is_null($shipmentInfo['booking_url']) && !empty($shipmentInfo['booking_url'])) {
            if (strpos($shipmentInfo['booking_url'], 'http:') === 0 || strpos($shipmentInfo['booking_url'], 'https:') === 0) {
                $booking_Url = $shipmentInfo['booking_url'];
            } else if ($testMode == 1) {
                $booking_Url = 'https://api-staging.wuunder.co' . $shipmentInfo['booking_url'];
            } else {
                $booking_Url = 'https://api.wuunder.co' . $shipmentInfo['booking_url'];
            }
        }

        // Retour ID found -> Shipping label was generated
        $linkurl = (!is_null($shipmentInfo['booking_url']) && !empty($shipmentInfo['booking_url']) ? $booking_Url : $this->getUrl('adminhtml/wuunder/processLabel', array('id' => $orderId)));
        $icons = '<a href="' . $linkurl . '" title="Verzendlabel aanmaken">' . ((!is_null($shipmentInfo['booking_url']) && !empty($shipmentInfo['booking_url'])) ? "Bekijk zending" : "Boek") . '</a>';
//        }

        if ($icons != '') {
            $icons = '<div class="wuunder-icons">' . $icons . '</div>';
        }

        return $icons;
    }
}