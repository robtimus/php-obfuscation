<?php
namespace Robtimus\Obfuscation;

use stdClass;
use ValueError;

/**
 * An object that will obfuscate object properties.
 *
 * @package Robtimus\Obfuscation
 * @author  Rob Spoor <robtimus@users.noreply.github.com>
 * @license https://www.apache.org/licenses/LICENSE-2.0.txt The Apache Software License, Version 2.0
 */
abstract class PropertyObfuscator
{
    /**
     * Obfuscates the value for a single property.
     *
     * @param string $propertyName The name of the property to obfuscate a value for.
     * @param string $value        The value to obfuscate.
     *
     * @return string The obfuscated value.
     */
    abstract public function obfuscateProperty(string $propertyName, string $value): string;

    /**
     * Obfuscate the public properties of an object.
     * This method will traverse into the object as necessary.
     *
     * @param object $object The object for which to obfuscate the public properties.
     *
     * @return stdClass A new `stdClass` instance with the same properties but obfuscated values.
     */
    abstract public function obfuscateObjectProperties(object $object): stdClass;

    /**
     * Obfuscate the properties of an array.
     * This method will traverse into the array as necessary.
     *
     * @param array<mixed> $array The array for which to obfuscate the properties.
     *
     * @return array<mixed> A new array with the same properties but obfuscated values.
     */
    abstract public function obfuscateArrayProperties(array $array): array;

    /**
     * Obfuscates the properties of a value.
     * This method will call `obfuscateObjectProperties` if the given value is an object,
     * call `obfuscateArrayProperties` if the given value is an array,
     * or return the unmodified value otherwise.
     *
     * @param mixed $value The value for which to obfuscate the properties.
     *
     * @return mixed The reuslt of obfuscating the value.
     */
    public function obfuscateProperties(mixed $value): mixed
    {
        if (is_object($value)) {
            return $this->obfuscateObjectProperties($value);
        }
        if (is_array($value)) {
            return $this->obfuscateArrayProperties($value);
        }
        return $value;
    }

