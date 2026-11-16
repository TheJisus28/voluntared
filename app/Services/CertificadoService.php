<?php

namespace App\Services;

use App\Core\Config;
use FPDF;

/**
 * Certificado de horas de voluntariado en PDF: permite al voluntario acreditar
 * su experiencia ante instituciones educativas o empleadores (justificación del ERS).
 */
final class CertificadoService
{
    public static function codigo(int $idUsuario, float $horas): string
    {
        return strtoupper(substr(hash_hmac('sha256', "cert|$idUsuario|$horas", Config::get('app.clave')), 0, 12));
    }

    public static function generar(array $usuario, array $actividades, float $totalHoras): string
    {
        $t = [Exportador::class, 'txt'];
        $asistidas = array_values(array_filter($actividades, static fn ($a) => (int) $a['asistio'] === 1));

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetTitle('Certificado de voluntariado', true);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        // Marco
        $pdf->SetDrawColor(31, 122, 77);
        $pdf->SetLineWidth(1.2);
        $pdf->Rect(10, 10, 190, 277);
        $pdf->SetLineWidth(0.3);
        $pdf->Rect(13, 13, 184, 271);

        $pdf->SetY(26);
        $pdf->SetFont('Helvetica', 'B', 24);
        $pdf->SetTextColor(31, 122, 77);
        $pdf->Cell(0, 10, 'VoluntaRed', 0, 1, 'C');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(110, 110, 110);
        $pdf->Cell(0, 6, $t('Plataforma digital para la vinculación entre voluntarios y fundaciones'), 0, 1, 'C');
        $pdf->Ln(10);

        $pdf->SetFont('Helvetica', 'B', 16);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->Cell(0, 9, $t('CERTIFICADO DE HORAS DE VOLUNTARIADO'), 0, 1, 'C');
        $pdf->Ln(6);

        $pdf->SetFont('Helvetica', '', 12);
        $pdf->SetX(25);
        $pdf->MultiCell(160, 7, $t('VoluntaRed hace constar que'), 0, 'C');
        $pdf->SetFont('Helvetica', 'B', 18);
        $pdf->SetTextColor(31, 122, 77);
        $pdf->Cell(0, 12, $t(trim($usuario['nombre'] . ' ' . $usuario['apellido'])), 0, 1, 'C');
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetX(25);
        $texto = sprintf(
            'participó como voluntario(a) en %d actividad(es) de fundaciones verificadas, acumulando un total de %s horas de servicio voluntario registradas por las organizaciones convocantes.',
            count($asistidas),
            number_format($totalHoras, 1, ',', '.')
        );
        $pdf->MultiCell(160, 7, $t($texto), 0, 'C');
        $pdf->Ln(8);

        // Detalle de actividades
        $pdf->SetX(20);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetFillColor(31, 122, 77);
        $pdf->SetTextColor(255, 255, 255);
        $anchos = [70, 55, 30, 15];
        foreach (['Actividad', 'Fundación', 'Fecha', 'Horas'] as $i => $col) {
            $pdf->Cell($anchos[$i], 7, $t($col), 0, 0, 'L', true);
        }
        $pdf->Ln();
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(40, 40, 40);
        foreach ($asistidas as $n => $a) {
            $pdf->SetX(20);
            $pdf->SetFillColor(...($n % 2 ? [244, 249, 246] : [255, 255, 255]));
            $pdf->Cell($anchos[0], 6.5, $t(recortar($a['titulo'], 42)), 0, 0, 'L', true);
            $pdf->Cell($anchos[1], 6.5, $t(recortar($a['razonSocial'], 32)), 0, 0, 'L', true);
            $pdf->Cell($anchos[2], 6.5, $t(fecha_corta($a['fechaInicio'])), 0, 0, 'L', true);
            $pdf->Cell($anchos[3], 6.5, number_format((float) $a['horasCumplidas'], 1, ',', ''), 0, 1, 'R', true);
            if ($pdf->GetY() > 240) {
                break;
            }
        }
        if (!$asistidas) {
            $pdf->SetX(20);
            $pdf->Cell(170, 8, $t('Aún no hay actividades con asistencia registrada.'), 0, 1, 'C');
        }

        $pdf->SetY(-62);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->Cell(0, 6, $t('Expedido en Montería, Córdoba, el ' . fecha(date('Y-m-d')) . '.'), 0, 1, 'C');
        $pdf->Ln(4);
        $pdf->SetFont('Helvetica', '', 8.5);
        $pdf->Cell(0, 5, $t('Código de verificación: ' . self::codigo((int) $usuario['idUsuario'], $totalHoras)), 0, 1, 'C');
        $pdf->Cell(0, 5, $t('Las horas son reportadas por cada fundación al registrar la participación del voluntario.'), 0, 1, 'C');

        return $pdf->Output('S');
    }
}
