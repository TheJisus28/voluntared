<?php
/**
 * Copia de seguridad de la base de datos (requisito no funcional de confiabilidad).
 *
 *   php database/respaldo.php
 *
 * Genera storage/respaldos/voluntared_AAAAMMDD_HHMMSS.sql con mysqldump y conserva
 * los últimos 14 respaldos. Para que sea diaria, prográmalo en el Programador de
 * tareas de Windows (o con cron en Linux), por ejemplo a las 2:00 a. m.:
 *   schtasks /create /sc daily /st 02:00 /tn "Respaldo VoluntaRed"
 *            /tr "C:\xampp\php\php.exe C:\xampp\htdocs\voluntared\database\respaldo.php"
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Config;

date_default_timezone_set(Config::get('app.zona_horaria'));

$carpeta = __DIR__ . '/../storage/respaldos';
if (!is_dir($carpeta)) {
    mkdir($carpeta, 0775, true);
}

$mysqldump = is_file('C:\\xampp\\mysql\\bin\\mysqldump.exe') ? 'C:\\xampp\\mysql\\bin\\mysqldump.exe' : 'mysqldump';
$archivo = sprintf('%s/voluntared_%s.sql', $carpeta, date('Ymd_His'));

$comando = sprintf(
    '%s --host=%s --port=%d --user=%s %s --default-character-set=utf8mb4 --single-transaction --routines %s',
    escapeshellarg($mysqldump),
    escapeshellarg((string) Config::get('db.host')),
    (int) Config::get('db.puerto'),
    escapeshellarg((string) Config::get('db.usuario')),
    Config::get('db.clave') !== '' ? '--password=' . escapeshellarg((string) Config::get('db.clave')) : '',
    escapeshellarg((string) Config::get('db.nombre'))
);

exec($comando . ' > ' . escapeshellarg($archivo), $salida, $codigo);
if ($codigo !== 0 || !is_file($archivo) || filesize($archivo) === 0) {
    if (is_file($archivo)) {
        unlink($archivo); // no se deja un respaldo vacío o incompleto
    }
    fwrite(STDERR, "No se pudo generar el respaldo (código $codigo).\n");
    exit(1);
}

// Conserva solo los 14 respaldos más recientes.
$respaldos = glob($carpeta . '/voluntared_*.sql') ?: [];
rsort($respaldos);
foreach (array_slice($respaldos, 14) as $viejo) {
    unlink($viejo);
}

echo 'Respaldo creado: ' . realpath($archivo) . ' (' . round(filesize($archivo) / 1024) . " KB)\n";
