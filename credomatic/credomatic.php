<?php
/**
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@buy-addons.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author     test<test@mail.com>
*  @copyright 2007-2016 PrestaShop SA
*  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(_PS_MODULE_DIR_ . 'credomatic/lib/Credomatic_OrderState.php');

class Credomatic extends PaymentModule
{

    public $isTest;
    public $typePayment;
    protected $_keyId;
    protected $_key;

    public function __construct()
    {
        $this->name = 'credomatic';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.1';
        $this->author = 'singleUserLicence';
        $this->need_instance = 1;
        $this->bootstrap = true;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        parent::__construct();

        $this->ps_versions_compliancy = array('min' => '1.6.0', 'max' => _PS_VERSION_);

        $this->displayName = $this->l('Credomatic');
        $this->description = $this->l('Credomatic Payment Gateway');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall the module?');


        $config = Configuration::getMultiple(array('CREDOMATIC_SECURITY_KEY_ID', 'CREDOMATIC_SECURITY_KEY','CREDOMATIC_LIVE_MODE'));

        if (isset($config['CREDOMATIC_SECURITY_KEY_ID']))
            $this->_keyId = trim($config['CREDOMATIC_SECURITY_KEY_ID']);
        if (isset($config['CREDOMATIC_SECURITY_KEY']))
            $this->_key = trim($config['CREDOMATIC_SECURITY_KEY']);
        if (isset($config['CREDOMATIC_LIVE_MODE']))
            $this->isTest = $config['CREDOMATIC_LIVE_MODE'];
        if (isset($config['CREDOMATIC_TRANSACTION_TYPE']))
            $this->typePayment = $config['CREDOMATIC_TRANSACTION_TYPE'];

    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Credomatic_OrderState::setup();

        return (
            function_exists('curl_version') &&
            parent::install() &&
            in_array('curl', get_loaded_extensions()) &&
            $this->_createInitialDbTable() &&
            $this->_createHooks() &&
            Configuration::updateValue('CREDOMATIC_ASK_CVV', 'yes') &&
            Configuration::updateValue('CREDOMATIC_TRANSACTION_TYPE', 'sale') &&
            Configuration::updateValue('CREDOMATIC_SECURITY_KEY_ID', '') &&
            Configuration::updateValue('CREDOMATIC_SECURITY_KEY', '') &&
            Configuration::updateValue('CREDOMATIC_ORDER_STATUS', Configuration::get('PS_OS_PAYMENT')) &&
            Configuration::updateValue('CREDOMATIC_LIVE_MODE', 0)
        );
    }

    private function _createInitialDbTable()
    {


        if(Db::getInstance()->Execute('CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'credomatic` (
				  `id_credomatic` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
				) ENGINE='._MYSQL_ENGINE_.'  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;')) {
            return true;
        }

        return true;


    }

    public function _createHooks()
    {

        $registerStatus = $this->registerHook('header') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('backOfficeHeader');

        if (version_compare(_PS_VERSION_, '1.7.0.0', '<')) {
            $registerStatus &= $this->registerHook('payment');
        } else {
            $registerStatus &= $this->registerHook('paymentOptions');
        }

        return $registerStatus;
    }

    public function uninstall()
    {
        Credomatic_OrderState::remove();

        if (!parent::uninstall() ||
            !Configuration::deleteByName('CREDOMATIC_ASK_CVV') ||
            !Configuration::deleteByName('CREDOMATIC_TRANSACTION_TYPE') ||
            !Configuration::deleteByName('CREDOMATIC_LIVE_MODE') ||
            !Configuration::deleteByName('CREDOMATIC_ORDER_STATUS') ||
            !Configuration::deleteByName('CREDOMATIC_SECURITY_KEY_ID') ||
            !Configuration::deleteByName('CREDOMATIC_SECURITY_KEY')){
            return false;
        }

        return true;
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitCredomaticModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCredomaticModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $statuses_options = array();
        $statuses = $this->getOrderStatuses();
        foreach ($statuses as $k => $v) {
            $statuses_options[] = array(
                'id_option' => $k,
                'name' => $v,
            );
        }


        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),

                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->l('Ask CVC?'),
                        'name' => 'CREDOMATIC_ASK_CVV',
                        'options' => array(
                            'id' => 'id_option',
                            'name' => 'name',
                            'query' => array(
                                array(
                                    'id_option' => 'yes',
                                    'name' => $this->l('Yes')
                                ),
                                array(
                                    'id_option' => 'no',
                                    'name' => $this->l('No')
                                )
                            )
                        ),
                    ),

                    array(
                        'type' => 'select',
                        'label' => $this->l('Transaction Type'),
                        'name' => 'CREDOMATIC_TRANSACTION_TYPE',
                        'options' => array(
                            'id' => 'id_option',
                            'name' => 'name',
                            'query' => array(
                                array(
                                    'id_option' => 'sale',
                                    'name' => $this->l('Sale')
                                ),
                                array(
                                    'id_option' => 'auth',
                                    'name' => $this->l('Authorization')
                                ),
                                array(
                                    'id_option' => 'credit',
                                    'name' => $this->l('Credit')
                                )

                            )
                        ),
                    ),

                    array(
                        'type' => 'select',
                        'label' => $this->l('Order status after payment'),
                        'name' => 'CREDOMATIC_ORDER_STATUS',
                        'options' => array(
                            'id' => 'id_option',
                            'name' => 'name',
                            'query' => $statuses_options
                        ),
                    ),

                    array(
                        'type' => 'text',
                        'name' => 'CREDOMATIC_SECURITY_KEY_ID',
                        'label' => $this->l('Security Key ID'),
                    ),

                    array(
                        'type' => 'text',
                        'name' => 'CREDOMATIC_SECURITY_KEY',
                        'label' => $this->l('Security Key'),
                    ),

                    array(
                        'type' => 'switch',
                        'label' => $this->l('SANDBOX mode'),
                        'name' => 'CREDOMATIC_LIVE_MODE',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),

                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.CREDOMATIC_ASC_CVV
     */
    protected function getConfigFormValues()
    {
        return array(
            'CREDOMATIC_ASK_CVV' => Configuration::get('CREDOMATIC_ASK_CVV'),
            'CREDOMATIC_TRANSACTION_TYPE' => Configuration::get('CREDOMATIC_TRANSACTION_TYPE'),
            'CREDOMATIC_ORDER_STATUS' => Configuration::get('CREDOMATIC_ORDER_STATUS'),
            'CREDOMATIC_SECURITY_KEY_ID' => Configuration::get('CREDOMATIC_SECURITY_KEY_ID'),
            'CREDOMATIC_SECURITY_KEY' => Configuration::get('CREDOMATIC_SECURITY_KEY'),
            'CREDOMATIC_LIVE_MODE' => Configuration::get('CREDOMATIC_LIVE_MODE'),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/jquery.card.js');
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    /**
     * This method is used to render the payment button,
     * Take care if the button should be displayed or not.
     */
    public function hookPayment($params)
    {
//        var_dump($params['cart']);
        $currency_id = $params['cart']->id_currency;
        $currency = new Currency((int)$currency_id);

//        if (in_array($currency->iso_code, $this->limited_currencies) == false)
//            return false;

        $this->smarty->assign('module_dir', $this->_path);

        $credomatic_recommended = array();
        if (Configuration::get('CREDOMATIC_SEND_RECOMMENDED') == 'yes') {
            $billing_address = new Address($params['cart']->id_address_invoice);
            $state = new State($billing_address->id_state);
            $customer = new Customer($params['cart']->id_customer);

            $credomatic_recommended = array(
                'ipaddress' => $this->getIpAddress(),
                'firstname' => $billing_address->firstname,
                'lastname' => $billing_address->lastname,
                'address1' => $billing_address->address1,
                'city' => $billing_address->city,
                'state' => $state->name,
                'zip' => $billing_address->postcode,
                'country' => $billing_address->country,
                'phone' => $billing_address->phone,
                'email' => $customer->email,
            );
        }

        $time = time();

        $credomatic_order_id = $params['cart']->id;
//        $credomatic_order_id = 'test';
        $credomatic_type = Configuration::get('CREDOMATIC_TRANSACTION_TYPE', 'sale');
        $credomatic_security_key_id = Configuration::get('CREDOMATIC_SECURITY_KEY_ID', null);
        $credomatic_security_key = Configuration::get('CREDOMATIC_SECURITY_KEY', null);
        $credomatic_amount = number_format((float)$params['cart']->getOrderTotal(), 2, '.', '');
//        $credomatic_amount = 110;
        $credomatic_ask_cvv = Configuration::get('CREDOMATIC_ASK_CVV', null);
        $credomatic_ask_name = Configuration::get('CREDOMATIC_ASK_NAME', null);
        if ($credomatic_ask_name == 'yes') {
            unset($credomatic_recommended['firstname']);
            unset($credomatic_recommended['lastname']);
        }

        $credomatic_redirect = Context::getContext()->link->getModuleLink('credomatic', 'validation');

        $credomatic_hash = md5("$credomatic_order_id|$credomatic_amount|$time|$credomatic_security_key");
        $this->smarty->assign(array(
            'credomatic_order_id' => $credomatic_order_id,
            'credomatic_type' => $credomatic_type,
            'credomatic_security_key_id' => $credomatic_security_key_id,
            'credomatic_redirect' => $credomatic_redirect,
            'credomatic_amount' => $credomatic_amount,
            'credomatic_hash' => $credomatic_hash,
            'credomatic_time' => $time,
            'credomatic_ask_cvv' => $credomatic_ask_cvv,
            'credomatic_recommended' => $credomatic_recommended,
            'credomatic_ask_name' => $credomatic_ask_name,
            'credomatic_ipaddress' => Tools::getRemoteAddr()
        ));



        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active)
            return;
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $this->smarty->assign(
            $this->getTemplateVars()
        );


        $modalOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $modalOption->setCallToActionText($this->l('Credomatic'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
            ->setAdditionalInformation($this->fetch('module:credomatic/views/templates/front/payment_onpage.tpl'))
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/logo.png'));

        return [$modalOption];
    }


    public function getTemplateVars()
    {
        $cart = $this->context->cart;
    }


    public function getTemplateVarsCopy()
    {
        $cart = $this->context->cart;
        $total = $this->trans(
            '%amount% (tax incl.)',
            array(
                '%amount%' => Tools::displayPrice($cart->getOrderTotal(true, Cart::BOTH)),
            ),
            'Modules.Checkpayment.Admin'
        );

        $checkOrder = Configuration::get('CHEQUE_NAME');
        if (!$checkOrder) {
            $checkOrder = '___________';
        }

        $checkAddress = Tools::nl2br(Configuration::get('CHEQUE_ADDRESS'));
        if (!$checkAddress) {
            $checkAddress = '___________';
        }

        return [
            'checkTotal' => $total,
            'checkOrder' => $checkOrder,
            'checkAddress' => $checkAddress,
        ];
    }

    /**
     * This hook is used to display the order confirmation page.
     */
    public function hookPaymentReturn($params)
    {
        if (!$this->active) return;

        global $smarty, $cart, $cookie;

        $addressdelivery = new Address(intval($cart->id_address_delivery));
        $addressbilling = new Address(intval($cart->id_address_invoice));

        if (version_compare(_PS_VERSION_, '1.7.0.0 ', '<')){
            $order = $params['objOrder'];
            $value = $params['total_to_pay'];
            $currence = $params['currencyObj'];
        }else{
            $order = $params['order'];
            $value = $params['order']->getOrdersTotalPaid();
            $currence = new Currency($params['order']->id_currency);
        }

        $id_order = $_GET['id_order'];

        $currency = $this->getCurrency();
        $idcurrency = $order->id_currency;

        foreach ($currency as $mon) {
            if ($idcurrency == $mon['id_currency']) $currency = $mon['iso_code'];
        }
        $iso = Country::getIsoById($addressdelivery->id_country);

        $refVenta = $order->reference;
        $state = $order->getCurrentState();

        $amount = number_format((float)$cart->getOrderTotal(), 2, '.', '');

        $time = time();

        $hash = md5("$id_order|$amount|$time|$this->_key");

        if ($state) {
            $smarty->assign(array(
                    'this_path_bw' => $this->_path,
                    'total_to_pay' => Tools::displayPrice($value, $currence, false),
                    'refVenta' => $refVenta,
                    'total' => $amount,
                    'orderid' => $id_order,
                    'currency' => $currency,
                    'iso' => $iso,
                    'keyId' => $this->_keyId,
                    'isTest' => $this->isTest,
                    'type' => $this->typePayment,
                    'time' => $time,
                    'hash' => $hash,
                    'custip' => $this->getIpAddress(),
                    'custname' => ($cookie->logged ? $cookie->customer_firstname . ' ' . $cookie->customer_lastname : false),
                    'p_billing_email' => $this->context->customer->email,
                    'p_billing_name' => $this->context->customer->firstname,
                    'p_billing_lastname' => $this->context->customer->lastname,
                    'address' => $addressdelivery->address1,
                    'address_1' => $addressdelivery->address2,
                    'country' => $addressdelivery->country,
                    'state' => State::getNameById($addressdelivery->id_state),
                    'city' => $addressdelivery->city,
                    'postal' => $addressdelivery->postcode,
                    'phone' => $addressdelivery->phone,
                    'response' => Context::getContext()->link->getModuleLink('credomatic', "response"),
                    'restore' => Context::getContext()->link->getModuleLink('credomatic', 'restore')
                )
            );
        } else {
            $smarty->assign('status', 'failed');
        }
        return $this->display(__FILE__, "payment_execution.tpl");
    }

    public function updateOrderState($orderid, $state)
    {
        $id_state=(int)Configuration::get($state);

        $order = new Order($orderid);

        $current_state = $order->current_state;

        if ($current_state != Configuration::get('PS_OS_PAYMENT'))
        {
            $history = new OrderHistory();
            $history->id_order = (int)$order->id;
            $history->date_add = date("Y-m-d H:i:s");
            $history->changeIdOrderState($id_state, (int)$order->id);
            $history->addWithemail(false);
        }
        if ($state != 'PS_OS_PAYMENT')
        {
            foreach ($order->getProductsDetail() as $product)
                StockAvailable::updateQuantity($product['product_id'], $product['product_attribute_id'], + (int)$product['product_quantity'], $order->id_shop);
        }
    }

    public function getOrderStatuses()
    {
        $statuses = OrderState::getOrderStates(Context::getContext()->language->id);
        $result = array();
        foreach ($statuses as $status) {
            $result[$status['id_order_state']] = $status['name'];
        }

        return $result;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency((int)($cart->id_currency));
        $currencies_module = $this->getCurrency((int)$cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getIpAddress()
    {
        // check for shared internet/ISP IP
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && $this->validateIp($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        // check for IPs passing through proxies
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // check if multiple ips exist in var
            if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') !== false) {
                $iplist = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                foreach ($iplist as $ip) {
                    if ($this->validateIp($ip)) {
                        return $ip;
                    }
                }
            } else {
                if ($this->validateIp($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    return $_SERVER['HTTP_X_FORWARDED_FOR'];
                }
            }
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED']) && $this->validateIp($_SERVER['HTTP_X_FORWARDED'])) {
            return $_SERVER['HTTP_X_FORWARDED'];
        }
        if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && $this->validateIp($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
            return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        }
        if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && $this->validateIp($_SERVER['HTTP_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_FORWARDED_FOR'];
        }
        if (!empty($_SERVER['HTTP_FORWARDED']) && $this->validateIp($_SERVER['HTTP_FORWARDED'])) {
            return $_SERVER['HTTP_FORWARDED'];
        }

        // return unreliable ip since all else failed
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Ensures an ip address is both a valid IP and does not fall within
     * a private network range.
     */
    public function validateIp($ip)
    {
        if (Tools::strtolower($ip) === 'unknown') {
            return false;
        }

        // generate ipv4 network address
        $ip = ip2long($ip);

        // if the ip is set and not equivalent to 255.255.255.255
        if ($ip !== false && $ip !== -1) {
            // make sure to get unsigned long representation of ip
            // due to discrepancies between 32 and 64 bit OSes and
            // signed numbers (ints default to signed in PHP)
            $ip = sprintf('%u', $ip);
            // do private network range checking
            if ($ip >= 0 && $ip <= 50331647) {
                return false;
            }
            if ($ip >= 167772160 && $ip <= 184549375) {
                return false;
            }
            if ($ip >= 2130706432 && $ip <= 2147483647) {
                return false;
            }
            if ($ip >= 2851995648 && $ip <= 2852061183) {
                return false;
            }
            if ($ip >= 2886729728 && $ip <= 2887778303) {
                return false;
            }
            if ($ip >= 3221225984 && $ip <= 3221226239) {
                return false;
            }
            if ($ip >= 3232235520 && $ip <= 3232301055) {
                return false;
            }
            if ($ip >= 4294967040) {
                return false;
            }
        }
        return true;
    }
}
