<?php

class Wuunder_WuunderConnector_Model_Adminhtml_System_Config_Source_ParcelshopCarriers
{

    public function toOptionArray()
    {
        $type = array();
        $type[] = array(
            'value' => 'dpd',
            'label' => 'DPD',
            'selected' => 'selected'
        );

        $type[] = array(
            'value' => 'dhl',
            'label' => 'DHL'
        );

        $type[] = array(
            'value' => 'postnl',
            'label' => 'PostNL'
        );

        return $type;
    }

}
