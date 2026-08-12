<?php

declare(strict_types=1);

namespace App\Foundation\Runtime\Commands;

use App\Foundation\Runtime\ModuleDiscovery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Scaffolds a new portable module.
 *
 * Generates only the minimum structure required by the Module Authoring
 * Standard v1: module.json, the service provider, and a README based on
 * the canonical template. The command never installs, enables, migrates,
 * or mutates runtime module state — a scaffolded module stays in the
 * discovered state until module:install is run explicitly.
 */
class ModuleMakeCommand extends Command
{
    protected $signature = 'module:make {name : Module name in PascalCase or Title Case, e.g. WaterBilling or "Water Billing"} {--code= : Override the module code (lowercase alphanumeric with hyphens)} {--type=business : Module type: platform, business, or integration}';

    protected $description = 'Scaffold a new portable module (module.json, provider, README)';

    public function handle(ModuleDiscovery $discovery): int
    {
        $name = trim($this->argument('name'));
        $code = $this->option('code') ?: $this->codeFromName($name);
        $type = $this->option('type');

        $identityError = $this->validateIdentity($name, $code, $type);
        if ($identityError !== null) {
            $this->error($identityError);

            return self::FAILURE;
        }

        $directory = Str::studly($code);
        $provider = "Modules\\{$directory}\\{$directory}ServiceProvider";
        $modulesPath = base_path('Modules');

        $conflictError = $this->checkConflicts($discovery, $modulesPath, $directory, $code, $provider);
        if ($conflictError !== null) {
            $this->error($conflictError);

            return self::FAILURE;
        }

        $this->scaffold($modulesPath, $directory, $name, $code, $type, $provider);

        $this->info("Module '{$code}' scaffolded in Modules/{$directory}.");
        $this->line("The module is in the discovered state. Run `php artisan module:doctor {$code}` to verify it.");

        return self::SUCCESS;
    }

    private function validateIdentity(string $name, string $code, string $type): ?string
    {
        if ($name === '') {
            return 'Module name must not be empty.';
        }

        if (! preg_match('/^[A-Z][A-Za-z0-9]*(?:[ -][A-Za-z0-9]+)*$/', $name)) {
            return "Invalid module name '{$name}'. Use PascalCase or Title Case (e.g. WaterBilling or \"Water Billing\").";
        }

        if (! preg_match('/^[a-z][a-z0-9\-]*$/', $code)) {
            return "Invalid module code '{$code}'. Use lowercase alphanumeric with hyphens, starting with a letter.";
        }

        if (! in_array($type, ['platform', 'business', 'integration'], true)) {
            return "Invalid module type '{$type}'. Use one of: platform, business, integration.";
        }

        return null;
    }

    /**
     * Derive a module code from a PascalCase or Title Case name.
     *
     * "WaterBilling", "Water Billing", and "Water-Billing" all map to
     * "water-billing", matching the Module Authoring Standard naming table.
     */
    private function codeFromName(string $name): string
    {
        $words = preg_split('/[\s\-_]+|(?<=[a-z0-9])(?=[A-Z])/', $name) ?: [$name];

        return strtolower(implode('-', array_filter($words, fn (string $word) => $word !== '')));
    }

    private function checkConflicts(ModuleDiscovery $discovery, string $modulesPath, string $directory, string $code, string $provider): ?string
    {
        $target = $modulesPath.'/'.$directory;

        if (File::exists($target)) {
            return "Directory Modules/{$directory} already exists. Choose a different module name.";
        }

        $result = $discovery->discover();

        if (isset($result['manifests'][$code])) {
            $existing = $result['manifests'][$code];

            return "Module code '{$code}' is already used by the module at {$existing->path}.";
        }

        foreach ($result['manifests'] as $existingManifest) {
            if ($existingManifest->provider === $provider) {
                return "Provider class {$provider} is already used by the module at {$existingManifest->path}.";
            }
        }

        return null;
    }

