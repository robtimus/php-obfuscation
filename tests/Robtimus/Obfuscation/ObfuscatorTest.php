<?php
namespace Robtimus\Obfuscation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ValueError;

class ObfuscatorTest extends TestCase
{
    #[DataProvider('nonPositiveMinPrefixLengthParameters')]
    public function testNonPositiveMinPrefixLength(int $minPrefixLength): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage("$minPrefixLength < 1");

        new class($minPrefixLength) extends Obfuscator
        {
            public function __construct(int $minPrefixLength)
            {
                parent::__construct($minPrefixLength);
            }

            public function obfuscateText(string $text): string
            {
                return $text;
            }
        };
    }

    /**
     * @return array<array<int>>
     */
    public static function nonPositiveMinPrefixLengthParameters(): array
    {
        return [
            [-1],
            [0],
        ];
    }
}
