<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain\Exceptions;

use RuntimeException;

final class TenantCodeAlreadyExistsException extends RuntimeException
{
}
