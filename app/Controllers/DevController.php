<?php

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Mailer;

/**
 * Bandeja de correos de desarrollo: muestra los correos que el driver "log"
 * guardó en storage/correos. Solo está disponible con entorno = "local".
 */
final class DevController extends Controller
{
    public function correos(): void
    {
        $this->soloLocal();
        $this->vista('dev/correos', ['titulo' => 'Bandeja de correos (desarrollo)', 'correos' => Mailer::bandeja()]);
    }

    public function correo(string $id): void
    {
        $this->soloLocal();
        if (!preg_match('/^[\w-]+$/', $id) || !is_file($ruta = Mailer::CARPETA . "/$id.html")) {
            throw new HttpException(404);
        }
        header('Content-Type: text/html; charset=utf-8');
        readfile($ruta);
    }

    private function soloLocal(): void
    {
        if (config('app.entorno') !== 'local' || config('correo.driver') === 'smtp') {
            throw new HttpException(404);
        }
    }
}
