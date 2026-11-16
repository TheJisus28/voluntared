<?php

namespace App\Core;

/**
 * Validación declarativa de formularios.
 *
 *   $v = Validator::validar($datos, [
 *       'email' => 'requerido|email|max:150',
 *       'cupos' => 'requerido|entero|min_valor:1',
 *   ]);
 *   if ($v) { ... errores ... }
 */
final class Validator
{
    private const NOMBRES = [
        'nombre' => 'nombre', 'apellido' => 'apellido', 'email' => 'correo electrónico',
        'contrasena' => 'contraseña', 'telefono' => 'teléfono', 'nit' => 'NIT',
        'razonSocial' => 'razón social', 'descripcion' => 'descripción', 'direccion' => 'dirección',
        'ciudad' => 'ciudad', 'departamento' => 'departamento', 'fechaNacimiento' => 'fecha de nacimiento',
        'disponibilidad' => 'disponibilidad', 'titulo' => 'título', 'modalidad' => 'modalidad',
        'fechaInicio' => 'fecha de inicio', 'fechaFin' => 'fecha de fin', 'horaInicio' => 'hora de inicio',
        'horaFin' => 'hora de fin', 'cuposTotales' => 'cupos', 'categoria' => 'categoría',
        'calificacion' => 'calificación', 'motivo' => 'motivo', 'codigo' => 'código', 'sitioWeb' => 'sitio web',
        'ubicacion' => 'ubicación', 'mision' => 'misión', 'biografia' => 'biografía',
    ];

    /** @return array<string,string> errores por campo (vacío si todo es válido) */
    public static function validar(array $datos, array $reglas): array
    {
        $errores = [];
        foreach ($reglas as $campo => $definicion) {
            $valor = $datos[$campo] ?? null;
            if (is_string($valor)) {
                $valor = trim($valor);
            }
            $lista = is_array($definicion) ? $definicion : explode('|', $definicion);
            $vacio = $valor === null || $valor === '' || $valor === [];
            // Las reglas "requerido" y "acepta" (casillas de autorización) se evalúan aunque el campo venga vacío.
            if ($vacio && !in_array('requerido', $lista, true) && !in_array('acepta', $lista, true)) {
                continue;
            }
            foreach ($lista as $regla) {
                [$nombre, $arg] = array_pad(explode(':', $regla, 2), 2, null);
                $error = self::aplicar($nombre, $arg, $valor, $datos, self::nombre($campo));
                if ($error !== null) {
                    $errores[$campo] = $error;
                    break;
                }
            }
        }
        return $errores;
    }

    public static function nombre(string $campo): string
    {
        return self::NOMBRES[$campo] ?? str_replace('_', ' ', $campo);
    }

    private static function aplicar(string $regla, ?string $arg, mixed $valor, array $datos, string $etiqueta): ?string
    {
        $texto = is_scalar($valor) ? (string) $valor : '';
        return match ($regla) {
            'requerido' => ($valor === null || $valor === '' || $valor === [])
                ? "El campo $etiqueta es obligatorio." : null,
            'email' => filter_var($texto, FILTER_VALIDATE_EMAIL)
                ? null : 'Ingresa un correo electrónico válido.',
            'min' => mb_strlen($texto) < (int) $arg
                ? "El campo $etiqueta debe tener al menos $arg caracteres." : null,
            'max' => mb_strlen($texto) > (int) $arg
                ? "El campo $etiqueta no puede superar $arg caracteres." : null,
            'entero' => filter_var($texto, FILTER_VALIDATE_INT) === false
                ? "El campo $etiqueta debe ser un número entero." : null,
            'numero' => !is_numeric($texto)
                ? "El campo $etiqueta debe ser un número." : null,
            'min_valor' => (float) $texto < (float) $arg
                ? "El campo $etiqueta debe ser mayor o igual a $arg." : null,
            'max_valor' => (float) $texto > (float) $arg
                ? "El campo $etiqueta debe ser menor o igual a $arg." : null,
            'en' => !in_array($texto, explode(',', (string) $arg), true)
                ? "Selecciona un valor válido para $etiqueta." : null,
            'fecha' => !self::esFecha($texto)
                ? "El campo $etiqueta debe ser una fecha válida." : null,
            'hora' => !preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $texto)
                ? "El campo $etiqueta debe ser una hora válida." : null,
            'despues_o_igual' => self::esFecha($texto) && self::esFecha((string) ($datos[$arg] ?? ''))
                && $texto < $datos[$arg]
                ? "La $etiqueta debe ser igual o posterior a la " . self::nombre((string) $arg) . '.' : null,
            'hora_despues' => ($datos[$arg] ?? '') !== '' && substr($texto, 0, 5) <= substr((string) $datos[$arg], 0, 5)
                ? "La $etiqueta debe ser posterior a la " . self::nombre((string) $arg) . '.' : null,
            'igual' => $texto !== (string) ($datos[$arg] ?? null)
                ? 'Las contraseñas no coinciden.' : null,
            'contrasena' => (mb_strlen($texto) < 8 || !preg_match('/[A-Za-z]/', $texto) || !preg_match('/\d/', $texto))
                ? 'La contraseña debe tener al menos 8 caracteres e incluir letras y números.' : null,
            'nit' => !preg_match('/^\d{6,12}(-\d)?$/', $texto)
                ? 'El NIT debe tener entre 6 y 12 dígitos y, opcionalmente, el dígito de verificación (ej. 900123456-7).' : null,
            'telefono' => !preg_match('/^\+?[\d\s-]{7,15}$/', $texto)
                ? 'Ingresa un teléfono válido (solo números, de 7 a 15 dígitos).' : null,
            'url' => !filter_var($texto, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $texto)
                ? 'Ingresa una dirección web válida que empiece por http:// o https://.' : null,
            'edad_minima' => self::esFecha($texto) && (new \DateTime($texto))->diff(new \DateTime())->y < (int) $arg
                ? "Debes tener al menos $arg años para registrarte como voluntario." : null,
            'no_futura' => self::esFecha($texto) && $texto > date('Y-m-d')
                ? "La $etiqueta no puede ser una fecha futura." : null,
            'desde_hoy' => self::esFecha($texto) && $texto < date('Y-m-d')
                ? "La $etiqueta no puede ser anterior a hoy." : null,
            'acepta' => !in_array($texto, ['1', 'on', 'si', 'true'], true)
                ? 'Debes aceptar la política de tratamiento de datos personales.' : null,
            default => throw new \InvalidArgumentException("Regla desconocida: $regla"),
        };
    }

    private static function esFecha(string $valor): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $valor);
        return $d !== false && $d->format('Y-m-d') === $valor;
    }
}
