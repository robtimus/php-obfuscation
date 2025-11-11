<?php
namespace Robtimus\Obfuscation;

use ValueError;

/**
 * A builder for `HeaderObfuscator` instances.
 *
 * @package Robtimus\Obfuscation
 * @author  Rob Spoor <robtimus@users.noreply.github.com>
 * @license https://www.apache.org/licenses/LICENSE-2.0.txt The Apache Software License, Version 2.0
 */
interface HeaderObfuscatorBuilder
{
    /**
     * Adds a header to obfuscate.
     *
     * @param string     $headerName The name of the header.
     * @param Obfuscator $obfuscator The obfuscator to use for obfuscating the header.
     *
     * @return HeaderObfuscatorBuilder This object.
     * @throws ValueError If a header with the same name was already added, irregardless of case.
     */
    function withHeader(string $headerName, Obfuscator $obfuscator): HeaderObfuscatorBuilder;

    /**
     * Creates a new `HeaderObfuscator` with the headers and obfuscators added to this builder.
     *
     * @return HeaderObfuscator The created `HeaderObfuscator` instance.
     */
    function build(): HeaderObfuscator;
}
