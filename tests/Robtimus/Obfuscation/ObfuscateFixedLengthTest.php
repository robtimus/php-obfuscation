<?php
namespace Robtimus\Obfuscation;

use ValueError;

class ObfuscateFixedLengthTest extends ObfuscatorTestCase
{
    public static function obfuscationParameters(): array
    {
        return [
            [Obfuscate::fixedLength(8), 'foo', '********'],
            [Obfuscate::fixedLength(8), 'hello', '********'],
            [Obfuscate::fixedLength(8), '', '********'],
            [Obfuscate::fixedLength(5, 'x'), 'foo', 'xxxxx'],
            [Obfuscate::fixedLength(0), 'foo', ''],
        ];
    }

    public function testNegativeLength(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('-1 < 0');

        Obfuscate::fixedLength(-1);
    }
}
