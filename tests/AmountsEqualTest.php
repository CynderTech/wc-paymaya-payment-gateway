<?php
use PHPUnit\Framework\TestCase;

/** Used by order completion, refund and void to compare money to the cent. */
class AmountsEqualTest extends TestCase {
    /** @dataProvider equalAmounts */
    public function test_equal_to_the_cent($a, $b) {
        $this->assertTrue(Cynder_Paymaya_Webhook_Guard::amounts_equal($a, $b));
    }

    public static function equalAmounts() {
        return array(
            'string and float' => array('100', 100.0),
            'padded decimals' => array('150.50', 150.5),
            'float noise' => array(0.1 + 0.2, '0.30'),
            'webhook number vs woocommerce total string' => array(10, '10.00'),
            'under half a cent apart' => array(100.004, 100.00),
        );
    }

    /** @dataProvider differentAmounts */
    public function test_different_by_a_cent_or_more($a, $b) {
        $this->assertFalse(Cynder_Paymaya_Webhook_Guard::amounts_equal($a, $b));
    }

    public static function differentAmounts() {
        return array(
            'one cent over' => array(100.01, 100),
            'one cent under' => array('99.99', '100.00'),
            'half refund' => array(50, 100),
            'zero vs positive' => array(0, '0.01'),
        );
    }
}
