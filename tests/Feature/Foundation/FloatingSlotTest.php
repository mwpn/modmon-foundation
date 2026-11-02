<?php

declare(strict_types=1);

namespace Tests\Feature\Foundation;

use App\Foundation\SDK\Contracts\WorkspaceRegistryContract;
use App\Foundation\SDK\DTOs\DashboardWidget;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * Contract 1.5.0 (ADR-0013): widgets in `shell.floating` render on every
 * app-shell page, outside the content, in order.
 */
class FloatingSlotTest extends TestCase
{
    public function test_floating_widgets_render_in_the_app_shell_in_order(): void
    {
        view()->share('errors', new ViewErrorBag);
        $dir = sys_get_temp_dir().'/floating-slot-test';
        @mkdir($dir);
        file_put_contents("{$dir}/second.blade.php", '<div data-floating="second"></div>');
        file_put_contents("{$dir}/first.blade.php", '<div data-floating="first">{{ $label }}</div>');
        View::addNamespace('floating-test', $dir);

        $registry = app(WorkspaceRegistryContract::class);
        $registry->register(new DashboardWidget(id: 't.second', moduleCode: 't', slot: DashboardWidget::SHELL_FLOATING, view: 'floating-test::second', order: 20));
        $registry->register(new DashboardWidget(id: 't.first', moduleCode: 't', slot: DashboardWidget::SHELL_FLOATING, view: 'floating-test::first', order: 10, data: ['label' => 'Halo']));
        $registry->register(new DashboardWidget(id: 't.dash', moduleCode: 't', slot: 'workspace.default.dashboard.main', view: 'floating-test::second', order: 1));

        $html = Blade::render('<x-foundation::app-shell><p id="content">Isi</p></x-foundation::app-shell>');

        $this->assertSame('shell.floating', DashboardWidget::SHELL_FLOATING);
        $this->assertStringContainsString('<div data-floating="first">Halo</div>', $html);
        $this->assertSame(1, substr_count($html, 'data-floating="second"'), 'dashboard widgets stay on the dashboard');
        $this->assertTrue(strpos($html, 'data-floating="first"') < strpos($html, 'data-floating="second"'));
        $this->assertTrue(strpos($html, 'id="content"') < strpos($html, 'data-floating="first"'), 'after the page content');
    }
}
