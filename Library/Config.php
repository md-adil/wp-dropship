<?php

namespace Bigly\Dropship\Library;

class Config
{
    protected $configs;

    public function __construct($configs)
    {
        $this->configs = $configs;
    }

    public function get($key, $default = null)
    {
        $array = $this->configs;
        if (is_null($key)) {
            return $array;
        }

        if ($this->exists($array, $key)) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if ($this->accessible($array) && $this->exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }
        return $array;
    }

    public function set($key, $value = null)
    {
        $array = $this->configs;
        if (is_null($key)) {
            return $array = $value;
        }
        if (is_array($key)) {
            $this->configs = array_merge($this->configs, $key);
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

        $this->configs = $array;
    }
    public function accessible($value)
    {
        return true;
    }

    public function exists($array, $key)
    {
        return array_key_exists($key, $array);
    }
}
