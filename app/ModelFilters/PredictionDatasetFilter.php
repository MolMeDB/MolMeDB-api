<?php 

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class PredictionDatasetFilter extends ModelFilter
{
    public $sortBy = "id";

    public function sortBy($column)
    {
        $this->sortBy = $column;
        return $this;
    }

    public function sortByDirection($direction)
    {
        if(strtolower($direction) !== 'asc')
        {
            return $this->orderBy($this->sortBy, 'desc');
        }
        return $this->orderBy($this->sortBy, 'asc');
    }

    public function query($comment)
    {
        if(!$comment)
        {
            return $this;
        }

        $comment = strtolower($comment);

        if(str_starts_with($comment, 'id:'))
        {
            return $this->where('id', str_replace('id:', '', $comment));
        }

        return $this->whereRaw('LOWER(comment) LIKE ?', ['%' . $comment. '%'])
            ->distinct();
    }

    public function setup()
    {
    }
}
