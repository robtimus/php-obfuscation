<?php
namespace Robtimus\Obfuscation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

abstract class ObfuscatorTestCase extends TestCase
{
    #[DataProvider('obfuscationParameters')]
    public function testObfuscateText(Obfuscator $obfuscator, string $input, string $expected): void
    {
        $this->assertEquals($expected, $obfuscator->obfuscateText($input));
    }

    /**
     * @return array<array{0: Obfuscator, 1: string, 2: string}>
     */
    abstract public static function obfuscationParameters(): array;
}
