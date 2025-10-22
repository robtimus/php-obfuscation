<?php
namespace Robtimus\Obfuscation\Http;

use Robtimus\Obfuscation\Obfuscator;

/**
 * An object that will obfuscate header values.
 *
 * @package Robtimus\Obfuscation\Http
 * @author  Rob Spoor <robtimus@users.noreply.github.com>
 * @license https://www.apache.org/licenses/LICENSE-2.0.txt The Apache Software License, Version 2.0
 */
final class HeaderObfuscator
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
     * @param array<string, Obfuscator> $obfuscators An array with header names as keys and the obfuscators for those header names as values.
     */
    public function __construct(array $obfuscators)
    {
        $this->_obfuscators = [];
        foreach ($obfuscators as $name => $obfuscator) {
            $this->_obfuscators[mb_strtolower($name)] = $obfuscator;
        }
    }

    /**
     * Obfuscates the value of a header.
     *
     * @param string $name  The name of the header to obfuscate.
     * @param string $value The header value to obfuscate.
     *
     * @return string The obfuscated header value.
     */
    public function obfuscateValue(string $name, string $value): string
    {
        $lowerCaseName = mb_strtolower($name);
        return isset($this->_obfuscators[$lowerCaseName])
            ? $this->_obfuscators[$lowerCaseName]->obfuscateText($value)
            : $value;
    }

    /**
     * Obfuscates multiple values of a header.
     *
     * @param string        $name   The name of the header to obfuscate.
     * @param array<string> $values The header values to obfuscate.
     *
     * @return array<string> The obfuscated header values.
     */
    public function obfuscateValues(string $name, array &$values): array
    {
        $lowerCaseName = mb_strtolower($name);
        if (isset($this->_obfuscators[$lowerCaseName])) {
            $obfuscator = $this->_obfuscators[$lowerCaseName];
            $obfuscated = [];
            foreach ($values as $value) {
                $obfuscated[] = $obfuscator->obfuscateText($value);
            }
            return $obfuscated;
        }
        return $values;
    }
}
