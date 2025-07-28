<?php
namespace App\DTO\Stats;

class LineChart
{
    function __construct(
        /** @var LineChartItem[] */
        public array $data
    ){}

    public static function from(array $data) : self 
    {
        $d = new self(
            array_map(fn ($item) => LineChartItem::from($item), $data)
        );
        return $d;
    }

    public function toArray() : array 
    {
        return array_map(function(LineChartItem | array $item) {
            return $item->toArray();
        }, $this->data);
    }

    public function addItem(int | string $date, int $value1, ?int $value2) : void 
    {
        $this->data[] = new LineChartItem($date, $value1, $value2);
    }

    public static function makeItem(int | string $date, int $value1, ?int $value2 = null) : LineChartItem
    {
        return new LineChartItem($date, $value1, $value2);
    }
}

class LineChartItem 
{
    function __construct(
        public int | string $date,
        public int $value1,
        public ?int $value2
    ) {}

    public function toArray() : array 
    {
        return [
            'date' => $this->date,
            'value1' => $this->value1,
            'value2' => $this->value2
        ];
    }

    public static function from(array | LineChartItem $data) : self 
    {
        return is_array($data) ? new self(
            $data['date'],
            $data['value1'],
            $data['value2'] ?? null
        ) : $data;
    }
}