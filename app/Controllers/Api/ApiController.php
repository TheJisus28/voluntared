<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\HttpException;
use App\Core\ReglaNegocioException;
use App\Models\Conversacion;
use App\Models\Notificacion;
use App\Models\Oportunidad;
use App\Services\AuthService;
use App\Services\ChatService;
use App\Services\EstadisticaService;
use App\Services\PostulacionService;

/**
 * API REST en JSON. Permite que la interfaz web actualice el chat y las
 * notificaciones sin recargar, y deja lista la integración con una futura
 * aplicación móvil (requisito no funcional de escalabilidad).
 * Autenticación: cookie de sesión o cabecera "Authorization: Bearer <JWT>".
 */
final class ApiController extends Controller
{
    private function cuerpo(): array
    {
        $json = json_decode(file_get_contents('php://input') ?: '', true);
        return is_array($json) ? $json : $_POST;
    }

    public function login(): void
    {
        $datos = $this->cuerpo();
        try {
            $u = AuthService::autenticar((string) ($datos['email'] ?? ''), (string) ($datos['contrasena'] ?? ''));
        } catch (ReglaNegocioException $e) {
            $this->json(['error' => $e->getMessage()], 401);
            return;
        }
        $this->json([
            'token' => Auth::emitirToken($u),
            'tipo' => 'Bearer',
            'expira_en' => config('jwt.expiracion_horas') * 3600,
            'usuario' => [
                'id' => (int) $u['idUsuario'],
                'nombre' => trim($u['nombre'] . ' ' . $u['apellido']),
                'email' => $u['email'],
                'rol' => $u['rol'],
            ],
        ]);
    }

    public function logout(): void
    {
        $token = Auth::tokenActual();
        if ($token) {
            Auth::revocar($token);
        }
        $this->json(['mensaje' => 'Sesión cerrada.']);
    }

    public function oportunidades(): void
    {
        $porPagina = min(50, max(1, (int) ($_GET['por_pagina'] ?? config('paginacion'))));
        $pagina = $this->pagina();
        [$items, $total] = Oportunidad::buscar($_GET, $pagina, $porPagina, Auth::user()['perfil'] ?? null);
        $this->json([
            'datos' => array_map([$this, 'formatoOportunidad'], $items),
            'pagina' => $pagina,
            'por_pagina' => $porPagina,
            'total' => $total,
            'paginas' => (int) ceil($total / $porPagina),
        ]);
    }

    public function oportunidad(string $id): void
    {
        $o = Oportunidad::porId($this->entero($id));
        if (!$o || !$o['visible'] || $o['estadoVerificacion'] !== 'verificada') {
            throw new HttpException(404, 'Oportunidad no encontrada.');
        }
        $this->json($this->formatoOportunidad($o) + [
            'descripcion' => $o['descripcion'],
            'requisitos' => $o['requisitos'],
            'habilidades' => array_column($o['habilidades'], 'nombre'),
        ]);
    }

    public function postular(string $id): void
    {
        $datos = $this->cuerpo();
        $idPostulacion = PostulacionService::postular((int) $this->usuario()['idUsuario'], $this->entero($id), $datos['mensaje'] ?? null);
        $this->json(['id' => $idPostulacion, 'estado' => 'pendiente', 'mensaje' => 'Postulación registrada.'], 201);
    }

    private function formatoOportunidad(array $o): array
    {
        return [
            'id' => (int) $o['idOportunidad'],
            'titulo' => $o['titulo'],
            'fundacion' => $o['razonSocial'],
            'categoria' => $o['categoria'],
            'modalidad' => $o['modalidad'],
            'ciudad' => $o['ciudad'],
            'ubicacion' => $o['ubicacion'],
            'fecha_inicio' => $o['fechaInicio'],
            'fecha_fin' => $o['fechaFin'],
            'hora_inicio' => substr($o['horaInicio'], 0, 5),
            'hora_fin' => substr($o['horaFin'], 0, 5),
            'cupos_disponibles' => (int) $o['cuposDisponibles'],
            'cupos_totales' => (int) $o['cuposTotales'],
            'estado' => $o['estado'],
            'url' => url('/oportunidades/' . $o['idOportunidad']),
        ];
    }

    public function contadores(): void
    {
        $id = (int) $this->usuario()['idUsuario'];
        $this->json([
            'notificaciones' => Notificacion::noLeidas($id),
            'mensajes' => Conversacion::noLeidosTotal($id),
        ]);
    }

    public function notificaciones(): void
    {
        $id = (int) $this->usuario()['idUsuario'];
        $lista = Notificacion::deUsuario($id, min(50, max(1, (int) ($_GET['limite'] ?? 8))));
        $this->json([
            'no_leidas' => Notificacion::noLeidas($id),
            'datos' => array_map(static fn ($n) => [
                'id' => (int) $n['idNotificacion'],
                'tipo' => $n['tipo'],
                'titulo' => $n['titulo'],
                'contenido' => $n['contenido'],
                'leida' => (bool) $n['leida'],
                'hace' => hace($n['fechaCreacion']),
                'url' => url('/notificaciones/' . $n['idNotificacion'] . '/abrir'),
            ], $lista),
        ]);
    }

    public function leerNotificacion(string $id): void
    {
        Notificacion::marcarLeida($this->entero($id), (int) $this->usuario()['idUsuario']);
        $this->json(['ok' => true]);
    }

    public function mensajes(string $id): void
    {
        $u = $this->usuario();
        $conv = Conversacion::paraUsuario($this->entero($id), (int) $u['idUsuario']) ?? throw new HttpException(404);
        $nuevos = Conversacion::mensajes((int) $conv['idConversacion'], max(0, (int) ($_GET['desde'] ?? 0)));
        if ($nuevos) {
            Conversacion::marcarLeidos((int) $conv['idConversacion'], (int) $u['idUsuario']);
        }
        // Último mensaje propio leído por la otra parte (para mostrar "Leído").
        $ultimoLeido = \App\Core\Database::valor(
            'SELECT MAX(idMensaje) FROM mensaje WHERE Conversacion_idConversacion = ? AND emisor_id = ? AND leido = 1',
            [$conv['idConversacion'], $u['idUsuario']]
        );
        $this->json([
            'datos' => array_map(fn ($m) => $this->formatoMensaje($m, (int) $u['idUsuario']), $nuevos),
            'ultimo_leido' => (int) $ultimoLeido,
        ]);
    }

    public function enviarMensaje(string $id): void
    {
        $u = $this->usuario();
        $datos = $this->cuerpo();
        $m = ChatService::enviar((int) $u['idUsuario'], $this->entero($id), (string) ($datos['contenido'] ?? ''));
        $this->json($this->formatoMensaje($m, (int) $u['idUsuario']), 201);
    }

    private function formatoMensaje(array $m, int $yo): array
    {
        return [
            'id' => (int) $m['idMensaje'],
            'propio' => (int) $m['emisor_id'] === $yo,
            'contenido' => $m['contenido'],
            'hora' => date('g:i a', strtotime($m['fechaEnvio'])),
            'fecha' => fecha_corta($m['fechaEnvio']),
            'leido' => (bool) $m['leido'],
        ];
    }

    public function estadisticas(): void
    {
        $this->json(EstadisticaService::tablero($_GET['desde'] ?? null, $_GET['hasta'] ?? null, (int) ($_GET['categoria'] ?? 0) ?: null));
    }
}
