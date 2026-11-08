<?php
use PHPUnit\Framework\TestCase;

/**
 * Every client call goes through handleResponse(), and callers run array_key_exists('error', ...) on the result.
 * On PHP 8 that throws a TypeError for null or scalars, so the result must always be an array.
 */
class ClientResponseTest extends TestCase {
    private function handle($response) {
        $client = new Cynder_PaymayaClient(true, 'pk-test', 'sk-test');
        $method = new ReflectionMethod($client, 'handleResponse');

        /** Needed before PHP 8.1; a no-op from 8.1 and deprecated in 8.5. */
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        return $method->invoke($client, $response);
    }

    private function response($code, $body) {
        return array('response' => array('code' => $code), 'body' => $body);
    }

    /** @dataProvider unusableBodies */
    public function test_unusable_success_body_becomes_an_error($body) {
        $result = $this->handle($this->response(200, $body));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    public function unusableBodies() {
        return array(
            'empty' => array(''),
            'html from a proxy or waf' => array('<html><body>Blocked</body></html>'),
            'json null' => array('null'),
            'json scalar' => array('true'),
            'json number' => array('42'),
            'json string' => array('"ok"'),
            'truncated json' => array('{"checkoutId":'),
        );
    }

    public function test_valid_object_is_returned_unchanged() {
        $result = $this->handle($this->response(200, '{"checkoutId":"abc","redirectUrl":"https://example.test"}'));

        $this->assertSame(array('checkoutId' => 'abc', 'redirectUrl' => 'https://example.test'), $result);
    }

    public function test_empty_list_is_not_an_error() {
        $result = $this->handle($this->response(200, '[]'));

        $this->assertSame(array(), $result);
        $this->assertArrayNotHasKey('error', $result);
    }

    public function test_http_error_wraps_decoded_body() {
        $result = $this->handle($this->response(400, '{"message":"Invalid request"}'));

        $this->assertSame(array('error' => array('message' => 'Invalid request')), $result);
    }

    public function test_transport_error_is_reported() {
        $result = $this->handle(new Cynder_Paymaya_Test_WP_Error('cURL error 28: timed out'));

        $this->assertSame(array('error' => 'cURL error 28: timed out'), $result);
    }
}
