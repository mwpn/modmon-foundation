<?php

declare(strict_types=1);

namespace Modules\Branding\Tests\Feature;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\Branding\Domain\Contracts\BrandingContract;
use Modules\Branding\Domain\DTOs\BrandingData;
use Modules\Branding\Domain\DTOs\BrandingRead;
use Modules\Branding\Domain\Models\BrandingApplication;

class BrandingContractTest extends BrandingTestCase
{
    public function test_current_returns_defaults_without_seeding_a_row(): void
    {
        $this->installBranding();

        $read = app(BrandingContract::class)->current();

        $this->assertInstanceOf(BrandingRead::class, $read);
        $this->assertFalse($read->configured);
        $this->assertSame((string) config('app.name', 'ModMon'), $read->name);
        $this->assertNull($read->logoUrl);
        $this->assertNull($read->primaryColor);
        $this->assertDatabaseCount('branding_application', 0);
        $this->assertNotInstanceOf(BrandingApplication::class, $read);
    }

    public function test_update_creates_singleton_and_exposes_resolved_urls(): void
    {
        $this->installBranding();

        Storage::disk('public')->put('branding/logo.png', 'fake-logo');

        $read = app(BrandingContract::class)->update(new BrandingData(
            name: ' lacme ',
            logoPath: 'branding/logo.png',
            primaryColor: '#abc',
            accentColor: '#112233',
            loginTitle: 'Sign in',
            loginSubtitle: 'Continue',
        ));

        $this->assertTrue($read->configured);
        $this->assertSame('lacme', $read->name);
        $this->assertSame('#ABC', $read->primaryColor);
        $this->assertSame('#112233', $read->accentColor);
        $this->assertSame('Sign in', $read->loginTitle);
        $this->assertNotNull($read->logoUrl);
        $this->assertStringContainsString('branding/logo.png', $read->logoUrl);
        $this->assertDatabaseCount('branding_application', 1);

        $again = app(BrandingContract::class)->update(new BrandingData(
            name: 'lacme',
            primaryColor: '#FFFFFF',
        ));
        $this->assertDatabaseCount('branding_application', 1);
        $this->assertSame('#FFFFFF', $again->primaryColor);
        $this->assertNotNull($again->logoUrl);
    }

    public function test_update_rejects_invalid_color_and_foreign_asset_path(): void
    {
        $this->installBranding();
        $contract = app(BrandingContract::class);

        try {
            $contract->update(new BrandingData(name: 'X', primaryColor: 'blue'));
            $this->fail('Expected invalid color rejection');
        } catch (InvalidArgumentException) {
            //
        }

        try {
            $contract->update(new BrandingData(name: 'X', logoPath: 'other/logo.png'));
            $this->fail('Expected foreign asset path rejection');
        } catch (InvalidArgumentException) {
            //
        }

        $this->assertDatabaseCount('branding_application', 0);
    }

    public function test_clear_logo_removes_owned_asset(): void
    {
        $this->installBranding();
        Storage::disk('public')->put('branding/logo.png', 'fake-logo');

        app(BrandingContract::class)->update(new BrandingData(
            name: 'Host',
            logoPath: 'branding/logo.png',
        ));
        $this->assertTrue(Storage::disk('public')->exists('branding/logo.png'));

        $read = app(BrandingContract::class)->update(new BrandingData(
            name: 'Host',
            clearLogo: true,
        ));

        $this->assertNull($read->logoUrl);
        $this->assertFalse(Storage::disk('public')->exists('branding/logo.png'));
    }
}
