<?php
namespace Robtimus\Obfuscation;

use ValueError;

/**
 * A prefix of a specific length that uses a specific obfuscator.
 * It can be used to create combined obfuscators that obfuscate text for the part up to the length of this prefix using the prefix' obfuscator,
 * then the rest with another.
 *
 * @package Robtimus\Obfuscation
 * @author  Rob Spoor <robtimus@users.noreply.github.com>
 * @license https://www.apache.org/licenses/LICENSE-2.0.txt The Apache Software License, Version 2.0
 */
interface ObfuscatorPrefix
{
    /**
     * Returns an obfuscator that first uses the source of this object for the length of this prefix, then another obfuscator.
     * If the length of text to obfuscate is smaller than or equal to the length of this prefix, the other obfuscator will be skipped.
     *
     * The returned obfuscator is immutable if both the source of this object and the other obfuscator are.
     *
     * @param Obfuscator $other The other obfuscator to use for text after the length of this prefix has been exceeded.
     *
     * @return Obfuscator An obfuscator that combines the two obfuscators.
     */
    public function then(Obfuscator $other): Obfuscator;
}
