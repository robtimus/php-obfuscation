<?php
namespace Robtimus\Obfuscation;

use ValueError;

/**
 * A factory class for `Obfuscator` instances.
 *
 * @package Robtimus\Obfuscation
 * @author  Rob Spoor <robtimus@users.noreply.github.com>
 * @license https://www.apache.org/licenses/LICENSE-2.0.txt The Apache Software License, Version 2.0
 */
final class Obfuscate
{
    private function __construct()
    {
        // private constructor to prevent initialization
    }

    /**
     * Returns an immutable obfuscator that replaces all characters with a specific string.
     * The length of obfuscated contents will be as long as the length of the source times the length of the mask.
     *
     * @param string $mask The string to replace with.
     *
     * @return Obfuscator An obfuscator that replaces all characters with the given mask.
     */
    public static function all(string $mask = '*'): Obfuscator
    {
        return new class($mask) extends Obfuscator
        {
            private string $_mask;

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function __construct(string $mask)
            {
                $this->_mask = $mask;
            }

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function obfuscateText(string $text): string
            {
                return str_repeat($this->_mask, mb_strlen($text));
            }
        };
    }

    /**
     * Returns an immutable obfuscator that does not obfuscate anything.
     * This can be used as default value to prevent having to check for null.
     *
     * @return Obfuscator An obfuscator that does not obfuscate anything.
     */
    public static function none(): Obfuscator
    {
        return new class extends Obfuscator
        {
            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function __construct()
            {
            }

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function obfuscateText(string $text): string
            {
                return $text;
            }
        };
    }

    /**
     * Returns an immutable obfuscator that replaces all characters with a fixed number of a specific string.
     *
     * @param int    $fixedLength The fixed length.
     * @param string $mask        The string to replace with.
     *
     * @return Obfuscator An obfuscator that replaces all characters with the given number of the given string.
     * @throws ValueError If the given length is negative.
     */
    public static function fixedLength(int $fixedLength, string $mask = '*'): Obfuscator
    {
        if ($fixedLength < 0) {
            throw new ValueError("$fixedLength < 0");
        }
        return Obfuscate::fixedValue(str_repeat($mask, $fixedLength));
    }

    /**
     * Returns an immutable obfuscator that replaces all characters with a fixed value.
     *
     * @param string $fixedValue The fixed value.
     *
     * @return Obfuscator An obfuscator that replaces all characters with the given fixed value.
     */
    public static function fixedValue(string $fixedValue): Obfuscator
    {
        return new class($fixedValue) extends Obfuscator
        {
            private string $_fixedValue;

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function __construct(string $fixedValue)
            {
                $this->_fixedValue = $fixedValue;
            }

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function obfuscateText(string $text): string
            {
                return $this->_fixedValue;
            }
        };
    }

    /**
     * Returns a builder for obfuscators that obfuscate a specific portion of their input.
     *
     * @return PortionObfuscatorBuilder A builder for obfuscators that obfuscate a specific portion of their input.
     */
    public static function portion(): PortionObfuscatorBuilder
    {
        return new PortionObfuscatorBuilder();
    }
}
