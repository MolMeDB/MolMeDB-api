<?php
namespace App\DTO\Stats;

class BarChart
{
    function __construct(
        /** @var BarChartItem[] */
        public array $data
    ){}

    public static function from(array $data) : self 
    {
        $d = new self(
            array_map(fn ($item) => BarChartItem::from($item), $data)
        );
        return $d;
    }

    public function toArray() : array 
    {
        return array_map(function(BarChartItem | array $item) {
            return $item->toArray();
        }, $this->data);
    }

    public function addItem(string $name, int $value1, ?int $value2) : void 
    {
        $this->data[] = new BarChartItem($name, $value1, $value2);
    }

    public static function makeItem(string $name, int $value1, ?int $value2 = null) : BarChartItem
    {
        return new BarChartItem($name, $value1, $value2);
    }
}

class BarChartItem 
{
    function __construct(
        public string $name,
        public int $value1,
        public ?int $value2
    ) {}

    public function toArray() : array 
    {
        return [
            'name' => $this->name,
            'value1' => $this->value1,
            'value2' => $this->value2
        ];
    }

    public static function from(array | BarChartItem $data) : self 
    {
        return is_array($data) ? new self(
            $data['name'],
            $data['value1'],
            $data['value2'] ?? null
        ) : $data;
    }
}