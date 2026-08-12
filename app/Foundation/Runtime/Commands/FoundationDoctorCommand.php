<?php

declare(strict_types=1);

namespace App\Foundation\Runtime\Commands;

use App\Foundation\Runtime\CompatibilityChecker;
use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use Illuminate\Console\Command;

class FoundationDoctorCommand extends Command
{
    protected $signature = 'foundation:doctor';

    protected $description = 'Run Foundation-level diagnostics';

    public function handle(
        ModuleManager $manager,
        ModuleRegistrarContract $registrar,
        CapabilityRegistryContract $capabilities,
    ): int {
        $allPassed = true;

        $this->info('Foundation Diagnostics');
        $this->newLine();

        // 1. Foundation version
        $this->line("  <fg=green>✓</> Foundation Contract v" . CompatibilityChecker::FOUNDATION_VERSION);

        // 2. PHP version
        $this->line("  <fg=green>✓</> PHP " . PHP_VERSION);

        // 3. Laravel version
        $this->line("  <fg=green>✓</> Laravel " . app()->version());

        // 4. Modules directory
        $modulesPath = base_path('Modules');
        $dirExists = is_dir($modulesPath);
        $icon = $dirExists ? '<fg=green>✓</>' : '<fg=yellow>!</>';
        $this->line("  {$icon} Modules directory: " . ($dirExists ? 'exists' : 'not found (will be created on first module)'));

        // 5. Discovery
        $manifests = $manager->discover();
        $errors    = $manager->discoveryErrors();

        $this->line("  <fg=green>✓</> Discovered " . count($manifests) . " module(s)");

        if (! empty($errors)) {
            $allPassed = false;
            $this->line("  <fg=red>✗</> Discovery errors in " . count($errors) . " folder(s):");
            foreach ($errors as $folder => $msgs) {
                foreach ($msgs as $msg) {
                    $this->line("      [{$folder}] {$msg}");
                }
            }
        }

        // 6. State consistency
        $states = $registrar->all();
        $orphanedStates = array_diff_key($states, $manifests);
        if (! empty($orphanedStates)) {
            $allPassed = false;
            $codes = implode(', ', array_keys($orphanedStates));
            $this->line("  <fg=yellow>!</> Orphaned state entries (no matching module): {$codes}");
        } else {
            $this->line("  <fg=green>✓</> No orphaned state entries");
        }

        // 7. Enabled modules summary
        $enabledCount = count(array_filter($states, fn ($s) => $s->value === 'enabled'));
        $disabledCount = count(array_filter($states, fn ($s) => $s->value === 'disabled'));
        $installedCount = count(array_filter($states, fn ($s) => $s->value === 'installed'));

        $this->line("  <fg=green>✓</> States: {$enabledCount} enabled, {$disabledCount} disabled, {$installedCount} installed-only");

        // 8. Available capabilities
        $available = $capabilities->available();
        $this->line("  <fg=green>✓</> Available capabilities: " . (empty($available) ? 'none' : implode(', ', $available)));

        // 9. Dependency resolution
        $enabledManifests = $manager->getEnabledManifests();
        if (! empty($enabledManifests)) {
            $resolver = app(\App\Foundation\Runtime\DependencyResolver::class);
            $resolution = $resolver->resolve($enabledManifests);

            if (! empty($resolution['errors'])) {
                $allPassed = false;
                foreach ($resolution['errors'] as $error) {
                    $this->line("  <fg=red>✗</> {$error}");
                }
            } else {
                $this->line("  <fg=green>✓</> Dependency resolution: OK (boot order: " . implode(' → ', $resolution['order']) . ")");
            }
        }

        $this->newLine();
        if ($allPassed) {
            $this->info('All foundation checks passed.');
        } else {
            $this->warn('Some checks need attention. Review above.');
        }

        return $allPassed ? self::SUCCESS : self::FAILURE;
    }
}
