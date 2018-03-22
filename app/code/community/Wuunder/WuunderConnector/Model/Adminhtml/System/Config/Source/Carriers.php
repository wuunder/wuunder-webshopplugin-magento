<?php
class Wuunder_WuunderConnector_Model_Adminhtml_System_Config_Source_Carriers
{

    public function toOptionArray()
    {
        $type = array();
        $type[] = array('value' => 'DPD',
                        'label' => 'DPD',
                        'selected' => 'selected');

        $type[] = array('value' => 'DHL_PARCEL',
                        'label' => 'DHL');

        return $type;
    }

}
