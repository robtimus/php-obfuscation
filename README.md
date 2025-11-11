# Obfuscation
[![Packagist Version](https://img.shields.io/packagist/v/robtimus/obfuscation)](https://packagist.org/packages/robtimus/obfuscation)
[![Build Status](https://github.com/robtimus/data-url/actions/workflows/build.yml/badge.svg)](https://github.com/robtimus/php-obfuscation/actions/workflows/build.yml)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=robtimus%3Aobfuscation&metric=alert_status)](https://sonarcloud.io/summary/overall?id=robtimus%3Aobfuscation)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=robtimus%3Aobfuscation&metric=coverage)](https://sonarcloud.io/summary/overall?id=robtimus%3Aobfuscation)

Provides functionality for obfuscating text. This can be useful for logging information that contains sensitive information.

## Obfuscating strings

### Pre-defined obfuscators

The following pre-defined obfuscators are provided that all return an immutable obfuscator.

#### Obfuscate::all

Replaces all characters with a mask character that defaults to `*`.

```php
$obfuscator = Obfuscate::all();
$obfuscated = $obfuscator->obfuscateText('Hello World');
// $obfuscated is '***********'
```

Note: using this obfuscator still leaks out information about the length of the original text. One of the following two is more secure.

#### Obfuscate::fixedLength

Replaces the entire text with a fixed number of the given mask character that defaults to `*`.

```php
$obfuscator = Obfuscate::fixedLength(5);
$obfuscated = $obfuscator->obfuscateText('Hello World');
// $obfuscated is '*****'
```

#### Obfuscate::fixedValue

Replaces the entire text with a fixed value.

```php
$obfuscator = Obfuscate::fixedValue('foo');
$obfuscated = $obfuscator->obfuscateText('Hello World');
// $obfuscated is 'foo'
```

#### Obfuscate::portion

While the above examples are simple, they are not very flexible. Using `Obfuscate::portion` you can build obfuscators that obfuscate only specific portions of text. Some examples:

##### Obfuscating all but the last 4 characters

Useful for obfuscating values like credit card numbers.

```php
$obfuscator = Obfuscate::portion()
    ->keepAtEnd(4)
    ->build();
$obfuscated = $obfuscator->obfuscateText('1234567890123456');
// $obfuscated is '************3456'
```

It’s advised to use `atLeastFromStart`, to make sure that values of fewer than 16 characters are still obfuscated properly:

```php
$obfuscator = Obfuscate::portion()
    ->keepAtEnd(4)
    ->atLeastFromStart(12)
    ->build();
$obfuscated = $obfuscator->obfuscateText('1234567890');
// $obfuscated is '**********' and not '******7890'
```

##### Obfuscating only the last 2 characters

Useful for obfuscating values like zip codes, where the first part is not as sensitive as the full zip code:

```php
$obfuscator = Obfuscate::portion()
    ->keepAtStart(PHP_INT_MAX)
    ->atLeastFromEnd(2)
    ->build();
$obfuscated = $obfuscator->obfuscateText('SW1A 2AA');
// $obfuscated is 'SW1A 2**'
```

Here, the `keepAtStart` instructs the obfuscator to keep everything; however, `atLeastFromEnd` overrides that partly to ensure that the last two characters are obfuscated regardless of the value specified by `keepAtStart`.

##### Using a fixed length

Similar to using `Obfuscate::all`, by default an obfuscator built using `Obfuscate::portion` leaks out the length of the original text. If your text has a variable length, you should consider specifying a fixed total length for the result. The length of the result will then be the same no matter how long the input is:

```php
$obfuscator = Obfuscate::portion()
    ->keepAtStart(2)
    ->keepAtEnd(2)
    ->withFixedTotalLength(6)
    ->build();
$obfuscated = $obfuscator->obfuscateText('Hello World');
// $obfuscated is 'He**ld'
$obfuscated = $obfuscator->obfuscateText('foo');
// $obfuscated is 'fo**oo'
```

Note that if `keepAtStart` and `keepAtEnd` are both specified, parts of the input may be repeated in the result if the input’s length is less than the combined number of characters to keep. This makes it harder to find the original input. For example, if in the example `foo` would be obfuscated into `fo***o` instead, it would be clear that the input was `foo`. Instead, it can now be anything that starts with `fo` and ends with `oo`.

### Obfuscate::none

Does not perform any obfuscation at all. It can be used as default to prevent checks. For instance:

```php
$obfuscator = $somePossiblyUndefinedObfuscator ?: Obfuscate::none();
$obfuscated = $obfuscator->obfuscateText('Hello World');
// $obfuscated is 'Hello World' if $somePossiblyUndefinedObfuscator was null
```

### Obfuscate::exploded

Explodes the text to an array, obfuscates each element, then implodes the array again.

```php
$obfuscator = Obfuscate::exploded(', ', Obfuscate::fixedLength(3));
$obfuscated = $obfuscator->obfuscateText('a, b, c');
// $obfuscated is '***, ***, ***'
```

### Combining obfuscators

Sometimes the obfucators in this library alone cannot perform the obfuscation you need. For instance, if you want to obfuscate credit cards, but keep the first and last 4 characters. If the credit cards are all fixed length, `Obfuscate::portion` can do just that:

```php
$obfuscator = Obfuscate::portion()
    ->keepAtStart(4)
    ->keepAtEnd(4)
    ->build();
$obfuscated = $obfuscator->obfuscateText('1234567890123456');
// $obfuscated is '1234********3456'
```

However, if you attempt to use such an obfuscator on only a part of a credit card, you could end up leaking parts of the credit card that you wanted to obfuscate:

```php
$incorrectlyObfuscated = $obfuscator->obfuscateText('12345678901234');
// $incorrectlyObfuscated is '1234******1234' where '1234********34' would probably be preferred
```

To overcome this issue, it’s possible to combine obfuscators. The form is as follows:
* Specify the first obfuscator, and the input length to which it should be used.
* Specify any other obfuscators, and the input lengths to which they should be used. Note that each input length should be larger than the previous input length.
* Specify the obfuscator that will be used for the remainder.

For instance, for credit card numbers of exactly 16 characters, the above can also be written like this:

```php
$obfuscator = Obfuscate::none()->untilLength(4)
    ->then(Obfuscate::all())->untilLength(12)
    ->then(Obfuscate::none());
```

With this chaining, it’s now possible to keep the first and last 4 characters, but with at least 8 characters in between:

```php
$obfuscator = Obfuscate::none()->untilLength(4)
    ->then(Obfuscate::portion()
        ->keepAtEnd(4)
        ->atLeastFromStart(8)
        ->build());
$obfuscated = $obfuscator->obfuscateText('12345678901234');
// $obfuscated is '1234********34'
```

### Splitting text during obfuscation

To make it easier to create obfuscators for structured text like email addresses, use a `SplitPoint`. Three implementations are provided :
* `atFirst(s)` splits at the first occurrence of string `s`.
* `atLast(s)` splits at the last occurrence of string `s`.
* `atNth(s, occurrence)` splits at the zero-based specified occurrence of string `s`.

For instance:

```php
// Keep the domain as-is
$localPartObfuscator = Obfuscate::portion()
    ->keepAtStart(1)
    ->keepAtEnd(1)
    ->withFixedTotalLength(8)
    ->build();
$domainObfuscator = Obfuscate::none();
$obfuscator = SplitPoint::atFirst('@')->splitTo($localPartObfuscator, $domainObfuscator);
$obfuscated = $obfuscator->obfuscateText('test@example.org');
// $obfuscated is 't******t@example.org'
```

To obfuscate the domain except for the TLD, use a nested `SplitPoint`:

```php
// Keep only the TLD of the domain
$localPartObfuscator = Obfuscate::portion()
    ->keepAtStart(1)
    ->keepAtEnd(1)
    ->withFixedTotalLength(8)
    ->build();
$domainObfuscator = SplitPoint::atLast('.')->splitTo(Obfuscate::all(), Obfuscate::none());
$obfuscator = SplitPoint::atFirst('@')->splitTo($localPartObfuscator, $domainObfuscator);
$obfuscated = $obfuscator->obfuscateText('test@example.org');
// $obfuscated is 't******t@*******.org'
```

#### Custom obfuscators

To create a custom obfuscator, create a sub class of `Obfuscator`, override its constructor and implements its `obfuscateText` method. For instance:

```php
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
// $obfuscated is 'HELLO WORLD'
```

## Obfuscating object properties

Use `PropertyObfuscator::builder` to start creating objects that can obfuscate single object properties as well as recursively all properties in an object or array.

The simplest form provides an obfucator for each property to obfuscate:

```php
$propertyObfuscator = PropertyObfuscator::builder()
    ->withProperty('password', Obfuscate::fixedLength(3))
    ->build();
$obfuscatedPassword = $propertyObfuscator->obfuscateProperty('password', 'admin1234');
// $obfuscatedPassword is '***'
$obfuscatedUsername = $propertyObfuscator->obfuscateProperty('username', 'admin');
// $obfuscatedUsername is 'admin'
$object = new stdClass();
$object->username = 'admin';
$object->password = 'admin1234';
$obfuscatedObject = $propertyObfuscator->obfuscateProperties($object);
// $obfuscatedObject is a stdClass with properties username='admin' and password='***'
$obfuscatedArray = $propertyObfuscator->obfuscateProperties(array('username' => 'admin', 'password' => 'admin1234'));
// $obfuscatedArray is ['username' => 'admin', 'password' => '***']
```

This matches property names case sensitively, and for any nested objects or arrays will obfuscate any scalar property with the object's or array's obfuscator. This behaviour can be changed in two ways:

1. Per property. `withProperty` returns an object that can be used to further configure the property:
    ```php
    $propertyObfuscator = PropertyObfuscator::builder()
        ->withProperty('password', Obfuscate::fixedLength(3), false) // defaults to true
            ->forObjects(PropertyObfuscationMode::EXCLUDE) // defaults to INHERIT
            ->forArrays(PropertyObfuscationMode::EXCLUDE) // defaults to INHERIT
        ->build();
    ```
2. Using global options:
    ```php
    $propertyObfuscator = PropertyObfuscator::builder()
        ->caseInsensitiveByDefault() // defaults to case sensitive
        ->forObjectsByDefault(PropertyObfuscationMode::EXCLUDE) // defaults to INHERIT
        ->forArraysByDefault(PropertyObfuscationMode::EXCLUDE) // defaults to INHERIT
        ->withProperty('password', Obfuscate::fixedLength(3))
        ->build();
    ```

In both cases, `forObjects` and `forArrays` can take the following values:
* `PropertyObfuscationMode::SKIP` to skip obfuscating object or array values and all nested properties.
* `PropertyObfuscationMode::EXCLUDE` to not match properties with object or array values; nested properties will be matched separately.
* `PropertyObfuscationMode::INHERIT` to obfuscate each nested scalar property value or array element using the given obfuscator.
* `PropertyObfuscationMode::INHERIT_OVERRIDABLE` to obfuscate each nested scalar property value or array element using the given obfuscator; however, if a nested property has its own obfuscator defined this will be used instead.

## Obfuscating HTTP headers

Use `HeaderObfuscator::builder` to start creating objects that can obfuscate single HTTP headers (as strings and string arrays) and associative arrays containing multiple headers. It's much like `PropertyObfuscator`, but like HTTP headers it's always case insensitive. Unlike `PropertyObfuscator`, it doesn't support nested objects, and for nested arrays each element is obfuscated separately. It also does not support skipping obfuscation.

```php
$headerObfuscator = HeaderObfuscator::builder()
    ->withHeader('Authorization', Obfuscate::fixedLength(3))
    ->withHeader('Multiple-values', Obfuscate::exploded(', ', Obfuscate::fixedLength(3)))
    ->build();
$obfuscatedAuthorization = $headerObfuscator->obfuscateHeaderValue('authorization', 'Bearer someToken');
// $obfuscatedAuthorization is '***'
$obfuscatedAuthorizations = $headerObfuscator->obfuscateHeaderValues('authorization', array('Bearer someToken'));
// $obfuscatedAuthorizations is ['***']
$obfuscatedMultipleValues = $headerObfuscator->obfuscateHeaderValue('multiple-values', 'value1, value2, value3');
// $obfuscatedMultipleValues is '***, ***, ***'
$obfuscatedContentType = $headerObfuscator->obfuscateHeaderValue('Content-Type', 'application/json');
// $obfuscatedContentType is 'application/json'
$obfuscatedHeaders = $headerObfuscator->obfuscateHeaders(array(
    'authorization'   => 'Bearer someToken',
    'multiple-values' => 'value1, value2, value3',
    'content-type'    => 'application/json',
));
// $obfuscatedHeaders is ['authorization' => '***', 'multiple-values' => '***, ***, ***', 'content-type' => 'application/json']
```
