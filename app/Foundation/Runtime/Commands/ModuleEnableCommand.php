<?php

declare(strict_types=1);

namespace App\Foundation\Runtime\Commands;

use App\Foundation\Runtime\ModuleManager;
use Illuminate\Console\Command;

class ModuleEnableCommand extends Command
{
    protected $signature = 'module:enable {code : The module code to enable}';

    protected $description = 'Enable a previously disabled module';

    public function handle(ModuleManager $manager): int
    {
        $code = $this->argument('code');

        $this->info("Enabling module: {$code}");

        $result = $manager->enable($code);

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
