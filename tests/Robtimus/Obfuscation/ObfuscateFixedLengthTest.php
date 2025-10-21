<?php
namespace Robtimus\Obfuscation;

use ValueError;

class ObfuscateFixedLengthTest extends ObfuscatorTestCase
{
    public static function obfuscationParameters(): array
    {
        return [
            [Obfuscator::fixedLength(8), 'foo', '********'],
            [Obfuscator::fixedLength(8), 'hello', '********'],
            [Obfuscator::fixedLength(8), '', '********'],
            [Obfuscator::fixedLength(5, 'x'), 'foo', 'xxxxx'],
            [Obfuscator::fixedLength(0), 'foo', ''],
        ];
    }

    public function testNegativeLength(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('-1 < 0');

        Obfuscator::fixedLength(-1);
    }
}
