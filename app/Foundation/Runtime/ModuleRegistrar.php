<?php

declare(strict_types=1);

namespace App\Foundation\Runtime;

use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\File;

/**
 * JSON-file-backed module state registry.
 *
 * Stores module lifecycle states in a deterministic JSON file
 * at storage/app/modules.json.
 */
class ModuleRegistrar implements ModuleRegistrarContract
{
    private array $states = [];

    private bool $loaded = false;

    public function __construct(
        private readonly string $storagePath,
    ) {}

    public function getState(string $code): ?ModuleState
    {
        $this->load();

        if (! isset($this->states[$code])) {
            return null;
        }

        return ModuleState::from($this->states[$code]);
    }

    public function setState(string $code, ModuleState $state): void
    {
        $this->load();
        $this->states[$code] = $state->value;
        $this->persist();
    }

    public function all(): array
    {
        $this->load();

        return array_map(
            fn (string $value) => ModuleState::from($value),
            $this->states,
        );
    }

    public function isInstalled(string $code): bool
    {
        $state = $this->getState($code);

        return $state !== null && $state !== ModuleState::Discovered;
    }

    public function isEnabled(string $code): bool
    {
        return $this->getState($code) === ModuleState::Enabled;
    }

    public function forget(string $code): void
    {
        $this->load();
        unset($this->states[$code]);
        $this->persist();
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        if (file_exists($this->storagePath)) {
            $data = json_decode(file_get_contents($this->storagePath), true);
            $this->states = is_array($data) ? $data : [];
        }

        $this->loaded = true;
    }

    private function persist(): void
    {
        $dir = dirname($this->storagePath);
        if (! is_dir($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        file_put_contents(
            $this->storagePath,
            json_encode($this->states, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            LOCK_EX,
        );
    }
}
