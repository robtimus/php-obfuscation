<?php
namespace Robtimus\Obfuscation;

/**
 * The possible ways to deal with nested objects and arrays.
 *
 * @package Robtimus\Obfuscation
 * @author  Rob Spoor <robtimus@users.noreply.github.com>
 * @license https://www.apache.org/licenses/LICENSE-2.0.txt The Apache Software License, Version 2.0
 */
enum PropertyObfuscationMode
{
    /**
     * Nested objects or arrays will not be obfuscated, including all nested properties.
     */
    case SKIP;
    /**
     * Nested objects or arrays will not be obfuscated, but instead obfuscation will traverse into them.
     */
    case EXCLUDE;
    /**
     * Obfuscators defined for nested objects or arrays will be used for all nested scalar properties.
     */
    case INHERIT;
    /**
     * Obfuscators defined for nested objects or arrays will be used for all nested scalar properties.
     * If a nested property has its own obfuscator defined this will be used instead.
     */
    case INHERIT_OVERRIDABLE;
}
