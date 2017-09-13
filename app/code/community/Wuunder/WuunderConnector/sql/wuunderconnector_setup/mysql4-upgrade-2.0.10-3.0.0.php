<?php

$installer = $this;
Mage::log('running script', null, 'services_upgrade.log');

$installer->startSetup();
/**
 * BEGIN - DPD parcelshop picker implementation
 */

$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_selected', "boolean default '0'");
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_company', "varchar(255) null default ''");
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_city', "varchar(255) null default ''");
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_street', "varchar(255) null default ''");
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_zipcode', "varchar(255) null default ''");
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_country', "varchar(255) null default ''");
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_parcelshop_id', "varchar(255) null");
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_special_point', "boolean default '0'");
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_extra_info', "text null default ''");

/**
 *  END - DPD parcelshop picker implementation
 */

$installer->endSetup();