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
            [Obfuscate::portion()->keepAtStart(4)->build(), 'foo', 'foo'],
            [Obfuscate::portion()->keepAtStart(4)->build(), 'hello', 'hell*'],
            [Obfuscate::portion()->keepAtStart(4)->build(), 'hello world', 'hell*******'],
            [Obfuscate::portion()->keepAtStart(4)->build(), '', ''],
            [Obfuscate::portion()->keepAtStart(4)->withMask('x')->build(), 'hello world', 'hellxxxxxxx'],
            // keepAtEnd(4)
            [Obfuscate::portion()->keepAtEnd(4)->build(), 'foo', 'foo'],
            [Obfuscate::portion()->keepAtEnd(4)->build(), 'hello', '*ello'],
            [Obfuscate::portion()->keepAtEnd(4)->build(), 'hello world', '*******orld'],
            [Obfuscate::portion()->keepAtEnd(4)->build(), '', ''],
            [Obfuscate::portion()->keepAtEnd(4)->withMask('x')->build(), 'hello world', 'xxxxxxxorld'],
            // keepAtStart(4) + keepAtEnd(4)
            [Obfuscate::portion()->keepAtStart(4)->keepAtEnd(4)->build(), 'foo', 'foo'],
            [Obfuscate::portion()->keepAtStart(4)->keepAtEnd(4)->build(), 'hello', 'hello'],
            [Obfuscate::portion()->keepAtStart(4)->keepAtEnd(4)->build(), 'hello world', 'hell***orld'],
            [Obfuscate::portion()->keepAtStart(4)->keepAtEnd(4)->build(), '', ''],
            [Obfuscate::portion()->keepAtStart(4)->keepAtEnd(4)->withMask('x')->build(), 'hello world', 'hellxxxorld'],
            // keepAtStart(4) + atLeastFromEnd(4)
            [Obfuscate::portion()->keepAtStart(4)->atLeastFromEnd(4)->build(), 'foo', '***'],
            [Obfuscate::portion()->keepAtStart(4)->atLeastFromEnd(4)->build(), 'hello', 'h****'],
            [Obfuscate::portion()->keepAtStart(4)->atLeastFromEnd(4)->build(), 'hello world', 'hell*******'],
            [Obfuscate::portion()->keepAtStart(4)->atLeastFromEnd(4)->build(), '', ''],
            [Obfuscate::portion()->keepAtStart(4)->atLeastFromEnd(4)->withMask('x')->build(), 'hello world', 'hellxxxxxxx'],
            // keepAtEnd(4) + atLeastFromStart(4)
            [Obfuscate::portion()->keepAtEnd(4)->atLeastFromStart(4)->build(), 'foo', '***'],
            [Obfuscate::portion()->keepAtEnd(4)->atLeastFromStart(4)->build(), 'hello', '****o'],
            [Obfuscate::portion()->keepAtEnd(4)->atLeastFromStart(4)->build(), 'hello world', '*******orld'],
            [Obfuscate::portion()->keepAtEnd(4)->atLeastFromStart(4)->build(), '', ''],
            [Obfuscate::portion()->keepAtEnd(4)->atLeastFromStart(4)->withMask('x')->build(), 'hello world', 'xxxxxxxorld'],
            // keepAtStart(4) + withFixedTotalLength(9)
            [Obfuscate::portion()->keepAtStart(4)->withFixedTotalLength(9)->build(), 'foo', 'foo******'],
            [Obfuscate::portion()->keepAtStart(4)->withFixedTotalLength(9)->build(), 'hello', 'hell*****'],
            [Obfuscate::portion()->keepAtStart(4)->withFixedTotalLength(9)->build(), 'hello world', 'hell*****'],
            [Obfuscate::portion()->keepAtStart(4)->withFixedTotalLength(9)->build(), '', '*********'],
            [Obfuscate::portion()->keepAtStart(4)->withFixedTotalLength(9)->withMask('x')->build(), 'hello world', 'hellxxxxx'],
            // keepAtEnd(4) + withFixedTotalLength(9)
            [Obfuscate::portion()->keepAtEnd(4)->withFixedTotalLength(9)->build(), 'foo', '******foo'],
            [Obfuscate::portion()->keepAtEnd(4)->withFixedTotalLength(9)->build(), 'hello', '*****ello'],
            [Obfuscate::portion()->keepAtEnd(4)->withFixedTotalLength(9)->build(), 'hello world', '*****orld'],
            [Obfuscate::portion()->keepAtEnd(4)->withFixedTotalLength(9)->build(), '', '*********'],
            [Obfuscate::portion()->keepAtEnd(4)->withFixedTotalLength(9)->withMask('x')->build(), 'hello world', 'xxxxxorld'],
            // keepAtStart(4) + keepAtEnd(4) + withFixedTotalLength(9)
            [Obfuscate::portion()->keepAtStart(4)->keepAtEnd(4)->withFixedTotalLength(9)->build(), 'foo', 'foo***foo'],
            [Obfuscate::portion()->keepAtStart(4)->keepAtEnd(4)->withFixedTotalLength(9)->build(), 'hello', 'hell*ello'],
            [Obfuscate::portion()->keepAtStart(4)->keepAtEnd(4)->withFixedTotalLength(9)->build(), 'hello world', 'hell*orld'],
            [Obfuscate::portion()->keepAtStart(4)->keepAtEnd(4)->withFixedTotalLength(9)->build(), '', '*********'],
            [Obfuscate::portion()->keepAtStart(4)->keepAtEnd(4)->withFixedTotalLength(9)->withMask('x')->build(), 'hello world', 'hellxorld'],
            // keepAtStart(4) + atLeastFromEnd(4) + withFixedTotalLength(9)
            [Obfuscate::portion()->keepAtStart(4)->atLeastFromEnd(4)->withFixedTotalLength(9)->build(), 'foo', '*********'],
            [Obfuscate::portion()->keepAtStart(4)->atLeastFromEnd(4)->withFixedTotalLength(9)->build(), 'hello', 'h********'],
            [Obfuscate::portion()->keepAtStart(4)->atLeastFromEnd(4)->withFixedTotalLength(9)->build(), 'hello world', 'hell*****'],
            [Obfuscate::portion()->keepAtStart(4)->atLeastFromEnd(4)->withFixedTotalLength(9)->build(), '', '*********'],
            [Obfuscate::portion()->keepAtStart(4)->atLeastFromEnd(4)->withFixedTotalLength(9)->withMask('x')->build(), 'hello world', 'hellxxxxx'],
            // keepAtEnd(4) + atLeastFromStart(4) + withFixedTotalLength(9)
            [Obfuscate::portion()->keepAtEnd(4)->atLeastFromStart(4)->withFixedTotalLength(9)->build(), 'foo', '*********'],
            [Obfuscate::portion()->keepAtEnd(4)->atLeastFromStart(4)->withFixedTotalLength(9)->build(), 'hello', '********o'],
            [Obfuscate::portion()->keepAtEnd(4)->atLeastFromStart(4)->withFixedTotalLength(9)->build(), 'hello world', '*****orld'],
            [Obfuscate::portion()->keepAtEnd(4)->atLeastFromStart(4)->withFixedTotalLength(9)->build(), '', '*********'],
            [Obfuscate::portion()->keepAtEnd(4)->atLeastFromStart(4)->withFixedTotalLength(9)->withMask('x')->build(), 'hello world', 'xxxxxorld'],
            // keepAtStart(4) + withFixedTotalLength(4)
            [Obfuscate::portion()->keepAtStart(4)->withFixedTotalLength(4)->build(), 'foo', 'foo*'],
            [Obfuscate::portion()->keepAtStart(4)->withFixedTotalLength(4)->build(), 'hello', 'hell'],
            [Obfuscate::portion()->keepAtStart(4)->withFixedTotalLength(4)->build(), 'hello world', 'hell'],
            [Obfuscate::portion()->keepAtStart(4)->withFixedTotalLength(4)->build(), '', '****'],
            [Obfuscate::portion()->keepAtStart(4)->withFixedTotalLength(4)->withMask('x')->build(), 'hello world', 'hell'],
            // keepAtEnd(4) + withFixedTotalLength(4)
            [Obfuscate::portion()->keepAtEnd(4)->withFixedTotalLength(4)->build(), 'foo', '*foo'],
            [Obfuscate::portion()->keepAtEnd(4)->withFixedTotalLength(4)->build(), 'hello', 'ello'],
            [Obfuscate::portion()->keepAtEnd(4)->withFixedTotalLength(4)->build(), 'hello world', 'orld'],
            [Obfuscate::portion()->keepAtEnd(4)->withFixedTotalLength(4)->build(), '', '****'],
            [Obfuscate::portion()->keepAtEnd(4)->withFixedTotalLength(4)->withMask('x')->build(), 'hello world', 'orld'],
            // keepAtStart(4) + keepAtEnd(4) + withFixedTotalLength(8)
            [Obfuscate::portion()->keepAtStart(4)->keepAtEnd(4)->withFixedTotalLength(8)->build(), 'foo', 'foo**foo'],
            [Obfuscate::portion()->keepAtStart(4)->keepAtEnd(4)->withFixedTotalLength(8)->build(), 'hello', 'hellello'],
            [Obfuscate::portion()->keepAtStart(4)->keepAtEnd(4)->withFixedTotalLength(8)->build(), 'hello world', 'hellorld'],
            [Obfuscate::portion()->keepAtStart(4)->keepAtEnd(4)->withFixedTotalLength(8)->build(), '', '********'],
            [Obfuscate::portion()->keepAtStart(4)->keepAtEnd(4)->withFixedTotalLength(8)->withMask('x')->build(), 'hello world', 'hellorld'],
            // keepAtStart(4) + atLeastFromEnd(4) + withFixedTotalLength(4)
            [Obfuscate::portion()->keepAtStart(4)->atLeastFromEnd(4)->withFixedTotalLength(4)->build(), 'foo', '****'],
            [Obfuscate::portion()->keepAtStart(4)->atLeastFromEnd(4)->withFixedTotalLength(4)->build(), 'hello', 'h***'],
            [Obfuscate::portion()->keepAtStart(4)->atLeastFromEnd(4)->withFixedTotalLength(4)->build(), 'hello world', 'hell'],
            [Obfuscate::portion()->keepAtStart(4)->atLeastFromEnd(4)->withFixedTotalLength(4)->build(), '', '****'],
            [Obfuscate::portion()->keepAtStart(4)->atLeastFromEnd(4)->withFixedTotalLength(4)->withMask('x')->build(), 'hello world', 'hell'],
            // keepAtEnd(4) + atLeastFromStart(4) + withFixedTotalLength(4)
            [Obfuscate::portion()->keepAtEnd(4)->atLeastFromStart(4)->withFixedTotalLength(4)->build(), 'foo', '****'],
            [Obfuscate::portion()->keepAtEnd(4)->atLeastFromStart(4)->withFixedTotalLength(4)->build(), 'hello', '***o'],
            [Obfuscate::portion()->keepAtEnd(4)->atLeastFromStart(4)->withFixedTotalLength(4)->build(), 'hello world', 'orld'],
            [Obfuscate::portion()->keepAtEnd(4)->atLeastFromStart(4)->withFixedTotalLength(4)->build(), '', '****'],
            [Obfuscate::portion()->keepAtEnd(4)->atLeastFromStart(4)->withFixedTotalLength(4)->withMask('x')->build(), 'hello world', 'orld'],
            // obfuscate last 2 characters
            [Obfuscate::portion()->keepAtStart(PHP_INT_MAX)->atLeastFromEnd(2)->build(), 'foo', 'f**'],
            [Obfuscate::portion()->keepAtStart(PHP_INT_MAX)->atLeastFromEnd(2)->build(), 'hello', 'hel**'],
            [Obfuscate::portion()->keepAtStart(PHP_INT_MAX)->atLeastFromEnd(2)->build(), 'hello world', 'hello wor**'],
            [Obfuscate::portion()->keepAtStart(PHP_INT_MAX)->atLeastFromEnd(2)->build(), '', ''],
            [Obfuscate::portion()->keepAtStart(PHP_INT_MAX)->atLeastFromEnd(2)->withMask('x')->build(), 'hello world', 'hello worxx'],
            // obfuscate first 2 characters
            [Obfuscate::portion()->keepAtEnd(PHP_INT_MAX)->atLeastFromStart(2)->build(), 'foo', '**o'],
            [Obfuscate::portion()->keepAtEnd(PHP_INT_MAX)->atLeastFromStart(2)->build(), 'hello', '**llo'],
            [Obfuscate::portion()->keepAtEnd(PHP_INT_MAX)->atLeastFromStart(2)->build(), 'hello world', '**llo world'],
            [Obfuscate::portion()->keepAtEnd(PHP_INT_MAX)->atLeastFromStart(2)->build(), '', ''],
            [Obfuscate::portion()->keepAtEnd(PHP_INT_MAX)->atLeastFromStart(2)->withMask('x')->build(), 'hello world', 'xxllo world'],
        ];
    }

    public function testNegativeKeepAtStart(): void
    {
        $builder = Obfuscate::portion();

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('-1 < 0');

        $builder->keepAtStart(-1);
    }

    public function testNegativeKeepAtEnd(): void
    {
        $builder = Obfuscate::portion();

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('-1 < 0');

        $builder->keepAtEnd(-1);
    }

    public function testNegativeAtLeastFromStart(): void
    {
        $builder = Obfuscate::portion();

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('-1 < 0');

        $builder->atLeastFromStart(-1);
    }

    public function testNegativeAtLeastFromEnd(): void
    {
        $builder = Obfuscate::portion();

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('-1 < 0');

        $builder->atLeastFromEnd(-1);
    }

    public function testMaskTooShort(): void
    {
        $builder = Obfuscate::portion();

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage("'' is not exactly 1 character long");

        $builder->withMask('');
    }

    public function testMaskTooLong(): void
    {
        $builder = Obfuscate::portion();

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage("'**' is not exactly 1 character long");

        $builder->withMask('**');
    }

    public function testFixedTotalLengthSmallerThanKeepAtStartPlusKeepAtEnd(): void
    {
        $builder = Obfuscate::portion()->keepAtStart(1)->keepAtEnd(1)->withFixedTotalLength(1);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('fixedTotalLength (1) is smaller than keepAtStart (1) + keepAtEnd (1)');

        $builder->build();
    }
}
