<?php
use PHPUnit\Framework\TestCase;

/**
 * Registering webhooks deletes the existing ones and then creates new ones. Two failures made that destructive:
 * unusable URLs left the account with no webhooks, and once it had none, Maya's "no webhooks" reply was treated as a
 * failure so saving the settings could never register them again.
 */
class WebhookRegistrationTest extends TestCase {
    public function test_a_list_of_webhooks_is_returned_as_is() {
        $list = array(array('id' => 'w1', 'name' => 'PAYMENT_SUCCESS'));

        $this->assertSame($list, Cynder_Paymaya_Webhook_Guard::webhook_list($list));
    }

    public function test_an_empty_list_is_an_empty_list() {
        $this->assertSame(array(), Cynder_Paymaya_Webhook_Guard::webhook_list(array()));
    }

    /** @dataProvider noWebhooksReplies */
    public function test_mayas_no_webhooks_reply_means_empty_not_failed($response) {
        $this->assertSame(array(), Cynder_Paymaya_Webhook_Guard::webhook_list($response));
    }

    public static function noWebhooksReplies() {
        return array(
            'as Maya sends it' => array(array('error' => array('code' => 'PY0038', 'message' => 'Webhook does not exist.'))),
            'code as an integer-like value' => array(array('error' => array('code' => 'PY0038'))),
        );
    }

    /** @dataProvider realFailures */
    public function test_real_failures_are_still_failures($response) {
        $this->assertNull(Cynder_Paymaya_Webhook_Guard::webhook_list($response));
    }

    public static function realFailures() {
        return array(
            'bad key' => array(array('error' => array('code' => 'PY0016', 'message' => 'Invalid credentials'))),
            'a different missing thing' => array(array('error' => array('code' => 'PY0039', 'message' => 'Webhook does not exist.'))),
            'transport error' => array(array('error' => 'cURL error 28: timed out')),
            'null error from a non-JSON 4xx' => array(array('error' => null)),
            'not an array' => array(null),
            'scalar' => array('true'),
        );
    }

    public function test_valid_urls_pass() {
        $urls = array(
            'CHECKOUT_SUCCESS' => 'https://example.test/?wc-api=cynder_paymaya',
            'PAYMENT_SUCCESS' => 'http://localhost:8080/?wc-api=cynder_paymaya_payment',
            'PAYMENT_FAILED' => 'https://abcd-1-2-3-4.ngrok-free.app/?wc-api=cynder_paymaya_payment',
        );

        $this->assertSame(array(), Cynder_Paymaya_Webhook_Guard::invalid_webhook_urls($urls));
    }

    /** @dataProvider unusableUrls */
    public function test_unusable_urls_are_reported_by_webhook_name($url) {
        $this->assertSame(array('PAYMENT_SUCCESS'), Cynder_Paymaya_Webhook_Guard::invalid_webhook_urls(array('CHECKOUT_SUCCESS' => 'https://example.test/', 'PAYMENT_SUCCESS' => $url)));
    }

    public static function unusableUrls() {
        return array(
            'empty' => array(''),
            'no scheme' => array('example.test/?wc-api=cynder_paymaya'),
            'relative path' => array('/?wc-api=cynder_paymaya'),
            'wrong scheme' => array('ftp://example.test/hook'),
            'javascript scheme' => array('javascript:alert(1)'),
            'surrounding space' => array(' https://example.test/ '),
            'inner space' => array('https://exa mple.test/'),
            'host missing' => array('https://'),
            'not a string' => array(null),
        );
    }

    public function test_every_bad_url_is_listed() {
        $this->assertSame(
            array('CHECKOUT_SUCCESS', 'PAYMENT_FAILED'),
            Cynder_Paymaya_Webhook_Guard::invalid_webhook_urls(array('CHECKOUT_SUCCESS' => '', 'PAYMENT_SUCCESS' => 'https://example.test/', 'PAYMENT_FAILED' => 'nope'))
        );
    }
}
