<?php

declare(strict_types=1);

namespace Modules\Branding\Tests\Feature;

use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\Schema;
use Modules\Branding\Domain\Contracts\BrandingContract;
use Modules\Branding\Domain\DTOs\BrandingData;
use Modules\Branding\Domain\DTOs\BrandingRead;

class BrandingLifecycleTest extends BrandingTestCase
{
    public function test_disable_removes_capability_and_contributions_but_preserves_row(): void
    {
        $manager = $this->installBranding();
        app(BrandingContract::class)->update(new BrandingData(
            name: 'Acme Host',
            primaryColor: '#112233',
            loginTitle: 'Welcome',
        ));

        $this->assertTrue($manager->disable('branding')['success']);
        $this->assertEquals(ModuleState::Disabled, app(ModuleRegistrarContract::class)->getState('branding'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('branding.application'));
        $this->assertFalse(
            collect(app(NavigationRegistryContract::class)->items())
                ->contains(fn ($i) => $i->moduleCode === 'branding'),
        );
        $this->assertSame([], app(PermissionRegistryContract::class)->forModule('branding'));

        $this->assertTrue(Schema::hasTable('branding_application'));
        $this->assertDatabaseHas('branding_application', [
            'name' => 'Acme Host',
            'primary_color' => '#112233',
            'login_title' => 'Welcome',
        ]);
    }

    public function test_enable_restores_capability_and_data(): void
    {
        $manager = $this->installBranding();
        app(BrandingContract::class)->update(new BrandingData(
            name: 'Acme Host',
            primaryColor: '#AABBCC',
        ));
        $this->assertTrue($manager->disable('branding')['success']);

        $this->assertTrue($manager->enable('branding')['success']);
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('branding.application'));

        $read = app(BrandingContract::class)->current();
        $this->assertInstanceOf(BrandingRead::class, $read);
        $this->assertSame('Acme Host', $read->name);
        $this->assertSame('#AABBCC', $read->primaryColor);
        $this->assertTrue($read->configured);
        $this->assertNotEmpty(app(PermissionRegistryContract::class)->forModule('branding'));
    }
}
