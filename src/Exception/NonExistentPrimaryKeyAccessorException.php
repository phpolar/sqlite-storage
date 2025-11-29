<?php

declare(strict_types=1);

namespace Phpolar\SqliteStorage\Exception;

use DomainException;
use Throwable;

final class NonExistentPrimaryKeyAccessorException extends DomainException
{
    public function __construct(
        string $typeClassName,
        int $code = 0,
        Throwable|null $previous = null
    ) {
        parent::__construct(
            sprintf(
                "The class %s should have either a 'getPrimaryKey' method or an 'id' property.",
                $typeClassName,
            ),
            $code,
            $previous
        );
    }
}
