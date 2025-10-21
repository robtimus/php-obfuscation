<?php
namespace Robtimus\Obfuscation;

use ValueError;

/**
 * An object that will obfuscate text.
 *
 * @package Robtimus\Obfuscation
 * @author  Rob Spoor <robtimus@users.noreply.github.com>
 * @license https://www.apache.org/licenses/LICENSE-2.0.txt The Apache Software License, Version 2.0
 */
abstract class Obfuscator
{
    private static ?Obfuscator $_NONE = null;

    private int $_previousPrefixLength = 0;

    protected function __construct(int $previousPrefixLength = 0)
    {
        if ($previousPrefixLength < 0) {
            throw new ValueError("$previousPrefixLength < 0");
        }
        $this->_previousPrefixLength = $previousPrefixLength;
    }

    /**
     * Obfuscates the given text.
     *
     * @param string $text The text to obfuscate.
     *
     * @return string The obfuscated text.
     */
    abstract public function obfuscateText(string $text): string;

    /**
     * Creates a prefix that can be used to chain another obfuscator to this obfuscator.
     * For the part up to the given prefix length, this obfuscator will be used; for any remaining content another obfuscator will be used.
     * This makes it possible to easily create complex obfuscators that would otherwise be impossible using any of the other obfuscators provided
     * by this package. For instance, it would be impossible to use only `portion()` to create an obfuscator that does not obfuscate the first
     * 4 characters, then obfuscates _at least_ 8 characters, then does not obfuscate up to 4 characters at the end. With this method it's
     * possible to do that by combining `none()` and `portion()`:
     * ```php
     * $obfuscator = Obfuscator::none()->untilLength(4)->then(Obfuscator::portion()
     *         ->keepAtEnd(4)
     *         ->atLeastFromStart(8)
     *         ->build());
     * ```
     *
     * @param int $prefixLength The length of the part to use this obfuscator.
     *
     * @return ObfuscatorPrefix A prefix that can be used to chain another obfuscator to this obfuscator.
     * @throws ValueError If the prefix length is not larger than all previous prefix lengths in a method chain.
     *                    In other words, each prefix length must be larger than its direct predecessor.
     */
    final public function untilLength(int $prefixLength): ObfuscatorPrefix
    {
        if ($prefixLength <= $this->_previousPrefixLength) {
            throw new ValueError("$prefixLength <= {$this->_previousPrefixLength}");
        }
        return new class($this, $prefixLength) implements ObfuscatorPrefix
        {
            private Obfuscator $_obfuscator;
            private int $_prefixLength;

            function __construct(Obfuscator $obfuscator, int $prefixLength)
            {
                $this->_obfuscator = $obfuscator;
                $this->_prefixLength = $prefixLength;
            }

            public function then(Obfuscator $other): Obfuscator
            {
                return new class($this->_obfuscator, $this->_prefixLength, $other) extends Obfuscator
                {
                    private Obfuscator $_first;
                    private int $_lengthForFirst;
                    private Obfuscator $_second;

                    function __construct(Obfuscator $first, int $lengthForFirst, Obfuscator $second)
                    {
                        parent::__construct($lengthForFirst);
                        $this->_first = $first;
                        $this->_lengthForFirst = $lengthForFirst;
                        $this->_second = $second;
                    }

                    public function obfuscateText(string $text): string
                    {
                        $end = mb_strlen($text);
                        $splitAt = min($this->_lengthForFirst, $end);
                        if ($splitAt === $end) {
                            return $this->_first->obfuscateText($text);
                        }
                        return $this->_first->obfuscateText(mb_substr($text, 0, $splitAt))
                            . $this->_second->obfuscateText(mb_substr($text, $splitAt));
                    }
                };
            }
        };
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

            function __construct(string $mask)
            {
                $this->_mask = $mask;
            }

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
        if (is_null(Obfuscator::$_NONE)) {
            Obfuscator::$_NONE = new class extends Obfuscator
            {
                public function obfuscateText(string $text): string
                {
                    return $text;
                }
            };
        }
        return Obfuscator::$_NONE;
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
        return Obfuscator::fixedValue(str_repeat($mask, $fixedLength));
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

            function __construct(string $fixedValue)
            {
                $this->_fixedValue = $fixedValue;
            }

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
