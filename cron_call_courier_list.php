<?php
include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/../../init.php');

$module = Module::getInstanceByName('globinaryawbtracking');
if (!$module) {
    die("Module not found.");
}

$module->callCourierList();
?>
