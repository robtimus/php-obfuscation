<?php
namespace Robtimus\Obfuscation;

/**
 * An object that can be used to configure a property that should be obfuscated.
 *
 * @package Robtimus\Obfuscation
 * @author  Rob Spoor <robtimus@users.noreply.github.com>
 * @license https://www.apache.org/licenses/LICENSE-2.0.txt The Apache Software License, Version 2.0
 */
interface PropertyConfigurer extends PropertyObfuscatorBuilder
{
    /**
     * Indicates how to handle properties if their values are objects. The default is `INHERIT`.
     *
     * @param PropertyObfuscationMode $obfuscationMode The obfuscation mode that determines how to handle properties.
     *
     * @return PropertyConfigurer This object.
     */
    function forObjects(PropertyObfuscationMode $obfuscationMode): PropertyConfigurer;

    /**
     * Indicates how to handle properties if their values are arrays. The default is `INHERIT`.
     *
     * @param PropertyObfuscationMode $obfuscationMode The obfuscation mode that determines how to handle properties.
     *
     * @return PropertyConfigurer This object.
     */
    function forArrays(PropertyObfuscationMode $obfuscationMode): PropertyConfigurer;
}
