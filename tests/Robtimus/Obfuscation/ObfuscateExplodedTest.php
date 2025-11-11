<?php
namespace Robtimus\Obfuscation;

class ObfuscateExplodedTest extends ObfuscatorTestCase
{
    public static function obfuscationParameters(): array
    {
        $obfuscator = Obfuscate::exploded(', ', Obfuscate::fixedLength(3));
        return [
            [$obfuscator, 'foo', '***'],
            [$obfuscator, 'hello', '***'],
            [$obfuscator, 'foo, bar', '***, ***'],
            [$obfuscator, ', ', '***, ***'],
            [$obfuscator, ',', '***'],
            [$obfuscator, '', '***'],
        ];
    }
}
