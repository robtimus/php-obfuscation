<?php
namespace Robtimus\Obfuscation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ValueError;

class PropertyObfuscatorTest extends TestCase
{
    private const INPUT_JSON = <<<EOD
{
    "string": "string\"int",
    "int": 123456,
    "float": 1234.56,
    "booleanTrue": true,
    "booleanFalse": false,
    "null": null,
    "object": {
        "string": "string\"int",
        "int": 123456,
        "float": 1234.56,
        "booleanTrue": true,
        "booleanFalse": false,
        "null": null,
        "nested": [{
                "prop1": "1",
                "prop2": "2"
            }
        ]
    },
    "array": [["1", "2"], {}
    ],
    "notMatchedString": "123456",
    "notMatchedInt": 123456,
    "notMatchedFloat": 1234.56,
    "notMatchedBooleanTrue": true,
    "notMatchedBooleanFalse": false,
    "nonMatchedNull": null,
    "nonMatchedObject": {
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    },
    "nested": [{
            "string": "string\"int",
            "int": 123456,
            "float": 1234.56,
            "booleanTrue": true,
            "booleanFalse": false,
            "null": null,
            "object": {
                "string": "string\"int",
                "int": 123456,
                "float": 1234.56,
                "booleanTrue": true,
                "booleanFalse": false,
                "nested": [{
                        "prop1": "1",
                        "prop2": "2"
                    }
                ]
            },
            "array": [["1", "2"], {}
            ],
            "notMatchedString": "123456",
            "notMatchedInt": 123456,
            "notMatchedFloat": 1234.56,
            "notMatchedBooleanTrue": true,
            "notMatchedBooleanFalse": false,
            "nonMatchedNull": null
        }
    ],
    "notObfuscated": {
        "string": "string\"int",
        "int": 123456,
        "float": 1234.56,
        "booleanTrue": true,
        "booleanFalse": false,
        "null": null,
        "object": {
            "string": "string\"int",
            "int": 123456,
            "float": 1234.56,
            "booleanTrue": true,
            "booleanFalse": false,
            "nested": [{
                    "prop1": "1",
                    "prop2": "2"
                }
            ]
        },
        "array": [["1", "2"], {}
        ],
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    }
}
EOD;

    public function testDuplicateCaseSensitivePropertyNames(): void
    {
        $obfuscator = Obfuscate::fixedLength(3);
        $builder = PropertyObfuscator::builder()
            ->withProperty('test', $obfuscator)
            ->withProperty('test', $obfuscator, false)
            ->withProperty('TEST', $obfuscator);

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Duplicate property name: test (case sensitive)');

        $builder->withProperty('test', $obfuscator, true);
    }

    public function testDuplicateCaseInsensitivePropertyNames(): void
    {
        $obfuscator = Obfuscate::fixedLength(3);
        $builder = PropertyObfuscator::builder()
            ->withProperty('test', $obfuscator)
            ->withProperty('test', $obfuscator, false);

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Duplicate property name: TEST (case insensitive)');

        $builder->withProperty('TEST', $obfuscator, false);
    }

    #[DataProvider('obfuscatePropertyGloballyCaseSensitiveParameters')]
    public function testObfuscatePropertyGloballyCaseSensitive(string $propertyName, string $value, string $expected): void
    {
        $obfuscator = Obfuscate::fixedLength(3);
        // phpcs:disable PEAR.WhiteSpace.ObjectOperatorIndent
        $propertyObfuscator = PropertyObfuscator::builder()
            ->caseSensitiveByDefault()
            ->withProperty('string', $obfuscator)
            ->withProperty('notObfuscated', Obfuscate::none())
                ->forObjects(PropertyObfuscationMode::SKIP)
                ->forArrays(PropertyObfuscationMode::SKIP)
            ->build();
        // phpcs:enable

        $obfuscated = $propertyObfuscator->obfuscateProperty($propertyName, $value);

        $this->assertEquals($expected, $obfuscated);
    }

    /**
     * @return array<array{0: string, 1: string, 2: string}>
     */
    public static function obfuscatePropertyGloballyCaseSensitiveParameters(): array
    {
        return [
            ['string', 'hello world', '***'],
            ['notObfuscated', 'hello world', 'hello world'],
            ['notMatched', 'hello world', 'hello world'],
        ];
    }

    #[DataProvider('obfuscatePropertyGloballyCaseInsensitiveParameters')]
    public function testObfuscatePropertyGloballyCaseInsensitive(string $propertyName, string $value, string $expected): void
    {
        $obfuscator = Obfuscate::fixedLength(3);
        // phpcs:disable PEAR.WhiteSpace.ObjectOperatorIndent
        $propertyObfuscator = PropertyObfuscator::builder()
            ->caseInsensitiveByDefault()
            ->withProperty('STRING', $obfuscator)
            ->withProperty('NOTOBFUSCATED', Obfuscate::none())
                ->forObjects(PropertyObfuscationMode::SKIP)
                ->forArrays(PropertyObfuscationMode::SKIP)
            ->build();
        // phpcs:enable

        $obfuscated = $propertyObfuscator->obfuscateProperty($propertyName, $value);

        $this->assertEquals($expected, $obfuscated);
    }

    /**
     * @return array<array{0: string, 1: string, 2: string}>
     */
    public static function obfuscatePropertyGloballyCaseInsensitiveParameters(): array
    {
        return [
            ['string', 'hello world', '***'],
            ['notObfuscated', 'hello world', 'hello world'],
            ['notMatched', 'hello world', 'hello world'],
        ];
    }

