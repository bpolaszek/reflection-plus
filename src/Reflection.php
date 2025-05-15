<?php

declare(strict_types=1);

namespace BenTools\ReflectionPlus;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use WeakMap;

use function array_all;
use function array_filter;
use function array_unique;
use function array_values;
use function ltrim;

final class Reflection
{
    private static self $instance;
    private array $reflectionClassCache = [];
    private array $reflectionPropertyCache = [];
    private array $reflectionMethodCache = [];
    private WeakMap $reflectionTypesCache;

    private function __construct()
    {
        $this->reflectionTypesCache = new WeakMap();
    }

    private static function get(): self
    {
        return self::$instance ??= new self();
    }

    public static function class(object|string $class): ReflectionClass
    {
        $className = is_object($class) ? $class::class : $class;

        return self::get()->reflectionClassCache[$className] ??= new ReflectionClass($className);
    }

    public static function property(object|string $class, string $property): ReflectionProperty
    {
        $className = is_object($class) ? $class::class : $class;

        return self::get()->reflectionPropertyCache[$className][$property] ??= self::class($class)->getProperty($property);
    }

    public static function method(object|string $class, string $method): ReflectionMethod
    {
        $className = is_object($class) ? $class::class : $class;

        return self::get()->reflectionMethodCache[$className][$method] ??= self::class($class)->getMethod($method);
    }

    /**
     * @return class-string[]
     */
    public static function getSettableClassTypes(ReflectionProperty $property): array
    {
        $classNames = [];
        $type = $property->getSettableType();

        if ($type === null) {
            return [];
        }

        self::collectClassNames($type, $classNames);

        return array_values(
            array_unique(
                array_filter(
                    $classNames,
                    fn (string $classType) => self::class($classType)->isInstantiable(),
                )
            )
        );
    }

    private static function collectClassNames(ReflectionType $type, array &$classNames): void
    {
        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();

            // Skip built-in types
            if (!$type->isBuiltin()) {
                // Remove nullable prefix if present
                $classNames[] = ltrim($typeName, '?');
            }
        } elseif ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            // For union or intersection types, process each type recursively
            foreach ($type->getTypes() as $subType) {
                self::collectClassNames($subType, $classNames);
            }
        }
    }

    public static function getBestClassForProperty(ReflectionProperty $property, array $classNames): string
    {
        return array_find($classNames, fn ($className) => self::isPropertyCompatible($property, $className))
            ?? throw new InvalidArgumentException("No compatible class found for property {$property->getName()}");
    }

    public static function isPropertyCompatible(ReflectionProperty $property, string $className): bool
    {
        $settableType = $property->getSettableType();

        return self::isTypeCompatible($settableType, $className);
    }

    public static function isTypeCompatible(ReflectionType $type, string $className): bool
    {
        return self::get()->reflectionTypesCache[$type] ??= match ($type::class) {
            ReflectionNamedType::class => self::isCompatibleWithNamedType($type, $className),
            ReflectionUnionType::class => self::isCompatibleWithUnionType($type, $className),
            ReflectionIntersectionType::class => self::isCompatibleWithIntersectionType($type, $className),
            default => false,
        };
    }

    private static function isCompatibleWithNamedType(ReflectionNamedType $type, string $className): bool
    {
        return !$type->isBuiltin() && is_a($className, ltrim($type->getName(), '?'), true);
    }

    private static function isCompatibleWithIntersectionType(ReflectionIntersectionType $type, string $className): bool
    {
        return array_any(
            $type->getTypes(),
            fn ($intersectionType) => self::isTypeCompatible($intersectionType, $className)
        );
    }

    private static function isCompatibleWithUnionType(ReflectionUnionType $type, string $className): bool
    {
        return array_all($type->getTypes(), fn ($unionType) => self::isTypeCompatible($unionType, $className));
    }
}
