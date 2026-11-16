<?php

namespace App\Core;

use PDO;
use PDOStatement;
use Throwable;

/** Conexión PDO única con consultas preparadas (previene inyección SQL). */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                Config::get('db.host'),
                Config::get('db.puerto'),
                Config::get('db.nombre')
            );
            self::$pdo = new PDO($dsn, Config::get('db.usuario'), Config::get('db.clave'), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            self::$pdo->exec("SET time_zone = '" . (new \DateTime())->format('P') . "'");
        }
        return self::$pdo;
    }

    /** Reinicia la conexión (usado por las pruebas al cambiar de base de datos). */
    public static function reiniciar(): void
    {
        self::$pdo = null;
    }

    public static function consulta(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        foreach ($params as $clave => $valor) {
            $nombre = is_int($clave) ? $clave + 1 : (str_starts_with($clave, ':') ? $clave : ':' . $clave);
            $tipo = match (true) {
                is_int($valor) => PDO::PARAM_INT,
                is_bool($valor) => PDO::PARAM_BOOL,
                $valor === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            $stmt->bindValue($nombre, $valor, $tipo);
        }
        $stmt->execute();
        return $stmt;
    }

    public static function fila(string $sql, array $params = []): ?array
    {
        $fila = self::consulta($sql, $params)->fetch();
        return $fila === false ? null : $fila;
    }

    public static function filas(string $sql, array $params = []): array
    {
        return self::consulta($sql, $params)->fetchAll();
    }

    public static function valor(string $sql, array $params = []): mixed
    {
        $v = self::consulta($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    /** Inserta un registro y devuelve el id generado. */
    public static function insertar(string $tabla, array $datos): int
    {
        $columnas = array_keys($datos);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $tabla,
            implode(', ', $columnas),
            implode(', ', array_map(static fn ($c) => ':' . $c, $columnas))
        );
        self::consulta($sql, $datos);
        return (int) self::pdo()->lastInsertId();
    }

    /** Actualiza registros: actualizar('usuario', ['activo' => 0], 'idUsuario = :id', ['id' => 5]). */
    public static function actualizar(string $tabla, array $datos, string $donde, array $paramsDonde = []): int
    {
        $sets = [];
        $params = [];
        foreach ($datos as $col => $valor) {
            $sets[] = "$col = :set_$col";
            $params["set_$col"] = $valor;
        }
        $sql = sprintf('UPDATE %s SET %s WHERE %s', $tabla, implode(', ', $sets), $donde);
        return self::consulta($sql, $params + $paramsDonde)->rowCount();
    }

    /** Ejecuta la función dentro de una transacción; revierte si lanza una excepción. */
    public static function transaccion(callable $fn): mixed
    {
        $pdo = self::pdo();
        if ($pdo->inTransaction()) {
            return $fn();
        }
        $pdo->beginTransaction();
        try {
            $resultado = $fn();
            $pdo->commit();
            return $resultado;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
