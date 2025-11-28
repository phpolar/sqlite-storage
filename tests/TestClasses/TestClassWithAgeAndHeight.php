<?php

declare(strict_types=1);

namespace Phpolar\SqliteStorage\TestClasses;

final class TestClassWithAgeAndHeight
{
    public string $id;
    public string $name;
    public int $age;
    public float $height;

    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->name = $data["name"];
        $this->age = $data["age"];
        $this->height = $data["height"];
    }
}
