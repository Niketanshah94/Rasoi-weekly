<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\File;

trait ReadsSeedData
{
    /**
     * Decode a file from data/, failing loudly on bad JSON.
     *
     * @return array<mixed>
     */
    protected function seedData(string $file): array
    {
        return json_decode(File::get(base_path("data/{$file}")), true, flags: JSON_THROW_ON_ERROR);
    }
}
