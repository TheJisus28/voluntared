<?php

namespace App\Models;

use App\Core\Database as DB;

/** Tabla perfil_voluntario y sus relaciones con habilidades y categorías de interés. */
final class PerfilVoluntario
{
    public static function porUsuario(int $idUsuario): ?array
    {
        return DB::fila('SELECT * FROM perfil_voluntario WHERE Usuario_idUsuario = ?', [$idUsuario]);
    }

    /** Crea o actualiza el perfil y devuelve su id. */
    public static function guardar(int $idUsuario, array $datos): int
    {
        $campos = [
            'fechaNacimiento' => $datos['fechaNacimiento'],
            'biografia' => $datos['biografia'] !== '' ? $datos['biografia'] : null,
            'disponibilidad' => $datos['disponibilidad'],
            'ciudad' => $datos['ciudad'],
            'departamento' => $datos['departamento'],
        ];
        $actual = self::porUsuario($idUsuario);
        if ($actual) {
            DB::actualizar('perfil_voluntario', $campos, 'idPerfilVoluntario = :id', ['id' => $actual['idPerfilVoluntario']]);
            return (int) $actual['idPerfilVoluntario'];
        }
        return DB::insertar('perfil_voluntario', $campos + ['Usuario_idUsuario' => $idUsuario]);
    }

    public static function habilidades(int $idPerfil): array
    {
        return DB::filas(
            'SELECT h.* FROM voluntario_has_habilidad vh JOIN habilidad h ON h.idHabilidad = vh.Habilidad_idHabilidad
             WHERE vh.PerfilVoluntario_id = ? ORDER BY h.nombre',
            [$idPerfil]
        );
    }

    public static function categorias(int $idPerfil): array
    {
        return DB::filas(
            'SELECT c.* FROM voluntario_has_categoria vc JOIN categoria c ON c.idCategoria = vc.Categoria_idCategoria
             WHERE vc.PerfilVoluntario_id = ? ORDER BY c.nombre',
            [$idPerfil]
        );
    }

    public static function sincronizarHabilidades(int $idPerfil, array $ids): void
    {
        DB::consulta('DELETE FROM voluntario_has_habilidad WHERE PerfilVoluntario_id = ?', [$idPerfil]);
        foreach (array_unique(array_map('intval', $ids)) as $id) {
            DB::insertar('voluntario_has_habilidad', ['PerfilVoluntario_id' => $idPerfil, 'Habilidad_idHabilidad' => $id]);
        }
    }

    public static function sincronizarCategorias(int $idPerfil, array $ids): void
    {
        DB::consulta('DELETE FROM voluntario_has_categoria WHERE PerfilVoluntario_id = ?', [$idPerfil]);
        foreach (array_unique(array_map('intval', $ids)) as $id) {
            DB::insertar('voluntario_has_categoria', ['PerfilVoluntario_id' => $idPerfil, 'Categoria_idCategoria' => $id]);
        }
    }

    /** Usuarios voluntarios activos interesados en una categoría (para notificar nuevas oportunidades). */
    public static function interesadosEnCategoria(int $idCategoria): array
    {
        return DB::filas(
            'SELECT u.idUsuario, u.nombre, u.email FROM voluntario_has_categoria vc
             JOIN perfil_voluntario p ON p.idPerfilVoluntario = vc.PerfilVoluntario_id
             JOIN usuario u ON u.idUsuario = p.Usuario_idUsuario
             WHERE vc.Categoria_idCategoria = ? AND u.activo = 1',
            [$idCategoria]
        );
    }

    /** Ficha completa del voluntario que ve una fundación al revisar una postulación. */
    public static function ficha(int $idUsuario): ?array
    {
        $u = DB::fila(
            'SELECT u.idUsuario, u.nombre, u.apellido, u.email, u.telefono, u.foto, u.fechaCreacion, p.*
             FROM usuario u LEFT JOIN perfil_voluntario p ON p.Usuario_idUsuario = u.idUsuario
             WHERE u.idUsuario = ? AND u.Rol_idRol = 1',
            [$idUsuario]
        );
        if (!$u) {
            return null;
        }
        $u['habilidades'] = $u['idPerfilVoluntario'] ? self::habilidades((int) $u['idPerfilVoluntario']) : [];
        $u['categorias'] = $u['idPerfilVoluntario'] ? self::categorias((int) $u['idPerfilVoluntario']) : [];
        $u['calificacion'] = Usuario::promedio($idUsuario);
        $u['horas'] = Participacion::totalHoras($idUsuario);
        $u['actividades'] = Participacion::historial($idUsuario);
        $u['evaluaciones'] = Evaluacion::recibidas($idUsuario);
        return $u;
    }
}
