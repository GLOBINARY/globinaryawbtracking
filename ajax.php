<?php
if (!defined('_PS_VERSION_')) {
    require_once dirname(__FILE__) . '/../../config/config.inc.php';
    require_once dirname(__FILE__) . '/../../init.php';
}

$token = Tools::getValue('token');
if (empty($token) || $token !== Tools::encrypt(Configuration::get('PS_SHOP_NAME'))) {
    die(json_encode(['success' => false, 'message' => 'Invalid token']));
}

// Employee check disabled: allow BO ajax with module token
$module = Module::getInstanceByName('globinaryawbtracking');
if (!$module) {
    die(json_encode(['success' => false, 'message' => 'Module not found']));
}

$action = Tools::getValue('action');
switch ($action) {
    case 'issue_awb':
        echo $module->processIssueAwb();
        break;
    case 'delete_awb':
        echo $module->deleteAwb();
        break;
    case 'call_courier':
        echo $module->callCourier();
        break;
    case 'call_courier_list':
        echo $module->callCourierList();
        break;
    case 'print_awb':
        echo $module->printAwb();
        break;
    case 'update_awb_status':
        echo $module->updateAwbStatus();
        break;
    case 'get_right_site_id':
        echo $module->getRightSiteId();
        break;
    case 'get_all_cities':
        echo $module->getAllCities();
        break;
    case 'calculate_price':
        echo $module->calculatePrice();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
