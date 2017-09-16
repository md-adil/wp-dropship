<?php
namespace Bigly\Dropship;

class Config
{
    protected static $configs = [];

    public static function get($key, $default = null)
    {
        $array = self::$configs;
        if (is_null($key)) {
            return $array;
        }

        if (static::exists($array, $key)) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }
        return $array;
    }

    public static function set($key, $value = null)
    {
        $array = self::$configs;
        if (is_null($key)) {
            return $array = $value;
        }
        if (is_array($key)) {
            return self::$configs = array_merge(self::$configs, $key);
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);
            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        self::$configs = $array;
    }
    public static function accessible($value)
    {
        return true;
    }

    public static function exists($array, $key)
    {
        return array_key_exists($key, $array);
    }
}
