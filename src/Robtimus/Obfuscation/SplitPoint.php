<?php
namespace Robtimus\Obfuscation;

use ValueError;

/**
 * A point in a text to split obfuscation.
 * Like `Obfuscator::untilLength`, this can be used to combine obfuscators. For instance, to obfuscate email addresses:
 *
 * ```php
 * $localPartObfuscator = Obfuscate::portion()
 *         ->keepAtStart(1)
 *         ->keepAtEnd(1)
 *         ->withFixedTotalLength(8)
 *         ->build();
 * $domainObfuscator = Obfuscate::none();
 * $obfuscator = SplitPoint::atFirst('@')->splitTo($localPartObfuscator, $domainObfuscator);
 * // Everything before @ will be obfuscated using $localPartObfuscator, everything after @ will not be obfuscated
 * // Example input: test@example.org
 * // Example output: t******t@example.org
 * ```
 *
 * Unlike `Obfuscator::untilLength` it's not possible to chain splitting, but it's of course possible to nest it:
 *
 * ```php
 * $localPartObfuscator = Obfuscate::portion()
 *         ->keepAtStart(1)
 *         ->keepAtEnd(1)
 *         ->withFixedTotalLength(8)
 *         ->build();
 * $domainObfuscator = SplitPoint::atLast('.')->splitTo(Obfuscate::all(), Obfuscate::none());
 * $obfuscator = SplitPoint::atFirst('@')->splitTo($localPartObfuscator, $domainObfuscator);
 * // Everything before @ will be obfuscated using $localPartObfuscator, everything after @ will be obfuscated until the last dot
 * // Example input: test@example.org
 * // Example output: t******t@*******.org
 * ```
 *
 * ## Sub classing
 * To create a sub class, implement both `splitStart` and `splitLength`.
 * Obfuscators created by calling `splitTo` use these two methods to determine how to split the text to obfuscate.
 * If `splitStart` returns -1, only the first obfuscator will be used. Otherwise, where `splitStart` is the result of calling `splitStart($text)`:
 * * The range from 0 to `splitStart` will be obfuscated using the first obfuscator.
 * * The range from `splitStart` to `splitStart + splitLength()` will not be obfuscated.
 * * The range from `splitStart + splitLength()` to `mb_strlen($text)` will be obfuscated using the second obfuscator.
 *
 * @package Robtimus\Obfuscation
 * @author  Rob Spoor <robtimus@users.noreply.github.com>
 * @license https://www.apache.org/licenses/LICENSE-2.0.txt The Apache Software License, Version 2.0
 */
abstract class SplitPoint
{
    /**
     * For a given text, finds the index where to start to split.
     *
     * @param string $text The text to find the split start index for.
     *
     * @return int The index in the given text where to start to split, or -1 to not split.
     */
    abstract protected function splitStart(string $text): int;

    /**
     * Returns the length of text ranges to not obfuscate when splitting.
     * All characters with an index between `splitStart` and `splitStart + splitLength()`,
     * where `splitStart` is the result of calling `splitStart($text)`, will not be obfuscated.
     *
     * @return int The length of text ranges to not obfuscate when splitting.
     */
    abstract protected function splitLength(): int;

    /**
     * Creates an obfuscator that splits obfuscation at this split point.
     * The part of the text before the split point will be obfuscated by one obfuscator, the part after the split point by another.
     *
     * @param Obfuscator $beforeSplitPoint The obfuscator to use before the split point.
     * @param Obfuscator $afterSplitPoint  The obfuscator to use after the split point.
     *
     * @return Obfuscator The created obfuscator.
     */
    final public function splitTo(Obfuscator $beforeSplitPoint, Obfuscator $afterSplitPoint): Obfuscator
    {
        /*
         * The splitStart and splitLength methods are protected, and therefore cannot be used in the anonymous Obfuscator class.
         * Use a callable function for the first and the return value for the second.
         * Unfortunately PHP doesn't (yet?) support fields of type callable, so it must be stored as mixed, and the phpstan type warning
         * when using it with call_user_func must be ignored.
         *
         * A possible alternative would be to use reflection, but that isn't more readable and unlikely to be (a lot more) performant.
         */

        $splitStart = function (string $text) {
            return $this->splitStart($text);
        };
        $splitLength = $this->splitLength();

        return new class($splitStart, $splitLength, $beforeSplitPoint, $afterSplitPoint) extends Obfuscator
        {
            private mixed $_splitStart;
            private int $_splitLength;
            private Obfuscator $_beforeSplitPoint;
            private Obfuscator $_afterSplitPoint;

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            function __construct(callable $splitStart, int $splitLength, Obfuscator $beforeSplitPoint, Obfuscator $afterSplitPoint)
            {
                $this->_splitStart = $splitStart;
                $this->_splitLength  = $splitLength;
                $this->_beforeSplitPoint = $beforeSplitPoint;
                $this->_afterSplitPoint = $afterSplitPoint;
            }

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function obfuscateText(string $text): string
            {
                // @phpstan-ignore-next-line (no error identifier available to be more specific)
                $splitStart = intval(call_user_func($this->_splitStart, $text));
                if ($splitStart === -1) {
                    return $this->_beforeSplitPoint->obfuscateText($text);
                }

                $resultBeforeSplitPoint = $this->_beforeSplitPoint->obfuscateText(mb_substr($text, 0, $splitStart));

                if ($this->_splitLength > 0) {
                    $split = mb_substr($text, $splitStart, $this->_splitLength);
                    $resultAfterSplitPoint = $this->_afterSplitPoint->obfuscateText(mb_substr($text, $splitStart + $this->_splitLength));
                    return $resultBeforeSplitPoint . $split . $resultAfterSplitPoint;
                }

                $resultAfterSplitPoint = $this->_afterSplitPoint->obfuscateText(mb_substr($text, $splitStart));
                return $resultBeforeSplitPoint . $resultAfterSplitPoint;
            }
        };
    }

