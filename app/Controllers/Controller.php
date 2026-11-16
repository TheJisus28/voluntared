<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\HttpException;
use App\Core\Validator;
use App\Core\View;

/** Funcionalidad común de los controladores. */
abstract class Controller
{
    protected function vista(string $vista, array $datos = []): void
    {
        View::render($vista, $datos);
    }

    protected function json(mixed $datos, int $estado = 200): void
    {
        View::json($datos, $estado);
    }

    /** Valida y, si hay errores, vuelve al formulario con los mensajes y los datos enviados. */
    protected function validar(array $datos, array $reglas): void
    {
        $errores = Validator::validar($datos, $reglas);
        if ($errores) {
            $this->volverConErrores($errores, $datos);
        }
    }

    protected function volverConErrores(array $errores, array $datos): never
    {
        Flash::conErrores($errores, $datos);
        Flash::set('danger', 'Revisa los campos marcados en el formulario.');
        back();
    }

    protected function usuario(): array
    {
        return Auth::user() ?? throw new HttpException(401);
    }

    protected function entero(mixed $valor): int
    {
        $n = filter_var($valor, FILTER_VALIDATE_INT);
        if ($n === false || $n <= 0) {
            throw new HttpException(404);
        }
        return $n;
    }

    protected function pagina(): int
    {
        return max(1, (int) ($_GET['pagina'] ?? 1));
    }

    protected function exito(string $mensaje): void
    {
        Flash::set('success', $mensaje);
    }
}
