<?php
namespace Robtimus\Obfuscation;

use ValueError;

/**
 * A builder for `PropertyObfuscator` instances.
 *
 * @package Robtimus\Obfuscation
 * @author  Rob Spoor <robtimus@users.noreply.github.com>
 * @license https://www.apache.org/licenses/LICENSE-2.0.txt The Apache Software License, Version 2.0
 */
interface PropertyObfuscatorBuilder
{
    /**
     * Adds a property to obfuscate.
     *
     * @param string     $propertyName  The name of the property.
     * @param Obfuscator $obfuscator    The obfuscator to use for obfuscating the property.
     * @param bool       $caseSensitive `true` to treat the property name case sensitively, `false` to treat the property name case insensitively,
     *                                  or `null` to use the default case sensitivity.
     *
     * @return PropertyConfigurer An object that can be used to configure the property, or continue building `PropertyObfuscator` instances.
     * @throws ValueError If a property with the same name and the same case sensitivity was already added.
     */
    function withProperty(string $propertyName, Obfuscator $obfuscator, ?bool $caseSensitive = null): PropertyConfigurer;

    /**
     * Sets the case sensitivity for newly added properties that have no case sensitivity defined to `true`.
     *
     * This will not change the case sensitivity of any property that was already added.
     *
     * @return PropertyObfuscatorBuilder This object.
     */
    function caseSensitiveByDefault(): PropertyObfuscatorBuilder;

    /**
     * Sets the case sensitivity for newly added properties that have no case sensitivity defined to `false`.
     *
     * This will not change the case sensitivity of any property that was already added.
     *
     * @return PropertyObfuscatorBuilder This object.
     */
    function caseInsensitiveByDefault(): PropertyObfuscatorBuilder;

    /**
     * Indicates how to handle properties if their values are objects. The default is `INHERIT`.
     *
     * This will not change the obfuscation mode for any property that was already added.
     *
     * @param PropertyObfuscationMode $obfuscationMode The obfuscation mode that determines how to handle properties.
     *
     * @return PropertyObfuscatorBuilder This object.
     */
    function forObjectsByDefault(PropertyObfuscationMode $obfuscationMode): PropertyObfuscatorBuilder;

    /**
     * Indicates how to handle properties if their values are arrays. The default is `INHERIT`.
     *
     * This will not change the obfuscation mode for any property that was already added.
     *
     * @param PropertyObfuscationMode $obfuscationMode The obfuscation mode that determines how to handle properties.
     *
     * @return PropertyObfuscatorBuilder This object.
     */
    function forArraysByDefault(PropertyObfuscationMode $obfuscationMode): PropertyObfuscatorBuilder;

    /**
     * Creates a new `PropertyObfuscator` with the properties and obfuscators added to this builder.
     *
     * @return PropertyObfuscator The created `PropertyObfuscator` instance.
     */
    function build(): PropertyObfuscator;
}
