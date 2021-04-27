<?php


namespace WebAppId\Lazy\Tools;

use Exception;
use ReflectionException;
use ReflectionProperty;

/**
 * @author: Dyan Galih<dyan.galih@gmail.com>
 * Date: 2019-07-02
 * Time: 07:11
 * Class Lazy
 * @package WebAppId\DDD\Tools
 */
class Lazy
{
    /**
     * @var int
     */
    public const NONE = 1;

    /**
     * @Var int
     */
    public const VALIDATE_ORIGIN = 2;

    /**
     * @var int
     */
    public const VALIDATE_DEST = 3;

    /**
     * @var int
     */
    public const VALIDATE_BOTH = 4;

    /**
     * @var int
     */
    public const AUTOCAST = 5;


    /**
     * @param object $fromClass
     * @param object $toClass
     * @param int $option
     * @return object
     */
    public static function copy(object $fromClass, object $toClass, int $option = self::NONE): object
    {
        try {
            switch ($option) {
                case self::VALIDATE_ORIGIN:
                    foreach (get_object_vars($fromClass) as $key => $value) {
                        if (self::_validate($fromClass, $fromClass, $key)) {
                            $toClass->$key = $value;
                        }
                    }
                    break;
                case self::VALIDATE_DEST:
                    foreach (get_object_vars($toClass) as $key => $value) {
                        if (property_exists($toClass, $key) && (self::_validate($fromClass, $toClass, $key))) {
                            $toClass->$key = $fromClass->$key;
                        }
                    }
                    break;
                case self::VALIDATE_BOTH:
                    foreach (get_object_vars($fromClass) as $key => $value) {
                        if (property_exists($toClass, $key) && self::_validate($fromClass, $fromClass, $key) && self::_validate($fromClass, $toClass, $key)) {
                            $toClass->$key = $value;
                        }
                    }
                    break;
                case self::AUTOCAST:
                    foreach (get_object_vars($toClass) as $key => $value) {
                        if (property_exists($toClass, $key)) {
                            $propertyClass = self::_getVarValue($toClass, $key);
                            if (isset($fromClass->$key)) {
                                $toClass->$key = self::castValue($propertyClass, $fromClass->$key);
                            }
                        }
                    }
                    break;
                default:
                    foreach (get_object_vars($fromClass) as $key => $value) {
                        $toClass->$key = $value;
                    }
                    break;

            }
        } catch (Exception $exception) {
            report($exception);
        }


        return $toClass;
    }

    /**
     * @param $fromClass
     * @param $toClass
     * @param $key
     * @return bool
     * @throws Exception
     */
    private static function _validate($fromClass, $toClass, $key)
    {
        $propertyClass = self::_getVarValue($toClass, $key);
        if (gettype($fromClass->$key) == $propertyClass) {
            return true;
        } else {
            throw new Exception('Type Mismatch on property ' . $key . '. The property type is ' . $propertyClass . ' but the value type is ' . gettype($fromClass->$key));
        }
    }

    private static function _getVarValue($toClass, $key)
    {
        $propertyClass = "";
        try {
            $property = new ReflectionProperty($toClass, $key);
            $propertyClass = self::getVar($property);
        } catch (ReflectionException $e) {
            report($e);
        }

        return $propertyClass;
    }

    /**
     * @param ReflectionProperty $property
     * @return mixed|null
     */
    private static function getVar(ReflectionProperty $property)
    {
        $typeMapping = [];
        $typeMapping['int'] = 'integer';
        $typeMapping['bool'] = 'boolean';

        // Get the content of the @var annotation
        if (preg_match('/@var\s+([^\s]+)/', $property->getDocComment(), $matches)) {
            if (isset($typeMapping[$matches[1]])) {
                return $typeMapping[$matches[1]];
            } else {
                return $matches[1];
            }
        } else {
            return null;
        }
    }

    private static function castValue($propertyClass, $from)
    {
        switch ($propertyClass) {
            case "integer":
                $value = (int)$from;
                break;
            case "float":
                $value = (float)$from;
                break;
            case "double":
                $value = (double)$from;
                break;
            case "boolean":
                $value = (boolean)$from;
                break;
            case "object":
                $value = (object)$from;
                break;
            case "array":
                $value = (array)$from;
                break;
            case "string":
                $value = (string)$from;
                break;
            default :
                $value = $from;
                break;
        }
        return $value;
    }

    /**
     * @param array $datas
     * @return array
     */
    public static function arrayNullToEmpty(array $datas): array
    {
        $newData = [];
        foreach ($datas as $key => $value) {
            $newData[$key] = $value == null ? "" : $value;
        }
        return $newData;
    }

    /**
     * @param object $fromClass
     * @param object $toClass
     * @param array $mappings
     * @return object
     */
    public static function transform(object $fromClass, object $toClass, array $mappings = []): object
    {
        foreach (get_object_vars($toClass) as $key => $value) {
            $propertyClass = self::_getVarValue($toClass, $key);
            if (isset($fromClass->$key)) {
                $toClass->$key = self::castValue($propertyClass, $fromClass->$key);
            }
        }

        foreach ($mappings as $mapping => $value) {
            if (property_exists($toClass, $mapping)) {
                $propertyClass = self::_getVarValue($toClass, $mapping);
                $toClass->$mapping = self::castValue($propertyClass, $fromClass->$value);
            }
        }

        return $toClass;
    }

    /**
     * @param object $class
     * @return bool
     * @throws Exception
     */
    public static function validate(object $class)
    {
        $status = true;
        foreach (get_object_vars($class) as $key => $value) {
            if ($status) {
                $status = self::_validate($class, $class, $key);
            }
        }

        return $status;
    }

    /**
     * @param string $fromJson
     * @param object $toClass
     * @param int $option
     * @return object
     * @throws Exception
     */
    public static function copyFromJson(string $fromJson, object $toClass, int $option = self::NONE): object
    {
        return self::copyFromArray(json_decode($fromJson, true), $toClass, $option);
    }

    /**
     * @param array $fromArray
     * @param object $toClass
     * @param int $option
     * @return object
     * @throws Exception
     */
    public static function copyFromArray(array $fromArray, object $toClass, int $option = self::NONE): object
    {
        $model = false;
        if (method_exists($toClass, 'getFillable')) {
            $varList = $toClass->getFillable();
            $vars = array_flip($varList);
            $model = true;
        } else {
            $vars = get_object_vars($toClass);
        }
        foreach ($vars as $key => $value) {
            if ($option == self::NONE) {
                if (!$model) {
                    self::_validate((object)$fromArray, $toClass, $key);
                }
                if (isset($fromArray[$key])) {
                    $toClass->$key = $fromArray[$key];
                }
            } elseif ($option == self::AUTOCAST) {
                $propertyClass = self::_getVarValue($toClass, $key);
                if (isset($fromArray[$key])) {
                    $toClass->$key = self::castValue($propertyClass, $fromArray[$key]);
                } else {
                    $toClass->$key = null;
                }
            }

        }
        return $toClass;
    }
}
