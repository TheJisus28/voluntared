<?php

namespace App\Core;

/**
 * Enrutador mínimo: asocia método + patrón de URL a un controlador,
 * aplicando middlewares de autenticación, rol y CSRF.
 *
 * Middlewares disponibles:
 *  - auth                    usuario autenticado
 *  - invitado                solo usuarios sin sesión
 *  - rol:voluntario,...      uno de los roles indicados
 *  - verificada              fundación con estado "verificada"
 *  - api                     responde en JSON y acepta token Bearer
 */
final class Router
{
    private array $rutas = [];

    public function get(string $patron, array $accion, array $middlewares = []): void
    {
        $this->agregar('GET', $patron, $accion, $middlewares);
    }

    public function post(string $patron, array $accion, array $middlewares = []): void
    {
        $this->agregar('POST', $patron, $accion, $middlewares);
    }

    public function agregar(string $metodo, string $patron, array $accion, array $middlewares): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', rtrim($patron, '/') ?: '/') . '$#';
        $this->rutas[] = compact('metodo', 'patron', 'regex', 'accion', 'middlewares');
    }

    public function despachar(string $metodo, string $ruta): void
    {
        $ruta = rtrim($ruta, '/') ?: '/';
        $metodoPermitido = false;

        foreach ($this->rutas as $r) {
            if (!preg_match($r['regex'], $ruta, $coincidencias)) {
                continue;
            }
            $metodoPermitido = true;
            if ($r['metodo'] !== $metodo) {
                continue;
            }
            $params = array_filter($coincidencias, 'is_string', ARRAY_FILTER_USE_KEY);
            $esApi = in_array('api', $r['middlewares'], true);
            $this->aplicarMiddlewares($r['middlewares'], $metodo, $esApi);

            [$clase, $funcion] = $r['accion'];
            $controlador = new $clase();
            $controlador->$funcion(...array_values($params));
            return;
        }

        throw new HttpException($metodoPermitido ? 405 : 404);
    }

    private function aplicarMiddlewares(array $middlewares, string $metodo, bool $esApi): void
    {
        if ($metodo === 'POST') {
            // El CSRF solo es un riesgo cuando la autenticación viaja en la cookie: las peticiones
            // API con "Authorization: Bearer" (o sin sesión, como /api/auth/login) no lo requieren.
            $usaCookie = !empty($_COOKIE[Config::get('jwt.cookie')]) && Auth::tokenDesdeCabecera() === null;
            if ((!$esApi || $usaCookie) && !Csrf::verificar()) {
                throw new HttpException(419, 'La sesión del formulario expiró. Recarga la página e inténtalo de nuevo.');
            }
        }

        foreach ($middlewares as $mw) {
            [$nombre, $arg] = array_pad(explode(':', $mw, 2), 2, null);
            switch ($nombre) {
                case 'auth':
                    if (!Auth::user()) {
                        if ($esApi) {
                            throw new HttpException(401, 'Debes iniciar sesión.');
                        }
                        Flash::set('info', 'Inicia sesión para continuar.');
                        redirect('/login', ['volver' => $_SERVER['REQUEST_URI'] ?? null]);
                    }
                    break;
                case 'invitado':
                    if (Auth::user()) {
                        redirect('/panel');
                    }
                    break;
                case 'rol':
                    $u = Auth::user();
                    if (!$u || !in_array($u['rol'], explode(',', $arg), true)) {
                        throw new HttpException(403, 'No tienes permiso para acceder a esta sección.');
                    }
                    break;
                case 'verificada':
                    $u = Auth::user();
                    if (($u['fundacion']['estadoVerificacion'] ?? null) !== 'verificada') {
                        if ($esApi) {
                            throw new HttpException(403, 'Tu fundación aún no ha sido verificada.');
                        }
                        Flash::set('warning', 'Esta función estará disponible cuando el administrador verifique tu fundación.');
                        redirect('/panel');
                    }
                    break;
            }
        }
    }
}
