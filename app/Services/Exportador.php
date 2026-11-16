<?php

namespace App\Services;

use FPDF;
use Shuchkin\SimpleXLSXGen;

/** Exportación de reportes a PDF (FPDF) y Excel (SimpleXLSXGen). */
final class Exportador
{
    private const VERDE = [31, 122, 77];

    /** Devuelve el contenido binario del PDF del reporte. */
    public static function pdf(array $reporte, string $autor = 'VoluntaRed'): string
    {
        $pdf = new class ('L', 'mm', 'A4') extends FPDF {
            public string $tituloReporte = '';
            public string $subtitulo = '';

            public function Header(): void
            {
                $this->SetFillColor(31, 122, 77);
                $this->Rect(0, 0, $this->GetPageWidth(), 4, 'F');
                $this->SetY(9);
                $this->SetFont('Helvetica', 'B', 15);
                $this->SetTextColor(31, 122, 77);
                $this->Cell(0, 7, Exportador::txt('VoluntaRed'), 0, 0, 'L');
                $this->SetFont('Helvetica', '', 9);
                $this->SetTextColor(100, 100, 100);
                $this->Cell(0, 7, Exportador::txt($this->subtitulo), 0, 1, 'R');
                $this->SetFont('Helvetica', 'B', 12);
                $this->SetTextColor(30, 30, 30);
                $this->Cell(0, 7, Exportador::txt($this->tituloReporte), 0, 1, 'L');
                $this->Ln(2);
            }

            public function Footer(): void
            {
                $this->SetY(-12);
                $this->SetFont('Helvetica', '', 8);
                $this->SetTextColor(120, 120, 120);
                $this->Cell(0, 6, Exportador::txt('Plataforma VoluntaRed · Universidad de Córdoba'), 0, 0, 'L');
                $this->Cell(0, 6, Exportador::txt('Página ') . $this->PageNo() . ' de {nb}', 0, 0, 'R');
            }

            /** Número de líneas que ocupa un texto en una celda de ancho $w (patrón clásico de FPDF). */
            public function lineas(float $w, string $txt): int
            {
                $cw = $this->CurrentFont['cw'];
                $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
                $s = str_replace("\r", '', $txt);
                $nb = strlen($s);
                if ($nb > 0 && $s[$nb - 1] === "\n") {
                    $nb--;
                }
                $sep = -1;
                $i = $j = $l = 0;
                $nl = 1;
                while ($i < $nb) {
                    $c = $s[$i];
                    if ($c === "\n") {
                        $i++;
                        $sep = -1;
                        $j = $i;
                        $l = 0;
                        $nl++;
                        continue;
                    }
                    if ($c === ' ') {
                        $sep = $i;
                    }
                    $l += $cw[$c] ?? 500;
                    if ($l > $wmax) {
                        if ($sep === -1) {
                            if ($i === $j) {
                                $i++;
                            }
                        } else {
                            $i = $sep + 1;
                        }
                        $sep = -1;
                        $j = $i;
                        $l = 0;
                        $nl++;
                    } else {
                        $i++;
                    }
                }
                return $nl;
            }
        };

        $pdf->tituloReporte = $reporte['titulo'];
        $pdf->subtitulo = 'Generado el ' . fecha($reporte['generado'], true) . ' por ' . $autor;
        $pdf->AliasNbPages();
        $pdf->SetMargins(12, 10, 12);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->SetTitle('VoluntaRed - ' . $reporte['titulo'], true);
        $pdf->SetAuthor($autor, true);
        $pdf->AddPage();

        if ($reporte['filtros']) {
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor(80, 80, 80);
            $pdf->MultiCell(0, 5, self::txt('Filtros: ' . implode(' · ', $reporte['filtros'])));
            $pdf->Ln(1);
        }

        // Resumen en "tarjetas"
        $pdf->SetFont('Helvetica', '', 8);
        $anchoTarjeta = min(45, (273 - 3 * (count($reporte['resumen']) - 1)) / max(1, count($reporte['resumen'])));
        $y = $pdf->GetY();
        $x = 12;
        foreach ($reporte['resumen'] as $etiqueta => $valor) {
            $pdf->SetFillColor(234, 244, 238);
            $pdf->Rect($x, $y, $anchoTarjeta, 13, 'F');
            $pdf->SetXY($x + 2, $y + 1.5);
            $pdf->SetFont('Helvetica', '', 7.5);
            $pdf->SetTextColor(90, 90, 90);
            $pdf->Cell($anchoTarjeta - 4, 4, self::txt($etiqueta), 0, 2);
            $pdf->SetFont('Helvetica', 'B', 11);
            $pdf->SetTextColor(31, 122, 77);
            $pdf->Cell($anchoTarjeta - 4, 6, self::txt(is_float($valor) ? number_format($valor, 1, ',', '.') : (string) $valor), 0, 0);
            $x += $anchoTarjeta + 3;
        }
        $pdf->SetY($y + 17);

        // Tabla
        $anchos = self::ajustarAnchos($reporte['anchos'], 273);
        $encabezado = function () use ($pdf, $reporte, $anchos) {
            $pdf->SetFont('Helvetica', 'B', 8.5);
            $pdf->SetFillColor(...self::VERDE);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetDrawColor(200, 200, 200);
            foreach ($reporte['columnas'] as $i => $col) {
                $pdf->Cell($anchos[$i], 7, self::txt($col), 1, 0, 'L', true);
            }
            $pdf->Ln();
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetTextColor(40, 40, 40);
        };
        $encabezado();

        if (!$reporte['filas']) {
            $pdf->Cell(array_sum($anchos), 10, self::txt('No hay datos para los filtros seleccionados.'), 1, 1, 'C');
        }
        foreach ($reporte['filas'] as $n => $fila) {
            $celdas = array_map(static fn ($v) => self::txt(is_float($v) ? number_format($v, 1, ',', '.') : (string) $v), $fila);
            $lineas = 1;
            foreach ($celdas as $i => $c) {
                $lineas = max($lineas, $pdf->lineas($anchos[$i], $c));
            }
            $alto = 4.5 * $lineas + 1.5;
            if ($pdf->GetY() + $alto > $pdf->GetPageHeight() - 15) {
                $pdf->AddPage();
                $encabezado();
            }
            $y = $pdf->GetY();
            $x = $pdf->GetX();
            $pdf->SetFillColor(...($n % 2 ? [247, 250, 248] : [255, 255, 255]));
            foreach ($celdas as $i => $c) {
                $pdf->Rect($x, $y, $anchos[$i], $alto, 'DF');
                $pdf->SetXY($x, $y + 0.75);
                $pdf->MultiCell($anchos[$i], 4.5, $c, 0, is_numeric(str_replace(',', '.', $c)) ? 'R' : 'L');
                $x += $anchos[$i];
            }
            $pdf->SetXY(12, $y + $alto);
        }
        return $pdf->Output('S');
    }

