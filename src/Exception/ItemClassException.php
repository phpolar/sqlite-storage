<?php

declare(strict_types=1);

namespace Phpolar\SqliteStorage\Exception;

use DomainException;

final class ItemClassException extends DomainException
{
    public function __construct(
        string $typeClassName,
    ) {
        parent::__construct(sprintf(
            "The item must be a %s",
            $typeClassName,
        ));
    }
}
