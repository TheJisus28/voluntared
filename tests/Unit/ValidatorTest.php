<?php

namespace Tests\Unit;

use App\Core\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Validación de formularios (entradas del sistema, sección 2.1.1). */
final class ValidatorTest extends TestCase
{
    public function testDatosValidosNoProducenErrores(): void
    {
        $errores = Validator::validar(
            ['nombre' => 'Ana', 'email' => 'ana@correo.com', 'contrasena' => 'Clave2026'],
            ['nombre' => 'requerido|max:100', 'email' => 'requerido|email', 'contrasena' => 'requerido|contrasena']
        );
        $this->assertSame([], $errores);
    }

    public function testCampoRequeridoVacio(): void
    {
        $errores = Validator::validar(['nombre' => '   '], ['nombre' => 'requerido']);
        $this->assertSame('El campo nombre es obligatorio.', $errores['nombre']);
    }

    public function testCampoOpcionalVacioSeOmite(): void
    {
        $this->assertSame([], Validator::validar(['telefono' => ''], ['telefono' => 'telefono']));
    }

    #[DataProvider('contrasenasInvalidas')]
    public function testPoliticaDeContrasena(string $clave): void
    {
        $errores = Validator::validar(['contrasena' => $clave], ['contrasena' => 'contrasena']);
        $this->assertArrayHasKey('contrasena', $errores);
    }

    public static function contrasenasInvalidas(): array
    {
        return ['muy corta' => ['Ab1'], 'sin números' => ['SoloLetras'], 'sin letras' => ['12345678']];
    }

    public function testCorreoInvalido(): void
    {
        $this->assertArrayHasKey('email', Validator::validar(['email' => 'ana@'], ['email' => 'email']));
    }

    public function testFormatoDeNit(): void
    {
        $this->assertSame([], Validator::validar(['nit' => '900123456-7'], ['nit' => 'nit']));
        $this->assertArrayHasKey('nit', Validator::validar(['nit' => '12AB'], ['nit' => 'nit']));
    }

    public function testFechaFinAnteriorAInicio(): void
    {
        $errores = Validator::validar(
            ['fechaInicio' => '2026-11-10', 'fechaFin' => '2026-11-09'],
            ['fechaFin' => 'fecha|despues_o_igual:fechaInicio']
        );
        $this->assertStringContainsString('igual o posterior', $errores['fechaFin']);
    }

    public function testHoraFinDebeSerPosterior(): void
    {
        $errores = Validator::validar(['horaInicio' => '10:00', 'horaFin' => '10:00'], ['horaFin' => 'hora|hora_despues:horaInicio']);
        $this->assertArrayHasKey('horaFin', $errores);
    }

    public function testCasillaDeAutorizacionDeDatosObligatoria(): void
    {
        $this->assertArrayHasKey('acepta', Validator::validar([], ['acepta' => 'acepta']));
        $this->assertSame([], Validator::validar(['acepta' => '1'], ['acepta' => 'acepta']));
    }

    public function testEdadMinimaDelVoluntario(): void
    {
        $nacimiento = date('Y-m-d', strtotime('-10 years'));
        $this->assertArrayHasKey('fechaNacimiento', Validator::validar(['fechaNacimiento' => $nacimiento], ['fechaNacimiento' => 'fecha|edad_minima:14']));
    }

    public function testConfirmacionDeContrasena(): void
    {
        $errores = Validator::validar(['contrasena' => 'Clave2026', 'confirmacion' => 'Otra2026'], ['confirmacion' => 'igual:contrasena']);
        $this->assertSame('Las contraseñas no coinciden.', $errores['confirmacion']);
    }

    public function testValoresPermitidosEnEnum(): void
    {
        $this->assertArrayHasKey('modalidad', Validator::validar(['modalidad' => 'remota'], ['modalidad' => 'en:presencial,virtual,mixta']));
    }
}
