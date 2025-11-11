<?php
namespace Robtimus\Obfuscation;

use PHPUnit\Framework\TestCase;
use stdClass;

class ExamplesTest extends TestCase
{
    public function testObfuscateAll(): void
    {
        $obfuscator = Obfuscate::all();
        $obfuscated = $obfuscator->obfuscateText('Hello World');
        $this->assertEquals('***********', $obfuscated);
    }

    public function testObfuscateFixedText(): void
    {
        $obfuscator = Obfuscate::fixedLength(5);
        $obfuscated = $obfuscator->obfuscateText('Hello World');
        $this->assertEquals('*****', $obfuscated);
    }

    public function testObfuscsateFixedValue(): void
    {
        $obfuscator = Obfuscate::fixedValue('foo');
        $obfuscated = $obfuscator->obfuscateText('Hello World');
        $this->assertEquals('foo', $obfuscated);
    }

    public function testObfuscatePortionAllButLast4KeepAtEndOnly(): void
    {
        $obfuscator = Obfuscate::portion()
            ->keepAtEnd(4)
            ->build();
        $obfuscated = $obfuscator->obfuscateText('1234567890123456');
        $this->assertEquals('************3456', $obfuscated);
    }

    public function testObfuscatePortionAllbutLast4KeepAtEndAndAtLeastFromStart(): void
    {
        $obfuscator = Obfuscate::portion()
            ->keepAtEnd(4)
            ->atLeastFromStart(12)
            ->build();
        $obfuscated = $obfuscator->obfuscateText('1234567890');
        $this->assertEquals('**********', $obfuscated);
    }

    public function testObfuscatePortionOnlyLast2(): void
    {
        $obfuscator = Obfuscate::portion()
            ->keepAtStart(PHP_INT_MAX)
            ->atLeastFromEnd(2)
            ->build();
        $obfuscated = $obfuscator->obfuscateText('SW1A 2AA');
        $this->assertEquals('SW1A 2**', $obfuscated);
    }

    public function testObfuscatePortionUsingFixedLength(): void
    {
        $obfuscator = Obfuscate::portion()
            ->keepAtStart(2)
            ->keepAtEnd(2)
            ->withFixedTotalLength(6)
            ->build();
        $obfuscated = $obfuscator->obfuscateText('Hello World');
        $this->assertEquals('He**ld', $obfuscated);
        $obfuscated = $obfuscator->obfuscateText('foo');
        $this->assertEquals('fo**oo', $obfuscated);
    }

    public function testObfuscateNone(): void
    {
        $somePossiblyUndefinedObfuscator = null;
        // @phpstan-ignore ternary.alwaysFalse
        $obfuscator = $somePossiblyUndefinedObfuscator ?: Obfuscate::none();
        $obfuscated = $obfuscator->obfuscateText('Hello World');
        $this->assertEquals('Hello World', $obfuscated);
    }

    public function testObfuscateExploded(): void
    {
        $obfuscator = Obfuscate::exploded(', ', Obfuscate::fixedLength(3));
        $obfuscated = $obfuscator->obfuscateText('a, b, c');
        $this->assertEquals('***, ***, ***', $obfuscated);
    }

    public function testCombiningObfuscatorsPortionOnly(): void
    {
        $obfuscator = Obfuscate::portion()
            ->keepAtStart(4)
            ->keepAtEnd(4)
            ->build();
        $obfuscated = $obfuscator->obfuscateText('1234567890123456');
        $this->assertEquals('1234********3456', $obfuscated);
        $incorrectlyObfuscated = $obfuscator->obfuscateText('12345678901234');
        $this->assertEquals('1234******1234', $incorrectlyObfuscated);
    }

    public function testCombiningObfuscators16CharCreditCardNumbers(): void
    {
        $obfuscator = Obfuscate::none()->untilLength(4)
            ->then(Obfuscate::all())->untilLength(12)
            ->then(Obfuscate::none());
        $obfuscated = $obfuscator->obfuscateText('1234567890123456');
        $this->assertEquals('1234********3456', $obfuscated);
    }

