<?php

declare(strict_types=1);

namespace Tests\Fixtures\ModuleLifecycle;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\ServiceProvider;

/**
 * Reproduces Identity's console-command workaround: resolving the
 * console kernel during register() while Foundation is still booting
 * providers. That creates the Artisan instance before deferred
 * providers (including migrate) are loaded.
 */
final class EarlyConsoleKernelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $command = new class extends Command
        {
            protected $signature = 'fixture:early-console-kernel';

            protected $description = 'Fixture command';

            public function handle(): int
            {
                return self::SUCCESS;
            }
        };

        $this->app->make(ConsoleKernel::class)->registerCommand($command);
    }
}
