<?php

declare(strict_types=1);

namespace App\Foundation\Runtime\Commands;

use App\Foundation\Runtime\ModuleManager;
use Illuminate\Console\Command;

class ModuleDisableCommand extends Command
{
    protected $signature = 'module:disable {code : The module code to disable}';

    protected $description = 'Disable a module (preserves data, removes runtime contributions)';

    public function handle(ModuleManager $manager): int
    {
        $code = $this->argument('code');

        $this->info("Disabling module: {$code}");

        $result = $manager->disable($code);

        foreach ($result['messages'] as $message) {
            if ($result['success']) {
                $this->info("  {$message}");
            } else {
                $this->error("  {$message}");
            }
        }

        return $result['success'] ? self::SUCCESS : self::FAILURE;
    }
}
