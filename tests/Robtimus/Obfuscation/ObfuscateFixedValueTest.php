<?php
namespace Robtimus\Obfuscation;

class ObfuscateFixedValueTest extends ObfuscatorTestCase
{
    public static function obfuscationParameters(): array
    {
        return [
            [Obfuscate::fixedValue('<obfuscated>'), 'foo', '<obfuscated>'],
            [Obfuscate::fixedValue('<obfuscated>'), 'hello', '<obfuscated>'],
            [Obfuscate::fixedValue('<obfuscated>'), '', '<obfuscated>'],
            [Obfuscate::fixedValue(''), 'foo', ''],
        ];
    }
}
