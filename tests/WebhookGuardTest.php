<?php
use PHPUnit\Framework\TestCase;

class WebhookGuardTest extends TestCase {
    private static $privateKey;
    private static $publicKey;

    public static function setUpBeforeClass(): void {
        self::$privateKey = openssl_pkey_new(array('private_key_bits' => 2048));
        self::$publicKey = openssl_pkey_get_details(self::$privateKey)['key'];
    }

    private function sign($data) {
        openssl_sign($data, $signature, self::$privateKey, 'sha256WithRSAEncryption');
        return bin2hex($signature);
    }

    public function test_valid_signature_is_accepted() {
        $this->assertTrue(Cynder_Paymaya_Webhook_Guard::is_valid_signature('a=1&nonce=x', $this->sign('a=1&nonce=x'), array(self::$publicKey)));
    }

    public function test_valid_signature_matches_any_key() {
        $other = openssl_pkey_get_details(openssl_pkey_new(array('private_key_bits' => 2048)))['key'];
        $this->assertTrue(Cynder_Paymaya_Webhook_Guard::is_valid_signature('d', $this->sign('d'), array($other, self::$publicKey)));
    }

    public function test_tampered_payload_is_rejected() {
        $this->assertFalse(Cynder_Paymaya_Webhook_Guard::is_valid_signature('a=2&nonce=x', $this->sign('a=1&nonce=x'), array(self::$publicKey)));
    }

    /** @dataProvider malformedSignatures */
    public function test_malformed_signature_is_rejected($signature) {
        $this->assertFalse(Cynder_Paymaya_Webhook_Guard::is_valid_signature('a=1&nonce=x', $signature, array(self::$publicKey)));
    }

    public function malformedSignatures() {
        return array(
            'one byte' => array('00'),
            'empty' => array(''),
            'odd length' => array('abc'),
            'non hex' => array('zz'),
            'null' => array(null),
            'right size junk' => array(str_repeat('ab', 256)),
        );
    }

    /** openssl_verify() returns -1 on error (here: unusable key); that must never count as valid. */
    public function test_openssl_error_is_not_treated_as_valid() {
        $this->assertFalse(Cynder_Paymaya_Webhook_Guard::is_valid_signature('d', $this->sign('d'), array('not a key')));
    }

    public function test_no_keys_is_rejected() {
        $this->assertFalse(Cynder_Paymaya_Webhook_Guard::is_valid_signature('d', $this->sign('d'), array()));
    }

    public function test_forwarding_headers_are_ignored() {
        $server = array(
            'REMOTE_ADDR' => '203.0.113.9',
            'HTTP_X_FORWARDED_FOR' => '18.138.50.235',
            'HTTP_X_CLIENT_IP' => '18.138.50.235',
            'HTTP_CLIENT_IP' => '18.138.50.235',
            'HTTP_X_FORWARDED_BY' => '18.138.50.235',
        );
        $this->assertSame('203.0.113.9', Cynder_Paymaya_Webhook_Guard::resolve_source_ip($server));
    }

    public function test_cf_header_is_ignored_when_peer_is_not_cloudflare() {
        $server = array('REMOTE_ADDR' => '203.0.113.9', 'HTTP_CF_CONNECTING_IP' => '18.138.50.235');
        $this->assertSame('203.0.113.9', Cynder_Paymaya_Webhook_Guard::resolve_source_ip($server));
    }

    public function test_cf_header_is_used_when_peer_is_cloudflare() {
        $server = array('REMOTE_ADDR' => '172.70.1.1', 'HTTP_CF_CONNECTING_IP' => '18.138.50.235');
        $this->assertSame('18.138.50.235', Cynder_Paymaya_Webhook_Guard::resolve_source_ip($server));

        $server = array('REMOTE_ADDR' => '2606:4700::1', 'HTTP_CF_CONNECTING_IP' => '3.1.207.200');
        $this->assertSame('3.1.207.200', Cynder_Paymaya_Webhook_Guard::resolve_source_ip($server));
    }

    public function test_invalid_cf_header_falls_back_to_peer() {
        $server = array('REMOTE_ADDR' => '172.70.1.1', 'HTTP_CF_CONNECTING_IP' => 'not-an-ip');
        $this->assertSame('172.70.1.1', Cynder_Paymaya_Webhook_Guard::resolve_source_ip($server));
    }

    public function test_missing_remote_addr_yields_empty_source() {
        $this->assertSame('', Cynder_Paymaya_Webhook_Guard::resolve_source_ip(array('HTTP_CF_CONNECTING_IP' => '18.138.50.235')));
    }

    public function test_cidr_boundaries() {
        $this->assertTrue(Cynder_Paymaya_Webhook_Guard::is_ip_in_cidr('173.245.63.255', '173.245.48.0/20'));
        $this->assertFalse(Cynder_Paymaya_Webhook_Guard::is_ip_in_cidr('173.245.64.0', '173.245.48.0/20'));
        $this->assertFalse(Cynder_Paymaya_Webhook_Guard::is_ip_in_cidr('::1', '173.245.48.0/20'));
        $this->assertFalse(Cynder_Paymaya_Webhook_Guard::is_ip_in_cidr('garbage', '173.245.48.0/20'));
        $this->assertFalse(Cynder_Paymaya_Webhook_Guard::is_ip_in_cidr('1.2.3.4', '1.2.3.4'));
    }
}
