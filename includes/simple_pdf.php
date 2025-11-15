<?php
declare(strict_types=1);

/**
 * Generates a basic PDF document with table-like rows.
 *
 * @param string $title
 * @param array<int, string> $headers
 * @param array<int, array<string, mixed>> $rows
 */
function generate_simple_pdf(string $title, array $headers, array $rows): string
{
    $title = $title === '' ? 'Report' : $title;
    $lineHeight = 18;
    $cursorY = 760;
    $marginLeft = 40;

    $contentLines = [];
    $contentLines[] = "BT";
    $contentLines[] = "/F1 18 Tf";
    $contentLines[] = sprintf("1 0 0 1 %d %d Tm", $marginLeft, $cursorY);
    $contentLines[] = '(' . pdf_escape_text($title) . ') Tj';

    $cursorY -= ($lineHeight + 6);
    $contentLines[] = "/F1 12 Tf";
    $contentLines[] = sprintf("1 0 0 1 %d %d Tm", $marginLeft, $cursorY);
    $contentLines[] = '(' . pdf_escape_text(implode(' | ', array_map('pdf_truncate', $headers))) . ') Tj';

    foreach ($rows as $row) {
        $cursorY -= $lineHeight;
        if ($cursorY < 60) {
            break; // Avoid overflowing single-page layout.
        }
        $contentLines[] = sprintf("1 0 0 1 %d %d Tm", $marginLeft, $cursorY);
        $values = [];
        foreach ($headers as $column) {
            $values[] = pdf_truncate(isset($row[$column]) ? stringify_pdf_value($row[$column]) : '');
        }
        $contentLines[] = '(' . pdf_escape_text(implode(' | ', $values)) . ') Tj';
    }

    $contentLines[] = "ET";

    $contentStream = implode("\n", $contentLines) . "\n";
    $length = strlen($contentStream);

    $objects = [
        "1 0 obj <</Type /Catalog /Pages 2 0 R>> endobj\n",
        "2 0 obj <</Type /Pages /Kids [3 0 R] /Count 1>> endobj\n",
        "3 0 obj <</Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources <</Font <</F1 5 0 R>>>>>> endobj\n",
        sprintf("4 0 obj <</Length %d>> stream\n%sendstream\nendobj\n", $length, $contentStream),
        "5 0 obj <</Type /Font /Subtype /Type1 /BaseFont /Helvetica>> endobj\n",
    ];

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $object) {
        $offsets[] = strlen($pdf);
        $pdf .= $object;
    }

    $xrefOffset = strlen($pdf);
    $pdf .= sprintf("xref\n0 %d\n", count($objects) + 1);
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer <</Size " . (count($objects) + 1) . " /Root 1 0 R>>\n";
    $pdf .= "startxref\n{$xrefOffset}\n%%EOF\n";

    return $pdf;
}

function pdf_escape_text(string $text): string
{
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function pdf_truncate(string $value, int $length = 40): string
{
    $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';
    $measure = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    if ($measure <= $length) {
        return $value;
    }
    $substr = function_exists('mb_substr') ? mb_substr($value, 0, $length - 3) : substr($value, 0, $length - 3);
    return $substr . '...';
}

function stringify_pdf_value(mixed $value): string
{
    if (is_scalar($value) || $value === null) {
        return (string)$value;
    }
    return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
}
