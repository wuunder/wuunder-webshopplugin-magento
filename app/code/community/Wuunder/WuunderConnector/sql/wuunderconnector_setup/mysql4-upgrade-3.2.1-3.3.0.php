<?php

$installer = $this;
Mage::log('running script', null, 'services_upgrade.log');
$installer->startSetup();
try {
    $installer->run("
	ALTER TABLE {$this->getTable('wuunder_shipments')} ADD carrier_tracking_code VARCHAR(255) NULL;
	ALTER TABLE {$this->getTable('wuunder_shipments')} ADD carrier_code VARCHAR(255) NULL;
");
} catch (Exception $e) {
    Mage::log("WuunderConnector: Cannot upgrade, see following exception:");
    Mage::log($e);
}

$installer->endSetup();