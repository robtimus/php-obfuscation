<?php
namespace Robtimus\Obfuscation;

class ObfuscateNoneTest extends ObfuscatorTestCase
{
    public static function obfuscationParameters(): array
    {
        return [
            [Obfuscate::none(), 'foo', 'foo'],
            [Obfuscate::none(), 'hello', 'hello'],
            [Obfuscate::none(), '', ''],
        ];
    }
}
