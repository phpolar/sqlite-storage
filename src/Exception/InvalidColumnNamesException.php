<?php

declare(strict_types=1);

namespace Phpolar\SqliteStorage\Exception;

use DomainException;

final class InvalidColumnNamesException extends DomainException
{
    public function __construct()
    {
        parent::__construct("One or more column names are invalid.");
    }
}
