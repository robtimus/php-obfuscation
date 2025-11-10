<?php
namespace Robtimus\Obfuscation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ValueError;

class SplitPointTest extends TestCase
{
    #[DataProvider('atFirstParameters')]
    public function testAtFirst(Obfuscator $obfuscator, string $input, string $expected): void
    {
        $this->assertEquals($expected, $obfuscator->obfuscateText($input));
    }

    /**
     * @return array<array{0: Obfuscator, 1: string, 2: string}>
     */
    public static function atFirstParameters(): array
    {
        $obfuscator = SplitPoint::atFirst('@')->splitTo(Obfuscate::all(), Obfuscate::fixedLength(5));
        return [
            [$obfuscator, 'test', '****'],
            [$obfuscator, 'test@', '****@*****'],
            [$obfuscator, 'test@example.org', '****@*****'],
            [$obfuscator, 'test@example.org@', '****@*****'],
            [$obfuscator, 'test@example.org@localhost', '****@*****'],
            [$obfuscator, '', ''],
        ];
    }

    #[DataProvider('atLastParameters')]
    public function testAtLast(Obfuscator $obfuscator, string $input, string $expected): void
    {
        $this->assertEquals($expected, $obfuscator->obfuscateText($input));
    }

    /**
     * @return array<array{0: Obfuscator, 1: string, 2: string}>
     */
    public static function atLastParameters(): array
    {
        $obfuscator = SplitPoint::atLast('@')->splitTo(Obfuscate::all(), Obfuscate::fixedLength(5));
        return [
            [$obfuscator, 'test', '****'],
            [$obfuscator, 'test@', '****@*****'],
            [$obfuscator, 'test@example.org', '****@*****'],
            [$obfuscator, 'test@example.org@', '****************@*****'],
            [$obfuscator, 'test@example.org@localhost', '****************@*****'],
            [$obfuscator, '', ''],
        ];
    }

    #[DataProvider('atNthParameters')]
    public function testAtNth(Obfuscator $obfuscator, string $input, string $expected): void
    {
        $this->assertEquals($expected, $obfuscator->obfuscateText($input));
    }

    /**
     * @return array<array{0: Obfuscator, 1: string, 2: string}>
     */
    public static function atNthParameters(): array
    {
        $obfuscator0 = SplitPoint::atNth('.', 0)
                ->splitTo(Obfuscate::fixedValue('xxx'), Obfuscate::fixedLength(5));
        $obfuscator1 = SplitPoint::atNth('.', 1)
                ->splitTo(Obfuscate::fixedValue('xxx'), Obfuscate::fixedLength(5));
        $obfuscator2 = SplitPoint::atNth('.', 2)
                ->splitTo(Obfuscate::fixedValue('xxx'), Obfuscate::fixedLength(5));
        return [
            // At 0th
            [$obfuscator0, 'alpha', 'xxx'],
            [$obfuscator0, 'alpha.', 'xxx.*****'],
            [$obfuscator0, 'alpha.bravo', 'xxx.*****'],
            [$obfuscator0, 'alpha.bravo.charlie', 'xxx.*****'],
            [$obfuscator0, 'alpha.bravo.charlie.', 'xxx.*****'],
            [$obfuscator0, 'alpha.bravo.charlie.delta', 'xxx.*****'],
            [$obfuscator0, 'alpha.bravo.charlie.delta.echo', 'xxx.*****'],
            [$obfuscator0, '........', 'xxx.*****'],
            [$obfuscator0, '', 'xxx'],
            // At 1st
            [$obfuscator1, 'alpha', 'xxx'],
            [$obfuscator1, 'alpha.bravo', 'xxx'],
            [$obfuscator1, 'alpha.bravo.', 'xxx.*****'],
            [$obfuscator1, 'alpha.bravo.charlie', 'xxx.*****'],
            [$obfuscator1, 'alpha.bravo.charlie.', 'xxx.*****'],
            [$obfuscator1, 'alpha.bravo.charlie.delta', 'xxx.*****'],
            [$obfuscator1, 'alpha.bravo.charlie.delta.echo', 'xxx.*****'],
            [$obfuscator1, '........', 'xxx.*****'],
            [$obfuscator1, '', 'xxx'],
            // At 2nd
            [$obfuscator2, 'alpha', 'xxx'],
            [$obfuscator2, 'alpha.bravo', 'xxx'],
            [$obfuscator2, 'alpha.bravo.', 'xxx'],
            [$obfuscator2, 'alpha.bravo.charlie', 'xxx'],
            [$obfuscator2, 'alpha.bravo.charlie.', 'xxx.*****'],
            [$obfuscator2, 'alpha.bravo.charlie.delta', 'xxx.*****'],
            [$obfuscator2, 'alpha.bravo.charlie.delta.echo', 'xxx.*****'],
            [$obfuscator2, '........', 'xxx.*****'],
            [$obfuscator2, '', 'xxx'],
        ];
    }

