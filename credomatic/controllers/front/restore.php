<?php
class CredomaticRestoreModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        if(isset($_GET['idorder'])){
            $id_cart = $_GET['idorder'];
        }else{
            return;
        }
        $order = new Order(Order::getOrderByCartId($id_cart));
        if ($order) {
            $oldCart = new Cart($id_cart);
            $duplication = $oldCart->duplicate();
            if (!$duplication || !Validate::isLoadedObject($duplication['cart'])) {
                $this->errors[] = Tools::displayError('Sorry. We cannot renew your order.');
            } elseif (!$duplication['success']) {
                $this->errors[] = Tools::displayError('Some items are no longer available, and we are unable to renew your order.');
            } else {
                $this->context->cookie->id_cart = $duplication['cart']->id;
                $context = $this->context;
                $context->cart = $duplication['cart'];
                CartRule::autoAddToCart($context);
                $this->context->cookie->write();
                if (Configuration::get('PS_ORDER_PROCESS_TYPE') == 1) {
                    Tools::redirect('index.php?controller=order-opc');
                }
                Tools::redirect('index.php?controller=order');
            }
        }
    }
}