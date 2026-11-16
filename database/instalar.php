<?php
/**
 * Instalador de la base de datos de VoluntaRed.
 *
 *   php database/instalar.php            Crea la base de datos y carga datos de demostración.
 *   php database/instalar.php --sin-demo Crea la base de datos solo con los catálogos y el administrador.
 *
 * Usa la configuración de app/config.php (o las variables de entorno VR_DB_*).
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/semilla.php';

use App\Core\Config;
use App\Core\Database;

date_default_timezone_set(Config::get('app.zona_horaria'));

$conDemo = !in_array('--sin-demo', $argv ?? [], true);
$nombreBd = Config::get('db.nombre');

echo "VoluntaRed - instalación de la base de datos «{$nombreBd}»\n";

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%d;charset=utf8mb4', Config::get('db.host'), Config::get('db.puerto')),
    Config::get('db.usuario'),
    Config::get('db.clave'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$pdo->exec("CREATE DATABASE IF NOT EXISTS `$nombreBd` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "  ✓ Base de datos lista\n";

Database::reiniciar();
ejecutarScript(Database::pdo(), __DIR__ . '/schema.sql');
echo "  ✓ Tablas creadas\n";

sembrarCatalogos();
echo "  ✓ Catálogos y administrador cargados\n";

if ($conDemo) {
    sembrarDemo();
    echo "  ✓ Datos de demostración cargados\n";
}

// Limpia los correos de desarrollo de instalaciones anteriores.
foreach (glob(__DIR__ . '/../storage/correos/*.html') ?: [] as $archivo) {
    unlink($archivo);
}

if (getenv('VR_DOCKER')) {
    echo "\nListo. Abre " . (getenv('VR_URL') ?: 'http://localhost:8080') . "\n\n";
} else {
    echo "\nListo. Inicia el servidor con:\n  php -S localhost:8000 -t public public/index.php\n";
    echo "y abre http://localhost:8000\n\n";
}
echo "Cuentas de prueba:\n";
echo "  Administrador: admin@voluntared.co / Admin2026\n";
if ($conDemo) {
    echo "  Fundación:     laura@sembrandofuturo.org / Fundacion2026\n";
    echo "  Voluntario:    ana.gomez@correo.com / Voluntario2026\n";
}