    /**
     * Creates a new split point that splits at the first occurrence of a string.
     * This split point is exclusive; the text itself will not be obfuscated.
     *
     * @param string $s The string to split at.
     *
     * @return SplitPoint The created split point.
     * @throws ValueError If the string to split at is empty.
     */
    public static function atFirst(string $s): SplitPoint
    {
        if (mb_strlen($s) === 0) {
            throw new ValueError("cannot split on empty strings");
        }
        return new class($s) extends SplitPoint
        {
            private string $_splitAt;
            private int $_splitLength;

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            function __construct(string $splitAt)
            {
                $this->_splitAt = $splitAt;
                $this->_splitLength = mb_strlen($splitAt);
            }

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function splitStart(string $text): int
            {
                $result = mb_strpos($text, $this->_splitAt);
                return $result === false ? -1 : $result;
            }

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function splitLength(): int
            {
                return $this->_splitLength;
            }
        };
    }

    /**
     * Creates a new split point that splits at the last occurrence of a string.
     * This split point is exclusive; the text itself will not be obfuscated.
     *
     * @param string $s The string to split at.
     *
     * @return SplitPoint The created split point.
     * @throws ValueError If the string to split at is empty.
     */
    public static function atLast(string $s): SplitPoint
    {
        if (mb_strlen($s) === 0) {
            throw new ValueError("cannot split on empty strings");
        }
        return new class($s) extends SplitPoint
        {
            private string $_splitAt;
            private int $_splitLength;

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            function __construct(string $splitAt)
            {
                $this->_splitAt = $splitAt;
                $this->_splitLength = mb_strlen($splitAt);
            }

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function splitStart(string $text): int
            {
                $result = mb_strrpos($text, $this->_splitAt);
                return $result === false ? -1 : $result;
            }

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function splitLength(): int
            {
                return $this->_splitLength;
            }
        };
    }

    /**
     * Creates a new split point that splits at a specific occurrence of a string.
     * This split point is exclusive; the text itself will not be obfuscated.
     *
     * @param string $s          The string to split at.
     * @param int    $occurrence The zero-based occurrence of the string to split at.
     *
     * @return SplitPoint The created split point.
     * @throws ValueError If the string to split at is empty, or the occurrence is negative.
     */
    public static function atNth(string $s, int $occurrence): SplitPoint
    {
        if (mb_strlen($s) === 0) {
            throw new ValueError("cannot split on empty strings");
        }
        if ($occurrence < 0) {
            throw new ValueError("$occurrence < 0");
        }
        return new class($s, $occurrence) extends SplitPoint
        {
            private string $_splitAt;
            private int $_occurrence;
            private int $_splitLength;

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            function __construct(string $splitAt, int $occurrence)
            {
                $this->_splitAt = $splitAt;
                $this->_occurrence = $occurrence;
                $this->_splitLength = mb_strlen($splitAt);
            }

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function splitStart(string $text): int
            {
                $result = mb_strpos($text, $this->_splitAt);
                for ($i = 1; $i <= $this->_occurrence && $result !== false; $i++) {
                    $result = mb_strpos($text, $this->_splitAt, $result + 1);
                }
                return $result === false ? -1 : $result;
            }

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function splitLength(): int
            {
                return $this->_splitLength;
            }
        };
    }
}