    /** Devuelve el contenido binario del archivo .xlsx. */
    public static function xlsx(array $reporte): string
    {
        $filas = [
            ['<b>VoluntaRed - ' . self::esc($reporte['titulo']) . '</b>'],
            ['Generado: ' . $reporte['generado']],
        ];
        if ($reporte['filtros']) {
            $filas[] = [self::esc('Filtros: ' . implode(' · ', $reporte['filtros']))];
        }
        $filas[] = [];
        $filas[] = array_map(static fn ($c) => '<style bgcolor="#1F7A4D" color="#FFFFFF"><b>' . self::esc($c) . '</b></style>', $reporte['columnas']);
        foreach ($reporte['filas'] as $fila) {
            $filas[] = array_map(static fn ($v) => is_string($v) ? self::esc($v) : $v, $fila);
        }
        $filas[] = [];
        foreach ($reporte['resumen'] as $etiqueta => $valor) {
            $filas[] = ['<b>' . self::esc($etiqueta) . '</b>', is_string($valor) ? self::esc($valor) : $valor];
        }
        $xlsx = SimpleXLSXGen::fromArray($filas, mb_substr($reporte['titulo'], 0, 31));
        $xlsx->setDefaultFont('Calibri')->setDefaultFontSize(11);
        return (string) $xlsx;
    }

    public static function descargar(string $contenido, string $nombre, string $tipoMime): void
    {
        header('Content-Type: ' . $tipoMime);
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Content-Length: ' . strlen($contenido));
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $contenido;
    }

    /** FPDF trabaja en Windows-1252: convierte el texto UTF-8 (tildes, ñ). */
    public static function txt(string $texto): string
    {
        $conv = @iconv('UTF-8', 'windows-1252//TRANSLIT', $texto);
        return $conv === false ? mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8') : $conv;
    }

    private static function esc(string $v): string
    {
        return str_replace(['<', '>'], ['&lt;', '&gt;'], $v);
    }

    private static function ajustarAnchos(array $anchos, float $total): array
    {
        $suma = array_sum($anchos);
        return array_map(static fn ($a) => $a * $total / $suma, $anchos);
    }
}
