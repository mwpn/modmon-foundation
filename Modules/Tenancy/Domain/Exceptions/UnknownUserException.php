<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain\Exceptions;

use RuntimeException;

/**
 * Thrown when a user id is unknown to Identity (`UserQueryContract`).
 */
final class UnknownUserException extends RuntimeException
{
}
