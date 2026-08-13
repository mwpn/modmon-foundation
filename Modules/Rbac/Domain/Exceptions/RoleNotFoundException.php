<?php

declare(strict_types=1);

namespace Modules\Rbac\Domain\Exceptions;

use RuntimeException;

/**
 * Thrown when a role id does not exist in the RBAC-owned `rbac_roles`.
 */
final class RoleNotFoundException extends RuntimeException
{
}
