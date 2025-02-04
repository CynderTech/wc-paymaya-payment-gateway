<?php
/**
 * PHP version 7
 * 
 * Paymaya Payment Plugin
 * 
 * @category Plugin
 * @package  Paymaya
 * @author   Cyndertech <devops@cynder.io>
 * @license  n/a (http://127.0.0.0)
 * @link     n/a
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
$fileDir = dirname(__FILE__);
include_once $fileDir.'/cynder-paymaya.php';

final class Cynder_Paymaya_WC_Blocks extends AbstractPaymentMethodType {
    private $gateway;
    protected $name = 'cynder-paymaya';
    public function initialize() {
        $this->settings = get_option( 'woocommerce_paymaya_settings', [] );
        $this->gateway = new Cynder_Paymaya_Gateway();
    }
    public function is_active() {
        return $this->gateway->is_available();
    }
    public function get_payment_method_script_handles() {
        wp_register_script(
            'wc-paymaya-blocks-integration',
            plugins_url('assets/js/checkout.js', CYNDER_PAYMAYA_MAIN_FILE),
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );
        return [ 'wc-paymaya-blocks-integration' ];
    }
    public function get_payment_method_data() {
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
        ];
    }
}
