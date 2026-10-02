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

    public function test_proxy_header_is_ignored_without_trusted_ranges() {
        $server = array('REMOTE_ADDR' => '10.0.0.5', 'HTTP_X_FORWARDED_FOR' => '18.138.50.235');
        $this->assertSame('10.0.0.5', Cynder_Paymaya_Webhook_Guard::resolve_source_ip($server, 'x-forwarded-for', array()));
    }

    public function test_proxy_header_is_ignored_when_peer_is_not_a_trusted_proxy() {
        $server = array('REMOTE_ADDR' => '203.0.113.9', 'HTTP_X_FORWARDED_FOR' => '18.138.50.235');
        $this->assertSame('203.0.113.9', Cynder_Paymaya_Webhook_Guard::resolve_source_ip($server, 'x-forwarded-for', array('10.0.0.0/8')));
    }

    public function test_proxy_header_is_used_when_peer_is_a_trusted_proxy() {
        $server = array('REMOTE_ADDR' => '10.0.0.5', 'HTTP_X_FORWARDED_FOR' => '18.138.50.235');
        $this->assertSame('18.138.50.235', Cynder_Paymaya_Webhook_Guard::resolve_source_ip($server, 'x-forwarded-for', array('10.0.0.0/8')));

        $server = array('REMOTE_ADDR' => '10.0.0.5', 'HTTP_X_REAL_IP' => '3.1.207.200');
        $this->assertSame('3.1.207.200', Cynder_Paymaya_Webhook_Guard::resolve_source_ip($server, 'x-real-ip', array('10.0.0.0/8')));
    }

    public function test_only_the_configured_header_is_read() {
        $server = array('REMOTE_ADDR' => '10.0.0.5', 'HTTP_X_FORWARDED_FOR' => '18.138.50.235');
        $this->assertSame('10.0.0.5', Cynder_Paymaya_Webhook_Guard::resolve_source_ip($server, 'x-real-ip', array('10.0.0.0/8')));
        $this->assertSame('10.0.0.5', Cynder_Paymaya_Webhook_Guard::resolve_source_ip($server, 'bogus', array('10.0.0.0/8')));
    }

    /** A client-supplied leading entry must not win over what the proxy appended. */
    public function test_forwarded_for_uses_rightmost_untrusted_hop() {
        $server = array('REMOTE_ADDR' => '10.0.0.5', 'HTTP_X_FORWARDED_FOR' => '18.138.50.235, 203.0.113.77, 10.0.0.9');
        $this->assertSame('203.0.113.77', Cynder_Paymaya_Webhook_Guard::resolve_source_ip($server, 'x-forwarded-for', array('10.0.0.0/8')));
    }

    public function test_garbage_in_forwarded_chain_falls_back_to_peer() {
        $server = array('REMOTE_ADDR' => '10.0.0.5', 'HTTP_X_FORWARDED_FOR' => '18.138.50.235, nonsense');
        $this->assertSame('10.0.0.5', Cynder_Paymaya_Webhook_Guard::resolve_source_ip($server, 'x-forwarded-for', array('10.0.0.0/8')));
    }

    public function test_parse_ranges() {
        $this->assertSame(
            array('10.0.0.0/8', '192.168.1.5/32', '2001:db8::/32', '2001:db8::1/128'),
            Cynder_Paymaya_Webhook_Guard::parse_ranges("10.0.0.0/8\n192.168.1.5, 2001:db8::/32  2001:db8::1")
        );
        $this->assertSame(array(), Cynder_Paymaya_Webhook_Guard::parse_ranges("nonsense\n10.0.0.0/99\n1.2.3.4/x\n"));
        $this->assertSame(array(), Cynder_Paymaya_Webhook_Guard::parse_ranges(''));
    }

    private function mayaPayment(array $override = array()) {
        return array_merge(array('id' => 'p1', 'status' => 'PAYMENT_SUCCESS', 'amount' => 150.5, 'currency' => 'PHP', 'requestReferenceNumber' => '123'), $override);
    }

    public function test_payment_confirmation_accepts_matching_payment() {
        $this->assertTrue(Cynder_Paymaya_Webhook_Guard::is_payment_confirmed($this->mayaPayment(), 150.5, 'PHP', '123', array('PAYMENT_SUCCESS')));
        $this->assertTrue(Cynder_Paymaya_Webhook_Guard::is_payment_confirmed($this->mayaPayment(array('status' => 'CAPTURED', 'requestReferenceNumber' => 123)), '150.50', 'php', 123, array('AUTHORIZED', 'CAPTURED', 'DONE')));
    }

    /** @dataProvider mismatchedPayments */
    public function test_payment_confirmation_rejects_mismatch($maya, $total, $currency, $reference, $statuses) {
        $this->assertFalse(Cynder_Paymaya_Webhook_Guard::is_payment_confirmed($maya, $total, $currency, $reference, $statuses));
    }

    public function mismatchedPayments() {
        return array(
            'failed status' => array($this->mayaPayment(array('status' => 'PAYMENT_FAILED')), 150.5, 'PHP', '123', array('PAYMENT_SUCCESS')),
            'status not allowed here' => array($this->mayaPayment(array('status' => 'AUTHORIZED')), 150.5, 'PHP', '123', array('PAYMENT_SUCCESS')),
            'other order' => array($this->mayaPayment(array('requestReferenceNumber' => '124')), 150.5, 'PHP', '123', array('PAYMENT_SUCCESS')),
            'wrong amount' => array($this->mayaPayment(array('amount' => 1)), 150.5, 'PHP', '123', array('PAYMENT_SUCCESS')),
            'missing id' => array($this->mayaPayment(array('id' => '')), 150.5, 'PHP', '123', array('PAYMENT_SUCCESS')),
            'wrong currency' => array($this->mayaPayment(array('currency' => 'USD')), 150.5, 'PHP', '123', array('PAYMENT_SUCCESS')),
            'missing currency' => array(array('id' => 'p1', 'status' => 'PAYMENT_SUCCESS', 'amount' => 150.5, 'requestReferenceNumber' => '123'), 150.5, 'PHP', '123', array('PAYMENT_SUCCESS')),
            'missing amount' => array(array('id' => 'p1', 'status' => 'PAYMENT_SUCCESS', 'currency' => 'PHP', 'requestReferenceNumber' => '123'), 150.5, 'PHP', '123', array('PAYMENT_SUCCESS')),
            'not an array' => array('PAYMENT_SUCCESS', 150.5, 'PHP', '123', array('PAYMENT_SUCCESS')),
        );
    }
}