    private function scaffold(string $modulesPath, string $directory, string $name, string $code, string $type, string $provider): void
    {
        $modulePath = $modulesPath.'/'.$directory;

        File::makeDirectory($modulePath, 0755, true);

        File::put($modulePath.'/module.json', $this->renderManifest($name, $code, $type, $provider));
        File::put($modulePath.'/'.$directory.'ServiceProvider.php', $this->renderProvider($directory, $name));
        File::put($modulePath.'/README.md', $this->renderReadme($directory, $name, $code, $type));
    }

    private function renderManifest(string $name, string $code, string $type, string $provider): string
    {
        $manifest = [
            'schema' => 1,
            'name' => $name,
            'code' => $code,
            'version' => '1.0.0',
            'type' => $type,
            'provider' => $provider,
            'compatibility' => [
                'php' => '^8.3',
                'laravel' => '^13.0',
                'foundation' => '^1.0',
            ],
            'requires' => [
                'capabilities' => [],
            ],
            'provides' => [],
        ];

        return json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    }

    private function renderProvider(string $directory, string $name): string
    {
        $template = <<<'PHP'
<?php

declare(strict_types=1);

namespace Modules\{DIRECTORY};

use Illuminate\Support\ServiceProvider;

/**
 * {NAME} module service provider.
 *
 * Implement Foundation contribution interfaces (ContributesRoutes,
 * ContributesNavigation, ContributesDashboard, ContributesPermissions)
 * as needed. The Foundation registers contributions automatically.
 */
class {DIRECTORY}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
PHP;

        return strtr($template, [
            '{DIRECTORY}' => $directory,
            '{NAME}' => $name,
        ]);
    }

    private function renderReadme(string $directory, string $name, string $code, string $type): string
    {
        $template = <<<'MD'
# {NAME}

{TODO: Describe what the module does in one or two sentences.}

## Type

`{TYPE}`

## Compatibility

| Requirement | Constraint |
|-------------|------------|
| PHP         | `^8.3`     |
| Laravel     | `^13.0`    |
| Foundation  | `^1.0`     |

## Provides

*None* — declare provided capabilities in `module.json` and document them here.

## Requires

*None* — declare required capabilities in `module.json` and document them here.

## Optional Integrations

*None* — document capabilities checked at runtime when available.

## Installation

```bash
# Copy the module into a compatible ModMon Foundation host
cp -r modmon-{CODE}/Modules/{DIRECTORY} /path/to/host/Modules/{DIRECTORY}

# Verify compatibility
php artisan module:doctor {CODE}

# Install and enable
php artisan module:install {CODE}
```

## Configuration

No static configuration required.

### Environment Variables

None.

### Runtime Settings

Requires modmon-settings (not yet available in Foundation v1).

## Permissions

*None* — declare permissions via `ContributesPermissions` when needed.

## Routes

*None* — declare routes via `ContributesRoutes` when needed.

## Events Published

*None* — this module publishes no events.

## Events Consumed

*None* — this module consumes no external events.

## Public Contracts

*None* — this module exposes no public contracts.

## Database Ownership

*None* — this module owns no tables.

### Cross-Module References

No cross-module database references.

## Navigation Contributions

*None* — this module contributes no navigation items.

## Dashboard Contributions

*None* — this module contributes no dashboard widgets.

## Testing

```bash
php artisan test --filter="Modules\\\\{NAME}"
```

### Test Coverage

| Area | Status |
|------|--------|
| Manifest validation | Not yet added |
| Discovery | Not yet added |
| Installation | Not yet added |
| Capability registration | Not yet added |
| Routes | Not yet added |
| Migrations | Not yet added |
| Contributions | Not yet added |
| Disable/Enable | Not yet added |
| Data preservation | Not yet added |
| Architecture boundary | Not yet added |

## Version History

| Version | Foundation | Description       |
|---------|------------|-------------------|
| 1.0.0   | ^1.0       | Initial scaffold. |
MD;

        return strtr($template, [
            '{DIRECTORY}' => $directory,
            '{NAME}' => $name,
            '{CODE}' => $code,
            '{TYPE}' => $type,
        ]);
    }
}
