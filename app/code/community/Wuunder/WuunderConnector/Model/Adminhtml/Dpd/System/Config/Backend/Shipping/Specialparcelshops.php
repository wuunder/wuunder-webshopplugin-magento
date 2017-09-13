<?php
/**
 * Created by PHPro
 *
 * @package      DPD
 * @subpackage   Shipping
 * @category     Checkout
 * @author       PHPro (info@phpro.be)
 */
/**
 * Class Wuunder_WuunderConnector_Model_Adminhtml_Dpd_System_Config_Backend_Shipping_Specialparcelshops
 */
class Wuunder_WuunderConnector_Model_Adminhtml_Dpd_System_Config_Backend_Shipping_Specialparcelshops extends Mage_Core_Model_Config_Data
{
    /**
     * Call the uploadAndImport function from the parcelshops recoursemodel.
     */
    public function _afterSave()
    {
        Mage::getResourceModel('wuunderconnector/specialparcelshops')->uploadAndImport($this);
    }
}
