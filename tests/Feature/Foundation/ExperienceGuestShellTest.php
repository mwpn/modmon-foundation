<?php

declare(strict_types=1);

namespace Tests\Feature\Foundation;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Experience Phase 2: generic guest shell is available without Identity imports.
 */
class ExperienceGuestShellTest extends TestCase
{
    public function test_guest_shell_renders_brand_and_slot(): void
    {
        $html = Blade::render(
            '<x-foundation::guest-shell title="Sign in">Auth form slot</x-foundation::guest-shell>',
        );

        $this->assertStringContainsString('ModMon', $html);
        $this->assertStringContainsString('Auth form slot', $html);
        $this->assertStringContainsString('Composition foundation', $html);
        $this->assertStringContainsString('Toggle dark mode', $html);
        $this->assertStringContainsString('bg-brand-950', $html);
    }

    public function test_guest_shell_component_has_no_identity_import(): void
    {
        $source = file_get_contents(
            base_path('app/Foundation/Experience/Components/GuestShell.php'),
        );

        $this->assertStringNotContainsString('Modules\\Identity', $source);
        $this->assertStringNotContainsString('use Modules\\', $source);
    }
}
