<?php

namespace WebAppId\Lazy\Tools;

use Exception;
use Illuminate\Support\Str;
use ReflectionException;
use ReflectionProperty;
use ReflectionClass;
use ReflectionType;
use ReflectionUnionType;

/**
 * @author: Dyan Galih<dyan.galih@gmail.com>
 * Date: 2019-07-02
 * Time: 07:11
 * Class Lazy
 * @package WebAppId\Lazy\Tools
 *
 * This class provides utilities for copying and transforming properties between objects,
 * automatically casting values to the destination property's declared type,
 * leveraging PHP 8+ features for improved type safety and reflection.
 * It supports both generic objects and Laravel models (via getFillable).
 */
class Lazy
{
    // The AUTOCAST constant is removed as auto-casting is now the default and only behavior for transformation.

    /**
     * Validates if a source value is compatible with the expected type of a destination property.
     * This method is primarily used internally by castValue to determine if a cast is feasible.
     *
     * @param string $sourceType
     * @param string $targetType The declared type of the destination property.
     * @param mixed $sourceValueForSpecificChecks The actual source value (for specific checks like is_int for float->int).
     * @return bool True if types are compatible.
     */
    private static function isTypeCompatible(string $sourceType, string $targetType, mixed $sourceValueForSpecificChecks): bool
    {
        // Normalize PHP's gettype() output to common type names for comparison
        $normalizedSourceType = match ($sourceType) {
            'integer' => 'int',
            'boolean' => 'bool',
            'double' => 'float', // gettype returns 'double' for floats
            'NULL' => 'null',
            default => $sourceType,
        };

        // Direct match
        if ($normalizedSourceType === $targetType) {
            return true;
        }

        // Handle specific cases for numeric types where PHP might implicitly convert
        if ($normalizedSourceType === 'int' && $targetType === 'float') {
            return true;
        }
        // Allow float to int if the float value is actually an integer (e.g., 5.0 to 5)
        if ($normalizedSourceType === 'float' && $targetType === 'int' && is_int($sourceValueForSpecificChecks)) {
            return true;
        }

        // Handle 'null' compatibility with nullable types or 'mixed'
        if ($normalizedSourceType === 'null' && (str_starts_with($targetType, '?') || $targetType === 'null' || $targetType === 'mixed')) {
            return true;
        }

        // Handle object type compatibility (e.g., if target is 'object' or a specific class)
        if ($normalizedSourceType === 'object' && $targetType === 'object') {
            return true;
        }
        // If the source is an object, check if it's an instance of the target class or interface
        if ($sourceType === 'object' && is_a($sourceValueForSpecificChecks, $targetType, true)) {
            return true;
        }

        // 'mixed' type in destination accepts anything
        if ($targetType === 'mixed') {
            return true;
        }

        return false;
    }

    /**
     * Gets the native PHP type(s) of a property using Reflection (PHP 8+).
     *
     * @param object $class The object to inspect.
     * @param string $key The property name.
     * @return string|array|null The property type (e.g., 'string', 'int', 'bool', 'array', 'mixed', 'object'),
     * an array of types for union types, or null if not typed or property doesn't exist.
     */
    private static function getPropertyNativeType(object $class, string $key): string|array|null
    {
        try {
            $reflectionClass = new ReflectionClass($class);
            if ($reflectionClass->hasProperty($key)) {
                $property = $reflectionClass->getProperty($key);
                $type = $property->getType(); // Returns ReflectionType, ReflectionNamedType, or ReflectionUnionType

                if ($type === null) {
                    return null; // Property has no type declaration
                }

                if ($type instanceof ReflectionUnionType) {
                    // For union types (e.g., 'int|string'), return an array of type names
                    return array_map(fn(ReflectionType $t) => $t->getName(), $type->getTypes());
                }

                // For ReflectionNamedType (single type)
                return $type->getName();
            }
        } catch (ReflectionException $e) {
            // Log or handle the exception if property doesn't exist or other reflection error.
            // For example: error_log("Reflection error for property {$key} on class " . get_class($class) . ": " . $e->getMessage());
        }
        return null;
    }

