<?php
use PHPUnit\Framework\TestCase;

/** How API keys are chosen at runtime and stored on save (constants in wp-config.php vs the database). */
class KeyResolutionTest extends TestCase {
    public function test_saved_settings_are_used_when_no_constant_is_defined() {
        $this->assertSame(array('pk-saved', 'sk-saved'), Cynder_Paymaya_Webhook_Guard::resolve_api_keys(
            array('public_key' => 'pk-saved', 'secret_key' => 'sk-saved'), null, null
        ));
    }

    public function test_constants_win_over_saved_settings_independently() {
        $settings = array('public_key' => 'pk-saved', 'secret_key' => 'sk-saved');

        $this->assertSame(array('pk-const', 'sk-saved'), Cynder_Paymaya_Webhook_Guard::resolve_api_keys($settings, 'pk-const', null));
        $this->assertSame(array('pk-saved', 'sk-const'), Cynder_Paymaya_Webhook_Guard::resolve_api_keys($settings, null, 'sk-const'));
        $this->assertSame(array('pk-const', 'sk-const'), Cynder_Paymaya_Webhook_Guard::resolve_api_keys($settings, 'pk-const', 'sk-const'));
    }

    public function test_missing_or_invalid_settings_give_empty_keys() {
        $this->assertSame(array('', ''), Cynder_Paymaya_Webhook_Guard::resolve_api_keys(array(), null, null));
        $this->assertSame(array('', ''), Cynder_Paymaya_Webhook_Guard::resolve_api_keys(null, null, null));
        $this->assertSame(array('', ''), Cynder_Paymaya_Webhook_Guard::resolve_api_keys('not an array', null, null));
    }

    public function test_an_empty_constant_still_wins() {
        $this->assertSame(array('', ''), Cynder_Paymaya_Webhook_Guard::resolve_api_keys(array('public_key' => 'pk-a', 'secret_key' => 'sk-a'), '', ''));
    }

    public function test_blank_secret_keeps_the_saved_secret() {
        $this->assertSame('sk-saved', Cynder_Paymaya_Webhook_Guard::resolve_key_field('', 'sk-saved', true, false));
        $this->assertSame('sk-saved', Cynder_Paymaya_Webhook_Guard::resolve_key_field('   ', 'sk-saved', true, false));
        $this->assertSame('sk-saved', Cynder_Paymaya_Webhook_Guard::resolve_key_field(null, 'sk-saved', true, false));
    }

    public function test_a_new_secret_replaces_the_saved_one_and_is_trimmed() {
        $this->assertSame('sk-new', Cynder_Paymaya_Webhook_Guard::resolve_key_field('  sk-new ', 'sk-saved', true, false));
    }

    public function test_remove_clears_a_saved_secret() {
        $this->assertSame('', Cynder_Paymaya_Webhook_Guard::resolve_key_field('', 'sk-saved', true, false, true));
    }

    public function test_a_typed_key_wins_over_remove() {
        // Ticking "Remove the saved key" while typing a new one means "replace", not "leave me with no key".
        $this->assertSame('sk-new', Cynder_Paymaya_Webhook_Guard::resolve_key_field('sk-new', 'sk-saved', true, false, true));
    }

    public function test_a_blank_public_key_is_cleared_not_kept() {
        $this->assertSame('', Cynder_Paymaya_Webhook_Guard::resolve_key_field('', 'pk-saved', false, false));
    }

    public function test_a_key_defined_by_constant_wipes_the_database_copy() {
        $this->assertSame('', Cynder_Paymaya_Webhook_Guard::resolve_key_field('', 'sk-saved', true, true));
        $this->assertSame('', Cynder_Paymaya_Webhook_Guard::resolve_key_field('sk-attacker', 'sk-saved', true, true));
        $this->assertSame('', Cynder_Paymaya_Webhook_Guard::resolve_key_field('pk-x', 'pk-saved', false, true));
    }

    /** @dataProvider keyPrefixes */
    public function test_key_prefix_check($value, $isSecret, $expected) {
        $this->assertSame($expected, Cynder_Paymaya_Webhook_Guard::key_looks_valid($value, $isSecret));
    }

    public static function keyPrefixes() {
        return array(
            'secret ok' => array('sk-abc', true, true),
            'public ok' => array('pk-abc', false, true),
            'public key in the secret field' => array('pk-abc', true, false),
            'wordpress password in the secret field' => array('Correct-Horse-9!', true, false),
            'empty' => array('', true, false),
            'prefix not at the start' => array('xsk-abc', true, false),
            'not a string' => array(null, true, false),
        );
    }
}
