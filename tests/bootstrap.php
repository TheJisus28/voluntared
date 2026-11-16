<?php
/**
 * Arranque de las pruebas: usa una base de datos aparte (voluntared_test),
 * la recrea desde schema.sql y carga los catálogos. Cada prueba corre dentro
 * de una transacción que se revierte al terminar (ver Tests\TestCase).
 */

putenv('VR_DB_NOMBRE=voluntared_test');
putenv('VR_CORREO_DRIVER=ninguno');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../database/semilla.php';

use App\Core\Config;
use App\Core\Database;

date_default_timezone_set(Config::get('app.zona_horaria'));

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%d;charset=utf8mb4', Config::get('db.host'), Config::get('db.puerto')),
    Config::get('db.usuario'),
    Config::get('db.clave'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$pdo->exec('CREATE DATABASE IF NOT EXISTS voluntared_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

Database::reiniciar();
ejecutarScript(Database::pdo(), __DIR__ . '/../database/schema.sql');
sembrarCatalogos();