    public function testObfuscateObjectGloballyCaseSensitive(): void
    {
        $inputObject = json_decode(self::INPUT_JSON, false);

        $obfuscator = Obfuscate::fixedLength(3);
        // phpcs:disable PEAR.WhiteSpace.ObjectOperatorIndent
        $propertyObfuscator = PropertyObfuscator::builder()
            ->caseSensitiveByDefault()
            ->withProperty('string', $obfuscator)
            ->withProperty('int', $obfuscator)
            ->withProperty('float', $obfuscator)
            ->withProperty('booleanTrue', $obfuscator)
            ->withProperty('booleanFalse', $obfuscator)
            ->withProperty('object', Obfuscate::fixedLength(3, 'o'))
            ->withProperty('array', Obfuscate::fixedLength(3, 'a'))
            ->withProperty('null', $obfuscator)
            ->withProperty('notObfuscated', Obfuscate::none())
                ->forObjects(PropertyObfuscationMode::SKIP)
                ->forArrays(PropertyObfuscationMode::SKIP)
            ->build();
        // phpcs:enable

        $obfuscated = $propertyObfuscator->obfuscateProperties($inputObject);

        $this->assertInstanceOf('stdClass', $obfuscated);

        $obfuscatedJson = json_encode($obfuscated, JSON_PRETTY_PRINT);

        $expectedJson = <<<EOD
{
    "string": "***",
    "int": "***",
    "float": "***",
    "booleanTrue": "***",
    "booleanFalse": "***",
    "null": "***",
    "object": {
        "string": "ooo",
        "int": "ooo",
        "float": "ooo",
        "booleanTrue": "ooo",
        "booleanFalse": "ooo",
        "null": "ooo",
        "nested": [
            {
                "prop1": "ooo",
                "prop2": "ooo"
            }
        ]
    },
    "array": [
        [
            "aaa",
            "aaa"
        ],
        {}
    ],
    "notMatchedString": "123456",
    "notMatchedInt": 123456,
    "notMatchedFloat": 1234.56,
    "notMatchedBooleanTrue": true,
    "notMatchedBooleanFalse": false,
    "nonMatchedNull": null,
    "nonMatchedObject": {
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    },
    "nested": [
        {
            "string": "***",
            "int": "***",
            "float": "***",
            "booleanTrue": "***",
            "booleanFalse": "***",
            "null": "***",
            "object": {
                "string": "ooo",
                "int": "ooo",
                "float": "ooo",
                "booleanTrue": "ooo",
                "booleanFalse": "ooo",
                "nested": [
                    {
                        "prop1": "ooo",
                        "prop2": "ooo"
                    }
                ]
            },
            "array": [
                [
                    "aaa",
                    "aaa"
                ],
                {}
            ],
            "notMatchedString": "123456",
            "notMatchedInt": 123456,
            "notMatchedFloat": 1234.56,
            "notMatchedBooleanTrue": true,
            "notMatchedBooleanFalse": false,
            "nonMatchedNull": null
        }
    ],
    "notObfuscated": {
        "string": "string\"int",
        "int": 123456,
        "float": 1234.56,
        "booleanTrue": true,
        "booleanFalse": false,
        "null": null,
        "object": {
            "string": "string\"int",
            "int": 123456,
            "float": 1234.56,
            "booleanTrue": true,
            "booleanFalse": false,
            "nested": [
                {
                    "prop1": "1",
                    "prop2": "2"
                }
            ]
        },
        "array": [
            [
                "1",
                "2"
            ],
            {}
        ],
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    }
}
EOD;
        $this->assertEquals($expectedJson, $obfuscatedJson);
    }

    public function testObfuscateObjectGloballyCaseInsensitive(): void
    {
        $inputObject = json_decode(self::INPUT_JSON, false);

        $obfuscator = Obfuscate::fixedLength(3);
        // phpcs:disable PEAR.WhiteSpace.ObjectOperatorIndent
        $propertyObfuscator = PropertyObfuscator::builder()
            ->caseInsensitiveByDefault()
            ->withProperty('STRING', $obfuscator)
            ->withProperty('INT', $obfuscator)
            ->withProperty('FLOAT', $obfuscator)
            ->withProperty('BOOLEANTRUE', $obfuscator)
            ->withProperty('BOOLEANFALSE', $obfuscator)
            ->withProperty('OBJECT', Obfuscate::fixedLength(3, 'o'))
            ->withProperty('ARRAY', Obfuscate::fixedLength(3, 'a'))
            ->withProperty('NULL', $obfuscator)
            ->withProperty('NOTOBFUSCATED', Obfuscate::none())
                ->forObjects(PropertyObfuscationMode::SKIP)
                ->forArrays(PropertyObfuscationMode::SKIP)
            ->build();
        // phpcs:enable

        $obfuscated = $propertyObfuscator->obfuscateProperties($inputObject);

        $this->assertInstanceOf('stdClass', $obfuscated);

        $obfuscatedJson = json_encode($obfuscated, JSON_PRETTY_PRINT);

        $expectedJson = <<<EOD
{
    "string": "***",
    "int": "***",
    "float": "***",
    "booleanTrue": "***",
    "booleanFalse": "***",
    "null": "***",
    "object": {
        "string": "ooo",
        "int": "ooo",
        "float": "ooo",
        "booleanTrue": "ooo",
        "booleanFalse": "ooo",
        "null": "ooo",
        "nested": [
            {
                "prop1": "ooo",
                "prop2": "ooo"
            }
        ]
    },
    "array": [
        [
            "aaa",
            "aaa"
        ],
        {}
    ],
    "notMatchedString": "123456",
    "notMatchedInt": 123456,
    "notMatchedFloat": 1234.56,
    "notMatchedBooleanTrue": true,
    "notMatchedBooleanFalse": false,
    "nonMatchedNull": null,
    "nonMatchedObject": {
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    },
    "nested": [
        {
            "string": "***",
            "int": "***",
            "float": "***",
            "booleanTrue": "***",
            "booleanFalse": "***",
            "null": "***",
            "object": {
                "string": "ooo",
                "int": "ooo",
                "float": "ooo",
                "booleanTrue": "ooo",
                "booleanFalse": "ooo",
                "nested": [
                    {
                        "prop1": "ooo",
                        "prop2": "ooo"
                    }
                ]
            },
            "array": [
                [
                    "aaa",
                    "aaa"
                ],
                {}
            ],
            "notMatchedString": "123456",
            "notMatchedInt": 123456,
            "notMatchedFloat": 1234.56,
            "notMatchedBooleanTrue": true,
            "notMatchedBooleanFalse": false,
            "nonMatchedNull": null
        }
    ],
    "notObfuscated": {
        "string": "string\"int",
        "int": 123456,
        "float": 1234.56,
        "booleanTrue": true,
        "booleanFalse": false,
        "null": null,
        "object": {
            "string": "string\"int",
            "int": 123456,
            "float": 1234.56,
            "booleanTrue": true,
            "booleanFalse": false,
            "nested": [
                {
                    "prop1": "1",
                    "prop2": "2"
                }
            ]
        },
        "array": [
            [
                "1",
                "2"
            ],
            {}
        ],
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    }
}
EOD;
        $this->assertEquals($expectedJson, $obfuscatedJson);
    }

