<?php

declare(strict_types=1);

namespace Phpolar\SqliteStorage\TestClasses;

use AllowDynamicProperties;

#[AllowDynamicProperties]
final class TestClassWithInvalidProps
{
    public string|int $id;

    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }
}