    public function testCombiningObfuscatorsAnyLengthCreditCardNumbers(): void
    {
        $obfuscator = Obfuscate::none()->untilLength(4)
            ->then(Obfuscate::portion()
                ->keepAtEnd(4)
                ->atLeastFromStart(8)
                ->build());
        $obfuscated = $obfuscator->obfuscateText('12345678901234');
        $this->assertEquals('1234********34', $obfuscated);
    }

    public function testSplittingTextKeepDomainAsIs(): void
    {
        // Keep the domain as-is
        $localPartObfuscator = Obfuscate::portion()
            ->keepAtStart(1)
            ->keepAtEnd(1)
            ->withFixedTotalLength(8)
            ->build();
        $domainObfuscator = Obfuscate::none();
        $obfuscator = SplitPoint::atFirst('@')->splitTo($localPartObfuscator, $domainObfuscator);
        $obfuscated = $obfuscator->obfuscateText('test@example.org');
        $this->assertEquals('t******t@example.org', $obfuscated);
    }

    public function testSplittingTextDomainKeepOnlyTLD(): void
    {
        // Keep only the TLD of the domain
        $localPartObfuscator = Obfuscate::portion()
            ->keepAtStart(1)
            ->keepAtEnd(1)
            ->withFixedTotalLength(8)
            ->build();
        $domainObfuscator = SplitPoint::atLast('.')->splitTo(Obfuscate::all(), Obfuscate::none());
        $obfuscator = SplitPoint::atFirst('@')->splitTo($localPartObfuscator, $domainObfuscator);
        $obfuscated = $obfuscator->obfuscateText('test@example.org');
        $this->assertEquals('t******t@*******.org', $obfuscated);
    }

    public function testCustomObfuscators(): void
    {
        $obfuscator = new class extends Obfuscator
        {
            public function __construct()
            {
                parent::__construct();
            }

            public function obfuscateText(string $text): string
            {
                return strtoupper($text);
            }
        };
        $obfuscated = $obfuscator->obfuscateText('Hello World');
        $this->assertEquals('HELLO WORLD', $obfuscated);
    }

    public function testObfuscatingObjectPropertiesConfiguredWithObfuscators(): void
    {
        $propertyObfuscator = PropertyObfuscator::builder()
            ->withProperty('password', Obfuscate::fixedLength(3))
            ->build();
        $obfuscatedPassword = $propertyObfuscator->obfuscateProperty('password', 'admin1234');
        $this->assertEquals('***', $obfuscatedPassword);
        $obfuscatedUsername = $propertyObfuscator->obfuscateProperty('username', 'admin');
        $this->assertEquals('admin', $obfuscatedUsername);
        $object = new stdClass();
        $object->username = 'admin';
        $object->password = 'admin1234';
        $obfuscatedObject = $propertyObfuscator->obfuscateProperties($object);
        $expectedObfuscatedObject = new stdClass();
        $expectedObfuscatedObject->username = 'admin';
        $expectedObfuscatedObject->password = '***';
        $this->assertEquals($expectedObfuscatedObject, $obfuscatedObject);
        $obfuscatedArray = $propertyObfuscator->obfuscateProperties(array('username' => 'admin', 'password' => 'admin1234'));
        $this->assertEquals(array('username' => 'admin', 'password' => '***'), $obfuscatedArray);
    }

