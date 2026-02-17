<?php

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'globinary_awb_tracking` (
    `id_globinary_awb_tracking` int(11) NOT NULL AUTO_INCREMENT,
    `order_id` int(11) NOT NULL,
    `awb_number` varchar(255) NOT NULL,
    `current_status` varchar(255) NOT NULL,
    `operation_code` varchar(255),
    `awb_date_added` datetime DEFAULT NULL,
    `last_status_change` DATETIME DEFAULT NULL,
    `backup_field_one` varchar(255),
    `backup_field_two` varchar(255),
    `backup_field_three` varchar(255),
    `backup_field_four` varchar(255),
    `backup_field_five` varchar(255),
    `backup_field_six` varchar(255),
    `backup_field_seven` varchar(255),
    `backup_field_eight` varchar(255),
    `backup_field_nine` varchar(255),
    `backup_field_ten` varchar(255),
    PRIMARY KEY (`id_globinary_awb_tracking`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'globinary_awb_dpd_sites` (
    `id` BIGINT(20) NOT NULL,
    `country_id` INT(11) NOT NULL,
    `main_site_id` BIGINT(20) DEFAULT NULL,
    `type` VARCHAR(10) DEFAULT NULL,
    `type_en` VARCHAR(10) DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL,
    `name_en` VARCHAR(100) DEFAULT NULL,
    `municipality` VARCHAR(100) DEFAULT NULL,
    `municipality_en` VARCHAR(100) DEFAULT NULL,
    `region` VARCHAR(100) NOT NULL,
    `region_en` VARCHAR(100) DEFAULT NULL,
    `post_code` VARCHAR(10) DEFAULT NULL,
    `address_nomenclature` INT(11) DEFAULT NULL,
    `x` FLOAT DEFAULT NULL,
    `y` FLOAT DEFAULT NULL,
    `serving_days` VARCHAR(7) DEFAULT NULL,
    `serving_office_id` INT(11) DEFAULT NULL,
    `serving_hub_office_id` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_region` (`region`),
    INDEX `idx_name` (`name`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'globinary_awb_sameday_sites` (
    `id` BIGINT(20) NOT NULL,
    `sameday_delivery_agency_id` INT(11) DEFAULT NULL,
    `sameday_delivery_agency` VARCHAR(100) DEFAULT NULL,
    `sameday_pickup_gency` VARCHAR(100) DEFAULT NULL,
    `next_day_delivery_agencyId` INT(11) DEFAULT NULL,
    `next_day_delivery_agency` VARCHAR(100) DEFAULT NULL,
    `next_day_pickup_agency` VARCHAR(100) DEFAULT NULL,
    `white_delivery_agency_id` INT(11) DEFAULT NULL,
    `white_delivery_agency` VARCHAR(100) DEFAULT NULL,
    `white_pickup_agency` VARCHAR(100) DEFAULT NULL,
    `logistic_circle` VARCHAR(100) DEFAULT NULL,
    `country_id` INT(11) DEFAULT NULL,
    `country_name` VARCHAR(100) DEFAULT NULL,
    `country_code` VARCHAR(10) DEFAULT NULL,
    `sameday_id` BIGINT(20) DEFAULT NULL,
    `name` VARCHAR(500) DEFAULT NULL,
    `county_id` INT(11) DEFAULT NULL,
    `county_name` VARCHAR(100) DEFAULT NULL,
    `county_code` VARCHAR(10) DEFAULT NULL,
    `county_latin_name` VARCHAR(100) DEFAULT NULL,
    `postal_code` VARCHAR(20) DEFAULT NULL,
    `extra_KM` FLOAT DEFAULT NULL,
    `village` VARCHAR(500) DEFAULT NULL,
    `broker_delivery` INT(11) DEFAULT NULL,
    `latin_name` VARCHAR(500) DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_sameday_county` (`county_name`),
    INDEX `idx_sameday_county_latin` (`county_latin_name`),
    INDEX `idx_sameday_name` (`name`),
    INDEX `idx_sameday_latin` (`latin_name`),
    INDEX `idx_sameday_postal` (`postal_code`),
    INDEX `idx_sameday_id` (`sameday_id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        die('SQL Error: ' . Db::getInstance()->getMsgError());
        return false;
    }
}
