<?php

declare(strict_types=1);

namespace Phpolar\SqliteStorage\Exception;

use DomainException;

final class NonExistentClassException extends DomainException
{
    public function __construct(
        string $typeClassName,
    ) {
        parent::__construct(
            sprintf("The class %s does not exist.", $typeClassName),
        );
    }
}
