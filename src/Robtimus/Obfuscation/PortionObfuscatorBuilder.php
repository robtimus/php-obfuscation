<?php
namespace Robtimus\Obfuscation;

use LogicException;
use ValueError;

/**
 * A builder for obfuscators that obfuscate a specific portion of their input.
 * An obfuscator created with `keepAtStart(x)` and `keepAtEnd(y)` will, for input `s`,
 * obfuscate all characters in the range `x` (inclusive) to `mb_strlen(s) - y` (exclusive).
 * If this range is empty, such an obfuscator will not obfuscate anything, unless if `withFixedTotalLength` is specified.
 *
 * @package Robtimus\Obfuscation
 * @author  Rob Spoor <robtimus@users.noreply.github.com>
 * @license https://www.apache.org/licenses/LICENSE-2.0.txt The Apache Software License, Version 2.0
 */
final class PortionObfuscatorBuilder
{
    private int $_keepAtStart = 0;
    private int $_keepAtEnd = 0;
    private int $_atLeastFromStart = 0;
    private int $_atLeastFromEnd = 0;
    private int $_fixedTotalLength = -1;
    private string $_mask = '*';

    /**
     * Sets the number of characters at the start that created obfuscators will skip when obfuscating.
     *
     * @param int $count The number of characters to skip. The default is 0.
     *
     * @return PortionObfuscatorBuilder This builder.
     * @throws ValueError If the count is negative.
     */
    public function keepAtStart(int $count): PortionObfuscatorBuilder
    {
        if ($count < 0) {
            throw new ValueError("$count < 0");
        }
        $this->_keepAtStart = $count;
        return $this;
    }

    /**
     * Sets the number of characters at the end that created obfuscators will skip when obfuscating.
     *
     * @param int $count The number of characters to skip. The default is 0.
     *
     * @return PortionObfuscatorBuilder This builder.
     * @throws ValueError If the count is negative.
     */
    public function keepAtEnd(int $count): PortionObfuscatorBuilder
    {
        if ($count < 0) {
            throw new ValueError("$count < 0");
        }
        $this->_keepAtEnd = $count;
        return $this;
    }

    /**
     * Sets the minimum number of characters from the start that need to be obfuscated.
     * This will overrule any value set with `keepAtStart` or `keepAtEnd`.
     *
     * @param int $count The minimum number of characters to obfuscate. The default is 0.
     *
     * @return PortionObfuscatorBuilder This builder.
     * @throws ValueError If the count is negative.
     */
    public function atLeastFromStart(int $count): PortionObfuscatorBuilder
    {
        if ($count < 0) {
            throw new ValueError("$count < 0");
        }
        $this->_atLeastFromStart = $count;
        return $this;
    }

    /**
     * Sets the minimum number of characters from the end that need to be obfuscated.
     * This will overrule any value set with `keepAtStart` or `keepAtEnd`.
     *
     * @param int $count The minimum number of characters to obfuscate. The default is 0.
     *
     * @return PortionObfuscatorBuilder This builder.
     * @throws ValueError If the count is negative.
     */
    public function atLeastFromEnd(int $count): PortionObfuscatorBuilder
    {
        if ($count < 0) {
            throw new ValueError("$count < 0");
        }
        $this->_atLeastFromEnd = $count;
        return $this;
    }

    /**
     * Sets or removes the fixed total length to use for obfuscated contents.
     * When obfuscating, the result will have the mask added until this total length has been reached.
     *
     * Note: when used in combination with `keepAtStart` and/or `keepAtEnd`, this total length must be at least the sum
     * of both other values. When used in combination with both, parts of the input may be repeated in the obfuscated content if the input's
     * length is less than the combined number of characters to keep.
     *
     * @param int $fixedTotalLength The fixed total length for obfuscated contents, or a negative value to use the actual length of the input.
     *                              The default is -1.
     *
     * @return PortionObfuscatorBuilder This builder.
     */
    public function withFixedTotalLength(int $fixedTotalLength): PortionObfuscatorBuilder
    {
        $this->_fixedTotalLength = max(-1, $fixedTotalLength);
        return $this;
    }

    /**
     * Sets the string that created obfuscators use for obfuscating.
     *
     * @param string $mask The mask string. The default is `*`.
     *
     * @return PortionObfuscatorBuilder This builder.
     * @throws ValueError If the mask string is not exactly 1 character long.
     */
    public function withMask(string $mask): PortionObfuscatorBuilder
    {
        if (mb_strlen($mask) != 1) {
            throw new ValueError("'$mask' is not exactly 1 character long");
        }
        $this->_mask = $mask;
        return $this;
    }

