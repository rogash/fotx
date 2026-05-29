<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventPhoto;

class PhotoMetadataCsvImporter
{
    /**
     * @return array{total_rows: int, imported_rows: int, skipped_rows: int}
     */
    public function import(Event $event, string $csv_path): array
    {
        $rows = $this->read_rows($csv_path);

        if ($rows === []) {
            return ['total_rows' => 0, 'imported_rows' => 0, 'skipped_rows' => 0];
        }

        $headers = array_map(fn (string $header): string => $this->normalize_header($header), array_shift($rows));
        $filename_index = $this->first_index($headers, ['filename', 'arquivo', 'foto', 'nome_arquivo', 'file', 'image']);
        $participant_code_index = $this->first_index($headers, ['participant_code', 'numero', 'numero_peito', 'number', 'codigo', 'code', 'dorsal']);
        $keyword_indexes = $this->keyword_indexes($headers);

        if ($filename_index === null) {
            return ['total_rows' => count($rows), 'imported_rows' => 0, 'skipped_rows' => count($rows)];
        }

        $photos_by_filename = $event->photos()
            ->get()
            ->mapWithKeys(fn (EventPhoto $event_photo): array => [
                $this->normalize_filename($event_photo->filename) => $event_photo,
            ]);

        $imported_rows = 0;
        $skipped_rows = 0;

        foreach ($rows as $row) {
            $filename = trim((string) ($row[$filename_index] ?? ''));
            $event_photo = $photos_by_filename[$this->normalize_filename($filename)] ?? null;

            if (! $event_photo) {
                $skipped_rows++;
                continue;
            }

            $participant_code = $participant_code_index === null ? null : trim((string) ($row[$participant_code_index] ?? ''));
            $search_keywords = $this->search_keywords_from_row($row, $keyword_indexes);

            $event_photo->update([
                'participant_code' => blank($participant_code) ? null : $participant_code,
                'search_keywords' => blank($search_keywords) ? null : $search_keywords,
            ]);

            $imported_rows++;
        }

        return [
            'total_rows' => count($rows),
            'imported_rows' => $imported_rows,
            'skipped_rows' => $skipped_rows,
        ];
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function read_rows(string $csv_path): array
    {
        $lines = file($csv_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false || $lines === []) {
            return [];
        }

        $delimiter = $this->detect_delimiter($lines[0]);

        return collect($lines)
            ->map(fn (string $line): array => str_getcsv($line, $delimiter))
            ->filter(fn (array $row): bool => collect($row)->filter(fn ($value): bool => filled($value))->isNotEmpty())
            ->values()
            ->all();
    }

    private function detect_delimiter(string $header_line): string
    {
        return collect([',', ';', "\t"])
            ->mapWithKeys(fn (string $delimiter): array => [$delimiter => count(str_getcsv($header_line, $delimiter))])
            ->sortDesc()
            ->keys()
            ->first() ?? ',';
    }

    private function first_index(array $headers, array $candidates): ?int
    {
        foreach ($candidates as $candidate) {
            $index = array_search($candidate, $headers, true);

            if ($index !== false) {
                return (int) $index;
            }
        }

        return null;
    }

    /**
     * @return array<int, int>
     */
    private function keyword_indexes(array $headers): array
    {
        $keyword_headers = ['search_keywords', 'tags', 'tag', 'nome', 'name', 'equipe', 'team', 'assessoria', 'turma', 'categoria', 'category'];

        return collect($headers)
            ->filter(fn (string $header): bool => in_array($header, $keyword_headers, true))
            ->keys()
            ->values()
            ->all();
    }

    private function search_keywords_from_row(array $row, array $keyword_indexes): ?string
    {
        $keywords = collect($keyword_indexes)
            ->map(fn (int $index): string => trim((string) ($row[$index] ?? '')))
            ->filter()
            ->unique()
            ->implode(' ');

        return blank($keywords) ? null : $keywords;
    }

    private function normalize_header(string $header): string
    {
        $normalized = mb_strtolower(trim($header));
        $normalized = strtr($normalized, [
            'á' => 'a',
            'à' => 'a',
            'ã' => 'a',
            'â' => 'a',
            'é' => 'e',
            'ê' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'õ' => 'o',
            'ô' => 'o',
            'ú' => 'u',
            'ç' => 'c',
        ]);

        return preg_replace('/[^a-z0-9]+/', '_', $normalized) ?: '';
    }

    private function normalize_filename(string $filename): string
    {
        return mb_strtolower(basename(trim($filename)));
    }
}
