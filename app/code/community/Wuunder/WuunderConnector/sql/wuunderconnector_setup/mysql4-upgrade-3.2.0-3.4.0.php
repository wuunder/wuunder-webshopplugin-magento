<?php

$installer = $this;
Mage::log('running script', null, 'services_upgrade.log');
$installer->startSetup();
try {
    $installer->getConnection()->dropColumn($installer->getTable('sales/quote'), 'dpd_selected');
    $installer->getConnection()->dropColumn($installer->getTable('sales/quote'), 'dpd_company');
    $installer->getConnection()->dropColumn($installer->getTable('sales/quote'), 'dpd_city');
    $installer->getConnection()->dropColumn($installer->getTable('sales/quote'), 'dpd_street');
    $installer->getConnection()->dropColumn($installer->getTable('sales/quote'), 'dpd_zipcode');
    $installer->getConnection()->dropColumn($installer->getTable('sales/quote'), 'dpd_country');

    $installer->run("
	DROP TABLE IF EXISTS {$this->getTable('wuunder_quote_data')};
	CREATE TABLE IF NOT EXISTS {$this->getTable('wuunder_quote_data')} (
	  `id` int(10) NOT NULL AUTO_INCREMENT,
	  `quote_id` int(10) NOT NULL,
	  `parcelshop_id` varchar(255) DEFAULT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
	ALTER TABLE {$this->getTable('wuunder_quote_data')} ADD KEY `quote_id` (quote_id);
    ");

} catch (Exception $e) {
    Mage::log("WuunderConnector: Cannot upgrade, see following exception:");
    Mage::log($e);
}

$installer->endSetup();