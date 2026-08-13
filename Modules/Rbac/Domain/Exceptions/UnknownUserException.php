<?php

declare(strict_types=1);

namespace Modules\Rbac\Domain\Exceptions;

use RuntimeException;

/**
 * Thrown when a user id does not exist in the Identity user domain
 * (`UserQueryContract`). RBAC never writes a user reference without
 * validating it against the public identity contract.
 */
final class UnknownUserException extends RuntimeException
{
}
