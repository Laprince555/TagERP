<?php

namespace Modules\General\Database\Seeders\System\Concerns;

trait EncodesTranslatableAttributes
{
    /**
     * @param  array<int, string>  $attributes
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function encodeTranslatableAttributes(array $payload, array $attributes = ['name', 'description']): array
    {
        foreach ($attributes as $attribute) {
            if (isset($payload[$attribute]) && is_array($payload[$attribute])) {
                $payload[$attribute] = json_encode($payload[$attribute], JSON_UNESCAPED_UNICODE);
            }
        }

        return $payload;
    }
}
