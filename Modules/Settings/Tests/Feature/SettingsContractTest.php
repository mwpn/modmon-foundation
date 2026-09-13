<?php

declare(strict_types=1);

namespace Modules\Settings\Tests\Feature;

use InvalidArgumentException;
use Modules\Settings\Domain\Contracts\RuntimeSettingsContract;
use stdClass;

/**
 * RuntimeSettingsContract behavior for JSON-serializable key/value store.
 */
class SettingsContractTest extends SettingsTestCase
{
    private RuntimeSettingsContract $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installSettings();
        $this->settings = app(RuntimeSettingsContract::class);
    }

    public function test_get_returns_default_for_missing_key(): void
    {
        $this->assertSame('fallback', $this->settings->get('missing.key', 'fallback'));
        $this->assertNull($this->settings->get('missing.key'));
    }

    public function test_set_get_has_round_trip_scalars_and_arrays(): void
    {
        $this->settings->set('settings.app.name', 'ModMon');
        $this->settings->set('billing.tax.rate', 11);
        $this->settings->set('billing.enabled', true);
        $this->settings->set('billing.meta', ['currency' => 'IDR', 'nested' => [1, 2]]);
        $this->settings->set('settings.app.tagline', null);

        $this->assertSame('ModMon', $this->settings->get('settings.app.name'));
        $this->assertSame(11, $this->settings->get('billing.tax.rate'));
        $this->assertTrue($this->settings->get('billing.enabled'));
        $this->assertSame(['currency' => 'IDR', 'nested' => [1, 2]], $this->settings->get('billing.meta'));
        $this->assertNull($this->settings->get('settings.app.tagline'));
        $this->assertTrue($this->settings->has('settings.app.tagline'));
    }

    public function test_round_trip_edge_scalars_and_nested_arrays(): void
    {
        $nested = [
            'a' => [
                'b' => [null, false, 0, '', 'x'],
                'c' => ['d' => ['e' => 1]],
            ],
        ];

        $this->settings->set('edge.null', null);
        $this->settings->set('edge.false', false);
        $this->settings->set('edge.zero', 0);
        $this->settings->set('edge.empty', '');
        $this->settings->set('edge.nested', $nested);

        $this->assertNull($this->settings->get('edge.null'));
        $this->assertFalse($this->settings->get('edge.false'));
        $this->assertSame(0, $this->settings->get('edge.zero'));
        $this->assertSame('', $this->settings->get('edge.empty'));
        $this->assertSame($nested, $this->settings->get('edge.nested'));

        $this->assertTrue($this->settings->has('edge.null'));
        $this->assertTrue($this->settings->has('edge.false'));
        $this->assertTrue($this->settings->has('edge.zero'));
        $this->assertTrue($this->settings->has('edge.empty'));
    }

    public function test_has_is_true_for_existing_null_valued_key(): void
    {
        $this->settings->set('settings.app.optional', null);

        $this->assertTrue($this->settings->has('settings.app.optional'));
        $this->assertNull($this->settings->get('settings.app.optional', 'default-unused'));
    }

    public function test_rejected_set_does_not_corrupt_existing_value(): void
    {
        $this->settings->set('settings.app.name', 'Keep Me');

        try {
            $this->settings->set('settings.app.name', new stdClass());
            $this->fail('Expected InvalidArgumentException for object value.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('JSON-serializable', $e->getMessage());
        }

        $this->assertSame('Keep Me', $this->settings->get('settings.app.name'));
        $this->assertTrue($this->settings->has('settings.app.name'));
    }

    public function test_all_prefix_treats_like_metacharacters_literally(): void
    {
        $this->settings->set('group%a.one', 1);
        $this->settings->set('group%a.two', 2);
        $this->settings->set('groupXa.one', 9);
        $this->settings->set('group_b.one', 3);
        $this->settings->set('groupXb.one', 8);

        $this->assertSame([
            'group%a.one' => 1,
            'group%a.two' => 2,
        ], $this->settings->all('group%a'));

        $this->assertSame([
            'group_b.one' => 3,
        ], $this->settings->all('group_b'));

        $this->assertArrayNotHasKey('groupXa.one', $this->settings->all('group%a'));
        $this->assertArrayNotHasKey('groupXb.one', $this->settings->all('group_b'));
    }

    public function test_set_upserts_existing_key(): void
    {
        $this->settings->set('settings.app.name', 'First');
        $this->settings->set('settings.app.name', 'Second');

        $this->assertSame('Second', $this->settings->get('settings.app.name'));
        $this->assertCount(1, $this->settings->all());
    }

    public function test_forget_is_idempotent(): void
    {
        $this->settings->set('billing.tax.rate', 11);
        $this->settings->forget('billing.tax.rate');
        $this->settings->forget('billing.tax.rate');

        $this->assertFalse($this->settings->has('billing.tax.rate'));
        $this->assertSame(0, $this->settings->get('billing.tax.rate', 0));
    }

    public function test_all_with_prefix_filters_by_namespace(): void
    {
        $this->settings->set('billing.tax.rate', 11);
        $this->settings->set('billing.currency', 'IDR');
        $this->settings->set('billingx.other', 'nope');
        $this->settings->set('settings.app.name', 'ModMon');

        $billing = $this->settings->all('billing');

        $this->assertSame([
            'billing.currency' => 'IDR',
            'billing.tax.rate' => 11,
        ], $billing);
        $this->assertArrayNotHasKey('billingx.other', $billing);
        $this->assertArrayNotHasKey('settings.app.name', $billing);
    }

    public function test_all_without_prefix_returns_every_key(): void
    {
        $this->settings->set('a.one', 1);
        $this->settings->set('b.two', 2);

        $this->assertSame([
            'a.one' => 1,
            'b.two' => 2,
        ], $this->settings->all());
    }

    public function test_empty_key_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->settings->set('   ', 'x');
    }

    public function test_object_values_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->settings->set('settings.app.owner', new stdClass());
    }

    public function test_nested_object_values_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->settings->set('settings.app.meta', ['owner' => new stdClass()]);
    }
}
