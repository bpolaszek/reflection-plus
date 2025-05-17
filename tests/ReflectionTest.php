<?php

declare(strict_types=1);

namespace BenTools\ReflectionPlus\Tests;

use BenTools\ReflectionPlus\Reflection;
use InvalidArgumentException;
use Mockery;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;

// Test fixtures
class ParentClass
{
}
class ChildClass extends ParentClass
{
}
interface TestInterface
{
}
class ImplementsTestInterface implements TestInterface
{
}
class ChildWithInterface extends ChildClass implements TestInterface
{
}

class ClassWithProperties
{
    public string $stringProperty;
    public ParentClass $parentClassProperty;
    public TestInterface $interfaceProperty;
    public ChildClass|ParentClass $unionProperty;
    public ChildClass&TestInterface $intersectionProperty;

    public function __construct()
    {
    }
}

// Basic Reflection methods tests
test('Reflection::class returns ReflectionClass when given a class string', function () {
    $reflectionClass = Reflection::class(ParentClass::class);

    expect($reflectionClass)
        ->toBeInstanceOf(ReflectionClass::class)
        ->and($reflectionClass->getName())
        ->toBe(ParentClass::class);
});

test('Reflection::class returns ReflectionClass when given an object', function () {
    $object = new ParentClass();
    $reflectionClass = Reflection::class($object);

    expect($reflectionClass)
        ->toBeInstanceOf(ReflectionClass::class)
        ->and($reflectionClass->getName())
        ->toBe(ParentClass::class);
});

test('Reflection::class caches instances', function () {
    $reflectionClass1 = Reflection::class(ParentClass::class);
    $reflectionClass2 = Reflection::class(ParentClass::class);

    expect($reflectionClass1)->toBe($reflectionClass2);
});

test('Reflection::property returns ReflectionProperty when given a class string and property name', function () {
    $reflectionProperty = Reflection::property(ClassWithProperties::class, 'stringProperty');

    expect($reflectionProperty)
        ->toBeInstanceOf(ReflectionProperty::class)
        ->and($reflectionProperty->getName())
        ->toBe('stringProperty');
});

test('Reflection::property returns ReflectionProperty when given an object and property name', function () {
    $object = new ClassWithProperties();
    $reflectionProperty = Reflection::property($object, 'stringProperty');

    expect($reflectionProperty)
        ->toBeInstanceOf(ReflectionProperty::class)
        ->and($reflectionProperty->getName())
        ->toBe('stringProperty');
});

test('Reflection::property caches instances', function () {
    $reflectionProperty1 = Reflection::property(ClassWithProperties::class, 'stringProperty');
    $reflectionProperty2 = Reflection::property(ClassWithProperties::class, 'stringProperty');

    expect($reflectionProperty1)->toBe($reflectionProperty2);
});

test('Reflection::method returns ReflectionMethod when given a class string and method name', function () {
    $reflectionMethod = Reflection::method(Reflection::class, 'method');

    expect($reflectionMethod)
        ->toBeInstanceOf(ReflectionMethod::class)
        ->and($reflectionMethod->getName())
        ->toBe('method');
});

test('Reflection::method returns ReflectionMethod when given an object and method name', function () {
    $reflectionMethod = Reflection::method(new ClassWithProperties(), '__construct');

    expect($reflectionMethod)
        ->toBeInstanceOf(ReflectionMethod::class)
        ->and($reflectionMethod->getName())
        ->toBe('__construct');
});

test('Reflection::method caches instances', function () {
    $reflectionMethod1 = Reflection::method(Reflection::class, 'method');
    $reflectionMethod2 = Reflection::method(Reflection::class, 'method');

    expect($reflectionMethod1)->toBe($reflectionMethod2);
});

// Type compatibility tests
test('isPropertyCompatible returns true for compatible classes', function () {
    $property = Reflection::property(ClassWithProperties::class, 'parentClassProperty');

    expect(Reflection::isPropertyCompatible($property, ParentClass::class))
        ->toBeTrue();
});

test('isPropertyCompatible returns true for child classes of compatible parent', function () {
    $property = Reflection::property(ClassWithProperties::class, 'parentClassProperty');

    expect(Reflection::isPropertyCompatible($property, ChildClass::class))
        ->toBeTrue();
});

test('isPropertyCompatible returns true for classes implementing required interface', function () {
    $property = Reflection::property(ClassWithProperties::class, 'interfaceProperty');

    expect(Reflection::isPropertyCompatible($property, ImplementsTestInterface::class))
        ->toBeTrue();
});

test('Reflection::getBestClassForProperty returns first compatible class', function () {
    $property = Reflection::property(ClassWithProperties::class, 'parentClassProperty');
    $classNames = [ParentClass::class, ChildClass::class];

    expect(Reflection::getBestClassForProperty($property, $classNames))
        ->toBe(ParentClass::class);
});

