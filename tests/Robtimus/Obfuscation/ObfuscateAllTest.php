<?php
namespace Robtimus\Obfuscation;

class ObfuscateAllTest extends ObfuscatorTestCase
{
    public static function obfuscationParameters(): array
    {
        return [
            [Obfuscate::all(), 'foo', '***'],
            [Obfuscate::all(), 'hello', '*****'],
            [Obfuscate::all(), '', ''],
            [Obfuscate::all('x'), 'foo', 'xxx'],
        ];
    }
}