    /**
     * Casts a value to a specified type.
     * Handles PHP 8+ native types like 'int', 'float', 'bool', 'string', 'array', 'object'.
     *
     * @param string|array|null $propertyType The target type(s) (e.g., 'int', 'string', ['int', 'null']).
     * @param mixed $from The value to cast.
     * @return float|object|int|bool|array|string|null The casted value, or original if type is unknown or not primitive.
     * @throws Exception If the value cannot be cast to any of the target types (for union types).
     */
    private static function castValue(string|array|null $propertyType, mixed $from): float|object|int|bool|array|string|null
    {
        // If propertyType is null, no explicit type is declared, return original value.
        if ($propertyType === null) {
            return $from;
        }

        // If propertyType is an array (union type), try to cast to the first compatible type.
        if (is_array($propertyType)) {
            foreach ($propertyType as $type) {
                if (self::isTypeCompatible(gettype($from), $type, $from)) {
                    return self::castValue($type, $from); // Recursively cast to the compatible type
                }
            }
            // If no type in the union is compatible, throw an exception.
            throw new Exception(
                'Cannot cast value of type ' . gettype($from) .
                ' to any of the union types: [' . implode(', ', $propertyType) . ']'
            );
        }

        // Handle nullable types (e.g., '?string' or 'string|null')
        if ($from === null && (str_starts_with($propertyType, '?') || $propertyType === 'null')) {
            return null;
        }

        // Perform the actual casting based on the target type
        return match ($propertyType) {
            "int", "integer" => (int)$from,
            "float", "double" => (float)$from,
            "bool", "boolean" => (bool)$from,
            "object" => (object)$from,
            "array" => (array)$from,
            "string" => (string)$from,
            "mixed" => $from, // 'mixed' type accepts anything, no explicit cast needed
            default => $from, // Return original if type is unknown or not a primitive cast
        };
    }

    /**
     * Converts null values in an array to empty strings.
     *
     * @param array $data The input array.
     * @return array The array with nulls converted to empty strings.
     */
    public static function arrayNullToEmpty(array $data): array
    {
        return array_map(function ($value) {
            return $value === null ? "" : $value;
        }, $data);
    }

    /**
     * Transforms properties from a source object to a destination object.
     * This method automatically copies values based on the destination object's public properties,
     * handles camelCase/snake_case conversions, and casts values to the destination property's
     * declared type. It also supports explicit property mappings and Laravel models.
     *
     * @param object $fromClass The source object.
     * @param object $toClass The destination object.
     * @param array $mappings An associative array for explicit property mappings (dest_prop => source_prop).
     * @return object The modified destination object.
     * @throws Exception If a type mismatch occurs during casting that cannot be resolved.
     */
    public static function transform(object $fromClass, object $toClass, array $mappings = []): object
    {
        try {
            $destColumns = null;
            // Check if the destination class has a 'getColumns' method (e.g., for DTOs/Models)
            if (method_exists($toClass, 'getColumns')) {
                $destColumns = $toClass->getColumns();
            }

            // Determine which properties to iterate over in the destination class.
            // If 'getColumns' exists, use its output; otherwise, use all public properties.
            $propertiesToIterate = $destColumns ?? get_object_vars($toClass);

            foreach ($propertiesToIterate as $key => $value) {
                // Get the declared type of the destination property
                $destinationPropertyType = self::getPropertyNativeType($toClass, $key);

                // 1. Try direct property name match
                if (property_exists($fromClass, $key)) {
                    $toClass->$key = self::castValue($destinationPropertyType, $fromClass->$key);
                }
                // 2. If not found, try camelCase conversion (e.g., 'user_id' in source to 'userId' in dest)
                else {
                    $camelKey = Str::camel($key);
                    if (property_exists($fromClass, $camelKey)) {
                        $toClass->$key = self::castValue($destinationPropertyType, $fromClass->$camelKey);
                    }
                    // 3. If still not found, try snake_case conversion (e.g., 'userId' in source to 'user_id' in dest)
                    else {
                        $snakeKey = Str::snake($key, '_');
                        if (property_exists($fromClass, $snakeKey)) {
                            $toClass->$key = self::castValue($destinationPropertyType, $fromClass->$snakeKey);
                        }
                    }
                }
            }

            // Apply explicit mappings, which take precedence over automatic mapping.
            foreach ($mappings as $sourceProp => $destinationProp) {
                if (property_exists($toClass, $destinationProp) && property_exists($fromClass, $sourceProp)) {
                    $destinationPropertyType = self::getPropertyNativeType($toClass, $destinationProp);
                    $toClass->$destinationProp = self::castValue($destinationPropertyType, $fromClass->$sourceProp);
                }
            }
        } catch (Exception $exception) {
            // Re-throw the exception to allow the calling code to handle it.
            // For Laravel specific logging, you would use: report($exception);
            throw $exception;
        }

        return $toClass;
    }

