<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain\Exceptions;

use RuntimeException;

/**
 * Thrown when setCurrent is refused: inactive tenant, missing membership,
 * or unknown user. Fail closed.
 */
final class InvalidTenantContextException extends RuntimeException
{
}
