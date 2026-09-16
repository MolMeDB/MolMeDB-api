<?php

namespace App\Libraries\Export;

class ExportFileColumn
{
    public function __construct(
        public string $key,
        public string $label
    ) {}

    public function getValue(array|object $data)
    {
        return data_get($data, $this->key);
    }

    public static function make(string $key, ?string $label = null)
    {
        return new self($key, $label ?? ucfirst(preg_replace('/^.*\./', '', $key)));
    }
}
