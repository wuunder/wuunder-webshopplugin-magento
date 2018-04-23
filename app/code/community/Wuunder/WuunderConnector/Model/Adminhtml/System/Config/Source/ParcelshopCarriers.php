<?php

class Wuunder_WuunderConnector_Model_Adminhtml_System_Config_Source_ParcelshopCarriers
{

    public function toOptionArray()
    {
        $type = array();
        $type[] = array(
            'value' => 'DPD',
            'label' => 'DPD',
            'selected' => 'selected'
        );

        $type[] = array(
            'value' => 'DHL_PARCEL',
            'label' => 'DHL'
        );

        $type[] = array(
            'value' => 'POST_NL',
            'label' => 'PostNL'
        );

        return $type;
    }

}
