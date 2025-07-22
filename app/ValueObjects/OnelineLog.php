<?php
namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class OnelineLog implements Arrayable, JsonSerializable
{
    public function __construct(public array $logs) {}

    public function jsonSerialize() : array
    {
        return $this->toArray();
    }
    
    public function toArray() : array
    {
        return $this->logs;
    }
}