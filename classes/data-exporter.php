<?php

final class DataExporter
{
    public static function isSupported(string $format): bool
    {
        return in_array(strtolower($format), ['csv', 'pdf'], true);
    }

    /** @return resource */
    public static function open(string $format, string $fileName)
    {
        $format = strtolower($format);
        if (!self::isSupported($format)) {
            throw new RuntimeException('Unsupported export format.');
        }

        $stream = fopen('php://temp/maxmemory:5242880', 'w+b');

        if (!is_resource($stream)) {
            throw new RuntimeException('Could not create the export file.');
        }

        return $stream;
    }

    /** @param resource $stream */
    public static function finish($stream, string $format, string $fileName, string $title, array $metadata = []): void
    {
        $format = strtolower($format);
        if ($format === 'csv') {
            rewind($stream);
            self::clearOutputBuffer();
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . self::fileName($fileName, 'csv') . '"');
            $output = fopen('php://output', 'wb');
            if (!is_resource($output)) {
                fclose($stream);
                throw new RuntimeException('Could not create the export file.');
            }

            fputcsv($output, ['Report', $title], ',', '"', '\\');
            foreach ($metadata as $label => $value) {
                $value = trim((string) $value);
                if ($value !== '') {
                    fputcsv($output, [(string) $label, $value], ',', '"', '\\');
                }
            }
            fputcsv($output, [], ',', '"', '\\');
            stream_copy_to_stream($stream, $output);
            fclose($output);
            fclose($stream);
            return;
        }

        rewind($stream);
        $rows = [];
        while (($row = fgetcsv($stream, null, ',', '"', '\\')) !== false) {
            $rows[] = array_map(static fn($value): string => (string) $value, $row);
        }
        fclose($stream);

        $headers = array_shift($rows) ?: ['Records'];
        $pdf = self::buildPdf($title, $headers, $rows, $metadata);

