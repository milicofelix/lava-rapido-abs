<?php

namespace App\Support\Pdf;

class SimplePdfDocument
{
    private const WIDTH = 595;

    private const HEIGHT = 842;

    /**
     * @var array<int, string>
     */
    private array $pages = [];

    public function addPage(): void
    {
        $this->pages[] = '';
    }

    public function text(float $x, float $y, string $text, int $size = 10): void
    {
        if ($this->pages === []) {
            $this->addPage();
        }

        $content = sprintf(
            "BT /F1 %d Tf %.2F %.2F Td (%s) Tj ET\n",
            $size,
            $x,
            $y,
            $this->escape($text)
        );

        $this->pages[array_key_last($this->pages)] .= $content;
    }

    public function render(): string
    {
        if ($this->pages === []) {
            $this->addPage();
        }

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pageObjectIds = [];
        $nextObjectId = 4;

        foreach ($this->pages as $content) {
            $contentObjectId = $nextObjectId++;
            $pageObjectId = $nextObjectId++;
            $pageObjectIds[] = $pageObjectId;

            $objects[$contentObjectId] = '<< /Length '.strlen($content)." >>\nstream\n".$content.'endstream';
            $objects[$pageObjectId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %d %d] /Resources << /Font << /F1 3 0 R >> >> /Contents %d 0 R >>',
                self::WIDTH,
                self::HEIGHT,
                $contentObjectId
            );
        }

        $objects[2] = sprintf(
            '<< /Type /Pages /Kids [%s] /Count %d >>',
            implode(' ', array_map(fn (int $id) => $id.' 0 R', $pageObjectIds)),
            count($pageObjectIds)
        );

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$body."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        foreach (array_keys($objects) as $id) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n".$xrefOffset."\n%%EOF";

        return $pdf;
    }

    private function escape(string $text): string
    {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);

        return str_replace(
            ['\\', '(', ')', "\r", "\n"],
            ['\\\\', '\(', '\)', ' ', ' '],
            $encoded === false ? $text : $encoded
        );
    }
}
