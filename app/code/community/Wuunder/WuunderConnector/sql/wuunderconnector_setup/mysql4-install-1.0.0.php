<?php

$installer = $this;
$installer->startSetup();
$installer->run("
	DROP TABLE IF EXISTS {$this->getTable('wuunder_shipments')};
	CREATE TABLE IF NOT EXISTS {$this->getTable('wuunder_shipments')} (
	  `shipment_id` int(10) NOT NULL AUTO_INCREMENT,
	  `order_id` int(10) NOT NULL,
	  `description` varchar(255) DEFAULT NULL,
	  `type` varchar(255) DEFAULT NULL,
	  `length` int(10) NOT NULL DEFAULT '0',
	  `width` int(10) NOT NULL DEFAULT '0',
	  `height` int(10) NOT NULL DEFAULT '0',
	  `weight` int(10) NOT NULL DEFAULT '0',
	  `phone_number` varchar(255) DEFAULT NULL,
	  `personal_message` text DEFAULT NULL,
	  `retour_message` text DEFAULT NULL,
	  `image` blob,
	  `label_id` varchar(255) DEFAULT NULL,
	  `label_date` int(10) NOT NULL DEFAULT '0',
	  `label_url` text DEFAULT NULL,
	  `label_tt_url` text DEFAULT NULL,
	  `booking_url` text DEFAULT NULL,
	  `booking_token` text DEFAULT NULL,
	  `retour_id` varchar(255) DEFAULT NULL,
	  `retour_date` int(10) NOT NULL DEFAULT '0',
	  `retour_url` text DEFAULT NULL,
	  `retour_tt_url` text DEFAULT NULL,
	  PRIMARY KEY (`shipment_id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
");
$installer->endSetup();