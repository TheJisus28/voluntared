<?php
/**
 * VoluntaRed - Front controller.
 * Todas las peticiones pasan por aquí (ver .htaccess). Con el servidor embebido de PHP:
 *     php -S localhost:8000 -t public public/index.php
 */

declare(strict_types=1);

// Con el servidor embebido, los archivos estáticos existentes se sirven directamente.
if (PHP_SAPI === 'cli-server') {
    $archivo = __DIR__ . urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if (is_file($archivo)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Flash;
use App\Core\HttpException;
use App\Core\ReglaNegocioException;
use App\Core\Router;
use App\Core\View;

date_default_timezone_set(config('app.zona_horaria'));

// Toda la entrada se normaliza a UTF-8 (un cliente mal configurado podría enviar Latin-1).
$aUtf8 = static function (&$valor): void {
    if (is_string($valor) && !mb_check_encoding($valor, 'UTF-8')) {
        $valor = mb_convert_encoding($valor, 'UTF-8', 'ISO-8859-1');
    }
};
array_walk_recursive($_GET, $aUtf8);
array_walk_recursive($_POST, $aUtf8);

session_name('vr_sesion');
session_set_cookie_params([
    'path' => base_path_url() ?: '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();
Flash::iniciar();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

$router = new Router();
require __DIR__ . '/../app/routes.php';

$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base = base_path_url();
if ($base !== '' && str_starts_with($ruta, $base)) {
    $ruta = substr($ruta, strlen($base)) ?: '/';
}
$esApi = str_starts_with($ruta, '/api/');

try {
    \App\Models\Oportunidad::finalizarVencidas();
    $router->despachar($_SERVER['REQUEST_METHOD'] ?? 'GET', $ruta);
} catch (ReglaNegocioException $e) {
    if ($esApi) {
        View::json(['error' => $e->getMessage()], 422);
    } else {
        Flash::set('danger', $e->getMessage());
        back();
    }
} catch (HttpException $e) {
    $titulos = [
        401 => 'Debes iniciar sesión', 403 => 'Acceso denegado', 404 => 'Página no encontrada',
        405 => 'Método no permitido', 419 => 'La página expiró',
    ];
    if ($esApi) {
        View::json(['error' => $e->getMessage() ?: ($titulos[$e->estado] ?? 'Error')], $e->estado);
    } else {
        http_response_code($e->estado);
        View::render('errores/error', [
            'titulo' => $titulos[$e->estado] ?? 'Error',
            'codigo' => $e->estado,
            'mensaje' => $e->getMessage(),
        ]);
    }
} catch (Throwable $e) {
    error_log('[VoluntaRed] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    $detalle = config('app.entorno') === 'local' ? $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')' : '';
    if ($esApi) {
        View::json(['error' => 'Error interno del servidor.', 'detalle' => $detalle ?: null], 500);
    } else {
        http_response_code(500);
        View::render('errores/error', [
            'titulo' => 'Algo salió mal',
            'codigo' => 500,
            'mensaje' => 'Ocurrió un error inesperado. Inténtalo de nuevo en unos minutos.',
            'detalle' => $detalle,
        ]);
    }
}
