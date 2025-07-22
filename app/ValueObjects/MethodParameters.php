<?php
namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class MethodParameters implements Arrayable, JsonSerializable
{
    public $alert_limits = [
        'logperm' => [
            'max' => null,
            'min' => null,
        ]
    ];

    public function __construct(private array $parameters) 
    {
        if (isset($this->parameters['alert_limits'])) {
            $this->alert_limits = $this->parameters['alert_limits'];
            unset($this->parameters['alert_limits']);
        }
    }

    public function jsonSerialize() : array
    {
        return $this->toArray();
    }
    
    public function toArray() : array
    {
        return [
            'alert_limits' => $this->alert_limits,
        ];
    }
}