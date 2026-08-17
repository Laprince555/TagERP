<?php

namespace App\Support\Import;

use Generator;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;

/**
 * Streams the first sheet of a .csv or .xlsx file as associative rows keyed by
 * its header line. A generator, never an array — an import file is only ever
 * read once, and a 50k-row sheet must not be held in memory to be staged.
 */
class SpreadsheetReader
{
    /**
     * @return Generator<int, array<string, mixed>> row number (1-based, header excluded) => cells
     */
    public function rows(string $absolutePath): Generator
    {
        $reader = $this->readerFor($absolutePath);
        $reader->open($absolutePath);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $headers = null;
                $number = 0;

                foreach ($sheet->getRowIterator() as $row) {
                    $cells = $row->toArray();

                    if ($headers === null) {
                        $headers = array_map(
                            fn (mixed $cell): string => strtolower(trim((string) $cell)),
                            $cells,
                        );

                        continue;
                    }

                    if ($this->isBlank($cells)) {
                        continue;
                    }

                    yield ++$number => $this->combine($headers, $cells);
                }

                // Only the first sheet is ever imported — a template has one.
                break;
            }
        } finally {
            $reader->close();
        }
    }

    protected function readerFor(string $path): ReaderInterface
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'csv', 'txt' => new CsvReader,
            'xlsx' => new XlsxReader,
            default => throw new RuntimeException(__('Only .csv and .xlsx files can be imported.')),
        };
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, mixed>  $cells
     * @return array<string, mixed>
     */
    protected function combine(array $headers, array $cells): array
    {
        $combined = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $cell = $cells[$index] ?? null;

            // Staged rows are stored as JSON, so a date cell has to leave the
            // reader as a plain string rather than a DateTime object.
            $combined[$header] = $cell instanceof \DateTimeInterface
                ? $cell->format('Y-m-d')
                : $cell;
        }

        return $combined;
    }

    /** @param  array<int, mixed>  $cells */
    protected function isBlank(array $cells): bool
    {
        foreach ($cells as $cell) {
            if ($cell instanceof \DateTimeInterface || trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