    public function testObfuscateObjectGloballyExcludingObjectsAndArrays(): void
    {
        $inputObject = json_decode(self::INPUT_JSON, false);

        $obfuscator = Obfuscate::fixedLength(3);
        // phpcs:disable PEAR.WhiteSpace.ObjectOperatorIndent
        $propertyObfuscator = PropertyObfuscator::builder()
            ->forObjectsByDefault(PropertyObfuscationMode::EXCLUDE)
            ->forArraysByDefault(PropertyObfuscationMode::EXCLUDE)
            ->withProperty('string', $obfuscator)
            ->withProperty('int', $obfuscator)
            ->withProperty('float', $obfuscator)
            ->withProperty('booleanTrue', $obfuscator)
            ->withProperty('booleanFalse', $obfuscator)
            ->withProperty('object', Obfuscate::fixedLength(3, 'o'))
            ->withProperty('array', Obfuscate::fixedLength(3, 'a'))
            ->withProperty('null', $obfuscator)
            ->withProperty('notObfuscated', Obfuscate::none())
                ->forObjects(PropertyObfuscationMode::SKIP)
                ->forArrays(PropertyObfuscationMode::SKIP)
            ->build();
        // phpcs:enable

        $obfuscated = $propertyObfuscator->obfuscateProperties($inputObject);

        $this->assertInstanceOf('stdClass', $obfuscated);

        $obfuscatedJson = json_encode($obfuscated, JSON_PRETTY_PRINT);

        $expectedJson = <<<EOD
{
    "string": "***",
    "int": "***",
    "float": "***",
    "booleanTrue": "***",
    "booleanFalse": "***",
    "null": "***",
    "object": {
        "string": "***",
        "int": "***",
        "float": "***",
        "booleanTrue": "***",
        "booleanFalse": "***",
        "null": "***",
        "nested": [
            {
                "prop1": "1",
                "prop2": "2"
            }
        ]
    },
    "array": [
        [
            "1",
            "2"
        ],
        {}
    ],
    "notMatchedString": "123456",
    "notMatchedInt": 123456,
    "notMatchedFloat": 1234.56,
    "notMatchedBooleanTrue": true,
    "notMatchedBooleanFalse": false,
    "nonMatchedNull": null,
    "nonMatchedObject": {
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    },
    "nested": [
        {
            "string": "***",
            "int": "***",
            "float": "***",
            "booleanTrue": "***",
            "booleanFalse": "***",
            "null": "***",
            "object": {
                "string": "***",
                "int": "***",
                "float": "***",
                "booleanTrue": "***",
                "booleanFalse": "***",
                "nested": [
                    {
                        "prop1": "1",
                        "prop2": "2"
                    }
                ]
            },
            "array": [
                [
                    "1",
                    "2"
                ],
                {}
            ],
            "notMatchedString": "123456",
            "notMatchedInt": 123456,
            "notMatchedFloat": 1234.56,
            "notMatchedBooleanTrue": true,
            "notMatchedBooleanFalse": false,
            "nonMatchedNull": null
        }
    ],
    "notObfuscated": {
        "string": "string\"int",
        "int": 123456,
        "float": 1234.56,
        "booleanTrue": true,
        "booleanFalse": false,
        "null": null,
        "object": {
            "string": "string\"int",
            "int": 123456,
            "float": 1234.56,
            "booleanTrue": true,
            "booleanFalse": false,
            "nested": [
                {
                    "prop1": "1",
                    "prop2": "2"
                }
            ]
        },
        "array": [
            [
                "1",
                "2"
            ],
            {}
        ],
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    }
}
EOD;
        $this->assertEquals($expectedJson, $obfuscatedJson);
    }

    public function testObfuscateObjectExcludingObjectsAndArraysOverridingGlobals(): void
    {
        $inputObject = json_decode(self::INPUT_JSON, false);

        $obfuscator = Obfuscate::fixedLength(3);
        // phpcs:disable PEAR.WhiteSpace.ObjectOperatorIndent
        $propertyObfuscator = PropertyObfuscator::builder()
            ->forObjectsByDefault(PropertyObfuscationMode::INHERIT)
            ->forArraysByDefault(PropertyObfuscationMode::INHERIT)
            ->withProperty('string', $obfuscator)
                ->forObjects(PropertyObfuscationMode::EXCLUDE)
                ->forArrays(PropertyObfuscationMode::EXCLUDE)
            ->withProperty('int', $obfuscator)
                ->forObjects(PropertyObfuscationMode::EXCLUDE)
                ->forArrays(PropertyObfuscationMode::EXCLUDE)
            ->withProperty('float', $obfuscator)
                ->forObjects(PropertyObfuscationMode::EXCLUDE)
                ->forArrays(PropertyObfuscationMode::EXCLUDE)
            ->withProperty('booleanTrue', $obfuscator)
                ->forObjects(PropertyObfuscationMode::EXCLUDE)
                ->forArrays(PropertyObfuscationMode::EXCLUDE)
            ->withProperty('booleanFalse', $obfuscator)
                ->forObjects(PropertyObfuscationMode::EXCLUDE)
                ->forArrays(PropertyObfuscationMode::EXCLUDE)
            ->withProperty('object', Obfuscate::fixedLength(3, 'o'))
                ->forObjects(PropertyObfuscationMode::EXCLUDE)
                ->forArrays(PropertyObfuscationMode::EXCLUDE)
            ->withProperty('array', Obfuscate::fixedLength(3, 'a'))
                ->forObjects(PropertyObfuscationMode::EXCLUDE)
                ->forArrays(PropertyObfuscationMode::EXCLUDE)
            ->withProperty('null', $obfuscator)
                ->forObjects(PropertyObfuscationMode::EXCLUDE)
                ->forArrays(PropertyObfuscationMode::EXCLUDE)
            ->withProperty('notObfuscated', Obfuscate::none())
                ->forObjects(PropertyObfuscationMode::EXCLUDE)
                ->forArrays(PropertyObfuscationMode::EXCLUDE)
            ->build();
        // phpcs:enable

        $obfuscated = $propertyObfuscator->obfuscateProperties($inputObject);

        $this->assertInstanceOf('stdClass', $obfuscated);

        $obfuscatedJson = json_encode($obfuscated, JSON_PRETTY_PRINT);

        $expectedJson = <<<EOD
{
    "string": "***",
    "int": "***",
    "float": "***",
    "booleanTrue": "***",
    "booleanFalse": "***",
    "null": "***",
    "object": {
        "string": "***",
        "int": "***",
        "float": "***",
        "booleanTrue": "***",
        "booleanFalse": "***",
        "null": "***",
        "nested": [
            {
                "prop1": "1",
                "prop2": "2"
            }
        ]
    },
    "array": [
        [
            "1",
            "2"
        ],
        {}
    ],
    "notMatchedString": "123456",
    "notMatchedInt": 123456,
    "notMatchedFloat": 1234.56,
    "notMatchedBooleanTrue": true,
    "notMatchedBooleanFalse": false,
    "nonMatchedNull": null,
    "nonMatchedObject": {
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    },
    "nested": [
        {
            "string": "***",
            "int": "***",
            "float": "***",
            "booleanTrue": "***",
            "booleanFalse": "***",
            "null": "***",
            "object": {
                "string": "***",
                "int": "***",
                "float": "***",
                "booleanTrue": "***",
                "booleanFalse": "***",
                "nested": [
                    {
                        "prop1": "1",
                        "prop2": "2"
                    }
                ]
            },
            "array": [
                [
                    "1",
                    "2"
                ],
                {}
            ],
            "notMatchedString": "123456",
            "notMatchedInt": 123456,
            "notMatchedFloat": 1234.56,
            "notMatchedBooleanTrue": true,
            "notMatchedBooleanFalse": false,
            "nonMatchedNull": null
        }
    ],
    "notObfuscated": {
        "string": "***",
        "int": "***",
        "float": "***",
        "booleanTrue": "***",
        "booleanFalse": "***",
        "null": "***",
        "object": {
            "string": "***",
            "int": "***",
            "float": "***",
            "booleanTrue": "***",
            "booleanFalse": "***",
            "nested": [
                {
                    "prop1": "1",
                    "prop2": "2"
                }
            ]
        },
        "array": [
            [
                "1",
                "2"
            ],
            {}
        ],
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    }
}
EOD;
        $this->assertEquals($expectedJson, $obfuscatedJson);
    }

