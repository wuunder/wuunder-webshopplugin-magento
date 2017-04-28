<?php
class Wuunder_WuunderConnector_Block_Adminhtml_Order_Renderer_WuunderIcons extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {

    	$icons      = '';
        $orderId    = $row->getData('entity_id');
    	$labelId    = $row->getData('label_id');
        $labelUrl   = $row->getData('label_url');
        $labelTTUrl = $row->getData('label_tt_url');
    	$retourId   = $row->getData('retour_id');
        $retourUrl  = $row->getData('retour_url');
   		$retourTTUrl = $row->getData('retour_tt_url');

        if (isset($retourId)) {

            // Retour ID found -> Retour label was generated
            $icons = '
            <li class="wuunder-label-download"><a href="'.$labelUrl.'" title="Download verzendlabel" target="_blank"></a></li>
            <li class="wuunder-retour-download"><a href="'.$labelUrl.'" title="Download retourlabel" target="_blank"></a></li>
            <li class="wuunder-retour-tracktrace"><a href="'.$retourTTUrl.'" title="Track & Trace retour" target="_blank"></a></li>
            ';

    	} else if (isset($labelId)) {
    		// Label ID found -> Shipping label was generated
            $linkurl  = Mage::helper('adminhtml')->getUrl('adminhtml/wuunder/create/', array('id' => $orderId));
    		$icons = '<li class="wuunder-label-download"><a href="'.$labelUrl.'" title="Download verzendlabel" target="_blank"></a></li>';
            $icons.= '<li class="wuunder-label-tracktrace"><a href="'.$labelTTUrl.'" title="Track & Trace" target="_blank"></a></li>';
            $icons.= '<li class="wuunder-retour-create"><a href="'.$linkurl.'" class="wuunder" title="Retourlabel aanmaken"></a></li>';

    	} else {

    		// Retour ID found -> Shipping label was generated
            $linkurl  = Mage::helper('adminhtml')->getUrl('adminhtml/wuunder/create/', array('id' => $orderId));
            $icons = '<li class="wuunder-label-create"><a href="'.$linkurl.'" class="wuunder" title="Verzendlabel aanmaken"></a></li>';
    	}

    	if ($icons != '') {
    		$icons = '<div class="wuunder-icons"><ul>'.$icons.'</ul></div>';
    	}

        return $icons;
    }
}