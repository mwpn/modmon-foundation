<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Foundation\Runtime\ManifestValidator;
use PHPUnit\Framework\TestCase;

class ManifestValidatorTest extends TestCase
{
    private ManifestValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ManifestValidator();
    }

    public function test_valid_manifest_passes(): void
    {
        $data = $this->validManifest();
        $errors = $this->validator->validate($data);
        $this->assertEmpty($errors, 'Valid manifest should have no errors: ' . implode(', ', $errors));
    }

    public function test_missing_required_fields_detected(): void
    {
        $errors = $this->validator->validate([]);
        $this->assertNotEmpty($errors);
        $this->assertCount(5, $errors); // schema, name, code, version, provider
    }

    public function test_invalid_code_format_detected(): void
    {
        $data = $this->validManifest();
        $data['code'] = 'Invalid Code';
        $errors = $this->validator->validate($data);
        $this->assertNotEmpty($errors);
        $this->assertTrue(
            collect($errors)->contains(fn ($e) => str_contains($e, 'code')),
            'Should report code format error',
        );
    }

    public function test_invalid_version_format_detected(): void
    {
        $data = $this->validManifest();
        $data['version'] = 'not-semver';
        $errors = $this->validator->validate($data);
        $this->assertNotEmpty($errors);
    }

    public function test_invalid_type_detected(): void
    {
        $data = $this->validManifest();
        $data['type'] = 'unknown';
        $errors = $this->validator->validate($data);
        $this->assertNotEmpty($errors);
    }

    public function test_invalid_provider_detected(): void
    {
        $data = $this->validManifest();
        $data['provider'] = 'NoNamespace';
        $errors = $this->validator->validate($data);
        $this->assertNotEmpty($errors);
    }

    public function test_invalid_schema_version_detected(): void
    {
        $data = $this->validManifest();
        $data['schema'] = -1;
        $errors = $this->validator->validate($data);
        $this->assertNotEmpty($errors);
    }

    public function test_validate_and_create_returns_manifest(): void
    {
        $data = $this->validManifest();
        $manifest = $this->validator->validateAndCreate($data, '/test/path');
        $this->assertEquals('test', $manifest->code);
        $this->assertEquals('/test/path', $manifest->path);
    }

    public function test_validate_and_create_throws_on_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->validator->validateAndCreate([]);
    }

    private function validManifest(): array
    {
        return [
            'schema' => 1,
            'name' => 'Test Module',
            'code' => 'test',
            'version' => '1.0.0',
            'type' => 'business',
            'provider' => 'Modules\\Test\\TestServiceProvider',
            'compatibility' => [
                'php' => '^8.3',
                'laravel' => '^13.0',
                'foundation' => '^1.0',
            ],
            'requires' => ['capabilities' => []],
            'provides' => ['test.capability'],
        ];
    }
}
