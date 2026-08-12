<?php

declare(strict_types=1);

namespace App\Foundation\Runtime\Commands;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Console\Command;

class ModuleDoctorCommand extends Command
{
    protected $signature = 'module:doctor {code : The module code to diagnose}';

    protected $description = 'Run diagnostics on a specific module';

    public function handle(ModuleManager $manager): int
    {
        $code = $this->argument('code');
        $diagnostics = $manager->diagnose($code);

        $this->info("Diagnostics for module: {$code}");
        $this->newLine();

        $allPassed = true;

        foreach ($diagnostics as $diagnostic) {
            $icon = $diagnostic->passed ? '<fg=green>✓</>' : '<fg=red>✗</>';
            $this->line("  {$icon} [{$diagnostic->check}] {$diagnostic->message}");

            if (! $diagnostic->passed) {
                $allPassed = false;
            }
        }

        $this->newLine();

        if ($allPassed) {
            // Determine appropriate message based on module state
            $state = app(ModuleRegistrarContract::class)->getState($code);
            $stateMessage = match ($state) {
                ModuleState::Enabled  => 'Module is installed and enabled.',
                ModuleState::Disabled => 'Module is installed but disabled.',
                ModuleState::Installed => 'Module is installed.',
                default               => 'Module is ready for installation.',
            };
            $this->info("All checks passed. {$stateMessage}");
        } else {
            $this->warn('Some checks failed. Review the diagnostics above.');
        }

        return $allPassed ? self::SUCCESS : self::FAILURE;
    }
}
