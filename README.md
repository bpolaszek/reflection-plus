# Reflection Plus!

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bentools/reflection-plus.svg?style=flat-square)](https://packagist.org/packages/bentools/reflection-plus)
[![Tests](https://img.shields.io/github/actions/workflow/status/bpolaszek/reflection-plus/ci.yml?branch=main&label=tests&style=flat-square)](https://github.com/bentools/reflection-plus/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/bentools/reflection-plus.svg?style=flat-square)](https://packagist.org/packages/bentools/reflection-plus)
[![codecov](https://codecov.io/gh/bpolaszek/reflection-plus/graph/badge.svg?token=ACv1Uwlqft)](https://codecov.io/gh/bpolaszek/reflection-plus)

**ReflectionPlus** is a lightweight wrapper around PHP's native Reflection API that makes working with reflection simpler, more efficient, and more intuitive.

## Features

- **Cache-enabled reflection**: Automatically caches reflection instances for better performance
- **Simplified API**: Clean, intuitive methods for accessing reflection information
- **Type compatibility analysis**: Easily determine if classes are compatible with property types
- **Support for complex type systems**: Full handling of union types, intersection types, and named types
- **Performance optimized**: Uses WeakMap and other optimizations to minimize memory usage

## Requirements

- PHP 8.4 or higher

## Installation

You can install the package via composer:

```bash
composer require bentools/reflection-plus
```

## Usage

### Basic Reflection Operations

Get a reflection class:

```php
use BenTools\ReflectionPlus\Reflection;

// From a class name
$reflectionClass = Reflection::class(MyClass::class);

// Or from an object
$object = new MyClass();
$reflectionClass = Reflection::class($object);
```

Get a reflection property:

```php
// From a class name
$reflectionProperty = Reflection::property(MyClass::class, 'someProperty');

// Or from an object
$object = new MyClass();
$reflectionProperty = Reflection::property($object, 'someProperty');
```

Get a reflection method:

```php
// From a class name
$reflectionMethod = Reflection::method(MyClass::class, 'someMethod');

// Or from an object
$object = new MyClass();
$reflectionMethod = Reflection::method($object, 'someMethod');
```

### Working with Property Types

Get all instantiable class types that can be set to a property:

```php
// Get a reflection property
$reflectionProperty = Reflection::property($myClass, 'myProperty');

// Get all class types that can be set to this property
$classTypes = Reflection::getSettableClassTypes($reflectionProperty);

// Returns an array of fully qualified class names
// that are compatible with the property type
// and are instantiable (no interfaces or abstract classes)
```

Check if a class is compatible with a property:

```php
$reflectionProperty = Reflection::property($myClass, 'myProperty');

// Check if a particular class is compatible
$isCompatible = Reflection::isPropertyCompatible($reflectionProperty, SomeClass::class);

// Returns true if SomeClass can be assigned to the property
```

Find the best class for a property from a list of candidates:

```php
$reflectionProperty = Reflection::property($myClass, 'myProperty');
$classNames = [ClassA::class, ClassB::class, ClassC::class];

// Find the first compatible class in the array
$bestClass = Reflection::getBestClassForProperty($reflectionProperty, $classNames);

// Returns the class name of the first compatible class
// or throws InvalidArgumentException if none are compatible
```

### Type Compatibility

Check type compatibility with different type systems:

```php
$reflectionProperty = Reflection::property($myClass, 'myProperty');
$type = $reflectionProperty->getType();

// Check if a class is compatible with this type
$isCompatible = Reflection::isTypeCompatible($type, SomeClass::class);

// Works with:
// - ReflectionNamedType (standard class or primitive types)
// - ReflectionUnionType (Type1|Type2)
// - ReflectionIntersectionType (Type1&Type2)
```

## Advanced Use Cases

### Working with Union Types

```php
// For a property like: public ClassA|ClassB $property;

$reflectionProperty = Reflection::property($class, 'property');
$classTypes = Reflection::getSettableClassTypes($reflectionProperty);

// $classTypes will contain [ClassA::class, ClassB::class] if both are instantiable
```

### Working with Intersection Types

```php
// For a property like: public ClassA&InterfaceB $property;

$reflectionProperty = Reflection::property($class, 'property');

// You can check if a specific class meets all requirements
$isCompatible = Reflection::isPropertyCompatible($reflectionProperty, SomeClass::class);

// Returns true if SomeClass extends/is ClassA AND implements InterfaceB
```

## Performance Considerations

ReflectionPlus automatically caches:

- ReflectionClass instances
- ReflectionProperty instances
- ReflectionMethod instances
- Type compatibility results

This makes it highly efficient for repeated usage, especially in loops or recursive operations.

## Use Cases

ReflectionPlus is particularly useful for:

- Factory implementations that need to determine which concrete classes to instantiate
- Dependency injection containers
- Type-based serialization/deserialization systems
- Code generators that need to inspect class structures
- Data mappers that need to determine type compatibility
- Framework development where reflection is frequently used

## How It Works

The package provides a static facade to underlying reflection capabilities with an intelligent caching system. It uses:

- Internal array caches for reflection classes, properties, and methods
- A WeakMap for reflection type compatibility results
- The "nullsafe coalescing assignment" operator (`??=`) for efficient caching

## License

MIT.
