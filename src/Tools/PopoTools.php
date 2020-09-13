<?php

/**
 * Author: galih
 * Date: 2019-05-19
 * Time: 22:49
 */

namespace WebAppId\Lazy\Tools;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @author: Dyan Galih<dyan.galih@gmail.com>
 * Date: 18/04/20
 * Time: 18.56
 * Class PopoTools
 * @package WebAppId\DDD\Tools
 */
class PopoTools
{

    /**
     * @param mixed $object
     * @return string|false
     */
    public function serializeJson($object)
    {
        return json_encode($this->serialize($object));
    }

    private function fixKey($key): string
    {
        if (stripos($key, "\0") === 0) {
            $newKey = $this->fixKeyName($key);
        } else {
            $newKey = $key;
        }
        return $newKey;
    }

    /**
     * @param $object
     * @return array
     */
    public function serialize($object)
    {
        $objectAsArray = (array)$object;

        foreach ($objectAsArray as $key => $value) {

            $newKey = $this->fixKey($key);
            if ($newKey != $key) {
                $this->replaceKey($objectAsArray, $key, $newKey);
            }
            if ($value instanceof Collection) {
                $value = $this->serialize($objectAsArray[$newKey]);
                $objectAsArray[$newKey] = $value['items'];
            } elseif (is_object($value)) {
                $objectAsArray[$newKey] = $this->serialize($objectAsArray[$newKey]);
            } elseif (is_array($value)) {
                if (isset($value[0])) {
                    for ($i = 0; $i < count($value); $i++) {
                        if ($value[$i] instanceof Model) {
                            //nothing todo
                        } else {
                            if (is_object($value[$i])) {
                                $value[$i] = $this->serialize($value[$i]);
                            }
                        }
                    }
                    $objectAsArray[$newKey] = $value;
                }
            }
        }

        return $objectAsArray;
    }

    /**
     * @param $array
     * @param $curkey
     * @param $newkey
     * @return bool
     */
    function replaceKey(&$array, $curkey, $newkey)
    {
        if (array_key_exists($curkey, $array)) {
            $array[$newkey] = $array[$curkey];
            unset($array[$curkey]);
            return true;
        }

        return false;
    }

    /**
     * @param string $oldKey
     * @return string
     */
    function fixKeyName(string $oldKey): string
    {
        return substr($oldKey, strpos($oldKey, "\0", 2) + 1);
    }
}