    /**
     * Validates all public properties of an object against their declared types.
     * This method provides a standalone utility for type validation of an object's own state.
     *
     * @param object $class The object whose properties are to be validated.
     * @return bool True if all properties match their types.
     * @throws Exception If any type mismatch is found.
     */
    public static function validate(object $class): bool
    {
        // Iterate through all public properties of the given class
        foreach (get_object_vars($class) as $key => $value) {
            // Get the declared type of the property
            $declaredType = self::getPropertyNativeType($class, $key);
            // Get the actual type of the property's current value
            $actualType = gettype($value);

            // If a declared type exists and the actual type is not compatible, throw an exception.
            // This uses the same compatibility logic as casting.
            if ($declaredType !== null) {
                if (is_array($declaredType)) { // Handle union types
                    $isCompatible = false;
                    foreach ($declaredType as $type) {
                        if (self::isTypeCompatible($actualType, $type, $value)) {
                            $isCompatible = true;
                            break;
                        }
                    }
                    if (!$isCompatible) {
                        throw new Exception(
                            'Type Mismatch on property ' . $key .
                            '. Expected one of [' . implode(', ', $declaredType) .
                            '] but found type ' . $actualType
                        );
                    }
                } else { // Handle single type
                    if (!self::isTypeCompatible($actualType, $declaredType, $value)) {
                        throw new Exception(
                            'Type Mismatch on property ' . $key .
                            '. Expected type ' . $declaredType .
                            ' but found type ' . $actualType
                        );
                    }
                }
            }
        }
        return true; // If no exception is thrown, all properties are valid.
    }

    /**
     * Copies data from a JSON string to an object using the transform method.
     *
     * @param string $fromJson The JSON string to decode.
     * @param object $toClass The destination object.
     * @param array $mappings An associative array for explicit property mappings (dest_prop => source_prop).
     * @return object The modified destination object.
     * @throws Exception If JSON decoding fails or during transformation.
     */
    public static function copyFromJson(string $fromJson, object $toClass, array $mappings = []): object
    {
        $data = json_decode($fromJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON decoding failed: ' . json_last_error_msg());
        }
        return self::copyFromArray($data, $toClass, $mappings);
    }

    /**
     * Copies data from an array to an object using the transform method,
     * based on destination object's public properties or fillable properties
     * if the destination is a Laravel Model, with automatic type casting.
     *
     * @param array $fromArray The source array.
     * @param object $toClass The destination object.
     * @param array $mappings An associative array for explicit property mappings (dest_prop => source_prop).
     * @return object The modified destination object.
     * @throws Exception If type validation fails during casting.
     */
    public static function copyFromArray(array $fromArray, object $toClass, array $mappings = []): object
    {
        // Convert the array to an anonymous object to leverage the object-based transform logic.
        // This allows 'transform' to handle property existence checks and casing conventions uniformly.
        $fromObject = (object) $fromArray;

        // Use the transform method to perform the actual copying and casting.
        // The mappings will be applied by transform.
        return self::transform($fromObject, $toClass, $mappings);
    }
}