    /**
     * Specifies that the default settings should be restored.
     * Calling this method is similar to calling the following:
     * * `keepAtStart(0)`
     * * `keepAtEnd(0)`
     * * `atLeastFromStart(0)`
     * * `atLeastFromEnd(0)`
     * * `withFixedTotalLength(-1)`
     * * `withMask('*')`
     *
     * @return PortionObfuscatorBuilder This builder.
     */
    public function withDefaults(): PortionObfuscatorBuilder
    {
        $this->keepAtStart(0);
        $this->keepAtEnd(0);
        $this->atLeastFromStart(0);
        $this->atLeastFromEnd(0);
        $this->withFixedTotalLength(-1);
        $this->withMask('*');
        return $this;
    }

    /**
     * Creates an immutable obfuscator with the current settings of this builder.
     *
     * @return Obfuscator An obfuscator with the current settings of this builder object.
     * @throws LogicException If this builder is in an invalid state, for example if `withFixedTotalLength` is smaller than
     *                        `keepAtStart` and `keepAtEnd` combined.
     */
    public function build(): Obfuscator
    {
        if ($this->_fixedTotalLength >= 0 && $this->_fixedTotalLength < $this->_keepAtStart + $this->_keepAtEnd) {
            throw new LogicException(
                "fixedTotalLength ($this->_fixedTotalLength) is smaller than keepAtStart ($this->_keepAtStart) + keepAtEnd ($this->_keepAtEnd)"
            );
        }

        return new class(
            $this->_keepAtStart,
            $this->_keepAtEnd,
            $this->_atLeastFromStart,
            $this->_atLeastFromEnd,
            $this->_fixedTotalLength,
            $this->_mask
        ) extends Obfuscator
        {
            private int $_keepAtStart;
            private int $_keepAtEnd;
            private int $_atLeastFromStart;
            private int $_atLeastFromEnd;
            private int $_fixedTotalLength;
            private string $_mask;

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function __construct(int $keepAtStart, int $keepAtEnd, int $atLeastFromStart, int $atLeastFromEnd, int $fixedTotalLength, string $mask)
            {
                $this->_keepAtStart = $keepAtStart;
                $this->_keepAtEnd = $keepAtEnd;
                $this->_atLeastFromStart = $atLeastFromStart;
                $this->_atLeastFromEnd = $atLeastFromEnd;
                $this->_fixedTotalLength = $fixedTotalLength;
                $this->_mask = $mask;
            }

            private function _fromStart(int $length): int
            {
                if ($this->_atLeastFromStart > 0) {
                    // the first characters need to be obfuscated so ignore keepAtStart
                    return 0;
                }
                // 0 <= keepAtMost <= length, the maximum number of characters to not obfuscate taking into account atLeastFromEnd
                // 0 <= result <= length, the minimum of what we want to obfuscate and what we can obfuscate
                $keepAtMost = max(0, $length - $this->_atLeastFromEnd);
                return min($this->_keepAtStart, $keepAtMost);
            }

            private function _fromEnd(int $length, int $keepFromStart, bool $allowDuplicates): int
            {
                if ($this->_atLeastFromEnd > 0) {
                    // the last characters need to be obfuscated so ignore keepAtEnd
                    return 0;
                }
                // 0 <= $available <= length, the number of characters not already handled by fromStart (to prevent characters being appended twice)
                //                            if $allowDuplicates then $available == $length
                // 0 <= $keepAtMost <= $length, the maximum number of characters to not obfuscate taking into account $this->_atLeastFromStart
                // 0 <= result <= $length, the minimum of what we want to obfuscate and what we can obfuscate
                $available = $allowDuplicates ? $length : $length - $keepFromStart;
                $keepAtMost = max(0, $length - $this->_atLeastFromStart);
                return min($this->_keepAtEnd, min($available, $keepAtMost));
            }

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function obfuscateText(string $text): string
            {
                $allowDuplicates = $this->_fixedTotalLength >= 0;

                $length = mb_strlen($text);
                $end = $length;
                $fromStart = $this->_fromStart($length);
                $fromEnd = $this->_fromEnd($length, $fromStart, $allowDuplicates);
                // 0 <= $fromStart <= $length
                // 0 <= $fromEnd <= $length

                if ($this->_fixedTotalLength >= 0) {
                    $length = $this->_fixedTotalLength;
                }

                // first build the content as expected: 0 to $fromStart non-obfuscated, then obfuscated, then from end - fromEnd non-obfuscated
                $result = mb_substr($text, 0, $fromStart);
                for ($i = $fromStart; $i < $length - $fromEnd; $i++) {
                    $result .= $this->_mask;
                }
                $result .= mb_substr($text, $end - $fromEnd);
                return $result;
            }
        };
    }
}
