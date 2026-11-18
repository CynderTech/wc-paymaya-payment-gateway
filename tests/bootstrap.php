<?php
define('CYNDER_PAYMAYA_TESTING', true);

/** The production files exit when loaded outside WordPress; the tests stand in for it. */
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/cynder-paymaya-webhook-guard.php';

/** Minimal WordPress stand-ins so the API client can be loaded without WordPress. */
if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return $thing instanceof Cynder_Paymaya_Test_WP_Error;
    }
}

if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response) {
        return isset($response['response']['code']) ? $response['response']['code'] : '';
    }
}

class Cynder_Paymaya_Test_WP_Error {
    private $message;

    public function __construct($message) {
        $this->message = $message;
    }

    public function get_error_message() {
        return $this->message;
    }
}

require_once __DIR__ . '/../classes/paymaya-client.php';
