<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database as DB;
use App\Models\Evaluacion;
use App\Models\Oportunidad;

final class HomeController extends Controller
{
    public function inicio(): void
    {
        $perfil = Auth::user()['perfil'] ?? null;
        [$proximas] = Oportunidad::buscar([], 1, 5, $perfil);
        $cifras = DB::fila(
            "SELECT (SELECT COUNT(*) FROM usuario WHERE Rol_idRol = 1 AND activo = 1) AS voluntarios,
                    (SELECT COUNT(*) FROM fundacion WHERE estadoVerificacion = 'verificada') AS fundaciones,
                    (SELECT COUNT(*) FROM oportunidad WHERE estado = 'abierta' AND visible = 1) AS abiertas,
                    (SELECT COALESCE(SUM(horasCumplidas), 0) FROM participacion WHERE asistio = 1) AS horas"
        );
        // Causas con el número de oportunidades abiertas en cada una.
        $causas = DB::filas(
            "SELECT c.idCategoria, c.nombre,
                    (SELECT COUNT(*) FROM oportunidad o JOIN fundacion f ON f.idFundacion = o.Fundacion_idFundacion
                     WHERE o.Categoria_idCategoria = c.idCategoria AND o.estado = 'abierta' AND o.visible = 1
                       AND f.estadoVerificacion = 'verificada') AS abiertas
             FROM categoria c ORDER BY abiertas DESC, c.nombre"
        );
        $this->vista('home/inicio', [
            'titulo' => 'Voluntariado en Córdoba',
            'proximas' => $proximas,
            'cifras' => $cifras,
            'causas' => $causas,
            'testimonio' => Evaluacion::testimonio(),
        ]);
    }

    public function privacidad(): void
    {
        $this->vista('home/privacidad', ['titulo' => 'Política de tratamiento de datos personales']);
    }
}
