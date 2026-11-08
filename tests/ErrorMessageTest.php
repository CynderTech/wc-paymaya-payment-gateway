<?php
use PHPUnit\Framework\TestCase;

/** Client errors arrive as a string, an object or nothing useful; callers need text in every case. */
class ErrorMessageTest extends TestCase {
    /** @dataProvider shapes */
    public function test_error_message_from_any_shape($response, $expected) {
        $this->assertSame($expected, Cynder_Paymaya_Webhook_Guard::error_message($response));
    }

    public function shapes() {
        return array(
            'string from a transport failure' => array(array('error' => 'cURL error 28: timed out'), 'cURL error 28: timed out'),
            'object with message' => array(array('error' => array('message' => 'Invalid key')), 'Invalid key'),
            'object with error' => array(array('error' => array('error' => 'Bad request')), 'Bad request'),
            'error wins over message' => array(array('error' => array('error' => 'first', 'message' => 'second')), 'first'),
            'object with only a code' => array(array('error' => array('code' => 'PY0016')), 'Maya error code PY0016'),
            'object with nothing usable' => array(array('error' => array('detail' => array('x'))), 'Unexpected response from Maya.'),
            'null error from a non-JSON 4xx body' => array(array('error' => null), 'Unexpected response from Maya.'),
            'empty string' => array(array('error' => ''), 'Unexpected response from Maya.'),
            'non-string message' => array(array('error' => array('message' => array('nested'))), 'Unexpected response from Maya.'),
            'not an array at all' => array(null, 'Unexpected response from Maya.'),
        );
    }

    public function test_custom_fallback_is_used() {
        $this->assertSame('custom', Cynder_Paymaya_Webhook_Guard::error_message(array('error' => null), 'custom'));
    }
}
