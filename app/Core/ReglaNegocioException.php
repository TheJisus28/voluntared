<?php

namespace App\Core;

use RuntimeException;

/** Violación de una regla de negocio (sin cupos, postulación duplicada, etc.). El mensaje se muestra al usuario. */
class ReglaNegocioException extends RuntimeException
{
}
