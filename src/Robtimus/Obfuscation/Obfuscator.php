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
    private int $_minPrefixLength = 1;

    /**
     * Creates a new obfuscator.
     *
     * @param int $minPrefixLength The minimum allowed length when calling `untilLength`.
     */
    protected function __construct(int $minPrefixLength = 1)
    {
        if ($minPrefixLength < 1) {
            throw new ValueError("$minPrefixLength < 1");
        }
        $this->_minPrefixLength = $minPrefixLength;
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
     *
     * ```php
     * $obfuscator = Obfuscate::none()->untilLength(4)->then(Obfuscate::portion()
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
        if ($prefixLength < $this->_minPrefixLength) {
            throw new ValueError("$prefixLength < {$this->_minPrefixLength}");
        }
        return new class($this, $prefixLength) implements ObfuscatorPrefix
        {
            private Obfuscator $_obfuscator;
            private int $_prefixLength;

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            function __construct(Obfuscator $obfuscator, int $prefixLength)
            {
                $this->_obfuscator = $obfuscator;
                $this->_prefixLength = $prefixLength;
            }

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function then(Obfuscator $other): Obfuscator
            {
                return new class($this->_obfuscator, $this->_prefixLength, $other) extends Obfuscator
                {
                    private Obfuscator $_first;
                    private int $_lengthForFirst;
                    private Obfuscator $_second;

                    // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
                    function __construct(Obfuscator $first, int $lengthForFirst, Obfuscator $second)
                    {
                        parent::__construct($lengthForFirst + 1);
                        $this->_first = $first;
                        $this->_lengthForFirst = $lengthForFirst;
                        $this->_second = $second;
                    }

                    // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
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
}
