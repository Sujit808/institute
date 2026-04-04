<?php

namespace App\Exports;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ModuleExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected array $headings,
        protected array|Arrayable|Collection $rows,
    ) {}

    public function collection(): Collection
    {
        return collect($this->rows instanceof Collection ? $this->rows : (array) $this->rows);
    }

    public function headings(): array
    {
        return $this->headings;
    }
}
