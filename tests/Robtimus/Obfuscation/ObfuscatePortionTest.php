<?php
namespace Robtimus\Obfuscation;

use LogicException;
use ValueError;

class ObfuscatePortionTest extends ObfuscatorTestCase
{
    public static function obfuscationParameters(): array
    {
        return [
            // keepAtStart(4)
            [Obfuscator::portion()->keepAtStart(4)->build(), 'foo', 'foo'],
            [Obfuscator::portion()->keepAtStart(4)->build(), 'hello', 'hell*'],
            [Obfuscator::portion()->keepAtStart(4)->build(), 'hello world', 'hell*******'],
            [Obfuscator::portion()->keepAtStart(4)->build(), '', ''],
            [Obfuscator::portion()->keepAtStart(4)->withMask('x')->build(), 'hello world', 'hellxxxxxxx'],
            // keepAtEnd(4)
            [Obfuscator::portion()->keepAtEnd(4)->build(), 'foo', 'foo'],
            [Obfuscator::portion()->keepAtEnd(4)->build(), 'hello', '*ello'],
            [Obfuscator::portion()->keepAtEnd(4)->build(), 'hello world', '*******orld'],
            [Obfuscator::portion()->keepAtEnd(4)->build(), '', ''],
            [Obfuscator::portion()->keepAtEnd(4)->withMask('x')->build(), 'hello world', 'xxxxxxxorld'],
            // keepAtStart(4) + keepAtEnd(4)
            [Obfuscator::portion()->keepAtStart(4)->keepAtEnd(4)->build(), 'foo', 'foo'],
            [Obfuscator::portion()->keepAtStart(4)->keepAtEnd(4)->build(), 'hello', 'hello'],
            [Obfuscator::portion()->keepAtStart(4)->keepAtEnd(4)->build(), 'hello world', 'hell***orld'],
            [Obfuscator::portion()->keepAtStart(4)->keepAtEnd(4)->build(), '', ''],
            [Obfuscator::portion()->keepAtStart(4)->keepAtEnd(4)->withMask('x')->build(), 'hello world', 'hellxxxorld'],
            // keepAtStart(4) + atLeastFromEnd(4)
            [Obfuscator::portion()->keepAtStart(4)->atLeastFromEnd(4)->build(), 'foo', '***'],
            [Obfuscator::portion()->keepAtStart(4)->atLeastFromEnd(4)->build(), 'hello', 'h****'],
            [Obfuscator::portion()->keepAtStart(4)->atLeastFromEnd(4)->build(), 'hello world', 'hell*******'],
            [Obfuscator::portion()->keepAtStart(4)->atLeastFromEnd(4)->build(), '', ''],
            [Obfuscator::portion()->keepAtStart(4)->atLeastFromEnd(4)->withMask('x')->build(), 'hello world', 'hellxxxxxxx'],
            // keepAtEnd(4) + atLeastFromStart(4)
            [Obfuscator::portion()->keepAtEnd(4)->atLeastFromStart(4)->build(), 'foo', '***'],
            [Obfuscator::portion()->keepAtEnd(4)->atLeastFromStart(4)->build(), 'hello', '****o'],
            [Obfuscator::portion()->keepAtEnd(4)->atLeastFromStart(4)->build(), 'hello world', '*******orld'],
            [Obfuscator::portion()->keepAtEnd(4)->atLeastFromStart(4)->build(), '', ''],
            [Obfuscator::portion()->keepAtEnd(4)->atLeastFromStart(4)->withMask('x')->build(), 'hello world', 'xxxxxxxorld'],
            // keepAtStart(4) + withFixedTotalLength(9)
            [Obfuscator::portion()->keepAtStart(4)->withFixedTotalLength(9)->build(), 'foo', 'foo******'],
            [Obfuscator::portion()->keepAtStart(4)->withFixedTotalLength(9)->build(), 'hello', 'hell*****'],
            [Obfuscator::portion()->keepAtStart(4)->withFixedTotalLength(9)->build(), 'hello world', 'hell*****'],
            [Obfuscator::portion()->keepAtStart(4)->withFixedTotalLength(9)->build(), '', '*********'],
            [Obfuscator::portion()->keepAtStart(4)->withFixedTotalLength(9)->withMask('x')->build(), 'hello world', 'hellxxxxx'],
            // keepAtEnd(4) + withFixedTotalLength(9)
            [Obfuscator::portion()->keepAtEnd(4)->withFixedTotalLength(9)->build(), 'foo', '******foo'],
            [Obfuscator::portion()->keepAtEnd(4)->withFixedTotalLength(9)->build(), 'hello', '*****ello'],
            [Obfuscator::portion()->keepAtEnd(4)->withFixedTotalLength(9)->build(), 'hello world', '*****orld'],
            [Obfuscator::portion()->keepAtEnd(4)->withFixedTotalLength(9)->build(), '', '*********'],
            [Obfuscator::portion()->keepAtEnd(4)->withFixedTotalLength(9)->withMask('x')->build(), 'hello world', 'xxxxxorld'],
            // keepAtStart(4) + keepAtEnd(4) + withFixedTotalLength(9)
            [Obfuscator::portion()->keepAtStart(4)->keepAtEnd(4)->withFixedTotalLength(9)->build(), 'foo', 'foo***foo'],
            [Obfuscator::portion()->keepAtStart(4)->keepAtEnd(4)->withFixedTotalLength(9)->build(), 'hello', 'hell*ello'],
            [Obfuscator::portion()->keepAtStart(4)->keepAtEnd(4)->withFixedTotalLength(9)->build(), 'hello world', 'hell*orld'],
            [Obfuscator::portion()->keepAtStart(4)->keepAtEnd(4)->withFixedTotalLength(9)->build(), '', '*********'],
            [Obfuscator::portion()->keepAtStart(4)->keepAtEnd(4)->withFixedTotalLength(9)->withMask('x')->build(), 'hello world', 'hellxorld'],
            // keepAtStart(4) + atLeastFromEnd(4) + withFixedTotalLength(9)
            [Obfuscator::portion()->keepAtStart(4)->atLeastFromEnd(4)->withFixedTotalLength(9)->build(), 'foo', '*********'],
            [Obfuscator::portion()->keepAtStart(4)->atLeastFromEnd(4)->withFixedTotalLength(9)->build(), 'hello', 'h********'],
            [Obfuscator::portion()->keepAtStart(4)->atLeastFromEnd(4)->withFixedTotalLength(9)->build(), 'hello world', 'hell*****'],
            [Obfuscator::portion()->keepAtStart(4)->atLeastFromEnd(4)->withFixedTotalLength(9)->build(), '', '*********'],
            [Obfuscator::portion()->keepAtStart(4)->atLeastFromEnd(4)->withFixedTotalLength(9)->withMask('x')->build(), 'hello world', 'hellxxxxx'],
            // keepAtEnd(4) + atLeastFromStart(4) + withFixedTotalLength(9)
            [Obfuscator::portion()->keepAtEnd(4)->atLeastFromStart(4)->withFixedTotalLength(9)->build(), 'foo', '*********'],
            [Obfuscator::portion()->keepAtEnd(4)->atLeastFromStart(4)->withFixedTotalLength(9)->build(), 'hello', '********o'],
            [Obfuscator::portion()->keepAtEnd(4)->atLeastFromStart(4)->withFixedTotalLength(9)->build(), 'hello world', '*****orld'],
            [Obfuscator::portion()->keepAtEnd(4)->atLeastFromStart(4)->withFixedTotalLength(9)->build(), '', '*********'],
            [Obfuscator::portion()->keepAtEnd(4)->atLeastFromStart(4)->withFixedTotalLength(9)->withMask('x')->build(), 'hello world', 'xxxxxorld'],
            // keepAtStart(4) + withFixedTotalLength(4)
            [Obfuscator::portion()->keepAtStart(4)->withFixedTotalLength(4)->build(), 'foo', 'foo*'],
            [Obfuscator::portion()->keepAtStart(4)->withFixedTotalLength(4)->build(), 'hello', 'hell'],
            [Obfuscator::portion()->keepAtStart(4)->withFixedTotalLength(4)->build(), 'hello world', 'hell'],
            [Obfuscator::portion()->keepAtStart(4)->withFixedTotalLength(4)->build(), '', '****'],
            [Obfuscator::portion()->keepAtStart(4)->withFixedTotalLength(4)->withMask('x')->build(), 'hello world', 'hell'],
            // keepAtEnd(4) + withFixedTotalLength(4)
            [Obfuscator::portion()->keepAtEnd(4)->withFixedTotalLength(4)->build(), 'foo', '*foo'],
            [Obfuscator::portion()->keepAtEnd(4)->withFixedTotalLength(4)->build(), 'hello', 'ello'],
            [Obfuscator::portion()->keepAtEnd(4)->withFixedTotalLength(4)->build(), 'hello world', 'orld'],
            [Obfuscator::portion()->keepAtEnd(4)->withFixedTotalLength(4)->build(), '', '****'],
            [Obfuscator::portion()->keepAtEnd(4)->withFixedTotalLength(4)->withMask('x')->build(), 'hello world', 'orld'],
            // keepAtStart(4) + keepAtEnd(4) + withFixedTotalLength(8)
            [Obfuscator::portion()->keepAtStart(4)->keepAtEnd(4)->withFixedTotalLength(8)->build(), 'foo', 'foo**foo'],
            [Obfuscator::portion()->keepAtStart(4)->keepAtEnd(4)->withFixedTotalLength(8)->build(), 'hello', 'hellello'],
            [Obfuscator::portion()->keepAtStart(4)->keepAtEnd(4)->withFixedTotalLength(8)->build(), 'hello world', 'hellorld'],
            [Obfuscator::portion()->keepAtStart(4)->keepAtEnd(4)->withFixedTotalLength(8)->build(), '', '********'],
            [Obfuscator::portion()->keepAtStart(4)->keepAtEnd(4)->withFixedTotalLength(8)->withMask('x')->build(), 'hello world', 'hellorld'],
            // keepAtStart(4) + atLeastFromEnd(4) + withFixedTotalLength(4)
            [Obfuscator::portion()->keepAtStart(4)->atLeastFromEnd(4)->withFixedTotalLength(4)->build(), 'foo', '****'],
            [Obfuscator::portion()->keepAtStart(4)->atLeastFromEnd(4)->withFixedTotalLength(4)->build(), 'hello', 'h***'],
            [Obfuscator::portion()->keepAtStart(4)->atLeastFromEnd(4)->withFixedTotalLength(4)->build(), 'hello world', 'hell'],
            [Obfuscator::portion()->keepAtStart(4)->atLeastFromEnd(4)->withFixedTotalLength(4)->build(), '', '****'],
            [Obfuscator::portion()->keepAtStart(4)->atLeastFromEnd(4)->withFixedTotalLength(4)->withMask('x')->build(), 'hello world', 'hell'],
            // keepAtEnd(4) + atLeastFromStart(4) + withFixedTotalLength(4)
            [Obfuscator::portion()->keepAtEnd(4)->atLeastFromStart(4)->withFixedTotalLength(4)->build(), 'foo', '****'],
            [Obfuscator::portion()->keepAtEnd(4)->atLeastFromStart(4)->withFixedTotalLength(4)->build(), 'hello', '***o'],
            [Obfuscator::portion()->keepAtEnd(4)->atLeastFromStart(4)->withFixedTotalLength(4)->build(), 'hello world', 'orld'],
            [Obfuscator::portion()->keepAtEnd(4)->atLeastFromStart(4)->withFixedTotalLength(4)->build(), '', '****'],
            [Obfuscator::portion()->keepAtEnd(4)->atLeastFromStart(4)->withFixedTotalLength(4)->withMask('x')->build(), 'hello world', 'orld'],
            // obfuscate last 2 characters
            [Obfuscator::portion()->keepAtStart(PHP_INT_MAX)->atLeastFromEnd(2)->build(), 'foo', 'f**'],
            [Obfuscator::portion()->keepAtStart(PHP_INT_MAX)->atLeastFromEnd(2)->build(), 'hello', 'hel**'],
            [Obfuscator::portion()->keepAtStart(PHP_INT_MAX)->atLeastFromEnd(2)->build(), 'hello world', 'hello wor**'],
            [Obfuscator::portion()->keepAtStart(PHP_INT_MAX)->atLeastFromEnd(2)->build(), '', ''],
            [Obfuscator::portion()->keepAtStart(PHP_INT_MAX)->atLeastFromEnd(2)->withMask('x')->build(), 'hello world', 'hello worxx'],
            // obfuscate first 2 characters
            [Obfuscator::portion()->keepAtEnd(PHP_INT_MAX)->atLeastFromStart(2)->build(), 'foo', '**o'],
            [Obfuscator::portion()->keepAtEnd(PHP_INT_MAX)->atLeastFromStart(2)->build(), 'hello', '**llo'],
            [Obfuscator::portion()->keepAtEnd(PHP_INT_MAX)->atLeastFromStart(2)->build(), 'hello world', '**llo world'],
            [Obfuscator::portion()->keepAtEnd(PHP_INT_MAX)->atLeastFromStart(2)->build(), '', ''],
            [Obfuscator::portion()->keepAtEnd(PHP_INT_MAX)->atLeastFromStart(2)->withMask('x')->build(), 'hello world', 'xxllo world'],
        ];
    }