    /**
     * Creates a new builder for `PropertyObfuscator` instances.
     *
     * @return PropertyObfuscatorBuilder The created builder.
     */
    public static function builder(): PropertyObfuscatorBuilder
    {
        return new class implements PropertyObfuscatorBuilder, PropertyConfigurer
        {
            /**
             * Case sensitively matched properties.
             *
             * @var array<string, array{'obfuscator': Obfuscator, 'forObjects': PropertyObfuscationMode, 'forArrays': PropertyObfuscationMode}>
             */
            private array $_caseSensitiveProperties = [];
            /**
             * Case insensitively matched properties.
             *
             * @var array<string, array{'obfuscator': Obfuscator, 'forObjects': PropertyObfuscationMode, 'forArrays': PropertyObfuscationMode}>
             */
            private array $_caseInsensitiveProperties = [];

            // default settings
            private bool $_caseSensitiveByDefault = true;
            private PropertyObfuscationMode $_forObjectsByDefault = PropertyObfuscationMode::INHERIT;
            private PropertyObfuscationMode $_forArraysByDefault = PropertyObfuscationMode::INHERIT;

            // per property settings
            private ?string $_propertyName = null;
            private ?Obfuscator $_obfuscator = null;
            private ?bool $_caseSensitive = null;
            private ?PropertyObfuscationMode $_forObjects = null;
            private ?PropertyObfuscationMode $_forArrays = null;

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function withProperty(string $propertyName, Obfuscator $obfuscator, ?bool $caseSensitive = null): PropertyConfigurer
            {
                $this->_addLastProperty();

                $caseSensitive = is_null($caseSensitive) ? $this->_caseSensitiveByDefault : $caseSensitive;
                $this->_testProperty($propertyName, $caseSensitive);

                $this->_propertyName = $propertyName;
                $this->_obfuscator = $obfuscator;
                $this->_caseSensitive = $caseSensitive;
                $this->_forObjects = $this->_forObjectsByDefault;
                $this->_forArrays = $this->_forArraysByDefault;

                return $this;
            }

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function caseSensitiveByDefault(): PropertyObfuscatorBuilder
            {
                $this->_caseSensitiveByDefault = true;
                return $this;
            }

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function caseInsensitiveByDefault(): PropertyObfuscatorBuilder
            {
                $this->_caseSensitiveByDefault = false;
                return $this;
            }

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function forObjectsByDefault(PropertyObfuscationMode $obfuscationMode): PropertyObfuscatorBuilder
            {
                $this->_forObjectsByDefault = $obfuscationMode;
                return $this;
            }

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function forArraysByDefault(PropertyObfuscationMode $obfuscationMode): PropertyObfuscatorBuilder
            {
                $this->_forArraysByDefault = $obfuscationMode;
                return $this;
            }

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function forObjects(PropertyObfuscationMode $obfuscationMode): PropertyConfigurer
            {
                $this->_forObjects = $obfuscationMode;
                return $this;
            }

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function forArrays(PropertyObfuscationMode $obfuscationMode): PropertyConfigurer
            {
                $this->_forArrays = $obfuscationMode;
                return $this;
            }

            private function _addLastProperty(): void
            {
                if (!is_null($this->_propertyName) && !is_null($this->_obfuscator) && !is_null($this->_forObjects) && !is_null($this->_forArrays)) {
                    $propertyConfig = array(
                        'obfuscator' => $this->_obfuscator,
                        'forObjects' => $this->_forObjects,
                        'forArrays'  => $this->_forArrays,
                    );
                    if ($this->_caseSensitive) {
                        $this->_caseSensitiveProperties[$this->_propertyName] = $propertyConfig;
                    } else {
                        $this->_caseInsensitiveProperties[mb_strtolower($this->_propertyName)] = $propertyConfig;
                    }
                }

                $this->_propertyName = null;
                $this->_obfuscator = null;
                $this->_caseSensitive = null;
                $this->_forObjects = null;
                $this->_forArrays = null;
            }

            private function _testProperty(string $propertyName, bool $caseSensitive): void
            {
                if ($caseSensitive && isset($this->_caseSensitiveProperties[$propertyName])) {
                    throw new ValueError("Duplicate key: $propertyName (case sensitive)");
                }
                if (!$caseSensitive && isset($this->_caseInsensitiveProperties[mb_strtolower($propertyName)])) {
                    throw new ValueError("Duplicate key: $propertyName (case insensitive)");
                }
            }

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function build(): PropertyObfuscator
            {
                $this->_addLastProperty();

                return new class($this->_caseSensitiveProperties, $this->_caseInsensitiveProperties) extends PropertyObfuscator
                {
                    /**
                     * Case sensitively matched properties.
                     *
                     * @var array<string, array{
                     *     'obfuscator': Obfuscator,
                     *     'forObjects': PropertyObfuscationMode,
                     *     'forArrays': PropertyObfuscationMode
                     * }>
                     */
                    private array $_caseSensitiveProperties = [];
                    /**
                     * Case insensitively matched properties.
                     *
                     * @var array<string, array{
                     *     'obfuscator': Obfuscator,
                     *     'forObjects': PropertyObfuscationMode,
                     *     'forArrays': PropertyObfuscationMode
                     * }>
                     */
                    private array $_caseInsensitiveProperties = [];

                    // phpcs:disable Generic.Files.LineLength.TooLong, PEAR.Commenting.FunctionComment.MissingParamComment
                    /**
                     * Creates a new `PropertyObfuscator`.
                     *
                     * @param array<string, array{ 'obfuscator': Obfuscator, 'forObjects': PropertyObfuscationMode, 'forArrays': PropertyObfuscationMode}> $caseSensitiveProperties
                     * @param array<string, array{'obfuscator': Obfuscator, 'forObjects': PropertyObfuscationMode, 'forArrays': PropertyObfuscationMode}>  $caseInsensitiveProperties
                     */
                    public function __construct(array $caseSensitiveProperties, array $caseInsensitiveProperties)
                    {
                        $this->_caseSensitiveProperties = $caseSensitiveProperties;
                        $this->_caseInsensitiveProperties = $caseInsensitiveProperties;
                    }
                    // phpcs:enable

                    // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
                    public function obfuscateProperty(string $propertyName, string $value): string
                    {
                        $propertyConfig = $this->_getPropertyConfig($propertyName);
                        if (is_null($propertyConfig)) {
                            return $value;
                        }
                        $obfuscator = $propertyConfig['obfuscator'];
                        return $obfuscator->obfuscateText($value);
                    }

                    // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
                    public function obfuscateObjectProperties(object $object): stdClass
                    {
                        return $this->_obfuscateObject($object, null);
                    }

                    // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
                    public function obfuscateArrayProperties(array $array): array
                    {
                        return $this->_obfuscateArray($array, null);
                    }

                    private function _obfuscateWithDefault(mixed $value, ?Obfuscator $defaultObfuscator): mixed
                    {
                        $result = $value;
                        if (is_scalar($value) || is_null($value)) {
                            $result = $this->_obfuscateScalar($value, $defaultObfuscator);
                        } elseif (is_object($value)) {
                            $result = $this->_obfuscateObject($value, $defaultObfuscator);
                        } elseif (is_array($value)) {
                            $result = $this->_obfuscateArray($value, $defaultObfuscator);
                        }
                        return $result;
                    }

                    // phpcs:disable Generic.Files.LineLength.TooLong
                    /**
                     * Obfuscates a single value.
                     *
                     * @param array{'obfuscator': Obfuscator, 'forObjects': PropertyObfuscationMode, 'forArrays': PropertyObfuscationMode}|null $propertyConfig
                     */
                    // phpcs::enable
                    private function _obfuscateValue(mixed $value, ?array $propertyConfig, ?Obfuscator $defaultObfuscator): mixed
                    {
                        if (is_null($propertyConfig)) {
                            return $this->_obfuscateWithDefault($value, $defaultObfuscator);
                        }
                        $result = $value;
                        $obfuscator = $propertyConfig['obfuscator'];
                        if (is_scalar($value) || is_null($value)) {
                            $result = $this->_obfuscateScalar($value, $obfuscator);
                        } elseif (is_object($value)) {
                            $obfuscationMode = $propertyConfig['forObjects'];
                            $result = match ($obfuscationMode) {
                                PropertyObfuscationMode::SKIP                => $value,
                                PropertyObfuscationMode::EXCLUDE             => $this->_obfuscateWithDefault($value, $defaultObfuscator),
                                PropertyObfuscationMode::INHERIT             => $this->_obfuscateScalars($value, $obfuscator),
                                PropertyObfuscationMode::INHERIT_OVERRIDABLE => $this->_obfuscateWithDefault($value, $obfuscator),
                            };
                        } elseif (is_array($value)) {
                            $obfuscationMode = $propertyConfig['forArrays'];
                            $result = match ($obfuscationMode) {
                                PropertyObfuscationMode::SKIP                => $value,
                                PropertyObfuscationMode::EXCLUDE             => $this->_obfuscateWithDefault($value, $defaultObfuscator),
                                PropertyObfuscationMode::INHERIT             => $this->_obfuscateScalars($value, $obfuscator),
                                PropertyObfuscationMode::INHERIT_OVERRIDABLE => $this->_obfuscateWithDefault($value, $obfuscator),
                            };
                        }
                        return $result;
                    }

                    private function _obfuscateObject(object $object, ?Obfuscator $defaultObfuscator): stdClass
                    {
                        $obfuscated = new stdClass();
                        foreach ($object as $propertyName => $value) {
                            $propertyConfig = $this->_getPropertyConfig($propertyName);
                            $obfuscated->$propertyName = $this->_obfuscateValue($value, $propertyConfig, $defaultObfuscator);
                        }
                        return $obfuscated;
                    }

                    /**
                     * Obfuscates an array.
                     *
                     * @param array<int|string, mixed> $array
                     *
                     * @return array<int|string, mixed>
                     */
                    private function _obfuscateArray(array $array, ?Obfuscator $defaultObfuscator): array
                    {
                        $obfuscated = [];
                        foreach ($array as $key => $value) {
                            if (is_string($key)) {
                                $propertyConfig = $this->_getPropertyConfig($key);
                                $obfuscated[$key] = $this->_obfuscateValue($value, $propertyConfig, $defaultObfuscator);
                            } else {
                                $obfuscated[$key] = $this->_obfuscateWithDefault($value, $defaultObfuscator);
                            }
                        }
                        return $obfuscated;
                    }

                    private function _obfuscateScalars(mixed $value, Obfuscator $obfuscator): mixed
                    {
                        $result = $value;
                        if (is_scalar($value) || is_null($value)) {
                            $result = $this->_obfuscateScalar($value, $obfuscator);
                        } elseif (is_object($value)) {
                            $obfuscated = new stdClass();
                            foreach ($value as $propertyName => $propertyValue) {
                                $obfuscated->$propertyName = $this->_obfuscateScalars($propertyValue, $obfuscator);
                            }
                            $result = $obfuscated;
                        } elseif (is_array($value)) {
                            $obfuscated = [];
                            foreach ($value as $propertyName => $propertyValue) {
                                $obfuscated[$propertyName] = $this->_obfuscateScalars($propertyValue, $obfuscator);
                            }
                            $result = $obfuscated;
                        }
                        return $result;
                    }

                    /**
                     * Obfuscates a scalar value.
                     *
                     * @param bool|float|int|string|null $value
                     */
                    private function _obfuscateScalar(mixed $value, ?Obfuscator $defaultObfuscator): mixed
                    {
                        if (is_null($defaultObfuscator)) {
                            return $value;
                        }
                        return $defaultObfuscator->obfuscateText(strval($value));
                    }

                    /**
                     * Returns the configuration for the given property name,
                     *
                     * @return array{'obfuscator': Obfuscator, 'forObjects': PropertyObfuscationMode, 'forArrays': PropertyObfuscationMode}|null
                     */
                    private function _getPropertyConfig(string $propertyName): ?array
                    {
                        if (isset($this->_caseSensitiveProperties[$propertyName])) {
                            return $this->_caseSensitiveProperties[$propertyName];
                        }
                        $lowerCasePropertyName = mb_strtolower($propertyName);
                        if (isset($this->_caseInsensitiveProperties[$lowerCasePropertyName])) {
                            return $this->_caseInsensitiveProperties[$lowerCasePropertyName];
                        }
                        return null;
                    }
                };
            }
        };
    }
}
