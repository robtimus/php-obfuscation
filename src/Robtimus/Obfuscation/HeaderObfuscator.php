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
    abstract public function obfuscateHeaderValue(string $headerName, string $value): string;

    /**
     * Obfuscates multiple values of a header.
     *
     * @param string        $headerName The name of the header to obfuscate.
     * @param array<string> $values     The header values to obfuscate.
     *
     * @return array<string> The obfuscated header values.
     */
    abstract public function obfuscateHeaderValues(string $headerName, array $values): array;

    /**
     * Obfuscates multiple headers.
     *
     * @param array<string, string|array<string>> $headers The headers to obfuscate.
     *
     * @return array<string, string|array<string>> The obfuscated headers.
     */
    public function obfuscateHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $name => $value) {
            if (is_string($value)) {
                $result[$name] = $this->obfuscateHeaderValue($name, $value);
            } else {
                $result[$name] = $this->obfuscateHeaderValues($name, $value);
            }
        }
        return $result;
    }

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
             * Case insensitively matched headers.
             *
             * @var array<string, Obfuscator>
             */
            private array $_headers = [];

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function withHeader(string $headerName, Obfuscator $obfuscator): HeaderObfuscatorBuilder
            {
                $lowerCaseHeaderName = mb_strtolower($headerName);
                if (isset($this->_headers[$lowerCaseHeaderName])) {
                    throw new ValueError("Duplicate header name: $headerName");
                }

                $this->_headers[$lowerCaseHeaderName] = $obfuscator;
                return $this;
            }

            // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
            public function build(): HeaderObfuscator
            {
                return new class($this->_headers) extends HeaderObfuscator
                {
                    /**
                     * Case insensitively matched headers
                     *
                     * @var array<string, Obfuscator>
                     */
                    private array $_headers;

                    /**
                     * Creates a new `HeaderObfuscator`.
                     *
                     * @param array<string, Obfuscator> $headers Case insensitively matched headers.
                     */
                    public function __construct(array $headers)
                    {
                        $this->_headers = $headers;
                    }

                    // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
                    public function obfuscateHeaderValue(string $headerName, string $value): string
                    {
                        $lowerCaseHeaderName = mb_strtolower($headerName);
                        return isset($this->_headers[$lowerCaseHeaderName])
                            ? $this->_headers[$lowerCaseHeaderName]->obfuscateText($value)
                            : $value;
                    }

                    // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
                    public function obfuscateHeaderValues(string $headerName, array $values): array
                    {
                        $lowerCaseHeaderName = mb_strtolower($headerName);
                        if (isset($this->_headers[$lowerCaseHeaderName])) {
                            $obfuscator = $this->_headers[$lowerCaseHeaderName];
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
