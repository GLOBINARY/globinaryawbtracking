<?php
/**
 * 2007-2026 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Globinaryawbtracking extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'globinaryawbtracking';
        $this->tab = 'shipping_logistics';
        $this->version = '1.6.9';
        $this->author = 'Globinary';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('GLOBINARY AWB România');
        $this->description = $this->l('AWB multi-curier, urmărire colete, sincronizare status și actualizare comenzi pentru România — gestionat integral în PrestaShop.');
        $this->confirmUninstall = $this->l('Sigur dorești să dezinstalezi modulul GLOBINARY AWB România?');
        $this->ps_versions_compliancy = array('min' => '8.0', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        $prevHandler = set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if (strpos($errstr, 'Undefined array key') !== false) {
                Logger::addLog('[Globinary AWB] Warning during install: ' . $errstr . ' in ' . $errfile . ':' . $errline, 3);
                return true;
            }
            return false;
        }, E_WARNING);

        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('Extensia cURL trebuie activată pentru a instala acest modul.');
            if ($prevHandler !== null) {
                restore_error_handler();
            }
            return false;
        }

        Configuration::updateValue('GLOBINARYAWB_LIVE_MODE', false);
        Configuration::updateValue('GLOBINARYAWB_DPD_LIVE_USER', '');
        Configuration::updateValue('GLOBINARYAWB_DPD_LIVE_PASS', '');
        Configuration::updateValue('GLOBINARYAWB_DPD_TEST_USER', '');
        Configuration::updateValue('GLOBINARYAWB_DPD_TEST_PASS', '');
        Configuration::updateValue('GLOBINARYAWB_DPD_PICKUP_START', '15:00');
        Configuration::updateValue('GLOBINARYAWB_DPD_PICKUP_END', '15:30');
        Configuration::updateValue('GLOBINARYAWB_DPD_LAST_SYNC', '');
        Configuration::updateValue('GLOBINARYAWB_DPD_IMPORT_OK', false);
        Configuration::updateValue('GLOBINARYAWB_DPD_IMPORT_LAST_ERROR', '');
        Configuration::updateValue('GLOBINARYAWB_EMAIL_NOTIF', false);
        Configuration::updateValue('GLOBINARYAWB_ISSUE_ORDER_STATE', 0);
        Configuration::updateValue('GLOBINARYAWB_SMARTBILL_AUTO', false);
        Configuration::updateValue('GLOBINARYAWB_DSC_LIVE_USER', '');
        Configuration::updateValue('GLOBINARYAWB_DSC_LIVE_PASS', '');
        Configuration::updateValue('GLOBINARYAWB_DSC_TEST_USER', '');
        Configuration::updateValue('GLOBINARYAWB_DSC_TEST_PASS', '');
        Configuration::updateValue('GLOBINARYAWB_SAMEDAY_LIVE_USER', '');
        Configuration::updateValue('GLOBINARYAWB_SAMEDAY_LIVE_PASS', '');
        Configuration::updateValue('GLOBINARYAWB_SAMEDAY_TEST_USER', '');
        Configuration::updateValue('GLOBINARYAWB_SAMEDAY_TEST_PASS', '');
        Configuration::updateValue('GLOBINARYAWB_SAMEDAY_SERVICE_ID', '7');
        Configuration::updateValue('GLOBINARYAWB_SAMEDAY_PICKUP_POINT_ID', '');
        Configuration::updateValue('GLOBINARYAWB_SAMEDAY_CONTACT_PERSON_ID', '');
        Configuration::updateValue('GLOBINARYAWB_SAMEDAY_RETURN_LOCATION_ID', '');
        Configuration::updateValue('GLOBINARYAWB_SAMEDAY_TOKEN', '');
        Configuration::updateValue('GLOBINARYAWB_SAMEDAY_TOKEN_EXPIRES', '');
        Configuration::updateValue('GLOBINARYAWB_SAMEDAY_TEST_TOKEN', '');
        Configuration::updateValue('GLOBINARYAWB_SAMEDAY_TEST_TOKEN_EXPIRES', '');
        Configuration::updateValue('GLOBINARYAWB_SAMEDAY_IMPORT_OK', false);
        Configuration::updateValue('GLOBINARYAWB_SAMEDAY_IMPORT_LAST_ERROR', '');
        Configuration::updateValue('GLOBINARYAWB_SAMEDAY_LAST_SYNC', '');

        foreach ($this->getDpdStatusCodes() as $code => $label) {
            Configuration::updateValue($this->getDpdStatusConfigKey($code), '');
        }
        foreach ($this->getSamedayStatusCodes() as $code => $label) {
            Configuration::updateValue($this->getSamedayStatusConfigKey($code), '');
        }

        include(dirname(__FILE__) . '/sql/install.php');
        if (Db::getInstance()->getNumberError()) {
            $this->_errors[] = $this->l('Eroare la crearea tabelei: ') . Db::getInstance()->getMsgError();
            return false;
        }

        if (!$this->importDpdSitesFromCsv()) {
            Configuration::updateValue('GLOBINARYAWB_DPD_IMPORT_OK', false);
            Configuration::updateValue('GLOBINARYAWB_DPD_IMPORT_LAST_ERROR', '');
            $this->_errors[] = $this->l('Importul localităților DPD a eșuat. Poți sincroniza manual din setări.');
        } else {
            Configuration::updateValue('GLOBINARYAWB_DPD_IMPORT_OK', true);
            Configuration::updateValue('GLOBINARYAWB_DPD_LAST_SYNC', date('Y-m-d H:i:s'));
        }

        if (!$this->importSamedaySitesFromDb()) {
            Configuration::updateValue('GLOBINARYAWB_SAMEDAY_IMPORT_OK', false);
            Configuration::updateValue('GLOBINARYAWB_SAMEDAY_IMPORT_LAST_ERROR', '');
            $this->_errors[] = $this->l('Importul localităților Sameday a eșuat. Poți sincroniza manual din setări.');
        } else {
            Configuration::updateValue('GLOBINARYAWB_SAMEDAY_IMPORT_OK', true);
            Configuration::updateValue('GLOBINARYAWB_SAMEDAY_LAST_SYNC', date('Y-m-d H:i:s'));
        }

        $result = parent::install()
            && $this->registerHook('displayOrderDetail')
            && $this->registerHook('displayAdminOrderTabLink')
            && $this->registerHook('displayAdminOrderTabContent')
            && $this->registerHook('displayAdminOrderSide');

        if ($prevHandler !== null) {
            restore_error_handler();
        }

        return $result;
    }

    public function uninstall()
    {
        Configuration::deleteByName('GLOBINARYAWB_LIVE_MODE');
        Configuration::deleteByName('GLOBINARYAWB_DPD_LIVE_USER');
        Configuration::deleteByName('GLOBINARYAWB_DPD_LIVE_PASS');
        Configuration::deleteByName('GLOBINARYAWB_DPD_TEST_USER');
        Configuration::deleteByName('GLOBINARYAWB_DPD_TEST_PASS');
        Configuration::deleteByName('GLOBINARYAWB_DPD_PICKUP_START');
        Configuration::deleteByName('GLOBINARYAWB_DPD_PICKUP_END');
        Configuration::deleteByName('GLOBINARYAWB_DPD_LAST_SYNC');
        Configuration::deleteByName('GLOBINARYAWB_DPD_IMPORT_OK');
        Configuration::deleteByName('GLOBINARYAWB_DPD_IMPORT_LAST_ERROR');
        Configuration::deleteByName('GLOBINARYAWB_EMAIL_NOTIF');
        Configuration::deleteByName('GLOBINARYAWB_ISSUE_ORDER_STATE');
        Configuration::deleteByName('GLOBINARYAWB_SMARTBILL_AUTO');
        Configuration::deleteByName('GLOBINARYAWB_DSC_LIVE_USER');
        Configuration::deleteByName('GLOBINARYAWB_DSC_LIVE_PASS');
        Configuration::deleteByName('GLOBINARYAWB_DSC_TEST_USER');
        Configuration::deleteByName('GLOBINARYAWB_DSC_TEST_PASS');
        Configuration::deleteByName('GLOBINARYAWB_SAMEDAY_LIVE_USER');
        Configuration::deleteByName('GLOBINARYAWB_SAMEDAY_LIVE_PASS');
        Configuration::deleteByName('GLOBINARYAWB_SAMEDAY_TEST_USER');
        Configuration::deleteByName('GLOBINARYAWB_SAMEDAY_TEST_PASS');
        Configuration::deleteByName('GLOBINARYAWB_SAMEDAY_SERVICE_ID');
        Configuration::deleteByName('GLOBINARYAWB_SAMEDAY_PICKUP_POINT_ID');
        Configuration::deleteByName('GLOBINARYAWB_SAMEDAY_CONTACT_PERSON_ID');
        Configuration::deleteByName('GLOBINARYAWB_SAMEDAY_RETURN_LOCATION_ID');
        Configuration::deleteByName('GLOBINARYAWB_SAMEDAY_TOKEN');
        Configuration::deleteByName('GLOBINARYAWB_SAMEDAY_TOKEN_EXPIRES');
        Configuration::deleteByName('GLOBINARYAWB_SAMEDAY_TEST_TOKEN');
        Configuration::deleteByName('GLOBINARYAWB_SAMEDAY_TEST_TOKEN_EXPIRES');
        Configuration::deleteByName('GLOBINARYAWB_SAMEDAY_IMPORT_OK');
        Configuration::deleteByName('GLOBINARYAWB_SAMEDAY_IMPORT_LAST_ERROR');
        Configuration::deleteByName('GLOBINARYAWB_SAMEDAY_LAST_SYNC');

        foreach ($this->getDpdStatusCodes() as $code => $label) {
            Configuration::deleteByName($this->getDpdStatusConfigKey($code));
        }
        foreach ($this->getSamedayStatusCodes() as $code => $label) {
            Configuration::deleteByName($this->getSamedayStatusConfigKey($code));
        }

        include(dirname(__FILE__) . '/sql/uninstall.php');

        return parent::uninstall();
    }

    public function getContent()
    {
        $output = '';

        if (((bool)Tools::isSubmit('submitGlobinaryAwbSyncDpdSites')) == true) {
            if ($this->syncDpdSitesFromCsv(true)) {
                $output .= $this->displayConfirmation($this->l('Localitățile DPD au fost sincronizate.'));
            } else {
                $output .= $this->displayError($this->l('Sincronizarea localităților DPD a eșuat.'));
            }
        }
        if (((bool)Tools::isSubmit('submitGlobinaryAwbSyncSamedaySites')) == true) {
            if ($this->syncSamedaySitesFromDb(true)) {
                $output .= $this->displayConfirmation($this->l('Localitățile Sameday au fost sincronizate.'));
            } else {
                $output .= $this->displayError($this->l('Sincronizarea localităților Sameday a eșuat.'));
            }
        }

        if (((bool)Tools::isSubmit('submitGlobinaryAwbTrackingModule')) == true) {
            $form_values = $this->getConfigFormValues();
            $passwordKeys = array(
                'GLOBINARYAWB_DPD_LIVE_PASS',
                'GLOBINARYAWB_DPD_TEST_PASS',
                'GLOBINARYAWB_SAMEDAY_LIVE_PASS',
                'GLOBINARYAWB_SAMEDAY_TEST_PASS',
                'GLOBINARYAWB_DSC_LIVE_PASS',
                'GLOBINARYAWB_DSC_TEST_PASS',
            );
            foreach (array_keys($form_values) as $key) {
                $value = Tools::getValue($key);
                if (in_array($key, $passwordKeys, true)) {
                    if ($value === '' || $value === null) {
                        continue;
                    }
                }
                Configuration::updateValue($key, $value);
            }
            $output .= $this->displayConfirmation($this->l('Setările au fost salvate.'));
        }

        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->smarty->assign('dpd_last_sync', Configuration::get('GLOBINARYAWB_DPD_LAST_SYNC', ''));
        $this->context->smarty->assign('dpd_import_ok', Configuration::get('GLOBINARYAWB_DPD_IMPORT_OK', false));
        $this->context->smarty->assign('dpd_import_last_error', Configuration::get('GLOBINARYAWB_DPD_IMPORT_LAST_ERROR', ''));
        $this->context->smarty->assign('sameday_last_sync', Configuration::get('GLOBINARYAWB_SAMEDAY_LAST_SYNC', ''));
        $this->context->smarty->assign('sameday_import_ok', Configuration::get('GLOBINARYAWB_SAMEDAY_IMPORT_OK', false));
        $this->context->smarty->assign('sameday_import_last_error', Configuration::get('GLOBINARYAWB_SAMEDAY_IMPORT_LAST_ERROR', ''));

        $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitGlobinaryAwbTrackingModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    protected function getConfigForm()
    {
        $inputs = array(
            array(
                'type' => 'switch',
                'label' => $this->l('Mod live'),
                'name' => 'GLOBINARYAWB_LIVE_MODE',
                'tab' => 'general',
                'is_bool' => true,
                'desc' => $this->l('Folosește datele live ale curierului.'),
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => true,
                        'label' => $this->l('Activ')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => false,
                        'label' => $this->l('Test')
                    )
                ),
            ),
            array(
                'type' => 'text',
                'name' => 'GLOBINARYAWB_DPD_LIVE_USER',
                'label' => $this->l('DPD utilizator (live)'),
                'tab' => 'dpd',
                'required' => false
            ),
            array(
                'type' => 'password',
                'name' => 'GLOBINARYAWB_DPD_LIVE_PASS',
                'label' => $this->l('DPD parolă (live)'),
                'tab' => 'dpd',
                'required' => false
            ),
            array(
                'type' => 'text',
                'name' => 'GLOBINARYAWB_DPD_TEST_USER',
                'label' => $this->l('DPD utilizator (test)'),
                'tab' => 'dpd',
                'required' => false
            ),
            array(
                'type' => 'password',
                'name' => 'GLOBINARYAWB_DPD_TEST_PASS',
                'label' => $this->l('DPD parolă (test)'),
                'tab' => 'dpd',
                'required' => false
            ),
            array(
                'type' => 'text',
                'name' => 'GLOBINARYAWB_SAMEDAY_LIVE_USER',
                'label' => $this->l('Sameday utilizator (live)'),
                'tab' => 'sameday',
                'required' => false
            ),
            array(
                'type' => 'password',
                'name' => 'GLOBINARYAWB_SAMEDAY_LIVE_PASS',
                'label' => $this->l('Sameday parolă (live)'),
                'tab' => 'sameday',
                'required' => false
            ),
            array(
                'type' => 'text',
                'name' => 'GLOBINARYAWB_SAMEDAY_TEST_USER',
                'label' => $this->l('Sameday utilizator (test)'),
                'tab' => 'sameday',
                'required' => false
            ),
            array(
                'type' => 'password',
                'name' => 'GLOBINARYAWB_SAMEDAY_TEST_PASS',
                'label' => $this->l('Sameday parolă (test)'),
                'tab' => 'sameday',
                'required' => false
            ),
            array(
                'type' => 'text',
                'name' => 'GLOBINARYAWB_SAMEDAY_SERVICE_ID',
                'label' => $this->l('Sameday service ID'),
                'tab' => 'sameday',
                'desc' => $this->l('Ex: 7 (Nextday 24H), 15 (Locker Nextday), 57 (PUDO Nextday)'),
                'required' => false
            ),
            array(
                'type' => 'text',
                'name' => 'GLOBINARYAWB_SAMEDAY_PICKUP_POINT_ID',
                'label' => $this->l('Sameday pickup point ID'),
                'tab' => 'sameday',
                'required' => false
            ),
            array(
                'type' => 'text',
                'name' => 'GLOBINARYAWB_SAMEDAY_CONTACT_PERSON_ID',
                'label' => $this->l('Sameday contact person ID'),
                'tab' => 'sameday',
                'required' => false
            ),
            array(
                'type' => 'text',
                'name' => 'GLOBINARYAWB_SAMEDAY_RETURN_LOCATION_ID',
                'label' => $this->l('Sameday return location ID (opțional)'),
                'tab' => 'sameday',
                'required' => false
            ),
            array(
                'type' => 'text',
                'name' => 'GLOBINARYAWB_DSC_LIVE_USER',
                'label' => $this->l('DSC live username'),
                'tab' => 'dsc',
                'required' => false
            ),
            array(
                'type' => 'password',
                'name' => 'GLOBINARYAWB_DSC_LIVE_PASS',
                'label' => $this->l('DSC live password'),
                'tab' => 'dsc',
                'required' => false
            ),
            array(
                'type' => 'text',
                'name' => 'GLOBINARYAWB_DSC_TEST_USER',
                'label' => $this->l('DSC test username'),
                'tab' => 'dsc',
                'required' => false
            ),
            array(
                'type' => 'password',
                'name' => 'GLOBINARYAWB_DSC_TEST_PASS',
                'label' => $this->l('DSC test password'),
                'tab' => 'dsc',
                'required' => false
            ),
            array(
                'type' => 'text',
                'name' => 'GLOBINARYAWB_DPD_PICKUP_START',
                'label' => $this->l('Ora start ridicare curier'),
                'tab' => 'general',
                'desc' => $this->l('Format HH:MM, ex. 15:00.'),
                'required' => false
            ),
            array(
                'type' => 'text',
                'name' => 'GLOBINARYAWB_DPD_PICKUP_END',
                'label' => $this->l('Ora final ridicare curier'),
                'tab' => 'general',
                'desc' => $this->l('Format HH:MM, ex. 15:30.'),
                'required' => false
            ),
            array(
                'type' => 'switch',
                'label' => $this->l('Notificări email către client'),
                'name' => 'GLOBINARYAWB_EMAIL_NOTIF',
                'tab' => 'general',
                'is_bool' => true,
                'desc' => $this->l('Trimite email când statusul AWB este actualizat.'),
                'values' => array(
                    array(
                        'id' => 'email_on',
                        'value' => true,
                        'label' => $this->l('Activ')
                    ),
                    array(
                        'id' => 'email_off',
                        'value' => false,
                        'label' => $this->l('Dezactivat')
                    )
                ),
            ),
            array(
                'type' => 'switch',
                'label' => $this->l('Emitere automată factură SmartBill după AWB'),
                'name' => 'GLOBINARYAWB_SMARTBILL_AUTO',
                'tab' => 'general',
                'is_bool' => true,
                'desc' => $this->l('Această setare este valabilă doar dacă aveți instalat modulul oficial SmartBill.'),
                'values' => array(
                    array(
                        'id' => 'smartbill_on',
                        'value' => true,
                        'label' => $this->l('Activ')
                    ),
                    array(
                        'id' => 'smartbill_off',
                        'value' => false,
                        'label' => $this->l('Dezactivat')
                    )
                ),
            ),
            array(
                'type' => 'select',
                'label' => $this->l('Setare status comandă după emitere AWB'),
                'name' => 'GLOBINARYAWB_ISSUE_ORDER_STATE',
                'tab' => 'general',
                'options' => array(
                    'query' => $this->getIssueOrderStateOptions(),
                    'id' => 'id_order_state',
                    'name' => 'name',
                ),
                'desc' => $this->l('Dacă este setat, statusul comenzii se schimbă imediat după emiterea AWB.'),
            ),
        );

        $orderStates = $this->getOrderStateOptions();
        foreach ($this->getDpdStatusCodes() as $code => $label) {
            $inputs[] = array(
                'type' => 'select',
                'label' => $this->l('DPD ') . $code . ' - ' . $this->l($label),
                'name' => $this->getDpdStatusConfigKey($code),
                'options' => array(
                    'query' => $orderStates,
                    'id' => 'id_order_state',
                    'name' => 'name',
                ),
                'desc' => $this->l('Alege statusul PrestaShop pentru acest cod DPD (opțional).'),
                'tab' => 'dpd'
            );
        }

        foreach ($this->getSamedayStatusCodes() as $code => $label) {
            $inputs[] = array(
                'type' => 'select',
                'label' => 'Sameday ' . $code . ' - ' . $label,
                'name' => $this->getSamedayStatusConfigKey($code),
                'options' => array(
                    'query' => $orderStates,
                    'id' => 'id_order_state',
                    'name' => 'name',
                ),
                'desc' => $this->l('Alege statusul PrestaShop pentru acest status Sameday (opțional).'),
                'tab' => 'sameday'
            );
        }

        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Setări'),
                    'icon' => 'icon-cogs',
                ),
                'tabs' => array(
                    'general' => $this->l('General'),
                    'dpd' => $this->l('DPD'),
                    'sameday' => $this->l('Sameday'),
                    'dsc' => $this->l('DSC'),
                ),
                'input' => $inputs,
                'submit' => array(
                    'title' => $this->l('Salvează'),
                ),
            ),
        );
    }

    protected function getConfigFormValues()
    {
        $values = array(
            'GLOBINARYAWB_LIVE_MODE' => Configuration::get('GLOBINARYAWB_LIVE_MODE', false),
            'GLOBINARYAWB_DPD_LIVE_USER' => Configuration::get('GLOBINARYAWB_DPD_LIVE_USER', ''),
            'GLOBINARYAWB_DPD_LIVE_PASS' => Configuration::get('GLOBINARYAWB_DPD_LIVE_PASS', ''),
            'GLOBINARYAWB_DPD_TEST_USER' => Configuration::get('GLOBINARYAWB_DPD_TEST_USER', ''),
            'GLOBINARYAWB_DPD_TEST_PASS' => Configuration::get('GLOBINARYAWB_DPD_TEST_PASS', ''),
            'GLOBINARYAWB_SAMEDAY_LIVE_USER' => Configuration::get('GLOBINARYAWB_SAMEDAY_LIVE_USER', ''),
            'GLOBINARYAWB_SAMEDAY_LIVE_PASS' => Configuration::get('GLOBINARYAWB_SAMEDAY_LIVE_PASS', ''),
            'GLOBINARYAWB_SAMEDAY_TEST_USER' => Configuration::get('GLOBINARYAWB_SAMEDAY_TEST_USER', ''),
            'GLOBINARYAWB_SAMEDAY_TEST_PASS' => Configuration::get('GLOBINARYAWB_SAMEDAY_TEST_PASS', ''),
            'GLOBINARYAWB_SAMEDAY_SERVICE_ID' => Configuration::get('GLOBINARYAWB_SAMEDAY_SERVICE_ID', '7'),
            'GLOBINARYAWB_SAMEDAY_PICKUP_POINT_ID' => Configuration::get('GLOBINARYAWB_SAMEDAY_PICKUP_POINT_ID', ''),
            'GLOBINARYAWB_SAMEDAY_CONTACT_PERSON_ID' => Configuration::get('GLOBINARYAWB_SAMEDAY_CONTACT_PERSON_ID', ''),
            'GLOBINARYAWB_SAMEDAY_RETURN_LOCATION_ID' => Configuration::get('GLOBINARYAWB_SAMEDAY_RETURN_LOCATION_ID', ''),
            'GLOBINARYAWB_DSC_LIVE_USER' => Configuration::get('GLOBINARYAWB_DSC_LIVE_USER', ''),
            'GLOBINARYAWB_DSC_LIVE_PASS' => Configuration::get('GLOBINARYAWB_DSC_LIVE_PASS', ''),
            'GLOBINARYAWB_DSC_TEST_USER' => Configuration::get('GLOBINARYAWB_DSC_TEST_USER', ''),
            'GLOBINARYAWB_DSC_TEST_PASS' => Configuration::get('GLOBINARYAWB_DSC_TEST_PASS', ''),
            'GLOBINARYAWB_DPD_PICKUP_START' => Configuration::get('GLOBINARYAWB_DPD_PICKUP_START', '15:00'),
            'GLOBINARYAWB_DPD_PICKUP_END' => Configuration::get('GLOBINARYAWB_DPD_PICKUP_END', '15:30'),
            'GLOBINARYAWB_EMAIL_NOTIF' => Configuration::get('GLOBINARYAWB_EMAIL_NOTIF', false),
            'GLOBINARYAWB_ISSUE_ORDER_STATE' => Configuration::get('GLOBINARYAWB_ISSUE_ORDER_STATE', 0),
            'GLOBINARYAWB_SMARTBILL_AUTO' => Configuration::get('GLOBINARYAWB_SMARTBILL_AUTO', false),
        );

        foreach ($this->getDpdStatusCodes() as $code => $label) {
            $values[$this->getDpdStatusConfigKey($code)] = Configuration::get($this->getDpdStatusConfigKey($code), '');
        }
        foreach ($this->getSamedayStatusCodes() as $code => $label) {
            $values[$this->getSamedayStatusConfigKey($code)] = Configuration::get($this->getSamedayStatusConfigKey($code), '');
        }

        return $values;
    }

    public function hookDisplayOrderDetail($params)
    {
        $order = $params['order'];
        $trackingEntries = array();

        if ($order && isset($order->id)) {
            $awbRows = $this->getAwbRowsByOrderId((int)$order->id);
            foreach ($awbRows as $row) {
                if (!empty($row['awb_number'])) {
                    $trackingEntries[] = array(
                        'awb_number' => $row['awb_number'],
                        'awb_status' => $row['current_status'],
                        'tracking_url' => $this->buildTrackingUrl((string)($row['backup_field_six'] ?? 'DPD'), (string)$row['awb_number']),
                    );
                }
            }
        }

        $this->context->smarty->assign(array(
            'tracking_entries' => $trackingEntries,
        ));

        return $this->display(__FILE__, 'views/templates/hook/order_detail.tpl');
    }

    public function hookDisplayAdminOrderTabLink($params)
    {
        $orderId = (int)$params['id_order'];
        $awbNumber = $this->getFirstAwbNumberByOrderId($orderId);

        $this->context->smarty->assign(array(
            'awb_number' => $awbNumber,
        ));

        return $this->display(__FILE__, 'views/templates/hook/admin_order_tab.tpl');
    }

    public function hookDisplayAdminOrderSide($params)
    {
        $orderId = (int)($params['id_order'] ?? Tools::getValue('id_order'));
        $awbCount = (int)Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'globinary_awb_tracking` WHERE `order_id` = ' . (int)$orderId);
        $this->context->smarty->assign(array(
            'order_id' => $orderId,
            'awb_count' => $awbCount,
        ));

        return $this->display(__FILE__, 'views/templates/hook/admin_order_side.tpl');
    }

public function hookDisplayAdminOrderTabContent($params)
    {
        $orderId = (int)($params['id_order'] ?? Tools::getValue('id_order'));
        $db = Db::getInstance();

        $awbList = $db->executeS('
            SELECT * FROM `' . _DB_PREFIX_ . 'globinary_awb_tracking`
            WHERE `order_id` = ' . (int)$orderId . ' ORDER BY `awb_date_added` ASC
        ');

        $awbData = array();
        if ($awbList) {
            foreach ($awbList as $awb) {
                $parcelNumber = '';
                $awbNumber = $awb['awb_number'];
                if (!empty($awb['backup_field_three']) && strpos($awb['backup_field_three'], '(') !== false) {
                    $awbNumber = substr($awb['backup_field_three'], strpos($awb['backup_field_three'], '(') + 1, -1);
                    $parcelNumber = $awb['awb_number'];
                }

                $awbData[] = array(
                    'awb_number' => $awbNumber,
                    'awb_date_added' => isset($awb['awb_date_added']) ? date('d.m.Y H:i', strtotime($awb['awb_date_added'])) : null,
                    'current_status' => $awb['current_status'] ?? '-',
                    'operation_code' => $awb['operation_code'] ?? '-',
                    'last_status_change' => isset($awb['last_status_change']) ? date('d.m.Y H:i', strtotime($awb['last_status_change'])) : '-',
                    'tracking_url' => $this->buildTrackingUrl((string)($awb['backup_field_six'] ?? 'DPD'), (string)$awbNumber),
                    'parcel_number' => $parcelNumber,
                    'courier_code' => $awb['backup_field_six'] ?? 'DPD',
                );
            }
        }

        $order = new Order($orderId);
        $products = array();
        $deliveryAddress = null;
        $customer = null;
        $country = null;
        $rambursValue = 0.0;
        $carrierName = '';
        $carrierNames = array();

        if (Validate::isLoadedObject($order)) {
            $orderProducts = $order->getProducts();
            $customer = new Customer($order->id_customer);
            $deliveryAddress = new Address($order->id_address_delivery);
            if (Validate::isLoadedObject($deliveryAddress) && $deliveryAddress->id_country) {
                $country = new Country((int)$deliveryAddress->id_country);
            }

            if ($order->module === 'ps_cashondelivery') {
                $rambursValue = (float)$order->total_paid;
                $rambursValue = number_format($rambursValue, 2, '.', '');
            }

            if (is_array($orderProducts)) {
                foreach ($orderProducts as $product) {
                    $products[] = array(
                        'name' => $product['product_name'] ?? 'Unknown product',
                        'reference' => $product['reference'] ?? '',
                        'quantity' => (int)($product['product_quantity'] ?? 1),
                        'weight' => (float)($product['weight'] ?? 0),
                        'height' => (float)($product['height'] ?? 0),
                        'width' => (float)($product['width'] ?? 0),
                        'depth' => (float)($product['depth'] ?? 0),
                    );
                }
            }

            $shippingRows = $order->getShipping();
            if (!empty($shippingRows)) {
                foreach ($shippingRows as $row) {
                    if (!empty($row['carrier_name'])) {
                        $name = (string)$row['carrier_name'];
                        if (!in_array($name, $carrierNames, true)) {
                            $carrierNames[] = $name;
                        }
                    }
                }
            }

            if (empty($carrierNames) && (int)$order->id_carrier) {
                $carrier = new Carrier((int)$order->id_carrier, (int)$this->context->language->id);
                if (Validate::isLoadedObject($carrier) && !empty($carrier->name)) {
                    $carrierNames[] = (string)$carrier->name;
                }
            }

            $carrierName = implode(', ', $carrierNames);
        }

        $stateName = '';
        if (isset($deliveryAddress->id_state) && $deliveryAddress->id_state) {
            $state = new State((int)$deliveryAddress->id_state);
            if (Validate::isLoadedObject($state)) {
                $stateName = (string)$state->name;
            }
        }

        $cityName = '';
        if (!empty($deliveryAddress->city)) {
            $cityName = (string)$deliveryAddress->city;
        }

        $maxHeight = 0;
        $maxWidth = 0;
        $maxDepth = 0;
        $totalWeight = 0;

        foreach ($products as $product) {
            $weight = (float)($product['weight'] ?? 0);
            $quantity = (int)($product['quantity'] ?? 1);
            $height = (float)($product['height'] ?? 15);
            $width = (float)($product['width'] ?? 15);
            $depth = (float)($product['depth'] ?? 15);

            $totalWeight += ($weight * $quantity);
            $maxHeight = max($maxHeight, $height);
            $maxWidth = max($maxWidth, $width);
            $maxDepth = max($maxDepth, $depth);
        }

        $totalWeight = max($totalWeight, 1);
        $maxHeight = max($maxHeight, 15);
        $maxWidth = max($maxWidth, 15);
        $maxDepth = max($maxDepth, 15);

        $this->context->smarty->assign(array(
            'order_id' => $orderId,
            'awb_list' => $awbData,
            'products' => $products,
            'customer' => $customer,
            'address' => $deliveryAddress,
            'state_name' => $stateName,
            'city_name' => $cityName,
            'total_weight' => $totalWeight,
            'max_height' => $maxHeight,
            'max_width' => $maxWidth,
            'max_depth' => $maxDepth,
            'country' => $country ?: null,
            'ramburs_value' => $rambursValue ?? null,
            'carrier_name' => $carrierName,
            'carrier_names' => $carrierNames,
            'smartbill_auto' => (bool)Configuration::get('GLOBINARYAWB_SMARTBILL_AUTO', false),
        ));

        return $this->display(__FILE__, 'views/templates/hook/admin_order_content.tpl');
    }

    
    public function processIssueAwb()
    {
        // Mapping is optional; only used for auto order state updates.

        $orderId = (int)Tools::getValue('id_order');
        if (!$orderId) {
            return json_encode(['success' => false, 'message' => $this->l('ID comandă lipsă.')]);
        }

        $issueButtonIs = Tools::getValue('issueButtonIs', 'dpd');
        if (!in_array($issueButtonIs, array('dpd', 'sameday', 'dsc'), true)) {
            return json_encode(['success' => false, 'message' => $this->l('Curierul selectat nu este disponibil încă.')]);
        }

        $dpdClientName = (string)Tools::getValue('dpd_client_name');
        $dpdContactName = '';
        if (strpos($dpdClientName, '(') !== false && strpos($dpdClientName, ')') !== false) {
            preg_match('/\((.*?)\)/', $dpdClientName, $m);
            if (!empty($m[1])) {
                $dpdContactName = trim($m[1]);
                $dpdClientName = trim(str_replace('(' . $dpdContactName . ')', '', $dpdClientName));
            }
        }
        if ($dpdClientName === '') {
            return json_encode(['success' => false, 'message' => $this->l('Nume client invalid.')]);
        }

        $parcelsCount = (int)Tools::getValue('dpd_parcels_count', 1);
        $extraPackages = $this->normalizeExtraPackages(Tools::getValue('extra_packages'), $parcelsCount);

        $data = array(
            'orderId' => $orderId,
            'dpd_service_id' => (int)Tools::getValue('dpd_service_id'),
            'dpd_parcels_count' => $parcelsCount,
            'dpd_total_weight' => (float)Tools::getValue('dpd_total_weight', 1),
            'dpd_length' => (float)Tools::getValue('dpd_length', 10),
            'dpd_width' => (float)Tools::getValue('dpd_width', 10),
            'dpd_height' => (float)Tools::getValue('dpd_height', 10),
            'dpd_private_person' => (bool)Tools::getValue('dpd_private_person', false),
            'dpd_saturday_delivery' => (bool)Tools::getValue('dpd_saturday_delivery', false),
            'dpd_insurance' => (bool)Tools::getValue('dpd_insurance', false),
            'dpd_fragile' => (bool)Tools::getValue('dpd_fragile', false),
            'dpd_open_package' => (bool)Tools::getValue('dpd_open_package', false),
            'dsc_urgency' => (bool)Tools::getValue('dsc_urgency', false),
            'dsc_sms' => (bool)Tools::getValue('dsc_sms', false),
            'dpd_courier_payer' => Tools::getValue('dpd_courier_payer', 'SENDER'),
            'dpd_client_name' => $dpdClientName,
            'dpd_contact_name' => $dpdContactName,
            'dpd_phone' => Tools::getValue('dpd_phone'),
            'dpd_email' => Tools::getValue('dpd_email'),
            'dpd_city' => Tools::getValue('dpd_city'),
            'dpd_county' => Tools::getValue('dpd_county'),
            'dpd_street_address' => Tools::getValue('dpd_street_address'),
            'dpd_ramburs_value' => (float)Tools::getValue('dpd_ramburs', 0),
            'dpd_shipment_note' => Tools::getValue('dpd_shipment_note'),
            'extra_packages' => $extraPackages,
        );

        $validation = $this->validateDpdRequest($data);
        if (!$validation['success']) {
            return json_encode(['success' => false, 'message' => $validation['message']]);
        }

        if ($issueButtonIs === 'sameday') {
            $samedayCredentials = $this->getSamedayCredentials();
            if (!$samedayCredentials['success']) {
                return json_encode(['success' => false, 'message' => $samedayCredentials['message']]);
            }

            $payloadRes = $this->prepareSamedayPayload($validation['data'], false);
            if (!$payloadRes['success']) {
                return json_encode(['success' => false, 'message' => $payloadRes['message']]);
            }

            $response = $this->requestSamedayApi('POST', '/api/awb', $payloadRes['payload']);
            if (!$response['success']) {
                return json_encode(['success' => false, 'message' => $response['message']]);
            }

            $responseJson = $response['data'];
            $mainAwb = $responseJson['awbNumber'] ?? '';
            if ($mainAwb === '') {
                return json_encode(['success' => false, 'message' => $this->l('AWB Sameday nu a fost emis.')]);
            }

            $parcelNumbers = array();
            if (!empty($responseJson['parcels']) && is_array($responseJson['parcels'])) {
                foreach ($responseJson['parcels'] as $parcel) {
                    if (!empty($parcel['awbNumber'])) {
                        $candidate = (string)$parcel['awbNumber'];
                        if ($candidate !== '' && $candidate !== (string)$mainAwb) {
                            $parcelNumbers[] = $candidate;
                        }
                    }
                }
            }
            $parcelNumbers = array_values(array_unique($parcelNumbers));
            // For single-parcel shipments, keep a single AWB row in BO.
            if ((int)$validation['data']['dpd_parcels_count'] <= 1) {
                $parcelNumbers = array();
            }

            $db = Db::getInstance();
            $dateNow = date('Y-m-d H:i:s');
            $this->insertAwbGroup(
                $db,
                $orderId,
                $mainAwb,
                $parcelNumbers,
                $this->l('AWB Sameday emis.'),
                'SMD',
                $samedayCredentials['live_mode'] ? 'true' : 'false',
                $validation['data']['dpd_county'],
                $validation['data']['dpd_city'],
                'SAMEDAY',
                $dateNow
            );

            $orderCarrierId = $db->getValue('
                SELECT `id_order_carrier`
                FROM `' . _DB_PREFIX_ . 'order_carrier`
                WHERE `id_order` = ' . (int)$orderId . '
                ORDER BY `id_order_carrier` DESC
            ');
            if ($orderCarrierId) {
                $db->update(
                    'order_carrier',
                    array('tracking_number' => pSQL($mainAwb)),
                    '`id_order_carrier` = ' . (int)$orderCarrierId
                );
            }
            $this->updateOrderStatusAfterIssue($orderId);

            return json_encode(array(
                'success' => true,
                'message' => $this->l('AWB Sameday emis cu succes.'),
                'awb_numbers' => array($mainAwb),
            ));
        }

        if ($issueButtonIs === 'dsc') {
            $dscCredentials = $this->getDscCredentials();
            if (!$dscCredentials['success']) {
                return json_encode(['success' => false, 'message' => $dscCredentials['message']]);
            }

            $dscCity = (string)$validation['data']['dpd_city'];
            $dscCounty = (string)$validation['data']['dpd_county'];
            if (ctype_digit($dscCity)) {
                $dpdSite = $this->getDpdSiteById((int)$dscCity);
                if ($dpdSite) {
                    $dscCity = (string)($dpdSite['name'] ?? $dscCity);
                    $dscCounty = (string)($dpdSite['region'] ?? $dscCounty);
                }
            }

            $destPays = strtoupper((string)$validation['data']['dpd_courier_payer']) === 'RECIPIENT' ? 1 : 0;
            $totalWeight = (float)$validation['data']['dpd_total_weight'];
            $extraPkgs = $validation['data']['extra_packages'] ?? array();
            if (!empty($extraPkgs)) {
                $totalWeight = 0.0;
                $parcels = $this->buildDpdParcels($extraPkgs, $validation['data']);
                foreach ($parcels as $parcel) {
                    $totalWeight += (float)$parcel['weight'];
                }
                if ($totalWeight <= 0) {
                    $totalWeight = (float)$validation['data']['dpd_total_weight'];
                }
            }

            $payload = array(
                'judet' => $dscCounty,
                'localitate' => $dscCity,
                'destinatar' => (string)$validation['data']['dpd_client_name'],
                'contact' => (string)$validation['data']['dpd_contact_name'],
                'telefon' => (string)$validation['data']['dpd_phone'],
                'adresa' => (string)$validation['data']['dpd_street_address'],
                'tipExpeditie' => 'colet',
                'nrColete' => (int)$validation['data']['dpd_parcels_count'],
                'greutate' => round($totalWeight, 3),
                'platitorEsteDestinatarul' => $destPays,
                'valoareRamburs' => (float)$validation['data']['dpd_ramburs_value'],
                'tipPlata' => 'cash',
                'valoareAsigurare' => $validation['data']['dpd_insurance'] ? (float)$validation['data']['dpd_ramburs_value'] : 0,
                'tarifUrgenta' => $validation['data']['dsc_urgency'] ? 1 : 0,
                'tarifSambata' => $validation['data']['dpd_saturday_delivery'] ? 1 : 0,
                'livrareSediu' => 0,
                'observatii' => (string)$validation['data']['dpd_shipment_note'],
                'deschidereColet' => $validation['data']['dpd_open_package'] ? 1 : 0,
                'sms' => $validation['data']['dsc_sms'] ? 1 : 0,
            );

            $responseDsc = $this->requestDscApi('POST', 'https://app.curierdragonstar.ro/awb/send', $payload, $dscCredentials);
            if (!$responseDsc['success']) {
                return json_encode([
                    'success' => false,
                    'message' => $this->l('Eroare emitere AWB DSC: ') . $responseDsc['message'],
                    'dsc_debug' => $responseDsc['debug'] ?? [],
                ]);
            }

            $dscJson = $responseDsc['data'] ?? array();
            $mainAwb = (string)($dscJson['awb'] ?? '');
            if ($mainAwb === '') {
                return json_encode([
                    'success' => false,
                    'message' => $this->l('AWB DSC nu a fost emis.'),
                    'dsc_debug' => $responseDsc['debug'] ?? [],
                ]);
            }

            $db = Db::getInstance();
            $dateNow = date('Y-m-d H:i:s');
            $this->insertAwbGroup(
                $db,
                $orderId,
                $mainAwb,
                array(),
                $this->l('AWB DSC emis.'),
                'DSCINIT',
                $dscCredentials['live_mode'] ? 'true' : 'false',
                $dscCounty,
                $dscCity,
                'DSC',
                $dateNow
            );

            $orderCarrierId = $db->getValue('
                SELECT `id_order_carrier`
                FROM `' . _DB_PREFIX_ . 'order_carrier`
                WHERE `id_order` = ' . (int)$orderId . '
                ORDER BY `id_order_carrier` DESC
            ');
            if ($orderCarrierId) {
                $db->update(
                    'order_carrier',
                    array('tracking_number' => pSQL($mainAwb)),
                    '`id_order_carrier` = ' . (int)$orderCarrierId
                );
            }

            $this->updateOrderStatusAfterIssue($orderId);

            return json_encode(array(
                'success' => true,
                'message' => $this->l('AWB DSC emis cu succes.'),
                'awb_numbers' => array($mainAwb),
            ));
        }

        $credentials = $this->getDpdCredentials();
        if (!$credentials['success']) {
            return json_encode(['success' => false, 'message' => $credentials['message']]);
        }

        $siteId = $this->resolveSiteId($validation['data']['dpd_city'], $validation['data']['dpd_county']);
        if (!$siteId) {
            return json_encode(['success' => false, 'message' => $this->l('Localitatea nu a fost găsită în baza DPD.')]);
        }

        $parcels = $this->buildDpdParcels($validation['data']['extra_packages'], $validation['data']);
        $totalWeight = 0.0;
        foreach ($parcels as $parcel) {
            $totalWeight += (float)$parcel['weight'];
        }

        $payload = $this->prepareDpdPayload($validation['data'], $siteId, $credentials, false);
        $payload['content']['parcels'] = $parcels;
        $payload['content']['parcelsCount'] = count($parcels);
        $payload['content']['totalWeight'] = round($totalWeight, 3);

        $response = $this->requestDpdApi('POST', 'https://api.dpd.ro/v1/shipment', $payload);
        if (!$response['success']) {
            return json_encode(['success' => false, 'message' => $response['message']]);
        }

        $responseJson = $response['data'];
        if (isset($responseJson['error'])) {
            if ($this->isDpdLocalityError($responseJson)) {
                $altCity = $this->toggleHyphenInCity($validation['data']['dpd_city']);
                if ($altCity !== $validation['data']['dpd_city']) {
                    $altSiteId = $this->resolveSiteId($altCity, $validation['data']['dpd_county']);
                    if ($altSiteId && $altSiteId !== $siteId) {
                        $payload = $this->prepareDpdPayload($validation['data'], $altSiteId, $credentials, false);
                        $payload['content']['parcels'] = $parcels;
                        $payload['content']['parcelsCount'] = count($parcels);
                        $payload['content']['totalWeight'] = round($totalWeight, 3);

                        $retry = $this->requestDpdApi('POST', 'https://api.dpd.ro/v1/shipment', $payload);
                        if ($retry['success']) {
                            $responseJson = $retry['data'];
                        }
                    }
                }
            }

            if (isset($responseJson['error'])) {
                $rawError = (string)($responseJson['error']['message'] ?? '');
                $friendly = $this->mapDpdIssueErrorMessage($rawError);
                return json_encode(['success' => false, 'message' => $friendly !== '' ? $friendly : ($rawError !== '' ? $rawError : $this->l('Eroare emitere AWB.'))]);
            }
        }

        $shipmentId = $responseJson['id'] ?? '';
        if ($shipmentId === '') {
            return json_encode(['success' => false, 'message' => $this->l('AWB nu a fost emis.')]);
        }

        $parcelIds = array();
        if (!empty($responseJson['parcels']) && is_array($responseJson['parcels'])) {
            foreach ($responseJson['parcels'] as $parcel) {
                if (!empty($parcel['id'])) {
                    $parcelIds[] = $parcel['id'];
                }
            }
        }

        $db = Db::getInstance();
        $dateNow = date('Y-m-d H:i:s');
        $operationCode = $responseJson['operationCode'] ?? '148';
        $this->insertAwbGroup(
            $db,
            $orderId,
            $shipmentId,
            $parcelIds,
            $responseJson['status'] ?? $this->l('Expedierea ta a fost înregistrată de către expeditor.'),
            $operationCode,
            $credentials['live_mode'] ? 'true' : 'false',
            $validation['data']['dpd_county'],
            $validation['data']['dpd_city'],
            'DPD',
            $dateNow
        );

        $orderCarrierId = $db->getValue('
            SELECT `id_order_carrier`
            FROM `' . _DB_PREFIX_ . 'order_carrier`
            WHERE `id_order` = ' . (int)$orderId . '
            ORDER BY `id_order_carrier` DESC
        ');
        if ($orderCarrierId) {
            $db->update(
                'order_carrier',
                array('tracking_number' => pSQL($shipmentId)),
                '`id_order_carrier` = ' . (int)$orderCarrierId
            );
        }

        $this->updateOrderStatusAfterIssue($orderId);
        $this->updatePrestaShopOrderStatus($orderId, $operationCode, array($shipmentId));

        return json_encode(array(
            'success' => true,
            'message' => $this->l('AWB emis cu succes.'),
            'awb_numbers' => array($shipmentId),
        ));
    }
    

    public function deleteAwb()
    {
        $orderId = (int)Tools::getValue('id_order');
        $awbNumber = trim((string)Tools::getValue('awb_number'));

        if (!$orderId || $awbNumber === '') {
            return json_encode(['success' => false, 'message' => $this->l('Parametri invalizi.')]);
        }

        $db = Db::getInstance();
        $row = $db->getRow('
            SELECT `order_id`, `awb_number`, `backup_field_six`
            FROM `' . _DB_PREFIX_ . 'globinary_awb_tracking`
            WHERE `order_id` = ' . (int)$orderId . '
              AND (`awb_number` = "' . pSQL($awbNumber) . '" OR `backup_field_three` LIKE "%(' . pSQL($awbNumber) . ')%")
            ORDER BY `id_globinary_awb_tracking` DESC
        ');

        if (!$row) {
            return json_encode(['success' => false, 'message' => $this->l('AWB-ul nu a fost găsit.')]);
        }

        $mainAwb = (string)$row['awb_number'];
        $courier = strtoupper((string)($row['backup_field_six'] ?? 'DPD'));

        if ($courier === 'SAMEDAY') {
            $resp = $this->requestSamedayApi('DELETE', '/api/awb/' . urlencode($mainAwb));
            if (!$resp['success']) {
                return json_encode(['success' => false, 'message' => $resp['message']]);
            }
        } elseif ($courier === 'DSC') {
            $credentials = $this->getDscCredentials();
            if (!$credentials['success']) {
                return json_encode(['success' => false, 'message' => $credentials['message']]);
            }

            $resp = $this->requestDscApi('DELETE', 'https://app.curierdragonstar.ro/awb/' . urlencode($mainAwb), null, $credentials);
            if (!$resp['success']) {
                return json_encode([
                    'success' => false,
                    'message' => $resp['message'],
                ]);
            }

            $json = $resp['data'] ?? array();
            $ok = false;
            if (is_array($json)) {
                if (isset($json['success']) && $json['success']) {
                    $ok = true;
                }
                if (!$ok && isset($json['message']) && trim((string)$json['message']) !== '') {
                    $msgLower = Tools::strtolower((string)$json['message']);
                    if (strpos($msgLower, 'sters') !== false || strpos($msgLower, 'sucs') !== false || strpos($msgLower, 'deleted') !== false) {
                        $ok = true;
                    }
                }
            }
            if (!$ok) {
                return json_encode([
                    'success' => false,
                    'message' => !empty($json['message']) ? (string)$json['message'] : $this->l('Eroare ștergere AWB DSC.'),
                ]);
            }
        } else {
            $credentials = $this->getDpdCredentials();
            if (!$credentials['success']) {
                return json_encode(['success' => false, 'message' => $credentials['message']]);
            }

            $payload = array(
                'userName' => $credentials['user'],
                'password' => $credentials['pass'],
                'shipmentId' => $mainAwb,
                'comment' => 'Deleted by user',
            );

            $resp = $this->requestDpdApi('DELETE', 'https://api.dpd.ro/v1/shipment', $payload);
            if (!$resp['success']) {
                return json_encode(['success' => false, 'message' => $resp['message']]);
            }
            if (!empty($resp['data']['error']['message'])) {
                return json_encode(['success' => false, 'message' => $resp['data']['error']['message']]);
            }
        }

        $deleted = $db->delete(
            'globinary_awb_tracking',
            '`order_id` = ' . (int)$orderId . ' AND `awb_number` = "' . pSQL($mainAwb) . '"'
        );

        if (!$deleted) {
            return json_encode(['success' => false, 'message' => $this->l('A apărut o eroare. Vă rog încercați din nou.')]);
        }

        $orderCarrierId = $db->getValue('
            SELECT `id_order_carrier`
            FROM `' . _DB_PREFIX_ . 'order_carrier`
            WHERE `id_order` = ' . (int)$orderId . '
            ORDER BY `id_order_carrier` DESC
        ');
        if ($orderCarrierId) {
            $db->update('order_carrier', array('tracking_number' => ''), '`id_order_carrier` = ' . (int)$orderCarrierId);
        }

        return json_encode(['success' => true, 'message' => $this->l('AWB șters cu succes.')]);
    }

    public function getRightSiteId()
    {
        $dpdCity = (string)Tools::getValue('dpd_city');
        $dpdCounty = (string)Tools::getValue('dpd_county');

        if ($dpdCity === '' || $dpdCounty === '') {
            return json_encode(['success' => false, 'message' => $this->l('City and county are required.')]);
        }

        $db = Db::getInstance();
        $countyRows = $db->executeS('SELECT DISTINCT `region` FROM `' . _DB_PREFIX_ . 'globinary_awb_dpd_sites` ORDER BY `region` ASC');
        $countyList = array();
        foreach ($countyRows as $row) {
            if (!empty($row['region'])) {
                $countyList[] = $row['region'];
            }
        }

        $selectedCounty = '';
        $normTarget = $this->normalizeDpdCityForMatch($dpdCounty);
        foreach ($countyList as $county) {
            if ($this->normalizeDpdCityForMatch($county) === $normTarget) {
                $selectedCounty = $county;
                break;
            }
        }
        if ($selectedCounty === '' && !empty($countyList)) {
            $selectedCounty = $countyList[0];
        }

        $cityRows = $db->executeS('SELECT `id`, `name`, `municipality` FROM `' . _DB_PREFIX_ . 'globinary_awb_dpd_sites` WHERE `region` = "' . pSQL($selectedCounty) . '" ORDER BY `name` ASC');
        $cityList = array();
        foreach ($cityRows as $row) {
            $cityList[] = array(
                'site_id' => (int)$row['id'],
                'site_name' => $row['name'],
                'site_name_display' => $this->formatDpdSiteDisplayName($row),
                'municipality' => $row['municipality'] ?? '',
            );
        }

        $selectedCityDisplay = '';
        $selectedCityId = null;
        $mapped = false;

        if (is_numeric($dpdCity)) {
            $site = $this->getDpdSiteById((int)$dpdCity);
            if ($site) {
                $selectedCityDisplay = $this->formatDpdSiteDisplayName($site);
                $selectedCityId = (int)$site['id'];
                $mapped = true;
            }
        } else {
            $target = $this->normalizeDpdCityForMatch($dpdCity);
            foreach ($cityList as $city) {
                $candNorm = $this->normalizeDpdCityForMatch($city['site_name']);
                if ($candNorm !== '' && $candNorm === $target) {
                    $selectedCityDisplay = $city['site_name_display'];
                    $selectedCityId = (int)$city['site_id'];
                    $mapped = true;
                    break;
                }
            }
            if (!$mapped) {
                foreach ($cityList as $city) {
                    $candNorm = $this->normalizeDpdCityForMatch($city['site_name_display']);
                    if ($candNorm !== '' && $candNorm === $target) {
                        $selectedCityDisplay = $city['site_name_display'];
                        $selectedCityId = (int)$city['site_id'];
                        $mapped = true;
                        break;
                    }
                }
            }
        }

        if (!$mapped) {
            $selectedCityDisplay = '';
            $selectedCityId = null;
        }

        return json_encode([
            'success' => true,
            'site_id' => $selectedCityId,
            'selected_city_id' => $selectedCityId,
            'selected_city' => $selectedCityDisplay,
            'selected_county' => $selectedCounty,
            'county_list' => $countyList,
            'city_list' => $cityList,
            'mapped_city' => $mapped,
            'message' => $mapped ? '' : $this->l('Localitatea nu a fost mapată automat. Te rugăm selectează manual.'),
        ]);
    }


    public function getAllCities()
    {
        $dpdCounty = (string)Tools::getValue('county');
        if ($dpdCounty === '') {
            return json_encode(['success' => false, 'message' => $this->l('County is required.'), 'cities' => []]);
        }

        $db = Db::getInstance();
        $rows = $db->executeS('SELECT `id`, `name`, `municipality` FROM `' . _DB_PREFIX_ . 'globinary_awb_dpd_sites` WHERE `region` = "' . pSQL($dpdCounty) . '" ORDER BY `name` ASC');
        $cities = array();
        foreach ($rows as $row) {
            $cities[] = array(
                'site_id' => (int)$row['id'],
                'site_name' => $this->formatDpdSiteDisplayName($row),
            );
        }

        return json_encode(['success' => true, 'cities' => $cities]);
    }

public function calculatePrice()
    {
        // Mapping is optional; only used for auto order state updates.

        $dpdCredentials = $this->getDpdCredentials();

        $dpdClientName = (string)Tools::getValue('dpd_client_name');
        $dpdContactName = '';
        if (strpos($dpdClientName, '(') !== false && strpos($dpdClientName, ')') !== false) {
            preg_match('/\((.*?)\)/', $dpdClientName, $m);
            if (!empty($m[1])) {
                $dpdContactName = trim($m[1]);
                $dpdClientName = trim(str_replace('(' . $dpdContactName . ')', '', $dpdClientName));
            }
        }

        $parcelsCount = (int)Tools::getValue('dpd_parcels_count', 1);
        $extraPackages = $this->normalizeExtraPackages(Tools::getValue('extra_packages'), $parcelsCount);

        $data = array(
            'orderId' => (int)Tools::getValue('id_order'),
            'dpd_service_id' => (int)Tools::getValue('dpd_service_id'),
            'dpd_parcels_count' => $parcelsCount,
            'dpd_total_weight' => (float)Tools::getValue('dpd_total_weight', 1),
            'dpd_length' => (float)Tools::getValue('dpd_length', 10),
            'dpd_width' => (float)Tools::getValue('dpd_width', 10),
            'dpd_height' => (float)Tools::getValue('dpd_height', 10),
            'dpd_private_person' => (bool)Tools::getValue('dpd_private_person', false),
            'dpd_saturday_delivery' => (bool)Tools::getValue('dpd_saturday_delivery', false),
            'dpd_insurance' => (bool)Tools::getValue('dpd_insurance', false),
            'dpd_fragile' => (bool)Tools::getValue('dpd_fragile', false),
            'dpd_open_package' => (bool)Tools::getValue('dpd_open_package', false),
            'dsc_urgency' => (bool)Tools::getValue('dsc_urgency', false),
            'dsc_sms' => (bool)Tools::getValue('dsc_sms', false),
            'dpd_courier_payer' => Tools::getValue('dpd_courier_payer', 'SENDER'),
            'dpd_client_name' => $dpdClientName,
            'dpd_contact_name' => $dpdContactName,
            'dpd_phone' => Tools::getValue('dpd_phone'),
            'dpd_email' => Tools::getValue('dpd_email'),
            'dpd_city' => Tools::getValue('dpd_city'),
            'dpd_county' => Tools::getValue('dpd_county'),
            'dpd_street_address' => Tools::getValue('dpd_street_address'),
            'dpd_ramburs_value' => (float)Tools::getValue('dpd_ramburs', 0),
            'dpd_shipment_note' => Tools::getValue('dpd_shipment_note'),
            'extra_packages' => $extraPackages,
        );

        $validation = $this->validateDpdRequest($data);
        if (!$validation['success']) {
            return json_encode(['success' => false, 'message' => $validation['message']]);
        }

        $dpdResponse = array('has_data' => false, 'cost' => 0, 'message' => $this->l('Credențialele DPD nu sunt configurate.'));
        if ($dpdCredentials['success']) {
            $siteId = $this->resolveSiteId($validation['data']['dpd_city'], $validation['data']['dpd_county']);
            if (!$siteId) {
                return json_encode(['success' => false, 'message' => $this->l('Localitatea nu a fost găsită în baza DPD.')]);
            }

            $payload = $this->prepareDpdPayload($validation['data'], $siteId, $dpdCredentials, true);
            $response = $this->requestDpdApi('POST', 'https://api.dpd.ro/v1/calculate', $payload);
            if ($response['success']) {
                $json = $response['data'];
                if (isset($json['error']) && $this->isDpdLocalityError($json)) {
                    $altCity = $this->toggleHyphenInCity($validation['data']['dpd_city']);
                    if ($altCity !== $validation['data']['dpd_city']) {
                        $altSiteId = $this->resolveSiteId($altCity, $validation['data']['dpd_county']);
                        if ($altSiteId && $altSiteId !== $siteId) {
                            $payload = $this->prepareDpdPayload($validation['data'], $altSiteId, $dpdCredentials, true);
                            $retry = $this->requestDpdApi('POST', 'https://api.dpd.ro/v1/calculate', $payload);
                            if ($retry['success']) {
                                $json = $retry['data'];
                            }
                        }
                    }
                }

                if (!empty($json['calculations'][0]['price'])) {
                    $price = $json['calculations'][0]['price'];
                    $dpdResponse = array(
                        'has_data' => true,
                        'cost' => $price['total'],
                        'message' => '(' . $price['amount'] . ' + TVA ' . $price['vat'] . ')',
                    );
                } elseif (!empty($json['calculations'][0]['error']['message'])) {
                    $dpdResponse = array(
                        'has_data' => false,
                        'cost' => 0,
                        'message' => $json['calculations'][0]['error']['message'],
                    );
                }
            } else {
                $dpdResponse = array('has_data' => false, 'cost' => 0, 'message' => $response['message']);
            }
        }

        $samedayResponse = array('has_data' => false, 'cost' => 0, 'message' => $this->l('Credențialele Sameday nu sunt configurate.'));
        $samedayCredentials = $this->getSamedayCredentials();
        if ($samedayCredentials['success']) {
            $payloadRes = $this->prepareSamedayPayload($validation['data'], true);
            if ($payloadRes['success']) {
                $responseSameday = $this->requestSamedayApi('POST', '/api/awb/estimate-cost', $payloadRes['payload']);
                if ($responseSameday['success']) {
                    $json = $responseSameday['data'];
                    if (isset($json['amount'])) {
                        $amount = (float)$json['amount'];
                        $amountWithVat = $amount * 1.19;
                        $samedayResponse = array(
                            'has_data' => true,
                            'cost' => number_format($amountWithVat, 2, '.', ''),
                            'message' => number_format($amount, 2, '.', '') . ' fără TVA',
                        );
                    } else {
                        $samedayResponse = array('has_data' => false, 'cost' => 0, 'message' => $this->l('Eroare la calcularea prețului Sameday.'));
                    }
                } else {
                    $samedayResponse = array('has_data' => false, 'cost' => 0, 'message' => $responseSameday['message']);
                }
            } else {
                $samedayResponse = array('has_data' => false, 'cost' => 0, 'message' => $payloadRes['message']);
            }
        } else {
            $samedayResponse = array('has_data' => false, 'cost' => 0, 'message' => $samedayCredentials['message']);
        }

        $dscResponse = array('has_data' => false, 'cost' => 0, 'message' => $this->l('Credențialele DSC nu sunt configurate.'));
        $dscDebug = array();
        $dscCredentials = $this->getDscCredentials();
        if ($dscCredentials['success']) {
            $dscCity = (string)$validation['data']['dpd_city'];
            $dscCounty = (string)$validation['data']['dpd_county'];
            if (ctype_digit($dscCity)) {
                $dpdSite = $this->getDpdSiteById((int)$dscCity);
                if ($dpdSite) {
                    $dscCity = (string)($dpdSite['name'] ?? $dscCity);
                    $dscCounty = (string)($dpdSite['region'] ?? $dscCounty);
                }
            }

            $destPays = strtoupper((string)$validation['data']['dpd_courier_payer']) === 'RECIPIENT' ? 1 : 0;
            $totalWeight = (float)$validation['data']['dpd_total_weight'];
            $extraPkgs = $validation['data']['extra_packages'] ?? array();
            if (!empty($extraPkgs)) {
                $totalWeight = 0.0;
                $parcels = $this->buildDpdParcels($extraPkgs, $validation['data']);
                foreach ($parcels as $parcel) {
                    $totalWeight += (float)$parcel['weight'];
                }
                if ($totalWeight <= 0) {
                    $totalWeight = (float)$validation['data']['dpd_total_weight'];
                }
            }
            $payload = array(
                'judet' => $dscCounty,
                'localitate' => $dscCity,
                'destinatar' => (string)$validation['data']['dpd_client_name'],
                'contact' => (string)$validation['data']['dpd_contact_name'],
                'telefon' => (string)$validation['data']['dpd_phone'],
                'adresa' => (string)$validation['data']['dpd_street_address'],
                'tipExpeditie' => 'colet',
                'nrColete' => (int)$validation['data']['dpd_parcels_count'],
                'greutate' => round($totalWeight, 2),
                'platitorEsteDestinatarul' => $destPays,
                'valoareRamburs' => (float)$validation['data']['dpd_ramburs_value'],
                'tipPlata' => 'cash',
                'valoareAsigurare' => $validation['data']['dpd_insurance'] ? (float)$validation['data']['dpd_ramburs_value'] : 0,
                'tarifUrgenta' => $validation['data']['dsc_urgency'] ? 1 : 0,
                'tarifSambata' => $validation['data']['dpd_saturday_delivery'] ? 1 : 0,
                'livrareSediu' => 0,
                'observatii' => (string)$validation['data']['dpd_shipment_note'],
                'deschidereColet' => $validation['data']['dpd_open_package'] ? 1 : 0,
                'sms' => $validation['data']['dsc_sms'] ? 1 : 0,
            );

            $responseDsc = $this->requestDscApi('POST', 'https://app.curierdragonstar.ro/awb/cost', $payload, $dscCredentials);
            $dscDebug = isset($responseDsc['debug']) && is_array($responseDsc['debug']) ? $responseDsc['debug'] : array();
            if ($responseDsc['success']) {
                $json = $responseDsc['data'];
                if (isset($json['totalNet'])) {
                    $dscResponse = array(
                        'has_data' => true,
                        'cost' => $json['totalNet'],
                        'message' => 'Cost KM=' . ($json['costKm'] ?? '-') . ', KG=' . ($json['costGreutate'] ?? '-') . ', TVA=' . ($json['tva'] ?? '-'),
                    );
                } else {
                    $dscResponse = array('has_data' => false, 'cost' => 0, 'message' => $this->l('Eroare la calcularea prețului DSC.'));
                }
            } else {
                $dscResponse = array('has_data' => false, 'cost' => 0, 'message' => $responseDsc['message']);
            }
        } else {
            $dscResponse = array('has_data' => false, 'cost' => 0, 'message' => $dscCredentials['message']);
        }

        return json_encode(array(
            'success' => true,
            'dpd_price' => $dpdResponse,
            'sameday_price' => $samedayResponse,
            'dsc_price' => $dscResponse,
            'message' => $this->l('Cost calculat cu succes.'),
        ));
    }


    private function getAwbRowsByOrderId($orderId)
    {
        return Db::getInstance()->executeS('
            SELECT `awb_number`, `current_status`
            FROM `' . _DB_PREFIX_ . 'globinary_awb_tracking`
            WHERE `order_id` = ' . (int)$orderId . '
        ');
    }

    private function getFirstAwbNumberByOrderId($orderId)
    {
        return Db::getInstance()->getValue('
            SELECT `awb_number` FROM `' . _DB_PREFIX_ . 'globinary_awb_tracking`
            WHERE `order_id` = ' . (int)$orderId . '
            ORDER BY `awb_date_added` ASC
        ');
    }

    private function insertAwbGroup(
        Db $db,
        int $orderId,
        string $mainAwb,
        array $parcelList,
        string $status,
        string $operation,
        string $liveMode,
        string $countyName,
        string $siteId,
        string $courierName,
        string $dateAdded
    ) {
        $db->insert(
            'globinary_awb_tracking',
            array(
                'order_id' => (int)$orderId,
                'awb_number' => pSQL($mainAwb),
                'current_status' => pSQL($status),
                'operation_code' => pSQL($operation),
                'awb_date_added' => pSQL($dateAdded),
                'backup_field_one' => 'courier_not_called',
                'backup_field_two' => pSQL($liveMode),
                'backup_field_three' => '',
                'backup_field_four' => pSQL($countyName),
                'backup_field_five' => pSQL($siteId),
                'backup_field_six' => pSQL($courierName),
            )
        );

        foreach ($parcelList as $parcel) {
            $parcel = (string)$parcel;
            if ($parcel === '' || $parcel === $mainAwb) {
                continue;
            }
            $db->insert(
                'globinary_awb_tracking',
                array(
                    'order_id' => (int)$orderId,
                    'awb_number' => pSQL($mainAwb),
                    'current_status' => pSQL($status),
                    'operation_code' => pSQL($operation),
                    'awb_date_added' => pSQL($dateAdded),
                    'backup_field_one' => 'courier_not_called',
                    'backup_field_two' => pSQL($liveMode),
                    'backup_field_three' => pSQL($mainAwb . ' (' . $parcel . ')'),
                    'backup_field_four' => pSQL($countyName),
                    'backup_field_five' => pSQL($siteId),
                    'backup_field_six' => pSQL($courierName),
                )
            );
        }
    }

    private function mapDpdIssueErrorMessage($raw)
    {
        $msg = trim((string)$raw);
        if ($msg === '') {
            return '';
        }
        $lower = Tools::strtolower($msg);

        if (strpos($lower, 'street no/block no') !== false || strpos($lower, 'address note field is required') !== false) {
            return $this->l('Adresa destinatarului este incompletă pentru DPD. Completează strada și numărul în câmpul Adresă (ex: Str Exemplu Nr 10) și încearcă din nou.');
        }

        return '';
    }

    private function normalizeExtraPackages($json, $parcelsCount)
    {
        $out = array();
        if (empty($json)) {
            return array();
        }
        $arr = json_decode($json, true);
        if (!is_array($arr)) {
            return array();
        }

        foreach ($arr as $item) {
            if (!is_array($item)) {
                continue;
            }
            $weight = isset($item['weight']) ? (float)$item['weight'] : 0.0;
            $height = isset($item['height']) ? (float)$item['height'] : 0.0;
            $length = isset($item['length']) ? (float)$item['length'] : 0.0;
            $width = isset($item['width']) ? (float)$item['width'] : 0.0;
            $obs = isset($item['observations']) ? (string)$item['observations'] : '';

            $out[] = array(
                'weight' => $weight,
                'height' => $height,
                'length' => $length,
                'width' => $width,
                'observations' => $obs,
            );
        }

        $target = max(0, (int)$parcelsCount - 1);
        if (count($out) > $target) {
            $out = array_slice($out, 0, $target);
        } elseif (count($out) < $target) {
            while (count($out) < $target) {
                $out[] = array('weight' => 0, 'height' => 0, 'length' => 0, 'width' => 0, 'observations' => '');
            }
        }

        return $out;
    }

    private function getDpdCredentials()
    {
        $liveMode = (bool)Configuration::get('GLOBINARYAWB_LIVE_MODE');
        $user = $liveMode ? Configuration::get('GLOBINARYAWB_DPD_LIVE_USER') : Configuration::get('GLOBINARYAWB_DPD_TEST_USER');
        $pass = $liveMode ? Configuration::get('GLOBINARYAWB_DPD_LIVE_PASS') : Configuration::get('GLOBINARYAWB_DPD_TEST_PASS');

        if (empty($user) || empty($pass)) {
            return array('success' => false, 'message' => $this->l('Credențialele DPD nu sunt configurate.'));
        }

        return array(
            'success' => true,
            'user' => $user,
            'pass' => $pass,
            'live_mode' => $liveMode,
        );
    }

    private function getDscCredentials()
    {
        $liveMode = (bool)Configuration::get('GLOBINARYAWB_LIVE_MODE');
        $user = $liveMode ? Configuration::get('GLOBINARYAWB_DSC_LIVE_USER') : Configuration::get('GLOBINARYAWB_DSC_TEST_USER');
        $pass = $liveMode ? Configuration::get('GLOBINARYAWB_DSC_LIVE_PASS') : Configuration::get('GLOBINARYAWB_DSC_TEST_PASS');

        if (empty($user) || empty($pass)) {
            return array('success' => false, 'message' => $this->l('Credențialele DSC nu sunt configurate.'));
        }

        return array(
            'success' => true,
            'user' => $user,
            'pass' => $pass,
            'live_mode' => $liveMode,
        );
    }

    private function getSamedayBaseUrl($liveMode)
    {
        return $liveMode ? 'https://api.sameday.ro' : 'https://sameday-api.demo.zitec.com';
    }

    private function getSamedayCredentials()
    {
        $liveMode = (bool)Configuration::get('GLOBINARYAWB_LIVE_MODE');
        $user = $liveMode ? Configuration::get('GLOBINARYAWB_SAMEDAY_LIVE_USER') : Configuration::get('GLOBINARYAWB_SAMEDAY_TEST_USER');
        $pass = $liveMode ? Configuration::get('GLOBINARYAWB_SAMEDAY_LIVE_PASS') : Configuration::get('GLOBINARYAWB_SAMEDAY_TEST_PASS');

        if (empty($user) || empty($pass)) {
            return array('success' => false, 'message' => $this->l('Credențialele Sameday nu sunt configurate.'));
        }

        return array(
            'success' => true,
            'user' => $user,
            'pass' => $pass,
            'live_mode' => $liveMode,
        );
    }

    private function logSamedayRequest($method, $url, $payload, $httpCode, $response)
    {
        $logDir = _PS_CACHE_DIR_ . 'globinaryawbtracking_logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $entry = array(
            'ts' => date('Y-m-d H:i:s'),
            'method' => $method,
            'url' => $url,
            'http_code' => $httpCode,
            'payload' => $payload,
            'response' => $response,
        );
        @file_put_contents($logDir . '/sameday_api.log', json_encode($entry, JSON_UNESCAPED_UNICODE) . "
", FILE_APPEND);
    }

    private function getSamedayToken($credentials)
    {
        $liveMode = (bool)$credentials['live_mode'];
        $tokenKey = $liveMode ? 'GLOBINARYAWB_SAMEDAY_TOKEN' : 'GLOBINARYAWB_SAMEDAY_TEST_TOKEN';
        $expKey = $liveMode ? 'GLOBINARYAWB_SAMEDAY_TOKEN_EXPIRES' : 'GLOBINARYAWB_SAMEDAY_TEST_TOKEN_EXPIRES';

        $token = Configuration::get($tokenKey);
        $expiresAt = Configuration::get($expKey);

        if (!empty($token) && !empty($expiresAt)) {
            $expTs = strtotime($expiresAt);
            if ($expTs && $expTs > (time() + 3600)) {
                return array('success' => true, 'token' => $token);
            }
        }

        $baseUrl = $this->getSamedayBaseUrl($liveMode);
        $url = $baseUrl . '/api/authenticate?remember_me=1';

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                'X-AUTH-USERNAME: ' . $credentials['user'],
                'X-AUTH-PASSWORD: ' . $credentials['pass'],
                'Accept: application/json',
            ),
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
        ));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        $this->logSamedayRequest('POST', $url, null, $httpCode, $response);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            return array('success' => false, 'message' => $curlErr ? $curlErr : ($this->l('Sameday API a returnat eroare: ') . $response));
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['token'])) {
            return array('success' => false, 'message' => $this->l('Răspuns invalid de la Sameday (token lipsă).'));
        }

        Configuration::updateValue($tokenKey, $data['token']);
        if (!empty($data['expire_at'])) {
            Configuration::updateValue($expKey, $data['expire_at']);
        } elseif (!empty($data['expire_at_utc'])) {
            Configuration::updateValue($expKey, $data['expire_at_utc']);
        }

        return array('success' => true, 'token' => $data['token']);
    }

    private function requestSamedayApi($method, $path, $payload = null, $isBinary = false)
    {
        $credentials = $this->getSamedayCredentials();
        if (!$credentials['success']) {
            return array('success' => false, 'message' => $credentials['message']);
        }

        $tokenResponse = $this->getSamedayToken($credentials);
        if (!$tokenResponse['success']) {
            return array('success' => false, 'message' => $tokenResponse['message']);
        }

        $baseUrl = $this->getSamedayBaseUrl($credentials['live_mode']);
        $url = $baseUrl . $path;

        $headers = array(
            'X-AUTH-TOKEN: ' . $tokenResponse['token'],
            $isBinary ? 'Accept: application/pdf' : 'Accept: application/json',
        );
        if ($payload !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            // DSC endpoint is sensitive to DNS/IPv6 routing on some hosts.
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 45,
        ));
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        $this->logSamedayRequest($method, $url, $payload, $httpCode, $isBinary ? '[binary]' : $response);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            return array('success' => false, 'message' => $curlErr ? $curlErr : ($this->l('Sameday API a returnat eroare: ') . $response));
        }

        if ($isBinary) {
            return array('success' => true, 'raw' => $response);
        }

        $data = json_decode($response, true);
        if ($data === null) {
            if (trim((string)$response) === '') {
                return array('success' => true, 'data' => array());
            }
            return array('success' => false, 'message' => $this->l('Răspuns invalid de la Sameday.'));
        }

        return array('success' => true, 'data' => $data);
    }

    private function buildSamedayParcels($data)
    {
        $parcels = array();

        $parcels[] = array(
            'weight' => (string)round((float)$data['dpd_total_weight'], 3),
            'width' => (string)round((float)$data['dpd_width'], 1),
            'length' => (string)round((float)$data['dpd_length'], 1),
            'height' => (string)round((float)$data['dpd_height'], 1),
        );

        if (!empty($data['extra_packages']) && is_array($data['extra_packages'])) {
            foreach ($data['extra_packages'] as $pkg) {
                if (empty($pkg)) {
                    continue;
                }
                $parcels[] = array(
                    'weight' => (string)round((float)($pkg['weight'] ?? $data['dpd_total_weight']), 3),
                    'width' => (string)round((float)($pkg['width'] ?? $data['dpd_width']), 1),
                    'length' => (string)round((float)($pkg['length'] ?? $data['dpd_length']), 1),
                    'height' => (string)round((float)($pkg['height'] ?? $data['dpd_height']), 1),
                );
            }
        }

        return $parcels;
    }

    private function prepareSamedayPayload($data, $isEstimate = false)
    {
        list($countyId, $cityId) = $this->findSamedayLocationIds($data['dpd_county'], $data['dpd_city']);
        if (!$cityId) {
            return array('success' => false, 'message' => $this->l('Localitatea nu a fost găsită în baza Sameday.'));
        }

        $site = Db::getInstance()->getRow('SELECT `postal_code` FROM `' . _DB_PREFIX_ . 'globinary_awb_sameday_sites` WHERE `id` = ' . (int)$cityId);
        $postalCode = $site && !empty($site['postal_code']) ? $site['postal_code'] : '';

        $serviceId = Configuration::get('GLOBINARYAWB_SAMEDAY_SERVICE_ID');
        $pickupPointId = Configuration::get('GLOBINARYAWB_SAMEDAY_PICKUP_POINT_ID');
        $contactPersonId = Configuration::get('GLOBINARYAWB_SAMEDAY_CONTACT_PERSON_ID');
        $returnLocationId = Configuration::get('GLOBINARYAWB_SAMEDAY_RETURN_LOCATION_ID');

        if (empty($pickupPointId)) {
            return array('success' => false, 'message' => $this->l('Pickup Point Sameday nu este configurat.'));
        }

        $parcels = $this->buildSamedayParcels($data);
        $totalWeight = 0.0;
        foreach ($parcels as $parcel) {
            $totalWeight += (float)$parcel['weight'];
        }

        $packageType = $totalWeight > 1.0 ? 0 : 1;

        $insuredValue = 0.0;
        if (!empty($data['dpd_insurance'])) {
            $insuredValue = (float)$data['dpd_ramburs_value'] > 0 ? (float)$data['dpd_ramburs_value'] : 0.0;
        }
        $cashOnDelivery = (float)$data['dpd_ramburs_value'] > 0 ? (float)$data['dpd_ramburs_value'] : 0.0;

        $clientName = $data['dpd_contact_name'] ?: $data['dpd_client_name'];
        if ($clientName === '') {
            return array('success' => false, 'message' => $this->l('Nume client invalid.'));
        }

        $personType = !empty($data['dpd_private_person']) ? 0 : 1;

        $payload = array(
            'pickupPoint' => (string)$pickupPointId,
            'service' => (string)$serviceId,
            'awbRecipient' => array(
                'name' => $clientName,
                'phoneNumber' => $data['dpd_phone'],
                'county' => (string)$countyId,
                'city' => (string)$cityId,
                'address' => $data['dpd_street_address'],
                'postalCode' => (string)$postalCode,
                'personType' => (string)$personType,
            ),
            'packageType' => (string)$packageType,
            'packageNumber' => (string)count($parcels),
            'packageWeight' => (string)round($totalWeight, 3),
            'parcels' => $parcels,
            'insuredValue' => (string)$insuredValue,
            'cashOnDelivery' => (string)$cashOnDelivery,
            'awbPayment' => '1',
            'thirdPartyPickup' => '0',
        );

        if (!empty($contactPersonId)) {
            $payload['contactPerson'] = (string)$contactPersonId;
        }
        if (!empty($returnLocationId)) {
            $payload['returnLocationId'] = (string)$returnLocationId;
        }
        if ($personType === 1) {
            $payload['awbRecipient']['companyName'] = $clientName;
        }

        return array('success' => true, 'payload' => $payload);
    }



    private function logDpdRequest($method, $url, $payload, $httpCode, $response)
    {
        $logDir = _PS_CACHE_DIR_ . 'globinaryawbtracking_logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $entry = array(
            'ts' => date('Y-m-d H:i:s'),
            'method' => $method,
            'url' => $url,
            'http_code' => $httpCode,
            'payload' => $payload,
            'response' => $response,
        );
        @file_put_contents($logDir . '/dpd_api.log', json_encode($entry, JSON_UNESCAPED_UNICODE) . "
", FILE_APPEND);
    }

    private function requestDpdApi($method, $url, $payload, $isBinary = false)
    {
        $ch = curl_init($url);
        $accept = $isBinary ? 'application/pdf' : 'application/json';
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'Accept: ' . $accept),
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
        ));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $this->logDpdRequest($method, $url, $payload, $httpCode, $isBinary ? '[binary]' : $response);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            return array(
                'success' => false,
                'message' => $curlErr ? $curlErr : ($this->l('DPD API a returnat eroare: ') . $response),
            );
        }

        if ($isBinary) {
            return array('success' => true, 'raw' => $response, 'content_type' => $contentType);
        }

        $data = json_decode($response, true);
        if ($data === null) {
            return array('success' => false, 'message' => $this->l('Răspuns invalid de la DPD.'));
        }

        return array('success' => true, 'data' => $data);
    }

    private function requestDscApi($method, $url, $payload, $credentials, $isBinary = false)
    {
        $host = (string)parse_url($url, PHP_URL_HOST);
        $resolvedIp = $host !== '' ? gethostbyname($host) : '';
        $auth = base64_encode($credentials['user'] . ':' . $credentials['pass']);
        $headers = array(
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json',
        );
        if ($isBinary) {
            $headers[] = 'Accept: application/pdf';
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 45,
        ));
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $primaryIp = (string)curl_getinfo($ch, CURLINFO_PRIMARY_IP);
        $localIp = defined('CURLINFO_LOCAL_IP') ? (string)curl_getinfo($ch, CURLINFO_LOCAL_IP) : '';
        $curlErrNo = curl_errno($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);

        $debug = array(
            'method' => $method,
            'url' => $url,
            'http_code' => (int)$httpCode,
            'curl_errno' => (int)$curlErrNo,
            'curl_error' => (string)$curlErr,
            'live_mode' => !empty($credentials['live_mode']),
            'host' => $host,
            'resolved_ip' => $resolvedIp,
            'primary_ip' => $primaryIp,
            'local_ip' => $localIp,
            'connect_timeout' => 20,
            'timeout' => 45,
        );

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            return array(
                'success' => false,
                'message' => $curlErr ? $curlErr : ($this->l('DSC API a returnat eroare: ') . $response),
                'debug' => $debug,
            );
        }

        if ($isBinary) {
            return array('success' => true, 'raw' => $response, 'content_type' => $contentType, 'debug' => $debug);
        }

        $data = json_decode($response, true);
        if ($data === null) {
            return array('success' => false, 'message' => $this->l('Răspuns invalid de la DSC.'), 'debug' => $debug);
        }

        return array('success' => true, 'data' => $data, 'debug' => $debug);
    }

    private function validateDpdRequest($data)
    {
        $required = array('dpd_service_id', 'dpd_parcels_count', 'dpd_total_weight', 'dpd_length', 'dpd_width', 'dpd_height', 'dpd_client_name', 'dpd_phone', 'dpd_email', 'dpd_city', 'dpd_county');
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                return array('success' => false, 'message' => $this->l('Câmp lipsă: ') . $field);
            }
        }

        if (!Validate::isEmail($data['dpd_email'])) {
            return array('success' => false, 'message' => $this->l('Email invalid.'));
        }

        $street = $this->extractStreetNameAndNumber($data['dpd_street_address']);
        $data['street_name'] = $street['street_name'];
        $data['street_number'] = $street['street_number'];

        $data['dpd_city'] = $this->removeDiacritics($data['dpd_city']);
        $data['dpd_county'] = $this->removeDiacritics($data['dpd_county']);

        return array('success' => true, 'message' => 'ok', 'data' => $data);
    }

    private function buildDpdParcels($extraPackages, $data)
    {
        $parcels = array();
        $extraPackages = array_slice($extraPackages, 0, 10);
        $idx = 1;
        foreach ($extraPackages as $pkg) {
            $weight = (float)($pkg['weight'] ?? 0);
            if ($weight <= 0) {
                $weight = 1.0;
            }
            $parcels[] = array(
                'seqNo' => $idx++,
                'weight' => $weight,
                'size' => array(
                    'width' => (int)($pkg['width'] ?? 0),
                    'height' => (int)($pkg['height'] ?? 0),
                    'depth' => (int)($pkg['length'] ?? 0),
                ),
                'ref1' => substr((string)($pkg['observations'] ?? ''), 0, 20),
            );
        }

        if (empty($parcels)) {
            $weight = (float)($data['dpd_total_weight'] ?? 1);
            if ($weight <= 0) {
                $weight = 1.0;
            }
            $parcels[] = array(
                'seqNo' => 1,
                'weight' => $weight,
                'size' => array(
                    'width' => (int)$data['dpd_width'],
                    'height' => (int)$data['dpd_height'],
                    'depth' => (int)$data['dpd_length'],
                ),
                'ref1' => substr((string)($data['dpd_shipment_note'] ?? ''), 0, 20),
            );
        }

        return $parcels;
    }

    private function prepareDpdPayload($data, $siteId, $credentials, $isPriceCalculation)
    {
        $payload = array(
            'userName' => $credentials['user'],
            'password' => $credentials['pass'],
            'service' => array(
                'autoAdjustPickupDate' => true,
                'saturdayDelivery' => (bool)$data['dpd_saturday_delivery'],
                'additionalServices' => array(),
            ),
            'content' => array(
                'parcelsCount' => (int)$data['dpd_parcels_count'],
                'totalWeight' => (float)$data['dpd_total_weight'],
                'contents' => 'Produse Globinary',
                'package' => 'CUTIE',
                'parcels' => array(),
            ),
            'payment' => array('courierServicePayer' => $data['dpd_courier_payer']),
            'recipient' => array(
                'phone1' => array('number' => $data['dpd_phone']),
                'privatePerson' => (bool)$data['dpd_private_person'],
                'clientName' => $data['dpd_client_name'],
                'contactName' => $data['dpd_contact_name'],
                'email' => $data['dpd_email'],
            ),
            'shipmentNote' => $data['dpd_shipment_note'],
            'ref1' => (string)($data['orderId'] ?? ''),
        );

        if ($isPriceCalculation) {
            $payload['service']['serviceIds'] = array((int)$data['dpd_service_id']);
            $payload['recipient']['addressLocation'] = array('siteId' => $siteId);
        } else {
            $payload['service']['serviceId'] = (int)$data['dpd_service_id'];
            $payload['recipient']['address'] = array(
                'siteId' => $siteId,
                'streetName' => $data['street_name'],
                'streetNo' => $data['street_number'],
            );
        }

        if ((float)$data['dpd_ramburs_value'] > 0) {
            $payload['service']['additionalServices']['cod'] = array(
                'amount' => (float)$data['dpd_ramburs_value'],
                'currency' => 'RON',
                'processingType' => 'CASH',
            );
        }
        if (!empty($data['dpd_insurance'])) {
            $payload['service']['additionalServices']['declaredValue'] = array(
                'amount' => (float)$data['dpd_ramburs_value'],
            );
        }
        if (!empty($data['dpd_fragile'])) {
            if (!isset($payload['service']['additionalServices']['declaredValue'])) {
                $payload['service']['additionalServices']['declaredValue'] = array();
            }
            $payload['service']['additionalServices']['declaredValue']['fragile'] = true;
        }

        // DPD calculate/create expects an object here, not an empty array.
        if (empty($payload['service']['additionalServices'])) {
            $payload['service']['additionalServices'] = (object)array();
        }

        return $payload;
    }

    private function resolveSiteId($city, $county)
    {
        if (ctype_digit($city)) {
            return (int)$city;
        }

        $sites = $this->getDpdSitesByRegion($county);
        if (empty($sites)) {
            return null;
        }

        $match = $this->findBestCityMatch($city, $sites);
        return $match ? (int)$match['id'] : null;
    }

    private function findBestCityMatch($city, $sites)
    {
        $city = $this->removeDiacritics($city);
        $cityClean = $this->removeComLocOrasFromCity($city);

        foreach ($sites as $site) {
            if ($site['name'] === $cityClean) {
                return $site;
            }
        }

        $cityToggled = $this->toggleHyphenInCity($cityClean);
        foreach ($sites as $site) {
            if ($site['name'] === $cityToggled) {
                return $site;
            }
        }

        $extract = $this->extractCityAndMunicipality($city);
        if (!empty($extract['city'])) {
            $candidates = array();
            foreach ($sites as $site) {
                if ($site['name'] === $extract['city']) {
                    $candidates[] = $site;
                }
            }
            if (count($candidates) === 1) {
                return $candidates[0];
            }
            if (count($candidates) > 1 && !empty($extract['municipality'])) {
                $mun = $this->removeDiacritics($extract['municipality']);
                foreach ($candidates as $site) {
                    if (!empty($site['municipality']) && strpos($this->removeDiacritics($site['municipality']), $mun) !== false) {
                        return $site;
                    }
                }
                return $candidates[0];
            }
        }

        return null;
    }

    private function getDpdCountyList()
    {
        $rows = Db::getInstance()->executeS('SELECT DISTINCT `region` FROM `' . _DB_PREFIX_ . 'globinary_awb_dpd_sites` ORDER BY `region` ASC');
        $counties = array();
        foreach ($rows as $row) {
            $counties[] = $row['region'];
        }
        return $counties;
    }

    private function getDpdSitesByRegion($region)
    {
        return Db::getInstance()->executeS('
            SELECT `id`, `name`, `municipality`, `region`
            FROM `' . _DB_PREFIX_ . 'globinary_awb_dpd_sites`
            WHERE `region` = "' . pSQL($region) . '"
            ORDER BY `name` ASC
        ');
    }

    private function getDpdSiteById($id)
    {
        return Db::getInstance()->getRow('
            SELECT `id`, `name`, `municipality`, `region`, `post_code`
            FROM `' . _DB_PREFIX_ . 'globinary_awb_dpd_sites`
            WHERE `id` = ' . (int)$id . '
        ');
    }

    private function formatDpdSiteDisplayName($site)
    {
        $name = $site['name'] ?? '';
        $municipality = $site['municipality'] ?? '';
        if ($municipality && $municipality !== $name) {
            return $name . ' (' . $municipality . ')';
        }
        return $name;
    }

    private function normalizeDpdCityForMatch($value)
    {
        $value = (string)$value;
        if ($value === '') {
            return '';
        }
        $value = $this->removeComLocOrasFromCity($value);
        $value = preg_replace('/\([^\)]*\)/', '', $value);
        $value = $this->removeDiacritics($value);
        $value = preg_replace('/[^A-Z0-9\s-]/', ' ', $value);
        $value = str_replace('-', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        return trim($value);
    }



    private function getSamedaySitesByCounty($county)
    {
        $county = trim((string)$county);
        if ($county === '') {
            return array();
        }
        $countySafe = pSQL($county);
        return Db::getInstance()->executeS('
            SELECT `id`, `county_id`, `county_name`, `county_latin_name`, `name`, `latin_name`, `village`, `postal_code`
            FROM `' . _DB_PREFIX_ . 'globinary_awb_sameday_sites`
            WHERE `county_name` = "' . $countySafe . '"
               OR `county_latin_name` = "' . $countySafe . '"
               OR `county_code` = "' . $countySafe . '"
            ORDER BY `name` ASC
        ');
    }

    private function findSamedayLocationIds($county, $city, $postalCode = null)
    {
        $countyName = (string)$county;
        $cityName = (string)$city;
        $postalCode = $postalCode ? (string)$postalCode : null;

        if (is_numeric($cityName)) {
            $dpdSite = $this->getDpdSiteById((int)$cityName);
            if ($dpdSite) {
                $cityName = (string)$dpdSite['name'];
                $countyName = (string)$dpdSite['region'];
                if (!empty($dpdSite['post_code'])) {
                    $postalCode = (string)$dpdSite['post_code'];
                }
            }
        }

        $sites = $this->getSamedaySitesByCounty($countyName);
        if (empty($sites)) {
            return array(null, null);
        }

        $cityNameClean = $this->removeComLocOrasFromCity($cityName);
        $variants = array_filter(array_unique(array(
            $cityName,
            $cityNameClean,
            $this->toggleHyphenInCity($cityName),
            $this->toggleHyphenInCity($cityNameClean),
        )));

        $normalizedVariants = array();
        foreach ($variants as $variant) {
            $normalizedVariants[] = $this->removeDiacritics($variant);
        }

        foreach ($sites as $site) {
            $siteName = $this->removeDiacritics($site['name'] ?? '');
            $siteLatin = $this->removeDiacritics($site['latin_name'] ?? '');
            $siteVillage = $this->removeDiacritics($site['village'] ?? '');
            $sitePostal = $site['postal_code'] ?? '';

            if ($postalCode && $sitePostal !== '' && $sitePostal !== $postalCode) {
                continue;
            }

            foreach ($normalizedVariants as $variant) {
                if ($variant === '' || $variant === null) {
                    continue;
                }
                if ($variant === $siteName || $variant === $siteLatin || $variant === $siteVillage) {
                    return array((int)$site['county_id'], (int)$site['id']);
                }
            }
        }

        return array(null, null);
    }

    private function getSamedayDbPath()
    {
        return _PS_MODULE_DIR_ . 'globinaryawbtracking/data/globinary_website_v2.db';
    }

    private function getSamedayImportLogFilePath()
    {
        $dir = _PS_MODULE_DIR_ . 'globinaryawbtracking/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir . '/sameday_import.log';
    }

    private function setSamedayImportError($message)
    {
        Configuration::updateValue('GLOBINARYAWB_SAMEDAY_IMPORT_LAST_ERROR', (string)$message);
        Logger::addLog('[Globinary AWB] Sameday import error: ' . $message, 3);
        $logFile = $this->getSamedayImportLogFilePath();
        @file_put_contents($logFile, date('Y-m-d H:i:s') . ' ' . $message . "
", FILE_APPEND);
    }

    public function importSamedaySitesFromDb($force = false)
    {
        $db = Db::getInstance();
        $table = _DB_PREFIX_ . 'globinary_awb_sameday_sites';
        $dbPath = $this->getSamedayDbPath();
        $logFile = $this->getSamedayImportLogFilePath();

        if (!file_exists($dbPath)) {
            $this->setSamedayImportError('SQLite DB missing: ' . $dbPath);
            return false;
        }

        if (!class_exists('SQLite3')) {
            $this->setSamedayImportError('SQLite3 extension missing on server.');
            return false;
        }

        $existingCount = (int)$db->getValue('SELECT COUNT(*) FROM `' . pSQL($table) . '`');
        if ($existingCount > 0 && !$force) {
            return true;
        }

        if ($force && $existingCount > 0) {
            $db->execute('TRUNCATE TABLE `' . pSQL($table) . '`');
        }

        $sqlite = null;
        try {
            $sqlite = new SQLite3($dbPath, SQLITE3_OPEN_READONLY);
        } catch (Exception $e) {
            $this->setSamedayImportError('SQLite open failed: ' . $e->getMessage());
            return false;
        }

        $columns = array(
            'id',
            'sameday_delivery_agency_id',
            'sameday_delivery_agency',
            'sameday_pickup_gency',
            'next_day_delivery_agencyId',
            'next_day_delivery_agency',
            'next_day_pickup_agency',
            'white_delivery_agency_id',
            'white_delivery_agency',
            'white_pickup_agency',
            'logistic_circle',
            'country_id',
            'country_name',
            'country_code',
            'sameday_id',
            'name',
            'county_id',
            'county_name',
            'county_code',
            'county_latin_name',
            'postal_code',
            'extra_KM',
            'village',
            'broker_delivery',
            'latin_name'
        );

        $query = 'SELECT ' . implode(', ', $columns) . ' FROM sameday_sites';
        $result = $sqlite->query($query);
        if ($result === false) {
            $this->setSamedayImportError('SQLite query failed: ' . $sqlite->lastErrorMsg());
            $sqlite->close();
            return false;
        }

        $parsedRows = 0;
        $insertedTotal = 0;

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if (!is_array($row) || empty($row['id'])) {
                continue;
            }
            $parsedRows++;

            $insertRow = array(
                'id' => (int)$row['id'],
                'sameday_delivery_agency_id' => $row['sameday_delivery_agency_id'] !== null && $row['sameday_delivery_agency_id'] !== '' ? (int)$row['sameday_delivery_agency_id'] : null,
                'sameday_delivery_agency' => $row['sameday_delivery_agency'] !== null && $row['sameday_delivery_agency'] !== '' ? pSQL($row['sameday_delivery_agency']) : null,
                'sameday_pickup_gency' => $row['sameday_pickup_gency'] !== null && $row['sameday_pickup_gency'] !== '' ? pSQL($row['sameday_pickup_gency']) : null,
                'next_day_delivery_agencyId' => $row['next_day_delivery_agencyId'] !== null && $row['next_day_delivery_agencyId'] !== '' ? (int)$row['next_day_delivery_agencyId'] : null,
                'next_day_delivery_agency' => $row['next_day_delivery_agency'] !== null && $row['next_day_delivery_agency'] !== '' ? pSQL($row['next_day_delivery_agency']) : null,
                'next_day_pickup_agency' => $row['next_day_pickup_agency'] !== null && $row['next_day_pickup_agency'] !== '' ? pSQL($row['next_day_pickup_agency']) : null,
                'white_delivery_agency_id' => $row['white_delivery_agency_id'] !== null && $row['white_delivery_agency_id'] !== '' ? (int)$row['white_delivery_agency_id'] : null,
                'white_delivery_agency' => $row['white_delivery_agency'] !== null && $row['white_delivery_agency'] !== '' ? pSQL($row['white_delivery_agency']) : null,
                'white_pickup_agency' => $row['white_pickup_agency'] !== null && $row['white_pickup_agency'] !== '' ? pSQL($row['white_pickup_agency']) : null,
                'logistic_circle' => $row['logistic_circle'] !== null && $row['logistic_circle'] !== '' ? pSQL($row['logistic_circle']) : null,
                'country_id' => $row['country_id'] !== null && $row['country_id'] !== '' ? (int)$row['country_id'] : null,
                'country_name' => $row['country_name'] !== null && $row['country_name'] !== '' ? pSQL($row['country_name']) : null,
                'country_code' => $row['country_code'] !== null && $row['country_code'] !== '' ? pSQL($row['country_code']) : null,
                'sameday_id' => $row['sameday_id'] !== null && $row['sameday_id'] !== '' ? (int)$row['sameday_id'] : null,
                'name' => $row['name'] !== null && $row['name'] !== '' ? pSQL($row['name']) : null,
                'county_id' => $row['county_id'] !== null && $row['county_id'] !== '' ? (int)$row['county_id'] : null,
                'county_name' => $row['county_name'] !== null && $row['county_name'] !== '' ? pSQL($row['county_name']) : null,
                'county_code' => $row['county_code'] !== null && $row['county_code'] !== '' ? pSQL($row['county_code']) : null,
                'county_latin_name' => $row['county_latin_name'] !== null && $row['county_latin_name'] !== '' ? pSQL($row['county_latin_name']) : null,
                'postal_code' => $row['postal_code'] !== null && $row['postal_code'] !== '' ? pSQL($row['postal_code']) : null,
                'extra_KM' => $row['extra_KM'] !== null && $row['extra_KM'] !== '' ? (float)$row['extra_KM'] : null,
                'village' => $row['village'] !== null && $row['village'] !== '' ? pSQL($row['village']) : null,
                'broker_delivery' => $row['broker_delivery'] !== null && $row['broker_delivery'] !== '' ? (int)$row['broker_delivery'] : null,
                'latin_name' => $row['latin_name'] !== null && $row['latin_name'] !== '' ? pSQL($row['latin_name']) : null,
            );

            if ($db->insert('globinary_awb_sameday_sites', $insertRow, true, true)) {
                $insertedTotal += 1;
            } else {
                @file_put_contents($logFile, date('Y-m-d H:i:s') . ' row insert failed: ' . $db->getMsgError() . "
", FILE_APPEND);
            }
        }

        $sqlite->close();

        if ($parsedRows === 0) {
            $this->setSamedayImportError('No data rows parsed from SQLite database.');
            return false;
        }

        if ($insertedTotal <= 0) {
            $this->setSamedayImportError('No rows inserted from SQLite database.');
            return false;
        }

        return true;
    }

    public function syncSamedaySitesFromDb($force = false)
    {
        if ($this->importSamedaySitesFromDb($force)) {
            Configuration::updateValue('GLOBINARYAWB_SAMEDAY_IMPORT_OK', true);
            Configuration::updateValue('GLOBINARYAWB_SAMEDAY_LAST_SYNC', date('Y-m-d H:i:s'));
            Configuration::updateValue('GLOBINARYAWB_SAMEDAY_IMPORT_LAST_ERROR', '');
            return true;
        }

        Configuration::updateValue('GLOBINARYAWB_SAMEDAY_IMPORT_OK', false);
        return false;
    }


    private function formatCityList($sites)
    {
        $list = array();
        foreach ($sites as $site) {
            $list[] = array(
                'site_id' => $site['id'],
                'site_name_display' => $site['name'] . (!empty($site['municipality']) ? ' (' . $site['municipality'] . ')' : ''),
                'site_name' => $site['name'],
            );
        }
        return $list;
    }

    private function removeDiacritics($name)
    {
        if ($name === null || $name === '') {
            return '';
        }
        $name = strtr($name, array(
            'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ş' => 's', 'ţ' => 't', 'ț' => 't',
            'Ă' => 'A', 'Â' => 'A', 'Î' => 'I', 'Ș' => 'S', 'Ş' => 'S', 'Ţ' => 'T', 'Ț' => 'T',
        ));
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        return strtoupper($name);
    }

    private function isDpdLocalityError($responseJson)
    {
        if (!isset($responseJson['error'])) {
            return false;
        }
        $context = $responseJson['error']['context'] ?? '';
        return strpos($context, 'valid-locality-id') !== false;
    }

    private function removeComLocOrasFromCity($cityName)
    {
        if (!$cityName) {
            return $cityName;
        }
        return trim(preg_replace('/^(com\.?|comuna|loc\.?|localitatea|oras\.?|mun\.?)\s+/i', '', $cityName));
    }

    private function extractCityAndMunicipality($cityRaw)
    {
        if (!$cityRaw) {
            return array('city' => '', 'municipality' => '');
        }

        $cityRaw = $this->removeDiacritics($cityRaw);
        $tokens = preg_split('/\s+/', preg_replace('/\b(comuna|com\.?|sat\.?|loc\.?|localitatea|oras\.?|mun\.?)\b/i', '', $cityRaw));
        $tokens = array_values(array_filter(array_map('trim', $tokens)));
        if (count($tokens) === 1) {
            return array('city' => $tokens[0], 'municipality' => '');
        }
        if (count($tokens) > 1) {
            $city = $tokens[count($tokens) - 1];
            $municipality = implode(' ', array_slice($tokens, 0, -1));
            return array('city' => $city, 'municipality' => $municipality);
        }

        return array('city' => '', 'municipality' => '');
    }

    private function toggleHyphenInCity($cityName)
    {
        if (!$cityName) {
            return '';
        }
        if (strpos($cityName, '-') !== false) {
            return str_replace('-', ' ', $cityName);
        }
        return str_replace(' ', '-', $cityName);
    }

    private function extractStreetNameAndNumber($address)
    {
        if (!$address || !is_string($address)) {
            return array('street_name' => '', 'street_number' => '');
        }
        $address = trim($address);
        $pattern = '/^(.*?)(\b(?:nr|numar|casa|bloc|bl|scara|sc|apartament|ap|etaj|et|flat|suite|unit|building|bldg|tower|floor)\b.*)?$/i';
        if (preg_match($pattern, $address, $matches)) {
            $streetName = trim($matches[1]);
            $streetNumber = '';
            if (!empty($matches[2])) {
                $streetNumber = trim($matches[2]);
            }
            if (strlen($streetNumber) > 10) {
                $streetNumber = substr($streetNumber, 0, 10);
            }
            return array('street_name' => $streetName, 'street_number' => $streetNumber);
        }
        return array('street_name' => $address, 'street_number' => '');
    }

    private function getDpdStatusCodes()
    {
        return array(
            '148' => 'Shipment data received',
            '-14' => 'Delivered',
            '111' => 'Return to Sender',
            '124' => 'Delivered Back to Sender',
            '1' => 'Arrival Scan',
            '2' => 'Departure Scan',
            '11' => 'Received in Office',
            '12' => 'Out for Delivery',
            '21' => 'Processed in Office',
            '38' => 'Returned to Office',
            '39' => 'Courier Pick-up',
            '44' => 'Unsuccessful Delivery',
            '69' => 'Deferred delivery',
            '112' => 'Processed to the Insurance dept.',
            '114' => 'Processed for Destruction',
            '115' => 'Redirected',
            '116' => 'Forwarded',
            '121' => 'Stopped by Sender',
            '123' => 'Refused by recipient',
            '125' => 'Destroyed',
            '127' => 'Theft/Burglary',
            '128' => 'Canceled',
            '129' => 'Administrative Closure',
            '134' => 'Prepared for Self-collecting Consignee',
            '136' => 'Clarify shipment delivery',
            '144' => 'Handover for contents check/test',
            '164' => 'Unsuccessful shipment pickup',
            '169' => 'Delivery capacity reached (tour/office)',
            '175' => 'Predict',
            '176' => 'Export to foreign provider',
            '181' => 'Unexpected delay',
            '190' => 'Postponed delivery due to inaccurate/incomplete address',
            '195' => 'Refuse contents check/test',
            '217' => 'Handover to midway carrier',
            '1134' => 'Notification sent for parcel in office/locker',
        );
    }


    private function getSamedayStatusCodes()
    {
        $file = dirname(__FILE__) . '/data/sameday-statuses.json';
        $codes = array();

        if (is_readable($file)) {
            $raw = @file_get_contents($file);
            $json = $raw ? json_decode($raw, true) : null;
            if (is_array($json) && !empty($json['data']) && is_array($json['data'])) {
                foreach ($json['data'] as $row) {
                    $id = isset($row['id']) ? (int)$row['id'] : 0;
                    $label = isset($row['label']) ? trim((string)$row['label']) : '';
                    if ($id > 0 && $label !== '' && !isset($codes[$id])) {
                        $codes[$id] = $label;
                    }
                }
            }
        }

        if (empty($codes)) {
            $codes = array(
                1 => 'Expedierea a fost înregistrată.',
                9 => 'Coletul a fost livrat cu succes.',
                29 => 'Coletul îți va fi returnat.',
                16 => 'Ți-am returnat coletul cu succes.',
            );
        }

        // Top 4 first (aligned to DPD required mapping flow), then all remaining statuses.
        $priority = array(1, 9, 29, 16);
        $ordered = array();
        foreach ($priority as $id) {
            if (isset($codes[$id])) {
                $ordered[(string)$id] = $codes[$id];
                unset($codes[$id]);
            }
        }
        foreach ($codes as $id => $label) {
            $ordered[(string)$id] = $label;
        }

        return $ordered;
    }

    private function getSamedayStatusConfigKey($code)
    {
        return 'GLOBINARYAWB_SAMEDAY_STATUS_' . (string)$code;
    }

    private function getDpdStatusConfigKey($code)
    {
        if ($code === '-14') {
            return 'GLOBINARYAWB_DPD_STATUS_NEG14';
        }
        return 'GLOBINARYAWB_DPD_STATUS_' . $code;
    }

    private function getOrderStateOptions()
    {
        $states = OrderState::getOrderStates($this->context->language->id);
        array_unshift($states, array('id_order_state' => 0, 'name' => $this->l('-- Selectează --')));
        return $states;
    }

    private function getIssueOrderStateOptions()
    {
        $states = OrderState::getOrderStates($this->context->language->id);
        array_unshift($states, array('id_order_state' => 0, 'name' => $this->l('Nu schimba status comanda')));
        return $states;
    }

    private function isStatusMappingConfigured()
    {
        return empty($this->getMissingRequiredMappings());
    }

    private function getMissingRequiredMappings()
    {
        $required = array('148', '-14', '111', '124');
        $missing = array();
        foreach ($required as $code) {
            $raw = Configuration::get($this->getDpdStatusConfigKey($code));
            $val = is_numeric($raw) ? (int)$raw : 0;
            if ($val <= 0) {
                $missing[] = $code;
            }
        }
        return $missing;
    }

    private function getMappedOrderStateId($operationCode, $courier = 'DPD')
    {
        $courier = strtoupper((string)$courier);
        if ($courier === 'SAMEDAY') {
            $key = $this->getSamedayStatusConfigKey($operationCode);
        } else {
            $key = $this->getDpdStatusConfigKey($operationCode);
        }
        $val = (int)Configuration::get($key);
        return $val > 0 ? $val : null;
    }

    private function updatePrestaShopOrderStatus($orderId, $operationCode, $awbNumbers, $courier = 'DPD')
    {
        $newOrderStatus = $this->getMappedOrderStateId($operationCode, $courier);
        if (!$newOrderStatus) {
            return "Skipped status update for operation code $operationCode (courier: $courier).";
        }

        $order = new Order($orderId);
        if (!Validate::isLoadedObject($order)) {
            return 'Invalid order, cannot update status.';
        }

        // Avoid duplicate history entries when status is already set.
        if ((int)$order->current_state === (int)$newOrderStatus) {
            return "Order #$orderId already has PrestaShop status ID: $newOrderStatus (no change).";
        }

        $history = new OrderHistory();
        $history->id_order = $orderId;
        $history->changeIdOrderState($newOrderStatus, $orderId);
        $history->addWithemail();

        if (Configuration::get('GLOBINARYAWB_EMAIL_NOTIF')) {
            $this->sendCustomerEmail($orderId, $operationCode, $awbNumbers);
        }

        return "Order #$orderId status updated to PrestaShop status ID: $newOrderStatus.";
    }

    private function updateOrderStatusAfterIssue($orderId)
    {
        $targetStatus = (int)Configuration::get('GLOBINARYAWB_ISSUE_ORDER_STATE');
        if ($targetStatus <= 0) {
            return 'No issue status configured.';
        }

        $order = new Order($orderId);
        if (!Validate::isLoadedObject($order)) {
            return 'Invalid order, cannot update status.';
        }

        if ((int)$order->current_state === (int)$targetStatus) {
            return "Order #$orderId already has configured issue status ID: $targetStatus (no change).";
        }

        $history = new OrderHistory();
        $history->id_order = $orderId;
        $history->changeIdOrderState($targetStatus, $orderId);
        $history->addWithemail();

        return "Order #$orderId status updated on AWB issue to PrestaShop status ID: $targetStatus.";
    }


    public function printAwb()
    {
        $awbNumber = (string)Tools::getValue('awb_number');
        $format = (string)Tools::getValue('format', 'A6');
        if ($awbNumber === '') {
            return json_encode(['success' => false, 'message' => $this->l('Număr AWB invalid.')]);
        }

        $db = Db::getInstance();
        $row = $db->getRow('SELECT `order_id`, `awb_number`, `backup_field_three`, `backup_field_six` FROM `' . _DB_PREFIX_ . 'globinary_awb_tracking` WHERE `awb_number` = "' . pSQL($awbNumber) . '" OR `backup_field_three` LIKE "%(' . pSQL($awbNumber) . ')%" ORDER BY `id_globinary_awb_tracking` DESC');
        if (!$row) {
            return json_encode(['success' => false, 'message' => $this->l('AWB-ul nu a fost găsit.')]);
        }
        $courier = strtoupper((string)($row['backup_field_six'] ?? 'DPD'));

        if ($courier === 'SAMEDAY') {
            $resp = $this->requestSamedayApi('GET', '/api/awb/download/' . urlencode($awbNumber), null, true);
            if (!$resp['success']) {
                return json_encode(['success' => false, 'message' => $resp['message']]);
            }
            header('Content-Type: application/pdf');
            echo $resp['raw'];
            exit;
        }

        if ($courier === 'DSC') {
            $credentials = $this->getDscCredentials();
            if (!$credentials['success']) {
                return json_encode(['success' => false, 'message' => $credentials['message']]);
            }

            $awbCandidates = array((string)$awbNumber);
            if (preg_match('/^[A-Za-z]{3}(.+)$/', (string)$awbNumber, $m) && !empty($m[1])) {
                $awbCandidates[] = trim((string)$m[1]);
            }
            $awbCandidates = array_values(array_unique(array_filter($awbCandidates)));

            $lastError = $this->l('Eroare la tipărirea AWB DSC.');
            foreach ($awbCandidates as $candidateAwb) {
                $resp = $this->requestDscApi(
                    'GET',
                    'https://app.curierdragonstar.ro/awb/print/' . urlencode($candidateAwb),
                    null,
                    $credentials,
                    true
                );
                if (!$resp['success']) {
                    $lastError = $resp['message'] ?? $lastError;
                    continue;
                }
                $raw = (string)($resp['raw'] ?? '');
                if (trim($raw) === '') {
                    $lastError = $this->l('Răspuns gol de la DSC pentru tipărire.');
                    continue;
                }
                header('Content-Type: application/pdf');
                echo $raw;
                exit;
            }

            return json_encode(['success' => false, 'message' => $lastError]);
        }

        $credentials = $this->getDpdCredentials();
        if (!$credentials['success']) {
            return json_encode(['success' => false, 'message' => $credentials['message']]);
        }

        $paperSize = in_array($format, array('A4', 'A6'), true) ? $format : 'A6';

        $awbRows = $db->executeS('SELECT `awb_number`, `backup_field_three` FROM `' . _DB_PREFIX_ . 'globinary_awb_tracking` WHERE `order_id` = ' . (int)$row['order_id'] . ' AND `awb_number` = "' . pSQL($row['awb_number']) . '"');
        $awbIds = array();
        foreach ($awbRows as $r) {
            if (!empty($r['backup_field_three']) && strpos($r['backup_field_three'], '(') !== false) {
                if (preg_match('/\(([^\)]+)\)/', $r['backup_field_three'], $m2)) {
                    $awbIds[] = $m2[1];
                }
            } else {
                $awbIds[] = $r['awb_number'];
            }
        }
        $awbIds = array_values(array_unique(array_filter($awbIds)));
        if (empty($awbIds)) {
            $awbIds = array($awbNumber);
        }

        $parcels = array();
        foreach ($awbIds as $id) {
            $parcels[] = array('parcel' => array('id' => $id));
        }

        $payload = array(
            'userName' => $credentials['user'],
            'password' => $credentials['pass'],
            'parcels' => $parcels,
            'paperSize' => $paperSize,
        );

        $response = $this->requestDpdApi('POST', 'https://api.dpd.ro/v1/print', $payload, true);
        if (!$response['success']) {
            return json_encode(['success' => false, 'message' => $response['message']]);
        }

        $raw = $response['raw'];
        $contentType = strtolower((string)($response['content_type'] ?? ''));
        $rawString = (string)$raw;
        $isPdfByHeader = (strpos($rawString, '%PDF-') !== false);
        $isPdfByContentType = (strpos($contentType, 'application/pdf') !== false);
        if (!$isPdfByContentType && !$isPdfByHeader) {
            $maybeJson = json_decode($rawString, true);
            if (is_array($maybeJson)) {
                // Some APIs return base64-encoded PDF in JSON payload.
                $base64Candidates = array(
                    $maybeJson['pdf'] ?? null,
                    $maybeJson['file'] ?? null,
                    $maybeJson['content'] ?? null,
                    $maybeJson['label'] ?? null,
                    isset($maybeJson['data']) && is_array($maybeJson['data']) ? ($maybeJson['data']['pdf'] ?? $maybeJson['data']['file'] ?? $maybeJson['data']['content'] ?? null) : null,
                );
                foreach ($base64Candidates as $candidate) {
                    if (!is_string($candidate) || $candidate === '') {
                        continue;
                    }
                    $decoded = base64_decode($candidate, true);
                    if ($decoded !== false && strpos($decoded, '%PDF-') !== false) {
                        $rawString = $decoded;
                        $isPdfByHeader = true;
                        break;
                    }
                }
                if (!$isPdfByHeader) {
                    $msg = $maybeJson['message'] ?? ($maybeJson['error']['message'] ?? $this->l('Eroare la tipărirea AWB.'));
                    return json_encode(['success' => false, 'message' => $msg]);
                }
            } else {
                // Fallback: some providers return plain base64 directly (not wrapped in JSON).
                $decodedRaw = base64_decode($rawString, true);
                if ($decodedRaw !== false && strpos($decodedRaw, '%PDF-') !== false) {
                    $rawString = $decodedRaw;
                    $isPdfByHeader = true;
                } else {
                    $snippet = trim(substr(preg_replace('/\s+/', ' ', $rawString), 0, 220));
                    $msg = $this->l('Eroare la tipărirea AWB. Răspuns invalid de la DPD.');
                    if ($snippet !== '') {
                        $msg .= ' ' . $this->l('Detalii:') . ' ' . $snippet;
                    }
                    return json_encode(['success' => false, 'message' => $msg]);
                }
            }
        }
        if (trim($rawString) === '') {
            return json_encode(['success' => false, 'message' => $this->l('Eroare la tipărirea AWB. Răspuns gol de la DPD.')]);
        }

        header('Content-Type: application/pdf');
        echo $rawString;
        exit;
    }

    public function updateAwbStatus()
    {
        $awbNumber = (string)Tools::getValue('awb_number');
        if ($awbNumber === '') {
            return json_encode(['success' => false, 'message' => $this->l('Număr AWB invalid.')]);
        }

        $db = Db::getInstance();
        $row = $db->getRow('SELECT `order_id`, `awb_number`, `backup_field_six` FROM `' . _DB_PREFIX_ . 'globinary_awb_tracking` WHERE `awb_number` = "' . pSQL($awbNumber) . '" OR `backup_field_three` LIKE "%(' . pSQL($awbNumber) . ')%" ORDER BY `id_globinary_awb_tracking` DESC');
        if (!$row) {
            return json_encode(['success' => false, 'message' => $this->l('AWB-ul nu a fost găsit.')]);
        }

        $mainAwb = (string)$row['awb_number'];
        $orderId = (int)$row['order_id'];
        $courier = strtoupper((string)($row['backup_field_six'] ?? 'DPD'));

        $statusRes = null;
        if ($courier === 'SAMEDAY') {
            $statusRes = $this->getSamedayStatusForAwb($awbNumber);
        } elseif ($courier === 'DSC') {
            $statusRes = $this->getDscStatusForAwb($awbNumber);
        } else {
            $credentials = $this->getDpdCredentials();
            if (!$credentials['success']) {
                return json_encode(['success' => false, 'message' => $credentials['message']]);
            }
            $statusRes = $this->getAwbStatusFromApi($credentials, $awbNumber);
        }

        if (!$statusRes || empty($statusRes['success'])) {
            $message = is_array($statusRes) && !empty($statusRes['message'])
                ? (string)$statusRes['message']
                : $this->l('Nu s-a putut actualiza statusul AWB.');

            return json_encode(['success' => false, 'message' => $message]);
        }

        $now = date('Y-m-d H:i:s');
        $operationCode = (string)($statusRes['operation_code'] ?? '');
        $where = $this->buildAwbMainWhereClause($orderId, $mainAwb);
        $updateData = array(
            'current_status' => pSQL($statusRes['status'] ?? '-'),
            'last_status_change' => pSQL($now),
        );
        if ($operationCode !== '') {
            $updateData['operation_code'] = pSQL($operationCode);
        }

        $updated = $db->update('globinary_awb_tracking', $updateData, $where);
        if (!$updated) {
            return json_encode(['success' => false, 'message' => $this->l('Nu s-a putut salva statusul AWB în baza de date.')]);
        }

        if ($operationCode !== '') {
            $this->updatePrestaShopOrderStatus($orderId, $operationCode, array($mainAwb), $courier);
        }

        return json_encode(['success' => true, 'message' => $this->l('Status actualizat cu succes.')]);
    }

    public function updateAllAwbStatuses()
    {
        $db = Db::getInstance();
        $rows = $db->executeS('SELECT DISTINCT `order_id`, `awb_number`, `backup_field_six` FROM `' . _DB_PREFIX_ . 'globinary_awb_tracking` WHERE `awb_number` <> ""');
        $updated = 0;
        foreach ($rows as $row) {
            $mainAwb = (string)$row['awb_number'];
            $orderId = (int)$row['order_id'];
            $courier = strtoupper((string)($row['backup_field_six'] ?? 'DPD'));

            $statusRes = null;
            if ($courier === 'SAMEDAY') {
                $statusRes = $this->getSamedayStatusForAwb($mainAwb);
            } elseif ($courier === 'DSC') {
                $statusRes = $this->getDscStatusForAwb($mainAwb);
            } else {
                $credentials = $this->getDpdCredentials();
                if (!$credentials['success']) {
                    continue;
                }
                $statusRes = $this->getAwbStatusFromApi($credentials, $mainAwb);
            }

            if (!$statusRes || empty($statusRes['success'])) {
                continue;
            }

            $now = date('Y-m-d H:i:s');
            $operationCode = (string)($statusRes['operation_code'] ?? '');
            $where = $this->buildAwbMainWhereClause($orderId, $mainAwb);
            $updateData = array(
                'current_status' => pSQL($statusRes['status'] ?? '-'),
                'last_status_change' => pSQL($now),
            );
            if ($operationCode !== '') {
                $updateData['operation_code'] = pSQL($operationCode);
            }

            $db->update('globinary_awb_tracking', $updateData, $where);

            if ($operationCode !== '') {
                $this->updatePrestaShopOrderStatus($orderId, $operationCode, array($mainAwb), $courier);
            }

            $updated++;
        }

        return $updated;
    }

    private function getSamedayStatusForAwb($awbNumber)
    {
        $fromTs = time() - 7200;
        $toTs = time();
        $syncRes = $this->requestSamedayStatusSync($fromTs, $toTs);
        if (empty($syncRes['success'])) {
            return array(
                'success' => false,
                'message' => (string)($syncRes['message'] ?? $this->l('Nu s-a putut interoga statusul Sameday.')),
            );
        }

        $match = $this->extractSamedayStatusFromResponse($syncRes['data'], $awbNumber);
        if (!$match) {
            return array(
                'success' => false,
                'message' => $this->l('Statusul Sameday nu a fost găsit în fereastra de sincronizare (ultimele 2 ore).'),
            );
        }

        return array(
            'success' => true,
            'status' => $match['status'] ?? '-',
            'operation_code' => (string)($match['operation_code'] ?? ''),
        );
    }

    private function getDscStatusForAwb($awbNumber)
    {
        $credentials = $this->getDscCredentials();
        if (!$credentials['success']) {
            return array('success' => false, 'message' => $credentials['message']);
        }

        $resp = $this->requestDscApi(
            'GET',
            'https://app.curierdragonstar.ro/awb/status/' . urlencode($awbNumber),
            null,
            $credentials
        );

        if (empty($resp['success'])) {
            return array(
                'success' => false,
                'message' => (string)($resp['message'] ?? $this->l('Nu s-a putut interoga statusul DSC.')),
            );
        }

        $data = (array)($resp['data'] ?? array());
        $status = (string)($data['status'] ?? $data['message'] ?? '');
        if ($status === '') {
            return array('success' => false, 'message' => $this->l('Răspuns invalid de la DSC (status lipsă).'));
        }

        return array(
            'success' => true,
            'status' => $status,
            'operation_code' => (string)($data['statusCode'] ?? $data['status_code'] ?? ''),
        );
    }

    private function requestSamedayStatusSync($fromTs, $toTs)
    {
        $attempts = array(
            array('startTimestamp' => $fromTs, 'endTimestamp' => $toTs),
            array('startTimestamp' => $fromTs * 1000, 'endTimestamp' => $toTs * 1000),
            array('fromDate' => date('Y-m-d H:i:s', $fromTs), 'toDate' => date('Y-m-d H:i:s', $toTs)),
            array('fromDate' => date('Y-m-d\TH:i:s', $fromTs), 'toDate' => date('Y-m-d\TH:i:s', $toTs)),
            array('fromTimestamp' => $fromTs, 'toTimestamp' => $toTs),
            array('startDate' => date('Y-m-d H:i:s', $fromTs), 'endDate' => date('Y-m-d H:i:s', $toTs)),
            array('fromDate' => $fromTs * 1000, 'toDate' => $toTs * 1000),
        );

        $endpoints = array('/api/client/status-sync', '/api/client/xb-status-sync');
        $lastMessage = '';
        $mergedItems = array();

        foreach ($endpoints as $endpoint) {
            foreach ($attempts as $params) {
                $page = 1;
                $maxPages = 25;
                $countPerPage = 100;
                while ($page <= $maxPages) {
                    $query = $params;
                    $query['page'] = $page;
                    $query['countPerPage'] = $countPerPage;

                    $path = $endpoint . '?' . http_build_query($query);
                    $resp = $this->requestSamedayApi('GET', $path, null);
                    if (empty($resp['success']) || !isset($resp['data'])) {
                        if (!empty($resp['message'])) {
                            $lastMessage = (string)$resp['message'];
                        }
                        break;
                    }

                    $data = $resp['data'];
                    $items = array();
                    if (isset($data['data']) && is_array($data['data'])) {
                        $items = $data['data'];
                    } elseif (isset($data['items']) && is_array($data['items'])) {
                        $items = $data['items'];
                    } elseif (isset($data['results']) && is_array($data['results'])) {
                        $items = $data['results'];
                    } elseif (isset($data[0]) && is_array($data)) {
                        $items = $data;
                    }

                    if (!empty($items)) {
                        $mergedItems = array_merge($mergedItems, $items);
                    }

                    $pages = (int)($data['pages'] ?? $data['totalPages'] ?? $data['lastPage'] ?? 1);
                    if ($pages <= 0) {
                        $pages = 1;
                    }
                    if ($page >= $pages) {
                        break;
                    }
                    $page++;
                }
            }
        }

        if (!empty($mergedItems)) {
            return array('success' => true, 'data' => array('data' => $mergedItems));
        }

        if ($lastMessage === '') {
            $lastMessage = $this->l('Nu s-a putut interoga endpoint-ul de status Sameday.');
        }

        return array('success' => false, 'message' => $lastMessage);
    }

    private function extractSamedayStatusFromResponse($data, $awbNumber)
    {
        $items = array();
        if (isset($data['data']) && is_array($data['data'])) {
            $items = $data['data'];
        } elseif (isset($data['items']) && is_array($data['items'])) {
            $items = $data['items'];
        } elseif (isset($data['results']) && is_array($data['results'])) {
            $items = $data['results'];
        } elseif (isset($data[0]) && is_array($data)) {
            $items = $data;
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $itemAwb = $item['awbNumber'] ?? $item['awb_number'] ?? $item['awb'] ?? null;
            if ($itemAwb === null && isset($item['awb']) && is_array($item['awb'])) {
                $itemAwb = $item['awb']['awbNumber'] ?? $item['awb']['awb_number'] ?? null;
            }
            if ($itemAwb !== null && (string)$itemAwb !== (string)$awbNumber) {
                continue;
            }
            if ($itemAwb === null) {
                continue;
            }
            $status = $item['status'] ?? $item['statusLabel'] ?? $item['statusName'] ?? $item['status_name'] ?? $item['statusDescription'] ?? null;
            $code = $item['statusId'] ?? $item['status_id'] ?? $item['statusCode'] ?? '';

            return array(
                'status' => $status ?: '-',
                'operation_code' => (string)$code,
            );
        }

        return null;
    }

    private function getAwbStatusFromApi($credentials, $awbNumber)
    {
        $payload = array(
            'userName' => $credentials['user'],
            'password' => $credentials['pass'],
            'parcels' => array(array('id' => $awbNumber)),
        );

        $response = $this->requestDpdApi('POST', 'https://api.dpd.ro/v1/track', $payload);
        if (!$response['success']) {
            return array('success' => false);
        }

        $data = $response['data'];
        if (isset($data['error'])) {
            return array('success' => false);
        }

        $operations = $data['parcels'][0]['operations'] ?? array();
        if (empty($operations)) {
            return array('success' => false);
        }

        $lastOperation = end($operations);
        return array(
            'success' => true,
            'status' => $lastOperation['description'] ?? '-',
            'operation_code' => (string)($lastOperation['operationCode'] ?? ''),
        );
    }


    private function buildAwbMainWhereClause($orderId, $mainAwb)
    {
        $escapedAwb = pSQL((string)$mainAwb);

        return ' `order_id` = ' . (int)$orderId
            . ' AND (`awb_number` = "' . $escapedAwb . '" OR `backup_field_three` LIKE "' . $escapedAwb . ' (%)%")';
    }

    private function buildTrackingUrl($courier, $awbNumber)
    {
        $awb = urlencode((string)$awbNumber);
        $courierCode = strtoupper((string)$courier);

        if ($courierCode === 'SAMEDAY') {
            return 'https://sameday.ro/#awb=' . $awb;
        }

        if ($courierCode === 'DSC') {
            return 'https://dragonstarcurier.ro/tracking-awb?awb=' . $awb;
        }

        return 'https://tracking.dpd.ro/?shipmentNumber=' . $awb;
    }

    private function sendCustomerEmail($orderId, $operationCode, $awbNumbers)
    {
        $order = new Order($orderId);
        if (!Validate::isLoadedObject($order)) {
            return false;
        }

        $customer = new Customer($order->id_customer);
        if (!Validate::isLoadedObject($customer) || !Validate::isEmail($customer->email)) {
            return false;
        }

        $messages = $this->getDpdStatusMessageMap();
        $message = isset($messages[$operationCode]) ? $messages[$operationCode] : $this->l('Statusul comenzii tale a fost actualizat.');

        $trackingLinks = '';
        foreach ($awbNumbers as $awbNumber) {
            $trackingUrl = $this->buildTrackingUrl('DPD', $awbNumber);
            $trackingLinks .= '<p>AWB: <strong>' . $awbNumber . '</strong> - <a href="' . $trackingUrl . '">Urmărește coletul</a></p>' . "\n";
        }

        $templateVars = array(
            '{firstname}' => pSQL($customer->firstname),
            '{lastname}' => pSQL($customer->lastname),
            '{order_id}' => (int)$orderId,
            '{message}' => $message,
            '{awb_list}' => $trackingLinks,
            '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
            '{shop_url}' => _PS_BASE_URL_,
            '{customer_account_url}' => _PS_BASE_URL_ . '/index.php?controller=identity',
            '{order_url}' => _PS_BASE_URL_ . '/index.php?controller=order-detail&id_order=' . (int)$orderId,
            '{shop_logo}' => _PS_IMG_ . Configuration::get('PS_LOGO'),
        );

        return Mail::Send(
            (int)$order->id_lang,
            'awb_status_update',
            Mail::l('Actualizare comandă', (int)$order->id_lang),
            $templateVars,
            $customer->email,
            $customer->firstname . ' ' . $customer->lastname,
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . 'globinaryawbtracking/mails/',
            false,
            (int)$order->id_shop
        );
    }

    private function getDpdStatusMessageMap()
    {
        return array(
            '148' => $this->l('Expedierea ta a fost înregistrată de către expeditor.'),
            '-14' => $this->l('Comanda ta a fost livrată.'),
            '111' => $this->l('Coletul este în curs de retur către expeditor.'),
            '124' => $this->l('Coletul a fost livrat înapoi către expeditor.'),
        );
    }

    private function syncDpdSitesFromCsv($force = false)
    {
        $result = $this->importDpdSitesFromCsv($force);
        if ($result) {
            Configuration::updateValue('GLOBINARYAWB_DPD_LAST_SYNC', date('Y-m-d H:i:s'));
        }
        return $result;
    }

    private function getImportLogFilePath()
    {
        $dir = _PS_CACHE_DIR_ . 'globinaryawbtracking_logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (!is_dir($dir)) {
            $dir = sys_get_temp_dir();
        }
        return rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . 'dpd_import.log';
    }

    private function setDpdImportError($message)
    {
        Configuration::updateValue('GLOBINARYAWB_DPD_IMPORT_LAST_ERROR', $message);
        Logger::addLog('[Globinary AWB] DPD import error: ' . $message, 3);
        $logFile = $this->getImportLogFilePath();
        @file_put_contents($logFile, date('Y-m-d H:i:s') . ' ' . $message . "\n", FILE_APPEND);
    }

private function importDpdSitesFromCsv($force = false)
    {
        $db = Db::getInstance();
        $table = _DB_PREFIX_ . 'globinary_awb_dpd_sites';
        $csvPath = _PS_MODULE_DIR_ . 'globinaryawbtracking/data/dpd_counties.csv';
        $logFile = $this->getImportLogFilePath();

        if (!file_exists($csvPath)) {
            $this->setDpdImportError('CSV missing: ' . $csvPath);
            return false;
        }

        $existingCount = (int)$db->getValue('SELECT COUNT(*) FROM `' . pSQL($table) . '`');
        if ($existingCount > 0 && !$force) {
            return true;
        }

        if ($force && $existingCount > 0) {
            $db->execute('TRUNCATE TABLE `' . pSQL($table) . '`');
        }

        $contents = @file_get_contents($csvPath);
        if ($contents === false) {
            $this->setDpdImportError('CSV read failed: ' . $csvPath);
            return false;
        }
        $contents = str_replace("\r\n", "\n", $contents);
        $contents = str_replace("\r", "\n", $contents);
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            $this->setDpdImportError('CSV temp stream open failed.');
            return false;
        }
        fwrite($handle, $contents);
        rewind($handle);

        $header = fgetcsv($handle, 0, ',', '"', '\\');
        if (!$header || count($header) < 5) {
            $this->setDpdImportError('CSV header invalid or delimiter mismatch.');
            fclose($handle);
            return false;
        }

        if (isset($header[0])) {
            $header[0] = preg_replace('/^\\xEF\\xBB\\xBF/', '', $header[0]);
        }

        $expected = array(
            'id', 'countryId', 'mainSiteId', 'type', 'typeEn',
            'name', 'nameEn', 'municipality', 'municipalityEn',
            'region', 'regionEn', 'postCode', 'addressNomenclature',
            'x', 'y', 'servingDays', 'servingOfficeId', 'servingHubOfficeId'
        );

        $map = array();
        foreach ($header as $idx => $col) {
            $col = trim($col);
            if (in_array($col, $expected, true)) {
                $map[$col] = $idx;
            }
        }

        if (!isset($map['id']) || !isset($map['name']) || !isset($map['region'])) {
            $this->setDpdImportError('CSV header missing required columns.');
            fclose($handle);
            return false;
        }

        $insertedTotal = 0;
        $parsedRows = 0;

        while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            if (!is_array($data) || (count($data) === 1 && $data[0] === null)) {
                continue;
            }

            $parsedRows++;

            if (!isset($data[$map['id']]) || $data[$map['id']] === '') {
                continue;
            }

            $row = array(
                'id' => (int)$data[$map['id']],
                'country_id' => isset($map['countryId']) ? (int)$data[$map['countryId']] : 0,
                'main_site_id' => (isset($map['mainSiteId']) && $data[$map['mainSiteId']] !== '') ? (int)$data[$map['mainSiteId']] : null,
                'type' => (isset($map['type']) && $data[$map['type']] !== '') ? pSQL($data[$map['type']]) : null,
                'type_en' => (isset($map['typeEn']) && $data[$map['typeEn']] !== '') ? pSQL($data[$map['typeEn']]) : null,
                'name' => pSQL($data[$map['name']]),
                'name_en' => (isset($map['nameEn']) && $data[$map['nameEn']] !== '') ? pSQL($data[$map['nameEn']]) : null,
                'municipality' => (isset($map['municipality']) && $data[$map['municipality']] !== '') ? pSQL($data[$map['municipality']]) : null,
                'municipality_en' => (isset($map['municipalityEn']) && $data[$map['municipalityEn']] !== '') ? pSQL($data[$map['municipalityEn']]) : null,
                'region' => pSQL($data[$map['region']]),
                'region_en' => (isset($map['regionEn']) && $data[$map['regionEn']] !== '') ? pSQL($data[$map['regionEn']]) : null,
                'post_code' => (isset($map['postCode']) && $data[$map['postCode']] !== '') ? pSQL($data[$map['postCode']]) : null,
                'address_nomenclature' => (isset($map['addressNomenclature']) && $data[$map['addressNomenclature']] !== '') ? (int)$data[$map['addressNomenclature']] : null,
                'x' => (isset($map['x']) && $data[$map['x']] !== '') ? (float)$data[$map['x']] : null,
                'y' => (isset($map['y']) && $data[$map['y']] !== '') ? (float)$data[$map['y']] : null,
                'serving_days' => (isset($map['servingDays']) && $data[$map['servingDays']] !== '') ? pSQL($data[$map['servingDays']]) : null,
                'serving_office_id' => (isset($map['servingOfficeId']) && $data[$map['servingOfficeId']] !== '') ? (int)$data[$map['servingOfficeId']] : null,
                'serving_hub_office_id' => (isset($map['servingHubOfficeId']) && $data[$map['servingHubOfficeId']] !== '') ? (int)$data[$map['servingHubOfficeId']] : null,
            );
            if ($db->insert('globinary_awb_dpd_sites', $row, true, true)) {
                $insertedTotal += 1;
            } else {
                @file_put_contents($logFile, date('Y-m-d H:i:s') . ' row insert failed: ' . $db->getMsgError() . "
", FILE_APPEND);
            }
        }

        fclose($handle);

        if ($parsedRows === 0) {
            $this->setDpdImportError('No data rows parsed. Check CSV delimiter/encoding.');
            return false;
        }

        if ($insertedTotal <= 0) {
            $this->setDpdImportError('No rows inserted. Check CSV header/encoding and permissions.');
            return false;
        }

        return true;
    }


    private function getDpdPickupWindow()
    {
        $start = (string)Configuration::get('GLOBINARYAWB_DPD_PICKUP_START', '15:00');
        $end = (string)Configuration::get('GLOBINARYAWB_DPD_PICKUP_END', '15:30');

        if (!preg_match('/^\\d{2}:\\d{2}$/', $start)) {
            $start = '15:00';
        }
        if (!preg_match('/^\\d{2}:\\d{2}$/', $end)) {
            $end = '15:30';
        }

        return array('start' => $start, 'end' => $end);
    }
}
