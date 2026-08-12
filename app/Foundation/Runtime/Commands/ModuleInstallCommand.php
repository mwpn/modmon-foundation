<?php

declare(strict_types=1);

namespace App\Foundation\Runtime\Commands;

use App\Foundation\Runtime\ModuleManager;
use Illuminate\Console\Command;

class ModuleInstallCommand extends Command
{
    protected $signature = 'module:install {code : The module code to install}';

    protected $description = 'Install a discovered module (validates, migrates, enables)';

    public function handle(ModuleManager $manager): int
    {
        $code = $this->argument('code');

        $this->info("Installing module: {$code}");

        $result = $manager->install($code);

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
