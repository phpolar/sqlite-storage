<?php

declare(strict_types=1);

namespace Phpolar\SqliteStorage;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

use const PROJECT_SIZE_THRESHOLD;
use const SRC_GLOB;

#[CoversNothing]
final class ProjectSizeTest extends TestCase
{
    #[Test]
    #[TestDox("Source code total size shall be below " . PROJECT_SIZE_THRESHOLD . " bytes")]
    public function shallBeBelowThreshold()
    {
        $listOfSourceFiles = glob(getcwd() . SRC_GLOB, GLOB_BRACE);
        $this->assertIsArray($listOfSourceFiles);

        $totalSize = mb_strlen(
            implode(
                preg_replace(
                    [
                        // strip comments
                        "/\/\*\*(.*?)\//s",
                        "/^(.*?)\/\/(.*?)$/s",
                    ],
                    "",
                    array_map(
                        file_get_contents(...),
                        $listOfSourceFiles === false ? [] : $listOfSourceFiles,
                    ),
                ) ?? [],
            )
        );
        $this->assertGreaterThan(0, $totalSize);
        $this->assertLessThanOrEqual(PROJECT_SIZE_THRESHOLD, $totalSize);
    }
}