    public function testNegativeKeepAtStart(): void
    {
        $builder = Obfuscator::portion();

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('-1 < 0');

        $builder->keepAtStart(-1);
    }

    public function testNegativeKeepAtEnd(): void
    {
        $builder = Obfuscator::portion();

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('-1 < 0');

        $builder->keepAtEnd(-1);
    }

    public function testNegativeAtLeastFromStart(): void
    {
        $builder = Obfuscator::portion();

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('-1 < 0');

        $builder->atLeastFromStart(-1);
    }

    public function testNegativeAtLeastFromEnd(): void
    {
        $builder = Obfuscator::portion();

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('-1 < 0');

        $builder->atLeastFromEnd(-1);
    }

    public function testMaskTooShort(): void
    {
        $builder = Obfuscator::portion();

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage("'' is not exactly 1 character long");

        $builder->withMask('');
    }

    public function testMaskTooLong(): void
    {
        $builder = Obfuscator::portion();

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage("'**' is not exactly 1 character long");

        $builder->withMask('**');
    }

    public function testFixedTotalLengthSmallerThanKeepAtStartPlusKeepAtEnd(): void
    {
        $builder = Obfuscator::portion()->keepAtStart(1)->keepAtEnd(1)->withFixedTotalLength(1);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('fixedTotalLength (1) is smaller than keepAtStart (1) + keepAtEnd (1)');

        $builder->build();
    }
}
