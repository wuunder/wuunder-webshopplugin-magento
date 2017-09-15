<?php

$installer = $this;
//$installer->installEntities();
Mage::log('running script', null, 'services_upgrade.log');

$installer->startSetup();
$installer->getConnection()->addColumn($this->getTable('wuunder_shipments'), 'label_tt_url', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'nullable' => true,
    'default' => null,
    'comment'   => 'label_tt_url'
));

$installer->endSetup();