    public function testObfuscateObjectInheritedObfuscators(): void
    {
        $inputObject = json_decode(self::INPUT_JSON, false);

        $obfuscator = Obfuscate::fixedLength(3);
        // phpcs:disable PEAR.WhiteSpace.ObjectOperatorIndent
        $propertyObfuscator = PropertyObfuscator::builder()
            ->withProperty('string', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT)
                ->forArrays(PropertyObfuscationMode::INHERIT)
            ->withProperty('int', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT)
                ->forArrays(PropertyObfuscationMode::INHERIT)
            ->withProperty('float', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT)
                ->forArrays(PropertyObfuscationMode::INHERIT)
            ->withProperty('booleanTrue', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT)
                ->forArrays(PropertyObfuscationMode::INHERIT)
            ->withProperty('booleanFalse', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT)
                ->forArrays(PropertyObfuscationMode::INHERIT)
            ->withProperty('object', Obfuscate::fixedLength(3, 'o'))
                ->forObjects(PropertyObfuscationMode::INHERIT)
                ->forArrays(PropertyObfuscationMode::INHERIT)
            ->withProperty('array', Obfuscate::fixedLength(3, 'a'))
                ->forObjects(PropertyObfuscationMode::INHERIT)
                ->forArrays(PropertyObfuscationMode::INHERIT)
            ->withProperty('null', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT)
                ->forArrays(PropertyObfuscationMode::INHERIT)
            ->withProperty('notObfuscated', Obfuscate::none())
                ->forObjects(PropertyObfuscationMode::SKIP)
                ->forArrays(PropertyObfuscationMode::SKIP)
            ->build();
        // phpcs:enable

        $obfuscated = $propertyObfuscator->obfuscateProperties($inputObject);

        $this->assertInstanceOf('stdClass', $obfuscated);

        $obfuscatedJson = json_encode($obfuscated, JSON_PRETTY_PRINT);

        $expectedJson = <<<EOD
{
    "string": "***",
    "int": "***",
    "float": "***",
    "booleanTrue": "***",
    "booleanFalse": "***",
    "null": "***",
    "object": {
        "string": "ooo",
        "int": "ooo",
        "float": "ooo",
        "booleanTrue": "ooo",
        "booleanFalse": "ooo",
        "null": "ooo",
        "nested": [
            {
                "prop1": "ooo",
                "prop2": "ooo"
            }
        ]
    },
    "array": [
        [
            "aaa",
            "aaa"
        ],
        {}
    ],
    "notMatchedString": "123456",
    "notMatchedInt": 123456,
    "notMatchedFloat": 1234.56,
    "notMatchedBooleanTrue": true,
    "notMatchedBooleanFalse": false,
    "nonMatchedNull": null,
    "nonMatchedObject": {
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    },
    "nested": [
        {
            "string": "***",
            "int": "***",
            "float": "***",
            "booleanTrue": "***",
            "booleanFalse": "***",
            "null": "***",
            "object": {
                "string": "ooo",
                "int": "ooo",
                "float": "ooo",
                "booleanTrue": "ooo",
                "booleanFalse": "ooo",
                "nested": [
                    {
                        "prop1": "ooo",
                        "prop2": "ooo"
                    }
                ]
            },
            "array": [
                [
                    "aaa",
                    "aaa"
                ],
                {}
            ],
            "notMatchedString": "123456",
            "notMatchedInt": 123456,
            "notMatchedFloat": 1234.56,
            "notMatchedBooleanTrue": true,
            "notMatchedBooleanFalse": false,
            "nonMatchedNull": null
        }
    ],
    "notObfuscated": {
        "string": "string\"int",
        "int": 123456,
        "float": 1234.56,
        "booleanTrue": true,
        "booleanFalse": false,
        "null": null,
        "object": {
            "string": "string\"int",
            "int": 123456,
            "float": 1234.56,
            "booleanTrue": true,
            "booleanFalse": false,
            "nested": [
                {
                    "prop1": "1",
                    "prop2": "2"
                }
            ]
        },
        "array": [
            [
                "1",
                "2"
            ],
            {}
        ],
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    }
}
EOD;
        $this->assertEquals($expectedJson, $obfuscatedJson);
    }

