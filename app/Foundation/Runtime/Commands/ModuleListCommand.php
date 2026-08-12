<?php

declare(strict_types=1);

namespace App\Foundation\Runtime\Commands;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use Illuminate\Console\Command;

class ModuleListCommand extends Command
{
    protected $signature = 'module:list';

    protected $description = 'List all discovered modules and their states';

    public function handle(ModuleManager $manager, ModuleRegistrarContract $registrar): int
    {
        $manifests = $manager->discover();
        $errors    = $manager->discoveryErrors();

        if (empty($manifests) && empty($errors)) {
            $this->info('No modules discovered in the Modules/ directory.');
            return self::SUCCESS;
        }

        $rows = [];
        foreach ($manifests as $code => $manifest) {
            $state = $registrar->getState($code);
            $rows[] = [
                $manifest->name,
                $code,
                $manifest->version,
                $manifest->type,
                $state?->value ?? 'discovered',
                implode(', ', $manifest->provides) ?: '-',
                implode(', ', $manifest->requires) ?: '-',
            ];
        }

        $this->table(
            ['Name', 'Code', 'Version', 'Type', 'State', 'Provides', 'Requires'],
            $rows,
        );

        if (! empty($errors)) {
            $this->newLine();
            $this->warn('Discovery errors:');
            foreach ($errors as $folder => $messages) {
                foreach ($messages as $msg) {
                    $this->error("  [{$folder}] {$msg}");
                }
            }
        }

        return self::SUCCESS;
    }
}