        self::clearOutputBuffer();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . self::fileName($fileName, 'pdf') . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
    }

    private static function fileName(string $fileName, string $extension): string
    {
        $base = preg_replace('/\.(csv|pdf)$/i', '', basename($fileName)) ?: 'pixelwar-export';
        $base = preg_replace('/[^a-z0-9._-]+/i', '-', $base) ?: 'pixelwar-export';
        return trim($base, '-.') . '.' . $extension;
    }

    private static function clearOutputBuffer(): void
    {
        if (ob_get_level() > 0) {
            ob_clean();
        }
    }

    /** @param string[] $headers @param array<int, string[]> $rows */
    private static function buildPdf(string $title, array $headers, array $rows, array $metadata): string
    {
        $pageWidth = 842.0;
        $pageHeight = 595.0;
        $margin = 30.0;
        $tableWidth = $pageWidth - ($margin * 2);
        $columnCount = max(1, count($headers));
        $columnWidth = $tableWidth / $columnCount;
        $fontSize = $columnCount >= 8 ? 6.2 : ($columnCount >= 6 ? 7.0 : 8.0);
        $lineHeight = $fontSize + 2.5;
        $headerHeight = 24.0;
        $pages = [];
        $currentRows = [];
        $availableHeight = $pageHeight - 125.0;
        $usedHeight = $headerHeight;

        if ($rows === []) {
            $rows[] = array_pad(['No records found.'], $columnCount, '');
        }

        foreach ($rows as $row) {
            $normalized = array_slice(array_pad($row, $columnCount, ''), 0, $columnCount);
            $wrapped = [];
            $maxLines = 1;
            foreach ($normalized as $value) {
                $cellLines = self::wrapText((string) $value, $columnWidth - 8.0, $fontSize);
                $wrapped[] = $cellLines;
                $maxLines = max($maxLines, count($cellLines));
            }
            $rowHeight = max(20.0, ($maxLines * $lineHeight) + 8.0);

            if ($currentRows !== [] && $usedHeight + $rowHeight > $availableHeight) {
                $pages[] = $currentRows;
                $currentRows = [];
                $usedHeight = $headerHeight;
            }

            $currentRows[] = ['cells' => $wrapped, 'height' => $rowHeight];
            $usedHeight += $rowHeight;
        }
        $pages[] = $currentRows;

        $objects = [];
        $pageReferences = [];
        $pageCount = count($pages);
        for ($index = 0; $index < $pageCount; $index++) {
            $pageReferences[] = (4 + ($index * 2)) . ' 0 R';
        }

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageReferences) . '] /Count ' . $pageCount . ' >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        foreach ($pages as $index => $pageRows) {
            $pageNumber = $index + 1;
            $pageObjectId = 4 + ($index * 2);
            $contentObjectId = $pageObjectId + 1;
            $content = self::pageContent(
                $title,
                $metadata,
                $headers,
                $pageRows,
                $pageNumber,
                $pageCount,
                $pageWidth,
                $pageHeight,
                $margin,
                $columnWidth,
                $fontSize,
                $lineHeight,
                $headerHeight
            );
            $objects[$pageObjectId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.0F %.0F] /Resources << /Font << /F1 3 0 R >> >> /Contents %d 0 R >>',
                $pageWidth,
                $pageHeight,
                $contentObjectId
            );
            $objects[$contentObjectId] = '<< /Length ' . strlen($content) . ">>\nstream\n" . $content . "\nendstream";
        }

        ksort($objects);
        $pdf = "%PDF-1.4\n%Pixelwar\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $objectCount = count($objects) + 1;
        $pdf .= "xref\n0 " . $objectCount . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($id = 1; $id < $objectCount; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id] ?? 0);
        }
        $pdf .= "trailer\n<< /Size " . $objectCount . " /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    /** @param string[] $headers @param array<int, array{cells: array<int, string[]>, height: float}> $rows */
    private static function pageContent(
        string $title,
        array $metadata,
        array $headers,
        array $rows,
        int $pageNumber,
        int $pageCount,
        float $pageWidth,
        float $pageHeight,
        float $margin,
        float $columnWidth,
        float $fontSize,
        float $lineHeight,
        float $headerHeight
    ): string {
        $metadataParts = [];
        foreach ($metadata as $label => $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                $metadataParts[] = trim((string) $label) . ': ' . $value;
            }
        }
        $metadataText = $metadataParts !== [] ? implode('  |  ', $metadataParts) : 'Pixelwar data export';

        $commands = [
            '0.15 0.10 0.06 rg',
            self::textCommand($margin, $pageHeight - 34, 16, $title),
            '0.38 0.32 0.27 rg',
            self::textCommand($margin, $pageHeight - 51, 8.2, self::shorten($metadataText, $pageWidth - ($margin * 2), 8.2)),
            self::textCommand($margin, $pageHeight - 66, 7.5, 'Generated ' . date('M j, Y g:i A')),
            self::textCommand($pageWidth - 92, $pageHeight - 66, 7.5, 'Page ' . $pageNumber . ' of ' . $pageCount),
        ];

        $top = $pageHeight - 84;
        $tableWidth = $columnWidth * count($headers);
        $commands[] = '1.00 0.82 0.40 rg';
        $commands[] = sprintf('%.2F %.2F %.2F %.2F re f', $margin, $top - $headerHeight, $tableWidth, $headerHeight);
        $commands[] = '0.15 0.10 0.06 RG 0.7 w';
        $commands[] = sprintf('%.2F %.2F %.2F %.2F re S', $margin, $top - $headerHeight, $tableWidth, $headerHeight);

        foreach ($headers as $index => $header) {
            $x = $margin + ($index * $columnWidth);
            if ($index > 0) {
                $commands[] = sprintf('%.2F %.2F m %.2F %.2F l S', $x, $top, $x, $top - $headerHeight);
            }
            $commands[] = '0.15 0.10 0.06 rg';
            $commands[] = self::textCommand($x + 4, $top - 15, min(7.2, $fontSize), strtoupper(self::shorten($header, $columnWidth, min(7.2, $fontSize))));
        }

        $y = $top - $headerHeight;
        foreach ($rows as $rowIndex => $row) {
            $rowHeight = $row['height'];
            $commands[] = $rowIndex % 2 === 0 ? '1 1 1 rg' : '0.97 0.95 0.91 rg';
            $commands[] = sprintf('%.2F %.2F %.2F %.2F re f', $margin, $y - $rowHeight, $tableWidth, $rowHeight);
            $commands[] = '0.72 0.68 0.62 RG 0.35 w';
            $commands[] = sprintf('%.2F %.2F %.2F %.2F re S', $margin, $y - $rowHeight, $tableWidth, $rowHeight);

            foreach ($row['cells'] as $cellIndex => $lines) {
                $x = $margin + ($cellIndex * $columnWidth);
                if ($cellIndex > 0) {
                    $commands[] = sprintf('%.2F %.2F m %.2F %.2F l S', $x, $y, $x, $y - $rowHeight);
                }
                $commands[] = '0.15 0.10 0.06 rg';
                foreach ($lines as $lineIndex => $line) {
                    $commands[] = self::textCommand($x + 4, $y - 12 - ($lineIndex * $lineHeight), $fontSize, $line);
                }
            }
            $y -= $rowHeight;
        }

        return implode("\n", $commands);
    }

    /** @return string[] */
    private static function wrapText(string $value, float $width, float $fontSize): array
    {
        $value = preg_replace('/\s+/u', ' ', trim(self::pdfText($value))) ?? '';
        if ($value === '') {
            return [''];
        }

        $maxCharacters = max(5, (int) floor($width / max(3.2, $fontSize * 0.52)));
        $wrapped = wordwrap($value, $maxCharacters, "\n", true);
        return array_slice(explode("\n", $wrapped), 0, 5);
    }

    private static function shorten(string $value, float $width, float $fontSize): string
    {
        $value = self::pdfText($value);
        $maxCharacters = max(4, (int) floor(($width - 8) / max(3.2, $fontSize * 0.52)));
        return strlen($value) > $maxCharacters ? substr($value, 0, max(1, $maxCharacters - 1)) . '.' : $value;
    }

    private static function textCommand(float $x, float $y, float $size, string $text): string
    {
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], self::pdfText($text));
        return sprintf('BT /F1 %.2F Tf %.2F %.2F Td (%s) Tj ET', $size, $x, $y, $escaped);
    }

    private static function pdfText(string $value): string
    {
        $value = str_replace(["\r", "\n", "\t"], ' ', $value);
        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);
            if ($converted !== false) {
                return $converted;
            }
        }
        return preg_replace('/[^\x20-\x7E]/', '?', $value) ?? '';
    }
}
