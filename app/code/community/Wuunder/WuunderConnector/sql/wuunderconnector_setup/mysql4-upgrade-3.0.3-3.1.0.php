<?php

$installer = $this;
Mage::log('running script', null, 'services_upgrade.log');
$installer->startSetup();
$installer->run("
	ALTER TABLE {$this->getTable('wuunder_shipments')} ADD KEY `order_id` (order_id);
");

$installer->endSetup();