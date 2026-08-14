<?php

namespace App\Support;

class FallbackValue
{
    /**
     * @param  array<int, string>  $keys
     */
    public static function get(mixed $target, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            $value = self::path($target, $key);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    }

    public static function path(mixed $target, string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $target;

        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];

                continue;
            }

            if (is_object($value) && isset($value->{$segment})) {
                $value = $value->{$segment};

                continue;
            }

            return $default;
        }

        return $value;
    }
}
