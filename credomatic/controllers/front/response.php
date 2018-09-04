<?php

class CredomaticResponseModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $credomatic = new Credomatic();

        if (empty($_GET)) exit;

        if (!isset($_GET['hash']) || !isset($_GET['response']) || !isset($_GET['cvvresponse'])) exit;

        $orderid = $_GET['orderid'];
        $response = $_GET['response'];

        $security_key = Configuration::get('CREDOMATIC_SECURITY_KEY' );

        $hash = $_GET['hash'];
        $hashLocal = md5("$orderid|{$_GET['amount']}|$response|{$_GET['transactionid']}|{$_GET['avsresponse']}|{$_GET['cvvresponse']}|{$_GET['time']}|$security_key");

        if ($hash != $hashLocal) exit;

        $state = 'PS_OS_PAYMENT';

        switch ($response ){
            case 2:
                $state = 'CREDOMATIC_OS_REJECTED';
                break;
            case 3:
                $state = 'CREDOMATIC_OS_FAILED';
        }

        $credomatic->updateOrderState($orderid, $state);

        Tools::redirect('index.php?controller=history');

        die();
    }
}