    public function testObfuscateObjectOverridableInheritedObfuscators(): void
    {
        $inputObject = json_decode(self::INPUT_JSON, false);

        $obfuscator = Obfuscate::fixedLength(3);
        // phpcs:disable PEAR.WhiteSpace.ObjectOperatorIndent
        $propertyObfuscator = PropertyObfuscator::builder()
            ->withProperty('string', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
                ->forArrays(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
            ->withProperty('int', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
                ->forArrays(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
            ->withProperty('float', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
                ->forArrays(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
            ->withProperty('booleanTrue', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
                ->forArrays(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
            ->withProperty('booleanFalse', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
                ->forArrays(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
            ->withProperty('object', Obfuscate::fixedLength(3, 'o'))
                ->forObjects(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
                ->forArrays(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
            ->withProperty('array', Obfuscate::fixedLength(3, 'a'))
                ->forObjects(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
                ->forArrays(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
            ->withProperty('null', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
                ->forArrays(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
            ->withProperty('notObfuscated', Obfuscate::none())
                ->forObjects(PropertyObfuscationMode::SKIP)
                ->forArrays(PropertyObfuscationMode::SKIP)
            ->build();
        // phpcs:enable

        $obfuscated = $propertyObfuscator->obfuscateProperties($inputObject);

        $this->assertInstanceOf('stdClass', $obfuscated);

        $obfuscatedJson = json_encode($obfuscated, JSON_PRETTY_PRINT);

        $expectedJson = <<<EOD
{
    "string": "***",
    "int": "***",
    "float": "***",
    "booleanTrue": "***",
    "booleanFalse": "***",
    "null": "***",
    "object": {
        "string": "***",
        "int": "***",
        "float": "***",
        "booleanTrue": "***",
        "booleanFalse": "***",
        "null": "***",
        "nested": [
            {
                "prop1": "ooo",
                "prop2": "ooo"
            }
        ]
    },
    "array": [
        [
            "aaa",
            "aaa"
        ],
        {}
    ],
    "notMatchedString": "123456",
    "notMatchedInt": 123456,
    "notMatchedFloat": 1234.56,
    "notMatchedBooleanTrue": true,
    "notMatchedBooleanFalse": false,
    "nonMatchedNull": null,
    "nonMatchedObject": {
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    },
    "nested": [
        {
            "string": "***",
            "int": "***",
            "float": "***",
            "booleanTrue": "***",
            "booleanFalse": "***",
            "null": "***",
            "object": {
                "string": "***",
                "int": "***",
                "float": "***",
                "booleanTrue": "***",
                "booleanFalse": "***",
                "nested": [
                    {
                        "prop1": "ooo",
                        "prop2": "ooo"
                    }
                ]
            },
            "array": [
                [
                    "aaa",
                    "aaa"
                ],
                {}
            ],
            "notMatchedString": "123456",
            "notMatchedInt": 123456,
            "notMatchedFloat": 1234.56,
            "notMatchedBooleanTrue": true,
            "notMatchedBooleanFalse": false,
            "nonMatchedNull": null
        }
    ],
    "notObfuscated": {
        "string": "string\"int",
        "int": 123456,
        "float": 1234.56,
        "booleanTrue": true,
        "booleanFalse": false,
        "null": null,
        "object": {
            "string": "string\"int",
            "int": 123456,
            "float": 1234.56,
            "booleanTrue": true,
            "booleanFalse": false,
            "nested": [
                {
                    "prop1": "1",
                    "prop2": "2"
                }
            ]
        },
        "array": [
            [
                "1",
                "2"
            ],
            {}
        ],
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    }
}
EOD;
        $this->assertEquals($expectedJson, $obfuscatedJson);
    }

    public function testObfuscateArrayGloballyCaseSensitive(): void
    {
        $inputObject = json_decode(self::INPUT_JSON, true);

        $obfuscator = Obfuscate::fixedLength(3);
        // phpcs:disable PEAR.WhiteSpace.ObjectOperatorIndent
        $propertyObfuscator = PropertyObfuscator::builder()
            ->caseSensitiveByDefault()
            ->withProperty('string', $obfuscator)
            ->withProperty('int', $obfuscator)
            ->withProperty('float', $obfuscator)
            ->withProperty('booleanTrue', $obfuscator)
            ->withProperty('booleanFalse', $obfuscator)
            ->withProperty('object', Obfuscate::fixedLength(3, 'o'))
            ->withProperty('array', Obfuscate::fixedLength(3, 'a'))
            ->withProperty('null', $obfuscator)
            ->withProperty('notObfuscated', Obfuscate::none())
                ->forObjects(PropertyObfuscationMode::SKIP)
                ->forArrays(PropertyObfuscationMode::SKIP)
            ->build();
        // phpcs:enable

        $obfuscated = $propertyObfuscator->obfuscateProperties($inputObject);

        $this->assertIsArray($obfuscated);

        $obfuscatedJson = json_encode($obfuscated, JSON_PRETTY_PRINT);

        $expectedJson = <<<EOD
{
    "string": "***",
    "int": "***",
    "float": "***",
    "booleanTrue": "***",
    "booleanFalse": "***",
    "null": "***",
    "object": {
        "string": "ooo",
        "int": "ooo",
        "float": "ooo",
        "booleanTrue": "ooo",
        "booleanFalse": "ooo",
        "null": "ooo",
        "nested": [
            {
                "prop1": "ooo",
                "prop2": "ooo"
            }
        ]
    },
    "array": [
        [
            "aaa",
            "aaa"
        ],
        []
    ],
    "notMatchedString": "123456",
    "notMatchedInt": 123456,
    "notMatchedFloat": 1234.56,
    "notMatchedBooleanTrue": true,
    "notMatchedBooleanFalse": false,
    "nonMatchedNull": null,
    "nonMatchedObject": {
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    },
    "nested": [
        {
            "string": "***",
            "int": "***",
            "float": "***",
            "booleanTrue": "***",
            "booleanFalse": "***",
            "null": "***",
            "object": {
                "string": "ooo",
                "int": "ooo",
                "float": "ooo",
                "booleanTrue": "ooo",
                "booleanFalse": "ooo",
                "nested": [
                    {
                        "prop1": "ooo",
                        "prop2": "ooo"
                    }
                ]
            },
            "array": [
                [
                    "aaa",
                    "aaa"
                ],
                []
            ],
            "notMatchedString": "123456",
            "notMatchedInt": 123456,
            "notMatchedFloat": 1234.56,
            "notMatchedBooleanTrue": true,
            "notMatchedBooleanFalse": false,
            "nonMatchedNull": null
        }
    ],
    "notObfuscated": {
        "string": "string\"int",
        "int": 123456,
        "float": 1234.56,
        "booleanTrue": true,
        "booleanFalse": false,
        "null": null,
        "object": {
            "string": "string\"int",
            "int": 123456,
            "float": 1234.56,
            "booleanTrue": true,
            "booleanFalse": false,
            "nested": [
                {
                    "prop1": "1",
                    "prop2": "2"
                }
            ]
        },
        "array": [
            [
                "1",
                "2"
            ],
            []
        ],
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    }
}
EOD;
        $this->assertEquals($expectedJson, $obfuscatedJson);
    }

    public function testObfuscateArrayGloballyCaseInsensitive(): void
    {
        $inputObject = json_decode(self::INPUT_JSON, true);

        $obfuscator = Obfuscate::fixedLength(3);
        // phpcs:disable PEAR.WhiteSpace.ObjectOperatorIndent
        $propertyObfuscator = PropertyObfuscator::builder()
            ->caseInsensitiveByDefault()
            ->withProperty('STRING', $obfuscator)
            ->withProperty('INT', $obfuscator)
            ->withProperty('FLOAT', $obfuscator)
            ->withProperty('BOOLEANTRUE', $obfuscator)
            ->withProperty('BOOLEANFALSE', $obfuscator)
            ->withProperty('OBJECT', Obfuscate::fixedLength(3, 'o'))
            ->withProperty('ARRAY', Obfuscate::fixedLength(3, 'a'))
            ->withProperty('NULL', $obfuscator)
            ->withProperty('NOTOBFUSCATED', Obfuscate::none())
                ->forObjects(PropertyObfuscationMode::SKIP)
                ->forArrays(PropertyObfuscationMode::SKIP)
            ->build();
        // phpcs:enable

        $obfuscated = $propertyObfuscator->obfuscateProperties($inputObject);

        $this->assertIsArray($obfuscated);

        $obfuscatedJson = json_encode($obfuscated, JSON_PRETTY_PRINT);

        $expectedJson = <<<EOD
{
    "string": "***",
    "int": "***",
    "float": "***",
    "booleanTrue": "***",
    "booleanFalse": "***",
    "null": "***",
    "object": {
        "string": "ooo",
        "int": "ooo",
        "float": "ooo",
        "booleanTrue": "ooo",
        "booleanFalse": "ooo",
        "null": "ooo",
        "nested": [
            {
                "prop1": "ooo",
                "prop2": "ooo"
            }
        ]
    },
    "array": [
        [
            "aaa",
            "aaa"
        ],
        []
    ],
    "notMatchedString": "123456",
    "notMatchedInt": 123456,
    "notMatchedFloat": 1234.56,
    "notMatchedBooleanTrue": true,
    "notMatchedBooleanFalse": false,
    "nonMatchedNull": null,
    "nonMatchedObject": {
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    },
    "nested": [
        {
            "string": "***",
            "int": "***",
            "float": "***",
            "booleanTrue": "***",
            "booleanFalse": "***",
            "null": "***",
            "object": {
                "string": "ooo",
                "int": "ooo",
                "float": "ooo",
                "booleanTrue": "ooo",
                "booleanFalse": "ooo",
                "nested": [
                    {
                        "prop1": "ooo",
                        "prop2": "ooo"
                    }
                ]
            },
            "array": [
                [
                    "aaa",
                    "aaa"
                ],
                []
            ],
            "notMatchedString": "123456",
            "notMatchedInt": 123456,
            "notMatchedFloat": 1234.56,
            "notMatchedBooleanTrue": true,
            "notMatchedBooleanFalse": false,
            "nonMatchedNull": null
        }
    ],
    "notObfuscated": {
        "string": "string\"int",
        "int": 123456,
        "float": 1234.56,
        "booleanTrue": true,
        "booleanFalse": false,
        "null": null,
        "object": {
            "string": "string\"int",
            "int": 123456,
            "float": 1234.56,
            "booleanTrue": true,
            "booleanFalse": false,
            "nested": [
                {
                    "prop1": "1",
                    "prop2": "2"
                }
            ]
        },
        "array": [
            [
                "1",
                "2"
            ],
            []
        ],
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    }
}
EOD;
        $this->assertEquals($expectedJson, $obfuscatedJson);
    }

    public function testObfuscateArrayGloballyExcludingObjectsAndArrays(): void
    {
        $inputObject = json_decode(self::INPUT_JSON, true);

        $obfuscator = Obfuscate::fixedLength(3);
        // phpcs:disable PEAR.WhiteSpace.ObjectOperatorIndent
        $propertyObfuscator = PropertyObfuscator::builder()
            ->forObjectsByDefault(PropertyObfuscationMode::EXCLUDE)
            ->forArraysByDefault(PropertyObfuscationMode::EXCLUDE)
            ->withProperty('string', $obfuscator)
            ->withProperty('int', $obfuscator)
            ->withProperty('float', $obfuscator)
            ->withProperty('booleanTrue', $obfuscator)
            ->withProperty('booleanFalse', $obfuscator)
            ->withProperty('object', Obfuscate::fixedLength(3, 'o'))
            ->withProperty('array', Obfuscate::fixedLength(3, 'a'))
            ->withProperty('null', $obfuscator)
            ->withProperty('notObfuscated', Obfuscate::none())
                ->forObjects(PropertyObfuscationMode::SKIP)
                ->forArrays(PropertyObfuscationMode::SKIP)
            ->build();
        // phpcs:enable

        $obfuscated = $propertyObfuscator->obfuscateProperties($inputObject);

        $this->assertIsArray($obfuscated);

        $obfuscatedJson = json_encode($obfuscated, JSON_PRETTY_PRINT);

        $expectedJson = <<<EOD
{
    "string": "***",
    "int": "***",
    "float": "***",
    "booleanTrue": "***",
    "booleanFalse": "***",
    "null": "***",
    "object": {
        "string": "***",
        "int": "***",
        "float": "***",
        "booleanTrue": "***",
        "booleanFalse": "***",
        "null": "***",
        "nested": [
            {
                "prop1": "1",
                "prop2": "2"
            }
        ]
    },
    "array": [
        [
            "1",
            "2"
        ],
        []
    ],
    "notMatchedString": "123456",
    "notMatchedInt": 123456,
    "notMatchedFloat": 1234.56,
    "notMatchedBooleanTrue": true,
    "notMatchedBooleanFalse": false,
    "nonMatchedNull": null,
    "nonMatchedObject": {
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    },
    "nested": [
        {
            "string": "***",
            "int": "***",
            "float": "***",
            "booleanTrue": "***",
            "booleanFalse": "***",
            "null": "***",
            "object": {
                "string": "***",
                "int": "***",
                "float": "***",
                "booleanTrue": "***",
                "booleanFalse": "***",
                "nested": [
                    {
                        "prop1": "1",
                        "prop2": "2"
                    }
                ]
            },
            "array": [
                [
                    "1",
                    "2"
                ],
                []
            ],
            "notMatchedString": "123456",
            "notMatchedInt": 123456,
            "notMatchedFloat": 1234.56,
            "notMatchedBooleanTrue": true,
            "notMatchedBooleanFalse": false,
            "nonMatchedNull": null
        }
    ],
    "notObfuscated": {
        "string": "string\"int",
        "int": 123456,
        "float": 1234.56,
        "booleanTrue": true,
        "booleanFalse": false,
        "null": null,
        "object": {
            "string": "string\"int",
            "int": 123456,
            "float": 1234.56,
            "booleanTrue": true,
            "booleanFalse": false,
            "nested": [
                {
                    "prop1": "1",
                    "prop2": "2"
                }
            ]
        },
        "array": [
            [
                "1",
                "2"
            ],
            []
        ],
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    }
}
EOD;
        $this->assertEquals($expectedJson, $obfuscatedJson);
    }

    public function testObfuscateArrayExcludingObjectsAndArraysOverridingGlobals(): void
    {
        $inputObject = json_decode(self::INPUT_JSON, true);

        $obfuscator = Obfuscate::fixedLength(3);
        // phpcs:disable PEAR.WhiteSpace.ObjectOperatorIndent
        $propertyObfuscator = PropertyObfuscator::builder()
            ->forObjectsByDefault(PropertyObfuscationMode::INHERIT)
            ->forArraysByDefault(PropertyObfuscationMode::INHERIT)
            ->withProperty('string', $obfuscator)
                ->forObjects(PropertyObfuscationMode::EXCLUDE)
                ->forArrays(PropertyObfuscationMode::EXCLUDE)
            ->withProperty('int', $obfuscator)
                ->forObjects(PropertyObfuscationMode::EXCLUDE)
                ->forArrays(PropertyObfuscationMode::EXCLUDE)
            ->withProperty('float', $obfuscator)
                ->forObjects(PropertyObfuscationMode::EXCLUDE)
                ->forArrays(PropertyObfuscationMode::EXCLUDE)
            ->withProperty('booleanTrue', $obfuscator)
                ->forObjects(PropertyObfuscationMode::EXCLUDE)
                ->forArrays(PropertyObfuscationMode::EXCLUDE)
            ->withProperty('booleanFalse', $obfuscator)
                ->forObjects(PropertyObfuscationMode::EXCLUDE)
                ->forArrays(PropertyObfuscationMode::EXCLUDE)
            ->withProperty('object', Obfuscate::fixedLength(3, 'o'))
                ->forObjects(PropertyObfuscationMode::EXCLUDE)
                ->forArrays(PropertyObfuscationMode::EXCLUDE)
            ->withProperty('array', Obfuscate::fixedLength(3, 'a'))
                ->forObjects(PropertyObfuscationMode::EXCLUDE)
                ->forArrays(PropertyObfuscationMode::EXCLUDE)
            ->withProperty('null', $obfuscator)
                ->forObjects(PropertyObfuscationMode::EXCLUDE)
                ->forArrays(PropertyObfuscationMode::EXCLUDE)
            ->withProperty('notObfuscated', Obfuscate::none())
                ->forObjects(PropertyObfuscationMode::EXCLUDE)
                ->forArrays(PropertyObfuscationMode::EXCLUDE)
            ->build();
        // phpcs:enable

        $obfuscated = $propertyObfuscator->obfuscateProperties($inputObject);

        $this->assertIsArray($obfuscated);

        $obfuscatedJson = json_encode($obfuscated, JSON_PRETTY_PRINT);

        $expectedJson = <<<EOD
{
    "string": "***",
    "int": "***",
    "float": "***",
    "booleanTrue": "***",
    "booleanFalse": "***",
    "null": "***",
    "object": {
        "string": "***",
        "int": "***",
        "float": "***",
        "booleanTrue": "***",
        "booleanFalse": "***",
        "null": "***",
        "nested": [
            {
                "prop1": "1",
                "prop2": "2"
            }
        ]
    },
    "array": [
        [
            "1",
            "2"
        ],
        []
    ],
    "notMatchedString": "123456",
    "notMatchedInt": 123456,
    "notMatchedFloat": 1234.56,
    "notMatchedBooleanTrue": true,
    "notMatchedBooleanFalse": false,
    "nonMatchedNull": null,
    "nonMatchedObject": {
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    },
    "nested": [
        {
            "string": "***",
            "int": "***",
            "float": "***",
            "booleanTrue": "***",
            "booleanFalse": "***",
            "null": "***",
            "object": {
                "string": "***",
                "int": "***",
                "float": "***",
                "booleanTrue": "***",
                "booleanFalse": "***",
                "nested": [
                    {
                        "prop1": "1",
                        "prop2": "2"
                    }
                ]
            },
            "array": [
                [
                    "1",
                    "2"
                ],
                []
            ],
            "notMatchedString": "123456",
            "notMatchedInt": 123456,
            "notMatchedFloat": 1234.56,
            "notMatchedBooleanTrue": true,
            "notMatchedBooleanFalse": false,
            "nonMatchedNull": null
        }
    ],
    "notObfuscated": {
        "string": "***",
        "int": "***",
        "float": "***",
        "booleanTrue": "***",
        "booleanFalse": "***",
        "null": "***",
        "object": {
            "string": "***",
            "int": "***",
            "float": "***",
            "booleanTrue": "***",
            "booleanFalse": "***",
            "nested": [
                {
                    "prop1": "1",
                    "prop2": "2"
                }
            ]
        },
        "array": [
            [
                "1",
                "2"
            ],
            []
        ],
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    }
}
EOD;
        $this->assertEquals($expectedJson, $obfuscatedJson);
    }

    public function testObfuscateArrayInheritedObfuscators(): void
    {
        $inputObject = json_decode(self::INPUT_JSON, true);

        $obfuscator = Obfuscate::fixedLength(3);
        // phpcs:disable PEAR.WhiteSpace.ObjectOperatorIndent
        $propertyObfuscator = PropertyObfuscator::builder()
            ->withProperty('string', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT)
                ->forArrays(PropertyObfuscationMode::INHERIT)
            ->withProperty('int', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT)
                ->forArrays(PropertyObfuscationMode::INHERIT)
            ->withProperty('float', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT)
                ->forArrays(PropertyObfuscationMode::INHERIT)
            ->withProperty('booleanTrue', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT)
                ->forArrays(PropertyObfuscationMode::INHERIT)
            ->withProperty('booleanFalse', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT)
                ->forArrays(PropertyObfuscationMode::INHERIT)
            ->withProperty('object', Obfuscate::fixedLength(3, 'o'))
                ->forObjects(PropertyObfuscationMode::INHERIT)
                ->forArrays(PropertyObfuscationMode::INHERIT)
            ->withProperty('array', Obfuscate::fixedLength(3, 'a'))
                ->forObjects(PropertyObfuscationMode::INHERIT)
                ->forArrays(PropertyObfuscationMode::INHERIT)
            ->withProperty('null', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT)
                ->forArrays(PropertyObfuscationMode::INHERIT)
            ->withProperty('notObfuscated', Obfuscate::none())
                ->forObjects(PropertyObfuscationMode::SKIP)
                ->forArrays(PropertyObfuscationMode::SKIP)
            ->build();
        // phpcs:enable

        $obfuscated = $propertyObfuscator->obfuscateProperties($inputObject);

        $this->assertIsArray($obfuscated);

        $obfuscatedJson = json_encode($obfuscated, JSON_PRETTY_PRINT);

        $expectedJson = <<<EOD
{
    "string": "***",
    "int": "***",
    "float": "***",
    "booleanTrue": "***",
    "booleanFalse": "***",
    "null": "***",
    "object": {
        "string": "ooo",
        "int": "ooo",
        "float": "ooo",
        "booleanTrue": "ooo",
        "booleanFalse": "ooo",
        "null": "ooo",
        "nested": [
            {
                "prop1": "ooo",
                "prop2": "ooo"
            }
        ]
    },
    "array": [
        [
            "aaa",
            "aaa"
        ],
        []
    ],
    "notMatchedString": "123456",
    "notMatchedInt": 123456,
    "notMatchedFloat": 1234.56,
    "notMatchedBooleanTrue": true,
    "notMatchedBooleanFalse": false,
    "nonMatchedNull": null,
    "nonMatchedObject": {
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    },
    "nested": [
        {
            "string": "***",
            "int": "***",
            "float": "***",
            "booleanTrue": "***",
            "booleanFalse": "***",
            "null": "***",
            "object": {
                "string": "ooo",
                "int": "ooo",
                "float": "ooo",
                "booleanTrue": "ooo",
                "booleanFalse": "ooo",
                "nested": [
                    {
                        "prop1": "ooo",
                        "prop2": "ooo"
                    }
                ]
            },
            "array": [
                [
                    "aaa",
                    "aaa"
                ],
                []
            ],
            "notMatchedString": "123456",
            "notMatchedInt": 123456,
            "notMatchedFloat": 1234.56,
            "notMatchedBooleanTrue": true,
            "notMatchedBooleanFalse": false,
            "nonMatchedNull": null
        }
    ],
    "notObfuscated": {
        "string": "string\"int",
        "int": 123456,
        "float": 1234.56,
        "booleanTrue": true,
        "booleanFalse": false,
        "null": null,
        "object": {
            "string": "string\"int",
            "int": 123456,
            "float": 1234.56,
            "booleanTrue": true,
            "booleanFalse": false,
            "nested": [
                {
                    "prop1": "1",
                    "prop2": "2"
                }
            ]
        },
        "array": [
            [
                "1",
                "2"
            ],
            []
        ],
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    }
}
EOD;
        $this->assertEquals($expectedJson, $obfuscatedJson);
    }

    public function testObfuscateArrayOverridableInheritedObfuscators(): void
    {
        $inputObject = json_decode(self::INPUT_JSON, true);

        $obfuscator = Obfuscate::fixedLength(3);
        // phpcs:disable PEAR.WhiteSpace.ObjectOperatorIndent
        $propertyObfuscator = PropertyObfuscator::builder()
            ->withProperty('string', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
                ->forArrays(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
            ->withProperty('int', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
                ->forArrays(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
            ->withProperty('float', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
                ->forArrays(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
            ->withProperty('booleanTrue', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
                ->forArrays(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
            ->withProperty('booleanFalse', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
                ->forArrays(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
            ->withProperty('object', Obfuscate::fixedLength(3, 'o'))
                ->forObjects(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
                ->forArrays(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
            ->withProperty('array', Obfuscate::fixedLength(3, 'a'))
                ->forObjects(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
                ->forArrays(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
            ->withProperty('null', $obfuscator)
                ->forObjects(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
                ->forArrays(PropertyObfuscationMode::INHERIT_OVERRIDABLE)
            ->withProperty('notObfuscated', Obfuscate::none())
                ->forObjects(PropertyObfuscationMode::SKIP)
                ->forArrays(PropertyObfuscationMode::SKIP)
            ->build();
        // phpcs:enable

        $obfuscated = $propertyObfuscator->obfuscateProperties($inputObject);

        $this->assertIsArray($obfuscated);

        $obfuscatedJson = json_encode($obfuscated, JSON_PRETTY_PRINT);

        $expectedJson = <<<EOD
{
    "string": "***",
    "int": "***",
    "float": "***",
    "booleanTrue": "***",
    "booleanFalse": "***",
    "null": "***",
    "object": {
        "string": "***",
        "int": "***",
        "float": "***",
        "booleanTrue": "***",
        "booleanFalse": "***",
        "null": "***",
        "nested": [
            {
                "prop1": "ooo",
                "prop2": "ooo"
            }
        ]
    },
    "array": [
        [
            "aaa",
            "aaa"
        ],
        []
    ],
    "notMatchedString": "123456",
    "notMatchedInt": 123456,
    "notMatchedFloat": 1234.56,
    "notMatchedBooleanTrue": true,
    "notMatchedBooleanFalse": false,
    "nonMatchedNull": null,
    "nonMatchedObject": {
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    },
    "nested": [
        {
            "string": "***",
            "int": "***",
            "float": "***",
            "booleanTrue": "***",
            "booleanFalse": "***",
            "null": "***",
            "object": {
                "string": "***",
                "int": "***",
                "float": "***",
                "booleanTrue": "***",
                "booleanFalse": "***",
                "nested": [
                    {
                        "prop1": "ooo",
                        "prop2": "ooo"
                    }
                ]
            },
            "array": [
                [
                    "aaa",
                    "aaa"
                ],
                []
            ],
            "notMatchedString": "123456",
            "notMatchedInt": 123456,
            "notMatchedFloat": 1234.56,
            "notMatchedBooleanTrue": true,
            "notMatchedBooleanFalse": false,
            "nonMatchedNull": null
        }
    ],
    "notObfuscated": {
        "string": "string\"int",
        "int": 123456,
        "float": 1234.56,
        "booleanTrue": true,
        "booleanFalse": false,
        "null": null,
        "object": {
            "string": "string\"int",
            "int": 123456,
            "float": 1234.56,
            "booleanTrue": true,
            "booleanFalse": false,
            "nested": [
                {
                    "prop1": "1",
                    "prop2": "2"
                }
            ]
        },
        "array": [
            [
                "1",
                "2"
            ],
            []
        ],
        "notMatchedString": "123456",
        "notMatchedInt": 123456,
        "notMatchedFloat": 1234.56,
        "notMatchedBooleanTrue": true,
        "notMatchedBooleanFalse": false,
        "nonMatchedNull": null
    }
}
EOD;
        $this->assertEquals($expectedJson, $obfuscatedJson);
    }

    #[DataProvider('obfuscateScalarsParameters')]
    public function testObfuscateScalars(mixed $value): void
    {
        $obfuscator = Obfuscate::fixedLength(3);
        // phpcs:disable PEAR.WhiteSpace.ObjectOperatorIndent
        $propertyObfuscator = PropertyObfuscator::builder()
            ->caseSensitiveByDefault()
            ->withProperty('string', $obfuscator)
            ->withProperty('notObfuscated', Obfuscate::none())
                ->forObjects(PropertyObfuscationMode::SKIP)
                ->forArrays(PropertyObfuscationMode::SKIP)
            ->build();
        // phpcs:enable

        $obfuscated = $propertyObfuscator->obfuscateProperties($value);

        $this->assertEquals($value, $obfuscated);
    }

    /**
     * @return array<array<mixed>>
     */
    public static function obfuscateScalarsParameters(): array
    {
        return [
            ['foo'],
            [1],
            [true],
            [false],
        ];
    }

    public function testBuildCreatesPropertiesSnapshots(): void
    {
        $builder = PropertyObfuscator::builder()
            ->withProperty('test1', Obfuscate::fixedLength(3), true)
            ->withProperty('test1', Obfuscate::all(), false);

        $obfuscator = $builder->build();

        $this->assertEquals('***', $obfuscator->obfuscateProperty('test1', 'value'));
        $this->assertEquals('*****', $obfuscator->obfuscateProperty('TEST1', 'value'));
        $this->assertEquals('value', $obfuscator->obfuscateProperty('test2', 'value'));
        $this->assertEquals('value', $obfuscator->obfuscateProperty('TEST2', 'value'));

        $builder->withProperty('test2', Obfuscate::all(), true)->withProperty('test2', Obfuscate::fixedValue('xxx'), false);

        $this->assertEquals('***', $obfuscator->obfuscateProperty('test1', 'value'));
        $this->assertEquals('*****', $obfuscator->obfuscateProperty('TEST1', 'value'));
        $this->assertEquals('value', $obfuscator->obfuscateProperty('test2', 'value'));
        $this->assertEquals('value', $obfuscator->obfuscateProperty('TEST2', 'value'));

        $obfuscator2 = $builder->build();

        $this->assertEquals('***', $obfuscator2->obfuscateProperty('test1', 'value'));
        $this->assertEquals('*****', $obfuscator2->obfuscateProperty('TEST1', 'value'));
        $this->assertEquals('*****', $obfuscator2->obfuscateProperty('test2', 'value'));
        $this->assertEquals('xxx', $obfuscator2->obfuscateProperty('TEST2', 'value'));
    }
}
