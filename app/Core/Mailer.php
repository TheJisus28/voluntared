<?php

namespace App\Core;

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Envío de correos electrónicos.
 *  - driver "smtp": usa PHPMailer con el servidor SMTP configurado.
 *  - driver "log":  guarda el correo como HTML en storage/correos (desarrollo),
 *                   visible en /dev/correos cuando el entorno es "local".
 */
final class Mailer
{
    public const CARPETA = __DIR__ . '/../../storage/correos';

    public static function enviar(string $para, string $nombre, string $asunto, string $vista, array $datos = []): bool
    {
        $html = View::capturar('correos/' . $vista, $datos + ['asunto' => $asunto, 'nombre' => $nombre]);
        $html = View::capturar('correos/layout', ['contenido' => $html, 'asunto' => $asunto]);

        if (Config::get('correo.driver') === 'ninguno') {
            return true; // pruebas automatizadas: la plantilla se renderiza pero no se envía
        }
        if (Config::get('correo.driver') !== 'smtp') {
            return self::guardarEnDisco($para, $asunto, $html);
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = Config::get('correo.host');
            $mail->Port = Config::get('correo.puerto');
            $mail->SMTPAuth = true;
            $mail->Username = Config::get('correo.usuario');
            $mail->Password = Config::get('correo.clave');
            $mail->SMTPSecure = Config::get('correo.cifrado') === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom(Config::get('correo.remitente'), Config::get('correo.nombre_remitente'));
            $mail->addAddress($para, $nombre);
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $html;
            $mail->AltBody = trim(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8'));
            return $mail->send();
        } catch (MailException $e) {
            error_log('[VoluntaRed] Error enviando correo: ' . $e->getMessage());
            return false;
        }
    }

    private static function guardarEnDisco(string $para, string $asunto, string $html): bool
    {
        if (!is_dir(self::CARPETA)) {
            mkdir(self::CARPETA, 0775, true);
        }
        $archivo = sprintf('%s/%s_%s.html', self::CARPETA, date('Ymd_His'), bin2hex(random_bytes(3)));
        $cabecera = sprintf("<!--\nPara: %s\nAsunto: %s\nFecha: %s\n-->\n", $para, $asunto, date('Y-m-d H:i:s'));
        return file_put_contents($archivo, $cabecera . $html) !== false;
    }

    /** Correos guardados en disco, del más reciente al más antiguo (bandeja de desarrollo). */
    public static function bandeja(): array
    {
        $archivos = glob(self::CARPETA . '/*.html') ?: [];
        rsort($archivos);
        $lista = [];
        foreach ($archivos as $ruta) {
            $inicio = file_get_contents($ruta, false, null, 0, 600);
            preg_match('/Para: (.*)\n/', $inicio, $para);
            preg_match('/Asunto: (.*)\n/', $inicio, $asunto);
            preg_match('/Fecha: (.*)\n/', $inicio, $fecha);
            $lista[] = [
                'id' => basename($ruta, '.html'),
                'para' => $para[1] ?? '',
                'asunto' => $asunto[1] ?? '',
                'fecha' => $fecha[1] ?? '',
            ];
        }
        return $lista;
    }
}
