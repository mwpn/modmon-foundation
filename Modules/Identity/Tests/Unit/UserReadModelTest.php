<?php

declare(strict_types=1);

namespace Modules\Identity\Tests\Unit;

use Modules\Identity\Domain\ReadModels\UserReadModel;
use PHPUnit\Framework\TestCase;

class UserReadModelTest extends TestCase
{
    public function test_constructs_immutable_dto(): void
    {
        $readModel = new UserReadModel(
            id: 42,
            name: 'Ada Lovelace',
            email: 'ada@example.com',
        );

        $this->assertSame(42, $readModel->id);
        $this->assertSame('Ada Lovelace', $readModel->name);
        $this->assertSame('ada@example.com', $readModel->email);
        $this->assertNull($readModel->emailVerifiedAt);
        $this->assertNull($readModel->createdAt);
    }

    public function test_constructs_with_dates(): void
    {
        $verified = new \DateTimeImmutable('2026-08-12 10:00:00', new \DateTimeZone('UTC'));
        $created = new \DateTimeImmutable('2026-01-01 00:00:00', new \DateTimeZone('UTC'));

        $readModel = new UserReadModel(
            id: 1,
            name: 'Grace Hopper',
            email: 'grace@example.com',
            emailVerifiedAt: $verified,
            createdAt: $created,
        );

        $this->assertSame($verified, $readModel->emailVerifiedAt);
        $this->assertSame($created, $readModel->createdAt);
    }

    public function test_is_final_and_readonly(): void
    {
        $reflection = new \ReflectionClass(UserReadModel::class);

        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }
}
