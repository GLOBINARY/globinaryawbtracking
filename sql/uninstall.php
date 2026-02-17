<?php

$sql = array();

$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'globinary_awb_tracking`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'globinary_awb_dpd_sites`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'globinary_awb_sameday_sites`';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
