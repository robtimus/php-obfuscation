<?php
namespace Robtimus\Obfuscation;

use Robtimus\Obfuscation\Obfuscator;
use ValueError;

/**
 * An object that will obfuscate header values.
 *
 * @package Robtimus\Obfuscation
 * @author  Rob Spoor <robtimus@users.noreply.github.com>
 * @license https://www.apache.org/licenses/LICENSE-2.0.txt The Apache Software License, Version 2.0
 */
abstract class HeaderObfuscator
{
    /**
     * Obfuscates the value of a header.
     *
     * @param string $headerName The name of the header to obfuscate.
     * @param string $value      The header value to obfuscate.
     *
     * @return string The obfuscated header value.
     */
    abstract public function obfuscateValue(string $headerName, string $value): string;

    /**
     * Obfuscates multiple values of a header.
     *
     * @param string        $headerName The name of the header to obfuscate.
     * @param array<string> $values     The header values to obfuscate.
     *
     * @return array<string> The obfuscated header values.
     */
    abstract public function obfuscateValues(string $headerName, array &$values): array;

    /**
     * Creates a new builder for `HeaderObfuscator` instances.
     *
     * @return HeaderObfuscatorBuilder The created builder.
     */
    public static function builder(): HeaderObfuscatorBuilder
    {
        return new class implements HeaderObfuscatorBuilder
        {
            /**
             * An array with lower-case header names as keys and obfuscators as values.
             *
             * @var array<string, Obfuscator>
             */
            private array $_obfuscators;

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function withHeader(string $headerName, Obfuscator $obfuscator): HeaderObfuscatorBuilder
            {
                $lowerCaseHeaderName = mb_strtolower($headerName);
                if (isset($this->_obfuscators[$lowerCaseHeaderName])) {
                    throw new ValueError("Duplicate header name: $headerName");
                }

                $this->_obfuscators[$lowerCaseHeaderName] = $obfuscator;
                return $this;
            }

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function build(): HeaderObfuscator
            {
                return new class($this->_obfuscators) extends HeaderObfuscator
                {
                    /**
                     * An array with lower-case header names as keys and obfuscators as values.
                     *
                     * @var array<string, Obfuscator>
                     */
                    private array $_obfuscators;

                    /**
                     * Creates a new header obfuscator.
                     *
                     * @param array<string, Obfuscator> $obfuscators An array with header names as keys and the obfuscators for those header names as
                     *                                               values.
                     */
                    public function __construct(array $obfuscators)
                    {
                        $this->_obfuscators = $obfuscators;
                    }

                    // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
                    public function obfuscateValue(string $headerName, string $value): string
                    {
                        $lowerCaseHeaderName = mb_strtolower($headerName);
                        return isset($this->_obfuscators[$lowerCaseHeaderName])
                            ? $this->_obfuscators[$lowerCaseHeaderName]->obfuscateText($value)
                            : $value;
                    }

                    // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
                    public function obfuscateValues(string $headerName, array &$values): array
                    {
                        $lowerCaseHeaderName = mb_strtolower($headerName);
                        if (isset($this->_obfuscators[$lowerCaseHeaderName])) {
                            $obfuscator = $this->_obfuscators[$lowerCaseHeaderName];
                            $obfuscated = [];
                            foreach ($values as $value) {
                                $obfuscated[] = $obfuscator->obfuscateText($value);
                            }
                            return $obfuscated;
                        }
                        return $values;
                    }

                };
            }
        };
    }
}
