<?php

namespace App\Controllers;

use App\Core\HttpException;
use App\Models\Conversacion;
use App\Services\ChatService;

/** Caso de uso Conversar en chat. La vista se actualiza en tiempo real con la API (consultas periódicas). */
final class MensajeController extends Controller
{
    public function index(): void
    {
        $u = $this->usuario();
        $conversaciones = Conversacion::deUsuario((int) $u['idUsuario']);
        $this->vista('mensajes/index', [
            'titulo' => 'Mensajes',
            'conversaciones' => $conversaciones,
            'actual' => null,
            'mensajes' => [],
        ]);
    }

    public function ver(string $id): void
    {
        $u = $this->usuario();
        $conv = Conversacion::paraUsuario($this->entero($id), (int) $u['idUsuario']);
        if (!$conv) {
            throw new HttpException(404, 'La conversación no existe.');
        }
        Conversacion::marcarLeidos((int) $conv['idConversacion'], (int) $u['idUsuario']);
        \App\Core\Database::consulta(
            "UPDATE notificacion SET leida = 1 WHERE Usuario_idUsuario = ? AND tipo = 'mensaje' AND enlace = ?",
            [$u['idUsuario'], '/mensajes/' . $conv['idConversacion']]
        );
        $this->vista('mensajes/index', [
            'titulo' => 'Mensajes',
            'conversaciones' => Conversacion::deUsuario((int) $u['idUsuario']),
            'actual' => $conv,
            'mensajes' => Conversacion::mensajes((int) $conv['idConversacion']),
        ]);
    }

    public function iniciar(): void
    {
        $u = $this->usuario();
        $id = ChatService::iniciar(
            $u,
            (int) ($_POST['oportunidad'] ?? 0) ?: null,
            (int) ($_POST['voluntario'] ?? 0) ?: null
        );
        redirect("/mensajes/$id");
    }

    /** Envío sin JavaScript (el chat usa normalmente POST /api/conversaciones/{id}/mensajes). */
    public function enviar(string $id): void
    {
        $u = $this->usuario();
        ChatService::enviar((int) $u['idUsuario'], $this->entero($id), (string) ($_POST['contenido'] ?? ''));
        redirect("/mensajes/$id");
    }
}
