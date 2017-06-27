<?php

$installer = $this;
//$installer->installEntities();
Mage::log('running script', null, 'services_upgrade.log');

$installer->startSetup();
$installer->getConnection()->addColumn($this->getTable('wuunder_shipments'), 'booking_url', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'nullable' => true,
    'default' => null,
    'comment'   => 'booking_url'
));

$installer->getConnection()->addColumn($this->getTable('wuunder_shipments'), 'booking_token', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'nullable' => true,
    'default' => null,
    'comment'   => 'booking_token'
));

$installer->endSetup();