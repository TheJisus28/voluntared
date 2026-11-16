<?php

namespace Tests;

use App\Core\Auth;
use App\Core\Database as DB;
use App\Models\Fundacion;
use App\Models\PerfilVoluntario;
use App\Models\Usuario;
use PHPUnit\Framework\TestCase as Base;

/**
 * Clase base: aísla cada prueba en una transacción y ofrece fábricas de datos.
 */
abstract class TestCase extends Base
{
    private static int $secuencia = 0;

    protected function setUp(): void
    {
        DB::pdo()->beginTransaction();
        Auth::refrescar(null);
    }

    protected function tearDown(): void
    {
        if (DB::pdo()->inTransaction()) {
            DB::pdo()->rollBack();
        }
    }

    protected function correoUnico(string $prefijo = 'usuario'): string
    {
        return $prefijo . '.' . (++self::$secuencia) . '.' . bin2hex(random_bytes(3)) . '@prueba.co';
    }

    /** Crea un voluntario verificado; con perfil (y categorías de interés) si se indica. */
    protected function crearVoluntario(bool $conPerfil = true, array $categorias = [1], string $clave = 'Clave2026'): array
    {
        $id = Usuario::crear([
            'nombre' => 'Vol', 'apellido' => 'Prueba', 'email' => $this->correoUnico('vol'),
            'contrasena' => $clave, 'emailVerificado' => 1,
        ], 'voluntario');
        if ($conPerfil) {
            $idPerfil = PerfilVoluntario::guardar($id, [
                'fechaNacimiento' => '2000-01-01', 'biografia' => '', 'disponibilidad' => 'flexible',
                'ciudad' => 'Montería', 'departamento' => 'Córdoba',
            ]);
            PerfilVoluntario::sincronizarCategorias($idPerfil, $categorias);
        }
        return Usuario::buscarConRol($id);
    }

    /** Crea una fundación con su usuario; devuelve el registro de fundación con datos del usuario. */
    protected function crearFundacion(string $estado = 'verificada'): array
    {
        $idUsuario = Usuario::crear([
            'nombre' => 'Contacto', 'apellido' => 'Fundación', 'email' => $this->correoUnico('fund'),
            'contrasena' => 'Clave2026', 'emailVerificado' => 1,
        ], 'fundacion');
        Fundacion::crear($idUsuario, [
            'nit' => (string) random_int(900000000, 999999999), 'razonSocial' => 'Fundación de Prueba ' . self::$secuencia,
            'descripcion' => 'Fundación creada para las pruebas automatizadas.', 'direccion' => 'Calle 1', 'ciudad' => 'Montería',
            'documentoSoporte' => 'documentos/prueba.pdf', 'estadoVerificacion' => $estado,
        ]);
        return Fundacion::porUsuario($idUsuario);
    }

    /** Datos válidos para el formulario de oportunidad; $cambios sobrescribe campos. */
    protected function datosOportunidad(array $cambios = []): array
    {
        return $cambios + [
            'titulo' => 'Jornada de prueba en el barrio',
            'descripcion' => 'Descripción suficientemente larga de la actividad de prueba.',
            'requisitos' => '',
            'categoria' => '1',
            'modalidad' => 'presencial',
            'ubicacion' => 'Parque principal',
            'ciudad' => 'Montería',
            'fechaInicio' => date('Y-m-d', strtotime('+5 days')),
            'fechaFin' => date('Y-m-d', strtotime('+6 days')),
            'horaInicio' => '08:00',
            'horaFin' => '12:00',
            'cuposTotales' => '3',
            'habilidades' => [],
        ];
    }

    protected function crearOportunidad(array $fundacion, array $cambios = []): array
    {
        $id = \App\Services\OportunidadService::crear($fundacion, $this->datosOportunidad($cambios));
        return \App\Models\Oportunidad::porId($id);
    }

    protected function oportunidad(int $id): array
    {
        return \App\Models\Oportunidad::porId($id);
    }

    protected function notificacionesDe(int $idUsuario): array
    {
        return DB::filas('SELECT * FROM notificacion WHERE Usuario_idUsuario = ? ORDER BY idNotificacion', [$idUsuario]);
    }
}
