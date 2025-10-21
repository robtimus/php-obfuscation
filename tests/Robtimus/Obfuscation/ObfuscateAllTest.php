<?php
namespace Robtimus\Obfuscation;

class ObfuscateAllTest extends ObfuscatorTestCase
{
    public static function obfuscationParameters(): array
    {
        return [
            [Obfuscator::all(), 'foo', '***'],
            [Obfuscator::all(), 'hello', '*****'],
            [Obfuscator::all(), '', ''],
            [Obfuscator::all('x'), 'foo', 'xxx'],
        ];
    }
}