test('Reflection::getBestClassForProperty throws exception when no compatible class found', function () {
    $property = Reflection::property(ClassWithProperties::class, 'parentClassProperty');
    $classNames = [TestInterface::class]; // Not compatible with ParentClass

    expect(fn () => Reflection::getBestClassForProperty($property, $classNames))
        ->toThrow(InvalidArgumentException::class);
});

// Advanced tests with mock of ReflectionType
test('isTypeCompatible works with named types', function () {
    $reflectionProperty = Reflection::property(ClassWithProperties::class, 'parentClassProperty');
    $type = $reflectionProperty->getType();

    expect(Reflection::isTypeCompatible($type, ParentClass::class))
        ->toBeTrue();
});

// Test union and intersection types would require more complex mocking
// Here we're just testing with the actual properties from our test class
test('isTypeCompatible works with union types', function () {
    $reflectionProperty = Reflection::property(ClassWithProperties::class, 'unionProperty');
    $type = $reflectionProperty->getType();

    if ($type instanceof ReflectionUnionType) {
        expect(Reflection::isTypeCompatible($type, ChildClass::class))
            ->toBeTrue();
    } else {
        // Skip test if PHP version doesn't support union types
        $this->markTestSkipped('Union types not supported in this PHP version');
    }
});

test('getSettableClassTypes returns class names for properties with class types', function () {
    $reflectionProperty = Reflection::property(ClassWithProperties::class, 'parentClassProperty');

    $classTypes = Reflection::getSettableClassTypes($reflectionProperty);

    expect($classTypes)->toContain(ParentClass::class);
});

test('getSettableClassTypes returns empty array for untyped properties', function () {
    // Mock a ReflectionProperty with no type
    $mockProperty = Mockery::mock(ReflectionProperty::class);
    $mockProperty->shouldReceive('getSettableType')->andReturn(null);
    $mockProperty->shouldReceive('getName')->andReturn('testProperty');

    $classTypes = Reflection::getSettableClassTypes($mockProperty);

    expect($classTypes)->toBeEmpty();
});

// Additional tests to improve code coverage

test('isTypeCompatible works with intersection types', function () {
    $reflectionProperty = Reflection::property(ClassWithProperties::class, 'intersectionProperty');
    $type = $reflectionProperty->getType();

    if ($type instanceof ReflectionIntersectionType) {
        // Testing with ChildWithInterface that satisfies both child class and interface requirements
        expect(Reflection::isTypeCompatible($type, ChildWithInterface::class))
            ->toBeTrue();
    } else {
        $this->markTestSkipped('Intersection types not supported in this PHP version');
    }
});

test('isTypeCompatible returns false for unknown reflection type', function () {
    // Creating a mock of a non-standard reflection type
    $mockType = Mockery::mock(ReflectionType::class);

    expect(Reflection::isTypeCompatible($mockType, ParentClass::class))
        ->toBeFalse();
});

test('collectClassNames properly collects class names from named types', function () {
    // Create a reflection property with a non-built-in type
    $reflectionProperty = Reflection::property(ClassWithProperties::class, 'parentClassProperty');
    $type = $reflectionProperty->getType();

    // We need to test the collectClassNames method which is private
    // So we'll test it indirectly through getSettableClassTypes
    $classTypes = Reflection::getSettableClassTypes($reflectionProperty);

    expect($classTypes)->not->toBeEmpty();
});

test('collectClassNames properly processes union types', function () {
    $reflectionProperty = Reflection::property(ClassWithProperties::class, 'unionProperty');
    $type = $reflectionProperty->getType();

    if ($type instanceof ReflectionUnionType) {
        $classTypes = Reflection::getSettableClassTypes($reflectionProperty);

        // Should contain both classes from the union
        expect($classTypes)->toHaveCount(2);
        expect($classTypes)->toContain(ParentClass::class);
        expect($classTypes)->toContain(ChildClass::class);
    } else {
        $this->markTestSkipped('Union types not supported in this PHP version');
    }
});

test('getSettableClassTypes filters non-instantiable classes', function () {
    // Create a property that uses TestInterface which is non-instantiable
    $reflectionProperty = Reflection::property(ClassWithProperties::class, 'interfaceProperty');

    // Get the actual class types
    $classTypes = Reflection::getSettableClassTypes($reflectionProperty);

    // The interface should be filtered out as it's not instantiable
    expect($classTypes)->not->toContain(TestInterface::class);
});

test('getSettableClassTypes filtering logic works with instantiable classes', function () {
    // Create a test class
    $obj = new class {
        public ParentClass $property;
    };

    // Get the reflection property and class types
    $reflectionProperty = Reflection::property($obj, 'property');
    $classTypes = Reflection::getSettableClassTypes($reflectionProperty);

    // ParentClass is instantiable, so it should be included
    expect($classTypes)->toContain(ParentClass::class);
});
