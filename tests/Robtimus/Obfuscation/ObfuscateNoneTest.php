<?php
namespace Robtimus\Obfuscation;

class ObfuscateNoneTest extends ObfuscatorTestCase
{
    public static function obfuscationParameters(): array
    {
        return [
            [Obfuscator::none(), 'foo', 'foo'],
            [Obfuscator::none(), 'hello', 'hello'],
            [Obfuscator::none(), '', ''],
        ];
    }
}
