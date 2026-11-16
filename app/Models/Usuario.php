<?php

namespace App\Models;

use App\Core\Database as DB;

/** Tabla usuario: cuentas de voluntarios, fundaciones y administradores. */
final class Usuario
{
    public const ROLES = ['voluntario' => 1, 'fundacion' => 2, 'administrador' => 3];

    public static function buscarConRol(int $id): ?array
    {
        return DB::fila(
            'SELECT u.*, r.nombre AS rol FROM usuario u JOIN rol r ON r.idRol = u.Rol_idRol WHERE u.idUsuario = ?',
            [$id]
        );
    }

    public static function porEmail(string $email): ?array
    {
        return DB::fila(
            'SELECT u.*, r.nombre AS rol FROM usuario u JOIN rol r ON r.idRol = u.Rol_idRol WHERE u.email = ?',
            [mb_strtolower(trim($email))]
        );
    }

    public static function emailExiste(string $email, ?int $excluirId = null): bool
    {
        return (bool) DB::valor(
            'SELECT 1 FROM usuario WHERE email = ? AND idUsuario <> ?',
            [mb_strtolower(trim($email)), $excluirId ?? 0]
        );
    }

    public static function crear(array $datos, string $rol): int
    {
        return DB::insertar('usuario', [
            'Rol_idRol' => self::ROLES[$rol],
            'nombre' => $datos['nombre'],
            'apellido' => $datos['apellido'] ?? null,
            'email' => mb_strtolower(trim($datos['email'])),
            'contrasena' => password_hash($datos['contrasena'], PASSWORD_BCRYPT),
            'telefono' => $datos['telefono'] ?? null,
            'emailVerificado' => (int) ($datos['emailVerificado'] ?? 0),
        ]);
    }

    public static function actualizar(int $id, array $datos): void
    {
        DB::actualizar('usuario', $datos, 'idUsuario = :id', ['id' => $id]);
    }

    public static function cambiarContrasena(int $id, string $nueva): void
    {
        self::actualizar($id, [
            'contrasena' => password_hash($nueva, PASSWORD_BCRYPT),
            'resetCode' => null,
            'resetCodeExpiration' => null,
            'intentosFallidos' => 0,
            'bloqueadoHasta' => null,
        ]);
    }

    /** Suma un intento fallido y bloquea la cuenta al llegar al máximo. Devuelve true si quedó bloqueada. */
    public static function registrarIntentoFallido(int $id, int $maximo, int $minutos): bool
    {
        DB::consulta('UPDATE usuario SET intentosFallidos = intentosFallidos + 1 WHERE idUsuario = ?', [$id]);
        $intentos = (int) DB::valor('SELECT intentosFallidos FROM usuario WHERE idUsuario = ?', [$id]);
        if ($intentos >= $maximo) {
            DB::consulta(
                'UPDATE usuario SET bloqueadoHasta = DATE_ADD(NOW(), INTERVAL ? MINUTE), intentosFallidos = 0 WHERE idUsuario = ?',
                [$minutos, $id]
            );
            return true;
        }
        return false;
    }

    public static function idsAdministradores(): array
    {
        return array_map('intval', array_column(
            DB::filas('SELECT idUsuario FROM usuario WHERE Rol_idRol = 3 AND activo = 1'),
            'idUsuario'
        ));
    }

    /** Promedio de calificaciones visibles recibidas por el usuario. */
    public static function promedio(int $id): array
    {
        $fila = DB::fila(
            'SELECT ROUND(AVG(calificacion), 1) AS promedio, COUNT(*) AS total FROM evaluacion WHERE evaluado_id = ? AND visible = 1',
            [$id]
        );
        return ['promedio' => $fila['promedio'] !== null ? (float) $fila['promedio'] : null, 'total' => (int) $fila['total']];
    }

    /** Listado para el administrador con búsqueda y filtros. */
    public static function listar(array $filtros, int $pagina, int $porPagina): array
    {
        $where = ['1 = 1'];
        $params = [];
        if (!empty($filtros['q'])) {
            $where[] = '(CONCAT(u.nombre, " ", COALESCE(u.apellido, "")) LIKE :q1 OR u.email LIKE :q2 OR f.razonSocial LIKE :q3)';
            $params['q1'] = $params['q2'] = $params['q3'] = '%' . $filtros['q'] . '%';
        }
        if (!empty($filtros['rol']) && isset(self::ROLES[$filtros['rol']])) {
            $where[] = 'u.Rol_idRol = :rol';
            $params['rol'] = self::ROLES[$filtros['rol']];
        }
        if (isset($filtros['estado']) && $filtros['estado'] !== '') {
            $where[] = 'u.activo = :activo';
            $params['activo'] = $filtros['estado'] === 'activo' ? 1 : 0;
        }
        $sqlWhere = implode(' AND ', $where);
        $base = "FROM usuario u JOIN rol r ON r.idRol = u.Rol_idRol LEFT JOIN fundacion f ON f.Usuario_idUsuario = u.idUsuario WHERE $sqlWhere";
        $total = (int) DB::valor("SELECT COUNT(*) $base", $params);
        $items = DB::filas(
            "SELECT u.idUsuario, u.nombre, u.apellido, u.email, u.activo, u.emailVerificado, u.fechaCreacion, u.foto,
                    r.nombre AS rol, f.razonSocial, f.estadoVerificacion
             $base ORDER BY u.fechaCreacion DESC LIMIT :limite OFFSET :offset",
            $params + ['limite' => $porPagina, 'offset' => ($pagina - 1) * $porPagina]
        );
        return [$items, $total];
    }
}
