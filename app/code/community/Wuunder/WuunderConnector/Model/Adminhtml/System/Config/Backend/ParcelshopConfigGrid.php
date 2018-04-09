<?php

class Wuunder_WuunderConnector_Model_Adminhtml_System_Config_Backend_ParcelshopConfigGrid extends Mage_Adminhtml_Model_System_Config_Backend_Serialized_Array {

    protected function _beforeSave() {
        Mage::helper('wuunderconnector')->log($this->getValue());
        $this->checkValues($this->getValue());
        parent::_beforeSave();
    }

    private function checkValues($values) {
        foreach ($values as $key => $row) {
            if ($key === "__empty")
                continue;
            foreach ($values as $k => $r) {
                if ($k !== $key) {
                    if ($row['carrier'] === $r['carrier']) {
                        Mage::throwException(Mage::helper('wuunderconnector')->__("Duplicated carrier") . ": " . $row['carrier']);
                    }
                }
            }
            if (empty($row['name'])) {
                Mage::throwException(Mage::helper('wuunderconnector')->__("name cannot be empty"));
            }
        }
    }

}