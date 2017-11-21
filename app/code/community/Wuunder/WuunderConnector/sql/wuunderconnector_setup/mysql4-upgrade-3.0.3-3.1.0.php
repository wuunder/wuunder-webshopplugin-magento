<?php

$installer = $this;
Mage::log('running script', null, 'services_upgrade.log');
$installer->startSetup();
try {
    $installer->run("
	ALTER TABLE {$this->getTable('wuunder_shipments')} ADD KEY `order_id` (order_id);
");
} catch (Exception $e) {
    Mage::log("WuunderConnector: Cannot upgrade, see following exception:");
    Mage::log($e);
}

$installer->endSetup();