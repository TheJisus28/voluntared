<?php
/**
 * Utilidad del contenedor Docker (la usa docker/entrypoint.sh).
 *
 *   php docker/bd.php esperar    Espera hasta 2 minutos a que la base de datos acepte conexiones.
 *   php docker/bd.php instalada  Sale con código 0 si las tablas ya existen y 1 si la base está vacía.
 */

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    getenv('VR_DB_HOST') ?: 'db',
    (int) (getenv('VR_DB_PUERTO') ?: 3306),
    getenv('VR_DB_NOMBRE') ?: 'voluntared'
);
$conectar = static fn () => new PDO($dsn, getenv('VR_DB_USUARIO') ?: 'root', getenv('VR_DB_CLAVE') ?: '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_TIMEOUT => 3,
]);

switch ($argv[1] ?? '') {
    case 'esperar':
        echo "[voluntared] Esperando la base de datos...\n";
        for ($i = 0; $i < 60; $i++) {
            try {
                $conectar();
                exit(0);
            } catch (Throwable) {
                sleep(2);
            }
        }
        fwrite(STDERR, "[voluntared] La base de datos no respondió en 2 minutos.\n");
        exit(1);

    case 'instalada':
        try {
            $conectar()->query('SELECT 1 FROM rol LIMIT 1');
            exit(0);
        } catch (Throwable) {
            exit(1);
        }

    default:
        fwrite(STDERR, "Uso: php docker/bd.php esperar|instalada\n");
        exit(2);
}
