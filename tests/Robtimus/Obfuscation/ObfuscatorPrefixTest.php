<?php
namespace Robtimus\Obfuscation;

use PHPUnit\Framework\Attributes\DataProvider;
use ValueError;

class ObfuscatorPrefixTest extends ObfuscatorTestCase
{
    public static function obfuscationParameters(): array
    {
        $obfuscator1 = Obfuscate::none()->untilLength(4)->then(Obfuscate::all())->untilLength(12)->then(Obfuscate::none());
        $obfuscator2 = Obfuscate::none()->untilLength(4)->then(Obfuscate::fixedLength(3));
        $obfuscator3 = Obfuscate::fixedLength(3)->untilLength(4)->then(Obfuscate::none());
        $obfuscator4 = Obfuscate::fixedLength(3)->untilLength(4)->then(Obfuscate::fixedValue('xxx'));
        return [
            // none() untilLength(4) then all(0) untilLength(12) then none()
            [$obfuscator1, '0', '0'],
            [$obfuscator1, '01', '01'],
            [$obfuscator1, '012', '012'],
            [$obfuscator1, '0123', '0123'],
            [$obfuscator1, '01234', '0123*'],
            [$obfuscator1, '012345', '0123**'],
            [$obfuscator1, '0123456', '0123***'],
            [$obfuscator1, '01234567', '0123****'],
            [$obfuscator1, '012345678', '0123*****'],
            [$obfuscator1, '0123456789', '0123******'],
            [$obfuscator1, '0123456789A', '0123*******'],
            [$obfuscator1, '0123456789AB', '0123********'],
            [$obfuscator1, '0123456789ABC', '0123********C'],
            [$obfuscator1, '0123456789ABCD', '0123********CD'],
            [$obfuscator1, '0123456789ABCDE', '0123********CDE'],
            [$obfuscator1, '0123456789ABCDEF', '0123********CDEF'],
            [$obfuscator1, '', ''],
            // none() untilLength(4) then fixedLength(3)
            [$obfuscator2, '0', '0'],
            [$obfuscator2, '01', '01'],
            [$obfuscator2, '012', '012'],
            [$obfuscator2, '0123', '0123'],
            [$obfuscator2, '01234', '0123***'],
            [$obfuscator2, '012345', '0123***'],
            [$obfuscator2, '', ''],
            // fixedLength(3) untilLength(4) then none()
            [$obfuscator3, '0', '***'],
            [$obfuscator3, '01', '***'],
            [$obfuscator3, '012', '***'],
            [$obfuscator3, '0123', '***'],
            [$obfuscator3, '01234', '***4'],
            [$obfuscator3, '012345', '***45'],
            [$obfuscator3, '', '***'],
            // fixedLength(3) untilLength(4) then fixedValue(xxx)
            [$obfuscator4, '0', '***'],
            [$obfuscator4, '01', '***'],
            [$obfuscator4, '012', '***'],
            [$obfuscator4, '0123', '***'],
            [$obfuscator4, '01234', '***xxx'],
            [$obfuscator4, '012345', '***xxx'],
            [$obfuscator4, '', '***'],
        ];
    }

    #[DataProvider('obfuscators')]
    public function testInvalidFirstPrefixLength(Obfuscator $obfuscator): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('0 <= 0');

        $obfuscator->untilLength(0);
    }

    #[DataProvider('obfuscators')]
    public function testInvalidSecondPrefixLength(Obfuscator $firstObfuscator): void
    {
        $obfuscator = $firstObfuscator->untilLength(1)->then(Obfuscate::all());

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('1 <= 1');

        $obfuscator->untilLength(1);
    }

    #[DataProvider('obfuscators')]
    public function testInvalidThirdPrefixLength(Obfuscator $firstObfuscator): void
    {
        $obfuscator = $firstObfuscator->untilLength(1)->then(Obfuscate::all())->untilLength(2)->then(Obfuscate::none());

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('2 <= 2');

        $obfuscator->untilLength(2);
    }

    /**
     * @return array<array{0: Obfuscator}>
     */
    public static function obfuscators(): array
    {
        return [
            [Obfuscate::all()],
            [Obfuscate::none()],
            [Obfuscate::fixedLength(3)],
            [Obfuscate::fixedValue('xxx')],
            [Obfuscate::portion()->build()],
        ];
    }
}
