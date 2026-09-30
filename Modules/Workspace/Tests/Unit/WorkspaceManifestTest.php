<?php

declare(strict_types=1);

namespace Modules\Workspace\Tests\Unit;

use PHPUnit\Framework\TestCase;

class WorkspaceManifestTest extends TestCase
{
    public function test_module_json_is_deterministic(): void
    {
        $path = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'module.json';
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(1, $data['schema']);
        $this->assertSame('Workspace', $data['name']);
        $this->assertSame('workspace', $data['code']);
        $this->assertSame('1.0.0', $data['version']);
        $this->assertSame('platform', $data['type']);
        $this->assertSame('Modules\\Workspace\\WorkspaceServiceProvider', $data['provider']);
        $this->assertSame(['tenancy.context'], $data['requires']['capabilities']);
        $this->assertSame(['workspace.owner', 'workspace.tenant'], $data['provides']);
        $this->assertSame('^8.3', $data['compatibility']['php']);
        $this->assertSame('^13.0', $data['compatibility']['laravel']);
        $this->assertSame('^1.1', $data['compatibility']['foundation']);
    }
}
