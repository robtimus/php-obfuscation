<?php
namespace Robtimus\Obfuscation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ValueError;

class HeaderObfuscatorTest extends TestCase
{
    public function testDuplicateHeaderNames(): void
    {
        $obfuscator = Obfuscate::fixedLength(3);
        $builder = HeaderObfuscator::builder()
            ->withHeader('Authorization', $obfuscator);

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Duplicate header name: authorization');

        $builder->withHeader('authorization', $obfuscator);
    }

    #[DataProvider('obfuscateHeaderValueParameters')]
    public function testobfuscateHeaderValue(HeaderObfuscator $obfuscator, string $name, string $value, string $expected): void
    {
        $this->assertEquals($expected, $obfuscator->obfuscateHeaderValue($name, $value));
    }

    /**
     * @return array<array{0: HeaderObfuscator, 1: string, 2: string, 3: string}>
     */
    public static function obfuscateHeaderValueParameters(): array
    {
        $obfuscator = HeaderObfuscator::builder()
            ->withHeader('Authorization', Obfuscate::all())
            ->build();
        return [
            [$obfuscator, 'authorization', 'value', '*****'],
            [$obfuscator, 'Authorization', 'value', '*****'],
            [$obfuscator, 'other', 'value', 'value'],
            [$obfuscator, 'Other', 'value', 'value'],
        ];
    }

    /**
     * @param array<string> $values
     * @param array<string> $expected
     */
    #[DataProvider('obfuscateHeaderValuesParameters')]
    public function testobfuscateHeaderValues(HeaderObfuscator $obfuscator, string $name, array $values, array $expected): void
    {
        $obfuscated = $obfuscator->obfuscateHeaderValues($name, $values);
        $this->assertEquals($expected, $obfuscated);
        if ($values !== $expected) {
            $this->assertNotEquals($values, $obfuscated);
        }
    }

    /**
     * @return array<array{0: HeaderObfuscator, 1: string, 2: array<string>, 3: array<string>}>
     */
    public static function obfuscateHeaderValuesParameters(): array
    {
        $obfuscator = HeaderObfuscator::builder()
            ->withHeader('Authorization', Obfuscate::portion()->keepAtEnd(2)->build())
            ->build();
        return [
            [$obfuscator, 'authorization', ['value1', 'value2'], ['****e1', '****e2']],
            [$obfuscator, 'Authorization', ['value1', 'value2'], ['****e1', '****e2']],
            [$obfuscator, 'other', ['value1', 'value2'], ['value1', 'value2']],
            [$obfuscator, 'Other', ['value1', 'value2'], ['value1', 'value2']],
        ];
    }

    public function testObfuscateHeaders(): void
    {
        $obfuscator = HeaderObfuscator::builder()
            ->withHeader('Authorization', Obfuscate::fixedLength(3))
            ->withHeader('Authorizations', Obfuscate::fixedLength(5))
            ->build();

        $headers = [
            'authorization'  => 'value',
            'authorizations' => ['value1', 'value2'],
            'other1'         => 'value',
            'other2'         => ['value1', 'value2'],
        ];

        $expected = [
            'authorization'  => '***',
            'authorizations' => ['*****', '*****'],
            'other1'         => 'value',
            'other2'         => ['value1', 'value2'],
        ];

        $obfuscated = $obfuscator->obfuscateHeaders($headers);

        $this->assertEquals($expected, $obfuscated);
    }

    public function testBuildCreatesHeadersSnapshot(): void
    {
        $builder = HeaderObfuscator::builder()
            ->withHeader('Authorization', Obfuscate::fixedLength(3));

        $obfuscator = $builder->build();

        $this->assertEquals('***', $obfuscator->obfuscateHeaderValue('authorization', 'value'));
        $this->assertEquals('value', $obfuscator->obfuscateHeaderValue('test', 'value'));

        $builder->withHeader('test', Obfuscate::all());

        $this->assertEquals('***', $obfuscator->obfuscateHeaderValue('authorization', 'value'));
        $this->assertEquals('value', $obfuscator->obfuscateHeaderValue('test', 'value'));

        $obfuscator2 = $builder->build();

        $this->assertEquals('***', $obfuscator2->obfuscateHeaderValue('authorization', 'value'));
        $this->assertEquals('*****', $obfuscator2->obfuscateHeaderValue('test', 'value'));
    }
}
