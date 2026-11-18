<?php
use PHPUnit\Framework\TestCase;

class LogRedactionTest extends TestCase {
    public function test_buyer_personal_data_is_masked_at_any_depth() {
        $payload = array(
            'totalAmount' => array('value' => 3018, 'currency' => 'PHP'),
            'buyer' => array(
                'firstName' => 'Juan',
                'lastName' => 'Dela Cruz',
                'contact' => array('phone' => '09170000000', 'email' => 'juan@example.test'),
                'shippingAddress' => array('line1' => '1 Sample St', 'line2' => 'Unit 2', 'city' => 'Quezon City', 'zipCode' => '1100', 'countryCode' => 'PH'),
            ),
            'requestReferenceNumber' => '1810',
        );

        $redacted = Cynder_Paymaya_Webhook_Guard::redact_for_log($payload);

        $this->assertSame('[redacted]', $redacted['buyer']['firstName']);
        $this->assertSame('[redacted]', $redacted['buyer']['lastName']);
        $this->assertSame('[redacted]', $redacted['buyer']['contact']['phone']);
        $this->assertSame('[redacted]', $redacted['buyer']['contact']['email']);
        $this->assertSame('[redacted]', $redacted['buyer']['shippingAddress']['line1']);
        $this->assertSame('[redacted]', $redacted['buyer']['shippingAddress']['line2']);
        $this->assertSame('[redacted]', $redacted['buyer']['shippingAddress']['zipCode']);
    }

    public function test_values_needed_for_debugging_are_kept() {
        $redacted = Cynder_Paymaya_Webhook_Guard::redact_for_log(array(
            'id' => 'abc',
            'status' => 'PAYMENT_SUCCESS',
            'amount' => '100',
            'currency' => 'PHP',
            'requestReferenceNumber' => '1573',
            'buyer' => array('shippingAddress' => array('city' => 'Quezon City', 'countryCode' => 'PH')),
        ));

        $this->assertSame('abc', $redacted['id']);
        $this->assertSame('PAYMENT_SUCCESS', $redacted['status']);
        $this->assertSame('100', $redacted['amount']);
        $this->assertSame('1573', $redacted['requestReferenceNumber']);
        $this->assertSame('Quezon City', $redacted['buyer']['shippingAddress']['city']);
    }

    public function test_payment_list_descriptions_tokens_and_order_keys_are_masked() {
        $redacted = Cynder_Paymaya_Webhook_Guard::redact_for_log(array(
            array('id' => 'p1', 'description' => 'Charge for someone@example.test', 'paymentTokenId' => 'tok_123'),
            array('wc-api' => 'cynder_paymaya_catch_redirect', 'order' => '75', 'key' => 'wc_order_abc'),
        ));

        $this->assertSame('[redacted]', $redacted[0]['description']);
        $this->assertSame('[redacted]', $redacted[0]['paymentTokenId']);
        $this->assertSame('[redacted]', $redacted[1]['key']);
        $this->assertSame('75', $redacted[1]['order']);
    }

    public function test_empty_values_and_non_arrays_pass_through() {
        $this->assertSame(array('line2' => '', 'email' => null), Cynder_Paymaya_Webhook_Guard::redact_for_log(array('line2' => '', 'email' => null)));
        $this->assertSame('plain string', Cynder_Paymaya_Webhook_Guard::redact_for_log('plain string'));
        $this->assertNull(Cynder_Paymaya_Webhook_Guard::redact_for_log(null));
    }

    public function test_further_buyer_and_card_fields_are_masked() {
        $redacted = Cynder_Paymaya_Webhook_Guard::redact_for_log(array(
            'buyer' => array('firstName' => 'Juan', 'middleName' => 'Santos', 'birthday' => '1990-01-01', 'ipAddress' => '203.0.113.9'),
            'fundSource' => array('type' => 'card', 'details' => array('scheme' => 'master-card', 'first6' => '412345', 'last4' => '1381', 'masked' => '412345******1381')),
        ));

        $this->assertSame('[redacted]', $redacted['buyer']['middleName']);
        $this->assertSame('[redacted]', $redacted['buyer']['birthday']);
        $this->assertSame('[redacted]', $redacted['buyer']['ipAddress']);
        $this->assertSame('[redacted]', $redacted['fundSource']['details']['first6']);
        $this->assertSame('[redacted]', $redacted['fundSource']['details']['masked']);
        // The scheme and last 4 digits stay: support needs them and they identify no one on their own.
        $this->assertSame('master-card', $redacted['fundSource']['details']['scheme']);
        $this->assertSame('1381', $redacted['fundSource']['details']['last4']);
    }
}
