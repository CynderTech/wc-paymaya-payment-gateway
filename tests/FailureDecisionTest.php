<?php
use PHPUnit\Framework\TestCase;

/** What a payment webhook that is not a success does to an order. */
class FailureDecisionTest extends TestCase {
    /** @dataProvider unpaidNormalOrders */
    public function test_unpaid_normal_orders_are_marked_failed_so_the_customer_can_retry($status, $note) {
        $this->assertSame(array('failed', $note), Cynder_Paymaya_Webhook_Guard::failure_decision($status, false, false));
    }

    public static function unpaidNormalOrders() {
        return array(
            'failed' => array('PAYMENT_FAILED', 'Payment failed'),
            'auth failed' => array('AUTH_FAILED', 'Payment failed'),
            'expired' => array('PAYMENT_EXPIRED', 'Payment expired'),
            'cancelled' => array('PAYMENT_CANCELLED', 'Payment cancelled'),
        );
    }

    /** @dataProvider failureStatuses */
    public function test_manual_capture_orders_go_on_hold($status) {
        $this->assertSame(array('on-hold', 'Failed payment'), Cynder_Paymaya_Webhook_Guard::failure_decision($status, false, true));
    }

    /** @dataProvider failureStatuses */
    public function test_an_already_paid_order_is_never_changed($status) {
        $this->assertSame('ignore', Cynder_Paymaya_Webhook_Guard::failure_decision($status, true, false)[0]);
        $this->assertSame('ignore', Cynder_Paymaya_Webhook_Guard::failure_decision($status, true, true)[0]);
    }

    public static function failureStatuses() {
        return array(
            'failed' => array('PAYMENT_FAILED'),
            'auth failed' => array('AUTH_FAILED'),
            'expired' => array('PAYMENT_EXPIRED'),
            'cancelled' => array('PAYMENT_CANCELLED'),
        );
    }

    /** @dataProvider nonFailureStatuses */
    public function test_other_statuses_are_not_failures($status) {
        $this->assertSame(array('none', ''), Cynder_Paymaya_Webhook_Guard::failure_decision($status, false, false));
        $this->assertSame(array('none', ''), Cynder_Paymaya_Webhook_Guard::failure_decision($status, true, true));
    }

    public static function nonFailureStatuses() {
        return array(
            'success' => array('PAYMENT_SUCCESS'),
            'authorized' => array('AUTHORIZED'),
            'processing' => array('PAYMENT_PROCESSING'),
            'unknown' => array('SOMETHING_NEW'),
            'empty' => array(''),
        );
    }
}
