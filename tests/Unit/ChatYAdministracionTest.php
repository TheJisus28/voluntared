<?php

namespace Tests\Unit;

use App\Core\Database as DB;
use App\Core\ReglaNegocioException;
use App\Models\Catalogo;
use App\Models\Conversacion;
use App\Models\Fundacion;
use App\Models\Oportunidad;
use App\Models\Usuario;
use App\Services\AdminService;
use App\Services\ChatService;
use App\Services\PostulacionService;
use Tests\TestCase;

/** RF10 (mensajería), RF5 (verificación) y RF14 (administración y moderación). */
final class ChatYAdministracionTest extends TestCase
{
    public function testVoluntarioIniciaConversacionDesdeUnaOportunidad(): void
    {
        $f = $this->crearFundacion();
        $o = $this->crearOportunidad($f);
        $v = $this->crearVoluntario();

        $id = ChatService::iniciar($v, (int) $o['idOportunidad'], null);
        $this->assertSame($id, ChatService::iniciar($v, (int) $o['idOportunidad'], null), 'Debe reutilizar la conversación');

        $m = ChatService::enviar((int) $v['idUsuario'], $id, 'Hola, ¿a qué hora empieza?');
        $this->assertSame('Hola, ¿a qué hora empieza?', $m['contenido']);
        $this->assertSame(1, Conversacion::noLeidosTotal((int) $f['Usuario_idUsuario']));
    }

    public function testSoloUnAvisoDeChatMientrasNoSeLea(): void
    {
        $f = $this->crearFundacion();
        $v = $this->crearVoluntario();
        $id = ChatService::iniciar($v, (int) $this->crearOportunidad($f)['idOportunidad'], null);
        ChatService::enviar((int) $v['idUsuario'], $id, 'Mensaje 1');
        ChatService::enviar((int) $v['idUsuario'], $id, 'Mensaje 2');

        $avisos = array_filter($this->notificacionesDe((int) $f['Usuario_idUsuario']), static fn ($n) => $n['tipo'] === 'mensaje');
        $this->assertCount(1, $avisos);
    }

    public function testFundacionSoloEscribeAVoluntariosPostulados(): void
    {
        $f = $this->crearFundacion();
        $usuarioF = Usuario::buscarConRol((int) $f['Usuario_idUsuario']);
        $v = $this->crearVoluntario();
        $this->expectExceptionMessage('se hayan postulado');
        ChatService::iniciar($usuarioF, null, (int) $v['idUsuario']);
    }

    public function testFundacionEscribeAUnPostulado(): void
    {
        $f = $this->crearFundacion();
        $o = $this->crearOportunidad($f);
        $v = $this->crearVoluntario();
        PostulacionService::postular((int) $v['idUsuario'], (int) $o['idOportunidad'], null);
        $id = ChatService::iniciar(Usuario::buscarConRol((int) $f['Usuario_idUsuario']), (int) $o['idOportunidad'], (int) $v['idUsuario']);
        $this->assertGreaterThan(0, $id);
    }

    public function testMensajeVacioOMuyLargo(): void
    {
        $v = $this->crearVoluntario();
        $id = ChatService::iniciar($v, (int) $this->crearOportunidad($this->crearFundacion())['idOportunidad'], null);
        try {
            ChatService::enviar((int) $v['idUsuario'], $id, '   ');
            $this->fail('Debía rechazar el mensaje vacío');
        } catch (ReglaNegocioException) {
            $this->addToAssertionCount(1);
        }
        $this->expectExceptionMessage('no puede superar');
        ChatService::enviar((int) $v['idUsuario'], $id, str_repeat('a', 2001));
    }

    public function testUnTerceroNoPuedeEscribirEnConversacionAjena(): void
    {
        $v = $this->crearVoluntario();
        $id = ChatService::iniciar($v, (int) $this->crearOportunidad($this->crearFundacion())['idOportunidad'], null);
        $this->expectExceptionMessage('Conversación no encontrada');
        ChatService::enviar((int) $this->crearVoluntario()['idUsuario'], $id, 'Hola');
    }

