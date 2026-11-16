<?php

namespace App\Models;

use App\Core\Database as DB;

/** Catálogos de categorías y habilidades administrados por el administrador. */
final class Catalogo
{
    private const TABLAS = [
        'categoria' => ['pk' => 'idCategoria', 'usos' => [
            'SELECT COUNT(*) FROM oportunidad WHERE Categoria_idCategoria = ?',
            'SELECT COUNT(*) FROM voluntario_has_categoria WHERE Categoria_idCategoria = ?',
        ]],
        'habilidad' => ['pk' => 'idHabilidad', 'usos' => [
            'SELECT COUNT(*) FROM oportunidad_has_habilidad WHERE Habilidad_idHabilidad = ?',
            'SELECT COUNT(*) FROM voluntario_has_habilidad WHERE Habilidad_idHabilidad = ?',
        ]],
    ];

    public static function categorias(): array
    {
        return DB::filas(
            'SELECT c.*, (SELECT COUNT(*) FROM oportunidad o WHERE o.Categoria_idCategoria = c.idCategoria) AS usos
             FROM categoria c ORDER BY c.nombre'
        );
    }

    public static function habilidades(): array
    {
        return DB::filas(
            'SELECT h.*, (SELECT COUNT(*) FROM oportunidad_has_habilidad oh WHERE oh.Habilidad_idHabilidad = h.idHabilidad)
                       + (SELECT COUNT(*) FROM voluntario_has_habilidad vh WHERE vh.Habilidad_idHabilidad = h.idHabilidad) AS usos
             FROM habilidad h ORDER BY h.nombre'
        );
    }

    public static function existeNombre(string $tabla, string $nombre, ?int $excluirId = null): bool
    {
        $pk = self::TABLAS[$tabla]['pk'];
        return (bool) DB::valor("SELECT 1 FROM $tabla WHERE nombre = ? AND $pk <> ?", [trim($nombre), $excluirId ?? 0]);
    }

    public static function guardar(string $tabla, array $datos, ?int $id = null): int
    {
        $pk = self::TABLAS[$tabla]['pk'];
        if ($id) {
            DB::actualizar($tabla, $datos, "$pk = :id", ['id' => $id]);
            return $id;
        }
        return DB::insertar($tabla, $datos);
    }

    public static function enUso(string $tabla, int $id): bool
    {
        foreach (self::TABLAS[$tabla]['usos'] as $sql) {
            if ((int) DB::valor($sql, [$id]) > 0) {
                return true;
            }
        }
        return false;
    }

    public static function eliminar(string $tabla, int $id): void
    {
        $pk = self::TABLAS[$tabla]['pk'];
        DB::consulta("DELETE FROM $tabla WHERE $pk = ?", [$id]);
    }

    public static function existe(string $tabla, int $id): bool
    {
        $pk = self::TABLAS[$tabla]['pk'];
        return (bool) DB::valor("SELECT 1 FROM $tabla WHERE $pk = ?", [$id]);
    }
}
