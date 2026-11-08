<?php
use PHPUnit\Framework\TestCase;

/** Reference numbers are WooCommerce order ids, so look-alike payments from other sites must not be chosen. */
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

    /** @dataProvider noUsableTransactionId */
    public function test_falls_back_to_the_first_payment($transactionId) {
        $this->assertSame('stranger-1', Cynder_Paymaya_Webhook_Guard::pick_payment($this->payments(), $transactionId)['id']);
    }

    public function noUsableTransactionId() {
        return array('empty' => array(''), 'null' => array(null), 'unknown id' => array('not-in-the-list'), 'non-string' => array(123));
    }

    public function test_keys_are_ignored_and_empty_list_gives_null() {
        $this->assertSame('b', Cynder_Paymaya_Webhook_Guard::pick_payment(array(5 => array('id' => 'a'), 9 => array('id' => 'b')), 'b')['id']);
        $this->assertSame('a', Cynder_Paymaya_Webhook_Guard::pick_payment(array(5 => array('id' => 'a')), '')['id']);
        $this->assertNull(Cynder_Paymaya_Webhook_Guard::pick_payment(array(), 'mine'));
    }

    public function test_malformed_entries_do_not_crash() {
        $this->assertSame('mine', Cynder_Paymaya_Webhook_Guard::pick_payment(array('junk', array('no-id' => 1), array('id' => 'mine')), 'mine')['id']);
    }
}
