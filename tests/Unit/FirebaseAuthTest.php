<?php

namespace Tests\Unit;

use App\Core\Config;
use App\Core\ReglaNegocioException;
use App\Models\Usuario;
use App\Services\FirebaseAuthService;
use Firebase\JWT\JWT;
use Tests\TestCase;

/**
 * RF2: inicio de sesión con Google (Firebase). Los tokens se firman aquí con una llave RSA
 * propia y su certificado, igual que los que publica Google, para probar sin conexión.
 */
final class FirebaseAuthTest extends TestCase
{
    private static string $llavePrivada = '';
    private static string $certificado = '';

    public static function setUpBeforeClass(): void
    {
        $opciones = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'digest_alg' => 'sha256'];
        $cnf = getenv('OPENSSL_CONF') ?: 'C:/xampp/php/extras/ssl/openssl.cnf';
        if (is_file($cnf)) {
            $opciones['config'] = $cnf;
        }
        $llave = openssl_pkey_new($opciones);
        $csr = openssl_csr_new(['commonName' => 'pruebas-voluntared'], $llave, $opciones);
        openssl_x509_export(openssl_csr_sign($csr, null, $llave, 1, $opciones), self::$certificado);
        openssl_pkey_export($llave, self::$llavePrivada, null, $opciones);
    }

    private function token(array $cambios = [], ?string $llave = null): string
    {
        $proyecto = Config::get('firebase.project_id');
        $ahora = time();
        $carga = array_replace([
            'iss' => "https://securetoken.google.com/$proyecto",
            'aud' => $proyecto,
            'auth_time' => $ahora - 5,
            'user_id' => 'uid-prueba',
            'sub' => 'uid-prueba',
            'iat' => $ahora - 5,
            'exp' => $ahora + 3600,
            'email' => 'Ana.Google@Gmail.com',
            'email_verified' => true,
            'name' => 'Ana Sofía Prueba Google',
            'picture' => 'https://lh3.googleusercontent.com/a/foto-prueba',
            'firebase' => ['sign_in_provider' => 'google.com'],
        ], $cambios);
        return JWT::encode($carga, $llave ?? self::$llavePrivada, 'RS256', 'kid-prueba');
    }

    private function verificar(string $token): array
    {
        return FirebaseAuthService::verificarToken($token, ['kid-prueba' => self::$certificado]);
    }

    public function testTokenValidoDevuelveLosDatosDeGoogle(): void
    {
        $datos = $this->verificar($this->token());
        $this->assertSame('uid-prueba', $datos['uid']);
        $this->assertSame('ana.google@gmail.com', $datos['email']);
        $this->assertSame('Ana Sofía Prueba Google', $datos['nombre']);
    }

    public function testTokenDeOtroProyectoEsRechazado(): void
    {
        $this->expectException(ReglaNegocioException::class);
        $this->verificar($this->token(['aud' => 'otro-proyecto', 'iss' => 'https://securetoken.google.com/otro-proyecto']));
    }

    public function testTokenExpiradoEsRechazado(): void
    {
        $this->expectException(ReglaNegocioException::class);
        $this->verificar($this->token(['iat' => time() - 7200, 'auth_time' => time() - 7200, 'exp' => time() - 3600]));
    }

    public function testTokenFirmadoConOtraLlaveEsRechazado(): void
    {
        $opciones = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
        $cnf = getenv('OPENSSL_CONF') ?: 'C:/xampp/php/extras/ssl/openssl.cnf';
        if (is_file($cnf)) {
            $opciones['config'] = $cnf;
        }
        openssl_pkey_export(openssl_pkey_new($opciones), $otra, null, $opciones);
        $this->expectException(ReglaNegocioException::class);
        $this->verificar($this->token([], $otra));
    }

    public function testSoloSeAceptaElProveedorGoogle(): void
    {
        $this->expectExceptionMessage('Solo se admite el inicio de sesión con Google');
        $this->verificar($this->token(['firebase' => ['sign_in_provider' => 'password']]));
    }

    public function testCorreoSinVerificarEsRechazado(): void
    {
        $this->expectExceptionMessage('correo verificado');
        $this->verificar($this->token(['email_verified' => false]));
    }

    public function testCorreoNuevoCreaUnaCuentaDeVoluntarioVerificada(): void
    {
        $email = $this->correoUnico('google');
        [$u, $nueva] = FirebaseAuthService::entrar(['uid' => 'x', 'email' => $email, 'nombre' => 'Ana Sofía Gómez Ruiz', 'foto' => null]);
        $this->assertTrue($nueva);
        $this->assertSame('voluntario', $u['rol']);
        $this->assertSame(1, (int) $u['emailVerificado']);
        $this->assertSame('Ana Sofía', $u['nombre']);
        $this->assertSame('Gómez Ruiz', $u['apellido']);
    }

    public function testCorreoExistenteEntraConSuCuentaYRol(): void
    {
        $f = $this->crearFundacion();
        $email = Usuario::buscarConRol((int) $f['Usuario_idUsuario'])['email'];
        [$u, $nueva] = FirebaseAuthService::entrar(['uid' => 'x', 'email' => $email, 'nombre' => 'Otro Nombre', 'foto' => null]);
        $this->assertFalse($nueva);
        $this->assertSame('fundacion', $u['rol']);
        $this->assertSame((int) $f['Usuario_idUsuario'], (int) $u['idUsuario']);
    }

    public function testCuentaSuspendidaNoPuedeEntrarConGoogle(): void
    {
        $v = $this->crearVoluntario();
        Usuario::actualizar((int) $v['idUsuario'], ['activo' => 0]);
        $this->expectExceptionMessage('suspendida');
        FirebaseAuthService::entrar(['uid' => 'x', 'email' => $v['email'], 'nombre' => 'Vol', 'foto' => null]);
    }
}
