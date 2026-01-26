<?php

declare(strict_types=1);

namespace App\Imports\Catalog;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithTitle;

final class PricebookSheetImport implements ToArray, WithTitle
{
    /**
     * @param array<string, array> $store
     */
    public function __construct(
        private array &$store,
        private readonly string $title,
        private readonly string $storeKey,
        private readonly bool $overwrite = false,
    )
    {
    }

    public function title(): string
    {
        return $this->title;
    }

    public function array(array $array): void
    {
        if (!$this->overwrite && array_key_exists($this->storeKey, $this->store)) {
            return;
        }
        $this->store[$this->storeKey] = $array;
    }
}
