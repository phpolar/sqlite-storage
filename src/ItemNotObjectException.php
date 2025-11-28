<?php

declare(strict_types=1);

namespace Phpolar\SqliteStorage;

use DomainException;

final class ItemNotObjectException extends DomainException
{
    public function __construct()
    {
        $this->message = "The item must be an object.";
    }
}
