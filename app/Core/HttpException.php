<?php

namespace App\Core;

use RuntimeException;

/** Error HTTP controlado (403, 404, 422...) que el front controller convierte en respuesta. */
class HttpException extends RuntimeException
{
    public function __construct(public readonly int $estado, string $mensaje = '')
    {
        parent::__construct($mensaje, $estado);
    }
}