    public function testObfuscatingObjectPropertiesConfiguredPerProperty(): void
    {
        $propertyObfuscator = PropertyObfuscator::builder()
            ->withProperty('password', Obfuscate::fixedLength(3), false) // defaults to true
                ->forObjects(PropertyObfuscationMode::EXCLUDE) // defaults to INHERIT
                ->forArrays(PropertyObfuscationMode::EXCLUDE) // defaults to INHERIT
            ->build();
        $obfuscatedPassword = $propertyObfuscator->obfuscateProperty('password', 'admin1234');
        $this->assertEquals('***', $obfuscatedPassword);
        $obfuscatedUsername = $propertyObfuscator->obfuscateProperty('username', 'admin');
        $this->assertEquals('admin', $obfuscatedUsername);
        $object = new stdClass();
        $object->username = 'admin';
        $object->password = 'admin1234';
        $obfuscatedObject = $propertyObfuscator->obfuscateProperties($object);
        $expectedObfuscatedObject = new stdClass();
        $expectedObfuscatedObject->username = 'admin';
        $expectedObfuscatedObject->password = '***';
        $this->assertEquals($expectedObfuscatedObject, $obfuscatedObject);
        $obfuscatedArray = $propertyObfuscator->obfuscateProperties(array('username' => 'admin', 'password' => 'admin1234'));
        $this->assertEquals(array('username' => 'admin', 'password' => '***'), $obfuscatedArray);
    }

    public function testObfuscatingObjectPropertiesConfiguredGlobally(): void
    {
        $propertyObfuscator = PropertyObfuscator::builder()
            ->caseInsensitiveByDefault() // defaults to case sensitive
            ->forObjectsByDefault(PropertyObfuscationMode::EXCLUDE) // defaults to INHERIT
            ->forArraysByDefault(PropertyObfuscationMode::EXCLUDE) // defaults to INHERIT
            ->withProperty('password', Obfuscate::fixedLength(3))
            ->build();
        $obfuscatedPassword = $propertyObfuscator->obfuscateProperty('password', 'admin1234');
        $this->assertEquals('***', $obfuscatedPassword);
        $obfuscatedUsername = $propertyObfuscator->obfuscateProperty('username', 'admin');
        $this->assertEquals('admin', $obfuscatedUsername);
        $object = new stdClass();
        $object->username = 'admin';
        $object->password = 'admin1234';
        $obfuscatedObject = $propertyObfuscator->obfuscateProperties($object);
        $expectedObfuscatedObject = new stdClass();
        $expectedObfuscatedObject->username = 'admin';
        $expectedObfuscatedObject->password = '***';
        $this->assertEquals($expectedObfuscatedObject, $obfuscatedObject);
        $obfuscatedArray = $propertyObfuscator->obfuscateProperties(array('username' => 'admin', 'password' => 'admin1234'));
        $this->assertEquals(array('username' => 'admin', 'password' => '***'), $obfuscatedArray);
    }

    public function testObfuscatingHttpHeaders(): void
    {
        $headerObfuscator = HeaderObfuscator::builder()
            ->withHeader('Authorization', Obfuscate::fixedLength(3))
            ->withHeader('Multiple-values', Obfuscate::exploded(', ', Obfuscate::fixedLength(3)))
            ->build();
        $obfuscatedAuthorization = $headerObfuscator->obfuscateHeaderValue('authorization', 'Bearer someToken');
        $this->assertEquals('***', $obfuscatedAuthorization);
        $obfuscatedAuthorizations = $headerObfuscator->obfuscateHeaderValues('authorization', array('Bearer someToken'));
        $this->assertEquals(array('***'), $obfuscatedAuthorizations);
        $obfuscatedMultipleValues = $headerObfuscator->obfuscateHeaderValue('multiple-values', 'value1, value2, value3');
        $this->assertEquals('***, ***, ***', $obfuscatedMultipleValues);
        $obfuscatedContentType = $headerObfuscator->obfuscateHeaderValue('Content-Type', 'application/json');
        $this->assertEquals('application/json', $obfuscatedContentType);
        $obfuscatedHeaders = $headerObfuscator->obfuscateHeaders(array(
            'authorization'   => 'Bearer someToken',
            'multiple-values' => 'value1, value2, value3',
            'content-type'    => 'application/json',
        ));
        $this->assertEquals(array('authorization' => '***', 'multiple-values' => '***, ***, ***', 'content-type' => 'application/json'), $obfuscatedHeaders);
    }
}
