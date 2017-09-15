<?php

$installer = $this;
$installer->startSetup();
$installer->run("
	DROP TABLE IF EXISTS {$this->getTable('wuunder_shipments')};
	CREATE TABLE IF NOT EXISTS {$this->getTable('wuunder_shipments')} (
	  `shipment_id` int(10) NOT NULL AUTO_INCREMENT,
	  `order_id` int(10) NOT NULL,
	  `label_id` varchar(255) DEFAULT NULL,
	  `label_url` text DEFAULT NULL,
	  `label_tt_url` text DEFAULT NULL,
	  `booking_url` text DEFAULT NULL,
	  `booking_token` text DEFAULT NULL,
	  PRIMARY KEY (`shipment_id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
");

/**
 * BEGIN - DPD parcelshop picker implementation
 */

$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_selected', "boolean default '0'");
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_company', "varchar(255) null default ''");
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_city', "varchar(255) null default ''");
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_street', "varchar(255) null default ''");
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_zipcode', "varchar(255) null default ''");
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_country', "varchar(255) null default ''");

/**
 *  END - DPD parcelshop picker implementation
 */

$installer->endSetup();
$methods = Mage::getSingleton('shipping/config')->getActiveCarriers();

$options = array();
foreach($methods as $_code => $_method)
{
    $options[] = $_code;
}
Mage::getConfig()->saveConfig('wuunderconnector/connect/wuunder_enabled_shipping_methods', implode(",", $options), 'default', 0);