    public function testAtFirstWithEmptyString(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('cannot split on empty strings');

        SplitPoint::atFirst('');
    }

    public function testAtLastWithEmptyString(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('cannot split on empty strings');

        SplitPoint::atLast('');
    }

    public function testAtNthWithEmptyString(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('cannot split on empty strings');

        SplitPoint::atNth('', 0);
    }

    public function testAtNthWithNegativeOccurrence(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('-1 < 0');

        SplitPoint::atNth('@', -1);
    }

    #[DataProvider('zeroSplitLengthParameters')]
    public function testZeroSplitLength(Obfuscator $obfuscator, string $input, string $expected): void
    {
        $this->assertEquals($expected, $obfuscator->obfuscateText($input));
    }

    /**
     * @return array<array{0: Obfuscator, 1: string, 2: string}>
     */
    public static function zeroSplitLengthParameters(): array
    {
        $splitPoint = new class extends SplitPoint
        {
            private SplitPoint $_delegate;

            public function __construct()
            {
                $this->_delegate = SplitPoint::atFirst('@');
            }

            protected function splitStart(string $text): int
            {
                return $this->_delegate->splitStart($text);
            }

            protected function splitLength(): int
            {
                return 0;
            }
        };
        $obfuscator = $splitPoint->splitTo(Obfuscate::fixedLength(3), Obfuscate::all('x'));
        return [
            [$obfuscator, 'test', '***'],
            [$obfuscator, 'test@', '***x'],
            [$obfuscator, 'test@example.org', '***xxxxxxxxxxxx'],
            [$obfuscator, 'test@example.org@', '***xxxxxxxxxxxxx'],
            [$obfuscator, 'test@example.org@localhost', '***xxxxxxxxxxxxxxxxxxxxxx'],
            [$obfuscator, '', '***'],
        ];
    }

    #[DataProvider('splitAtStartParameters')]
    public function testSplitAtStart(Obfuscator $obfuscator, string $input, string $expected): void
    {
        $this->assertEquals($expected, $obfuscator->obfuscateText($input));
    }

    /**
     * @return array<array{0: Obfuscator, 1: string, 2: string}>
     */
    public static function splitAtStartParameters(): array
    {
        $splitPoint = new class extends SplitPoint
        {
            protected function splitStart(string $text): int
            {
                return 0;
            }

            protected function splitLength(): int
            {
                return 0;
            }
        };
        $obfuscator = $splitPoint->splitTo(Obfuscate::fixedLength(3), Obfuscate::all('x'));
        return [
            [$obfuscator, 'test', '***xxxx'],
            [$obfuscator, 'test@', '***xxxxx'],
            [$obfuscator, 'test@example.org', '***xxxxxxxxxxxxxxxx'],
            [$obfuscator, 'test@example.org@', '***xxxxxxxxxxxxxxxxx'],
            [$obfuscator, 'test@example.org@localhost', '***xxxxxxxxxxxxxxxxxxxxxxxxxx'],
            [$obfuscator, '', '***'],
        ];
    }

    #[DataProvider('splitAtEndParameters')]
    public function testSplitAtEnd(Obfuscator $obfuscator, string $input, string $expected): void
    {
        $this->assertEquals($expected, $obfuscator->obfuscateText($input));
    }

    /**
     * @return array<array{0: Obfuscator, 1: string, 2: string}>
     */
    public static function splitAtEndParameters(): array
    {
        $splitPoint = new class extends SplitPoint
        {
            protected function splitStart(string $text): int
            {
                return mb_strlen($text);
            }

            protected function splitLength(): int
            {
                return 0;
            }
        };
        $obfuscator = $splitPoint->splitTo(Obfuscate::fixedLength(3), Obfuscate::fixedValue('xxx'));
        return [
            [$obfuscator, 'test', '***xxx'],
            [$obfuscator, 'test@', '***xxx'],
            [$obfuscator, 'test@example.org', '***xxx'],
            [$obfuscator, 'test@example.org@', '***xxx'],
            [$obfuscator, 'test@example.org@localhost', '***xxx'],
            [$obfuscator, '', '***xxx'],
        ];
    }
}
