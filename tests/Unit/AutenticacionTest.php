<?php

namespace Tests\Unit;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Database as DB;
use App\Core\ReglaNegocioException;
use App\Models\Usuario;
use App\Services\AuthService;
use Firebase\JWT\JWT;
use Tests\TestCase;

/** RF1 y RF2: registro, verificación de correo, JWT, bloqueo y restablecimiento de contraseña. */
final class AutenticacionTest extends TestCase
{
    public function testRegistroGuardaLaContrasenaConBcrypt(): void
    {
        $email = $this->correoUnico();
        $id = AuthService::registrarVoluntario(['nombre' => 'Ana', 'apellido' => 'Gómez', 'email' => $email, 'contrasena' => 'Clave2026']);
        $u = Usuario::buscarConRol($id);
        $this->assertSame('voluntario', $u['rol']);
        $this->assertStringStartsWith('$2y$', $u['contrasena']);
        $this->assertTrue(password_verify('Clave2026', $u['contrasena']));
        $this->assertSame(0, (int) $u['emailVerificado']);
    }

    public function testNoPermiteIniciarSesionSinVerificarElCorreo(): void
    {
        $email = $this->correoUnico();
        AuthService::registrarVoluntario(['nombre' => 'Ana', 'apellido' => 'G', 'email' => $email, 'contrasena' => 'Clave2026']);
        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage('confirmar tu correo');
        AuthService::autenticar($email, 'Clave2026');
    }

    public function testEnlaceDeVerificacionActivaLaCuenta(): void
    {
        $email = $this->correoUnico();
        $id = AuthService::registrarVoluntario(['nombre' => 'Ana', 'apellido' => 'G', 'email' => $email, 'contrasena' => 'Clave2026']);
        parse_str(parse_url(AuthService::enlaceVerificacion(Usuario::buscarConRol($id)), PHP_URL_QUERY), $q);
        AuthService::verificarCorreo((int) $q['u'], (int) $q['e'], $q['f']);
        $this->assertSame('voluntario', AuthService::autenticar($email, 'Clave2026')['rol']);
    }

    public function testEnlaceDeVerificacionAlteradoEsRechazado(): void
    {
        $v = $this->crearVoluntario();
        $this->expectException(ReglaNegocioException::class);
        AuthService::verificarCorreo((int) $v['idUsuario'], time() + 3600, str_repeat('a', 64));
    }

    public function testCredencialesIncorrectas(): void
    {
        $v = $this->crearVoluntario();
        $this->expectExceptionMessage('Correo o contraseña incorrectos.');
        AuthService::autenticar($v['email'], 'ClaveMala1');
    }

    public function testBloqueoTemporalTrasCincoIntentosFallidos(): void
    {
        $v = $this->crearVoluntario();
        for ($i = 0; $i < 4; $i++) {
            try {
                AuthService::autenticar($v['email'], 'mala');
            } catch (ReglaNegocioException) {
            }
        }
        try {
            AuthService::autenticar($v['email'], 'mala');
            $this->fail('El quinto intento debía bloquear la cuenta');
        } catch (ReglaNegocioException $e) {
            $this->assertStringContainsString('bloqueada', $e->getMessage());
        }
        // Aun con la contraseña correcta, la cuenta sigue bloqueada.
        $this->expectExceptionMessage('bloqueada temporalmente');
        AuthService::autenticar($v['email'], 'Clave2026');
    }

    public function testUsuarioSuspendidoNoPuedeIngresar(): void
    {
        $v = $this->crearVoluntario();
        Usuario::actualizar((int) $v['idUsuario'], ['activo' => 0]);
        $this->expectExceptionMessage('suspendida');
        AuthService::autenticar($v['email'], 'Clave2026');
    }

    public function testRestablecerContrasenaConCodigoTemporal(): void
    {
        $v = $this->crearVoluntario();
        AuthService::solicitarCodigo($v['email']);
        $codigo = DB::valor('SELECT resetCode FROM usuario WHERE idUsuario = ?', [$v['idUsuario']]);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $codigo);

        AuthService::restablecer($v['email'], $codigo, 'NuevaClave9');
        $this->assertSame((int) $v['idUsuario'], (int) AuthService::autenticar($v['email'], 'NuevaClave9')['idUsuario']);
        $this->assertNull(DB::valor('SELECT resetCode FROM usuario WHERE idUsuario = ?', [$v['idUsuario']]));
    }

    public function testCodigoDeRestablecimientoVencido(): void
    {
        $v = $this->crearVoluntario();
        Usuario::actualizar((int) $v['idUsuario'], ['resetCode' => '123456', 'resetCodeExpiration' => date('Y-m-d H:i:s', time() - 60)]);
        $this->expectExceptionMessage('expiró');
        AuthService::restablecer($v['email'], '123456', 'NuevaClave9');
    }

    public function testCodigoDeRestablecimientoIncorrecto(): void
    {
        $v = $this->crearVoluntario();
        AuthService::solicitarCodigo($v['email']);
        $this->expectExceptionMessage('no es válido');
        AuthService::restablecer($v['email'], 'abcdef', 'NuevaClave9');
    }

    public function testTokenJwtValidoContieneUsuarioYRol(): void
    {
        $v = $this->crearVoluntario();
        $datos = Auth::validarToken(Auth::emitirToken($v));
        $this->assertSame((int) $v['idUsuario'], $datos->sub);
        $this->assertSame('voluntario', $datos->rol);
        $this->assertGreaterThan(time(), $datos->exp);
    }

    public function testTokenJwtExpiradoEsRechazado(): void
    {
        $token = JWT::encode(['sub' => 1, 'rol' => 'voluntario', 'jti' => 'x1', 'iat' => time() - 7200, 'exp' => time() - 60],
            Config::get('app.clave'), 'HS256');
        $this->assertNull(Auth::validarToken($token));
    }

    public function testTokenJwtAlteradoEsRechazado(): void
    {
        $v = $this->crearVoluntario();
        [$h, $p, $s] = explode('.', Auth::emitirToken($v));
        $carga = json_decode(base64_decode(strtr($p, '-_', '+/')), true);
        $carga['rol'] = 'administrador';
        $falso = $h . '.' . rtrim(strtr(base64_encode(json_encode($carga)), '+/', '-_'), '=') . '.' . $s;
        $this->assertNull(Auth::validarToken($falso));
    }

    public function testTokenRevocadoAlCerrarSesionDejaDeSerValido(): void
    {
        $v = $this->crearVoluntario();
        $token = Auth::emitirToken($v);
        $this->assertNotNull(Auth::validarToken($token));
        Auth::revocar($token);
        $this->assertNull(Auth::validarToken($token));
    }
}
