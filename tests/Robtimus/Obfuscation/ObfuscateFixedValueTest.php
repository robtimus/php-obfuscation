<?php
namespace Robtimus\Obfuscation;

class ObfuscateFixedValueTest extends ObfuscatorTestCase
{
    public static function obfuscationParameters(): array
    {
        return [
            [Obfuscator::fixedValue('<obfuscated>'), 'foo', '<obfuscated>'],
            [Obfuscator::fixedValue('<obfuscated>'), 'hello', '<obfuscated>'],
            [Obfuscator::fixedValue('<obfuscated>'), '', '<obfuscated>'],
            [Obfuscator::fixedValue(''), 'foo', ''],
        ];
    }
}
