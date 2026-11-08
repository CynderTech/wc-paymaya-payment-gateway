<?php
use PHPUnit\Framework\TestCase;

/**
 * Reference numbers are WooCommerce order ids, so look-alike payments from other sites must never be chosen.
 * The caller is about to refund, void or capture, so "not found" must stay "not found".
 */
class PickPaymentTest extends TestCase {
    private function payments() {
        return array(
            array('id' => 'stranger-1', 'status' => 'PAYMENT_SUCCESS'),
            array('id' => 'mine', 'status' => 'PAYMENT_SUCCESS'),
            array('id' => 'stranger-2', 'status' => 'PAYMENT_SUCCESS'),
        );
    }

    public function test_prefers_the_payment_the_order_recorded() {
        $this->assertSame('mine', Cynder_Paymaya_Webhook_Guard::pick_payment($this->payments(), 'mine')['id']);
    }

    public function test_a_recorded_id_that_is_not_among_the_candidates_returns_null() {
        $this->assertNull(Cynder_Paymaya_Webhook_Guard::pick_payment($this->payments(), 'not-in-the-list'));
    }

    /** @dataProvider noRecordedTransactionId */
    public function test_without_a_recorded_id_the_first_valid_payment_is_used($transactionId) {
        $this->assertSame('stranger-1', Cynder_Paymaya_Webhook_Guard::pick_payment($this->payments(), $transactionId)['id']);
    }

    public static function noRecordedTransactionId() {
        return array('empty string' => array(''), 'null' => array(null), 'non-string' => array(123));
    }

    public function test_array_keys_are_ignored() {
        $this->assertSame('b', Cynder_Paymaya_Webhook_Guard::pick_payment(array(5 => array('id' => 'a'), 9 => array('id' => 'b')), 'b')['id']);
        $this->assertSame('a', Cynder_Paymaya_Webhook_Guard::pick_payment(array(5 => array('id' => 'a')), '')['id']);
    }

    public function test_empty_list_returns_null() {
        $this->assertNull(Cynder_Paymaya_Webhook_Guard::pick_payment(array(), 'mine'));
        $this->assertNull(Cynder_Paymaya_Webhook_Guard::pick_payment(array(), ''));
    }

    public function test_malformed_entries_are_skipped_and_never_returned() {
        $junk = array('junk', array('no-id' => 1), null);

        $this->assertSame('mine', Cynder_Paymaya_Webhook_Guard::pick_payment(array_merge($junk, array(array('id' => 'mine'))), 'mine')['id']);
        $this->assertNull(Cynder_Paymaya_Webhook_Guard::pick_payment($junk, ''));
        $this->assertNull(Cynder_Paymaya_Webhook_Guard::pick_payment($junk, 'mine'));
    }
}