    public function testVerificarFundacion(): void
    {
        $f = $this->crearFundacion('pendiente');
        AdminService::verificarFundacion((int) $f['idFundacion'], 'verificada', null);
        $f2 = Fundacion::porId((int) $f['idFundacion']);
        $this->assertSame('verificada', $f2['estadoVerificacion']);
        $this->assertNotNull($f2['fechaVerificacion']);
    }

    public function testRechazarFundacionExigeMotivo(): void
    {
        $f = $this->crearFundacion('pendiente');
        try {
            AdminService::verificarFundacion((int) $f['idFundacion'], 'rechazada', 'corto');
            $this->fail('Debía exigir un motivo');
        } catch (ReglaNegocioException $e) {
            $this->assertStringContainsString('motivo', $e->getMessage());
        }
        AdminService::verificarFundacion((int) $f['idFundacion'], 'rechazada', 'El RUT adjunto no es legible.');
        $this->assertSame('El RUT adjunto no es legible.', Fundacion::porId((int) $f['idFundacion'])['motivoRechazo']);
    }

    public function testSuspenderYReactivarUsuario(): void
    {
        $v = $this->crearVoluntario();
        AdminService::suspender(1, (int) $v['idUsuario'], 'Comportamiento inapropiado en el chat');
        $this->assertSame(0, (int) Usuario::buscarConRol((int) $v['idUsuario'])['activo']);
        AdminService::reactivar((int) $v['idUsuario']);
        $this->assertSame(1, (int) Usuario::buscarConRol((int) $v['idUsuario'])['activo']);
    }

    public function testNoSePuedeSuspenderAUnAdministrador(): void
    {
        $this->expectExceptionMessage('administrador');
        AdminService::suspender(1, 1, 'Motivo suficientemente largo');
    }

    public function testCatalogoEnUsoNoSeElimina(): void
    {
        $this->crearVoluntario(true, [7]);
        $this->assertTrue(Catalogo::enUso('categoria', 7));
        $id = Catalogo::guardar('habilidad', ['nombre' => 'Habilidad sin uso de prueba']);
        $this->assertFalse(Catalogo::enUso('habilidad', $id));
        Catalogo::eliminar('habilidad', $id);
        $this->assertFalse(Catalogo::existe('habilidad', $id));
    }

    public function testModerarOcultaLaOportunidadYNotificaALaFundacion(): void
    {
        $f = $this->crearFundacion();
        $o = $this->crearOportunidad($f);
        AdminService::moderarOportunidad((int) $o['idOportunidad'], false, 'Contenido engañoso');
        $this->assertSame(0, (int) Oportunidad::porId((int) $o['idOportunidad'])['visible']);
        $avisos = $this->notificacionesDe((int) $f['Usuario_idUsuario']);
        $this->assertSame('Oportunidad ocultada', end($avisos)['titulo']);
    }

    public function testRegistroDeFundacionQuedaPendienteYAvisaAlAdministrador(): void
    {
        $antes = count($this->notificacionesDe(1));
        $idUsuario = \App\Services\AuthService::registrarFundacion([
            'nombre' => 'Jorge', 'apellido' => 'Díaz', 'email' => $this->correoUnico('nueva'), 'contrasena' => 'Clave2026',
            'telefono' => '3001234567', 'nit' => '901999888-1', 'razonSocial' => 'Fundación Nueva', 'descripcion' => 'Descripción de la nueva fundación de prueba.',
            'direccion' => 'Calle 2', 'ciudad' => 'Sahagún',
        ], 'documentos/nuevo.pdf');
        $this->assertSame('pendiente', Fundacion::porUsuario($idUsuario)['estadoVerificacion']);
        $this->assertCount($antes + 1, $this->notificacionesDe(1));
        $this->assertSame(0, (int) DB::valor('SELECT emailVerificado FROM usuario WHERE idUsuario = ?', [$idUsuario]));
    }
}
