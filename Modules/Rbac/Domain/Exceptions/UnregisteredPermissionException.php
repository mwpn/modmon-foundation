<?php

declare(strict_types=1);

namespace Modules\Rbac\Domain\Exceptions;

use RuntimeException;

/**
 * Thrown when a permission id is not registered in the Foundation
 * `PermissionRegistry`. Only registered permissions may be assigned to
 * a role — the registry is the single source of truth and is never
 * snapshotted into RBAC tables.
 */
final class UnregisteredPermissionException extends RuntimeException
{
}
