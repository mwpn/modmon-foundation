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
    protected $signature = 'module:make
                            {name : Module name in PascalCase or Title Case, e.g. WaterBilling or "Water Billing"}
                            {--code= : Override the module code (lowercase alphanumeric with hyphens)}
                            {--type=business : Module type: platform, business, or integration}
                            {--purpose= : One or two sentences describing the module (README)}
                            {--provides= : Comma-separated capability identifiers this module provides}
                            {--requires= : Comma-separated capability identifiers required before install}';

    protected $description = 'Scaffold a new portable module (module.json, provider, README)';

    public function handle(ModuleDiscovery $discovery): int
    {
        $name = trim($this->argument('name'));
        $code = $this->option('code') ?: $this->codeFromName($name);
        $type = $this->option('type');
        $purpose = trim((string) $this->option('purpose'));
        $provides = $this->parseCapabilityList($this->option('provides'));
        $requires = $this->parseCapabilityList($this->option('requires'));

        $identityError = $this->validateIdentity($name, $code, $type, $provides, $requires);
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

        $this->scaffold($modulesPath, $directory, $name, $code, $type, $provider, $purpose, $provides, $requires);

        $this->info("Module '{$code}' scaffolded in Modules/{$directory}.");
        $this->line("The module is in the discovered state. Run `php artisan module:doctor {$code}` to verify it.");

        return self::SUCCESS;
    }

    private function validateIdentity(string $name, string $code, string $type, array $provides, array $requires): ?string
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

        foreach (array_merge($provides, $requires) as $capability) {
            if (! preg_match('/^[a-z][a-z0-9\-]*(?:\.[a-z][a-z0-9\-]*)+$/', $capability)) {
                return "Invalid capability identifier '{$capability}'. Use lowercase dot notation (e.g. identity.user).";
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function parseCapabilityList(mixed $value): array
    {
        if ($value === null || $value === false || $value === '') {
            return [];
        }

        $items = array_map('trim', explode(',', (string) $value));

        return array_values(array_filter($items, fn (string $item) => $item !== ''));
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

    private function scaffold(string $modulesPath, string $directory, string $name, string $code, string $type, string $provider, string $purpose, array $provides, array $requires): void
    {
        $modulePath = $modulesPath.'/'.$directory;

        File::makeDirectory($modulePath, 0755, true);

        File::put($modulePath.'/module.json', $this->renderManifest($name, $code, $type, $provider, $provides, $requires));
        File::put($modulePath.'/'.$directory.'ServiceProvider.php', $this->renderProvider($directory, $name));
        File::put($modulePath.'/README.md', $this->renderReadme($directory, $name, $code, $type, $purpose, $provides, $requires));
    }

    private function renderManifest(string $name, string $code, string $type, string $provider, array $provides, array $requires): string
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
                'capabilities' => $requires,
            ],
            'provides' => $provides,
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

    private function renderReadme(string $directory, string $name, string $code, string $type, string $purpose, array $provides, array $requires): string
    {
        $purposeText = $purpose !== ''
            ? $purpose
            : '{TODO: Describe what the module does in one or two sentences.}';

        $providesSection = $provides === []
            ? '*None* — declare provided capabilities in `module.json` and document them here.'
            : implode("\n", array_map(
                fn (string $capability) => "- `{$capability}`",
                $provides,
            ));

        $requiresSection = $requires === []
            ? '*None* — declare required capabilities in `module.json` and document them here.'
            : implode("\n", array_map(
                fn (string $capability) => "- `{$capability}`",
                $requires,
            ));

        $template = <<<'MD'
# {NAME}

{PURPOSE}

## Type

`{TYPE}`

## Compatibility

| Requirement | Constraint |
|-------------|------------|
| PHP         | `^8.3`     |
| Laravel     | `^13.0`    |
| Foundation  | `^1.0`     |

## Provides

{PROVIDES}

## Requires

{REQUIRES}

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
php artisan test --filter="Modules\\{DIRECTORY}"
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
            '{PURPOSE}' => $purposeText,
            '{PROVIDES}' => $providesSection,
            '{REQUIRES}' => $requiresSection,
        ]);
    }
}
