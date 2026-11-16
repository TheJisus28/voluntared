<?php
/**
 * Datos iniciales y de demostración de VoluntaRed.
 * Las fechas son relativas al día de instalación para que la demostración
 * siempre tenga actividades próximas, en curso y finalizadas.
 */

use App\Core\Database as DB;

/** Ejecuta un archivo .sql sentencia por sentencia. */
function ejecutarScript(PDO $pdo, string $ruta): void
{
    $sql = preg_replace('/^\s*--.*$/m', '', file_get_contents($ruta));
    foreach (preg_split('/;\s*(\r?\n|$)/', $sql) as $sentencia) {
        if (trim($sentencia) !== '') {
            $pdo->exec($sentencia);
        }
    }
}

/** Fecha relativa a hoy: d(-30) = hace 30 días; d(10, '09:00') = dentro de 10 días a las 9. */
function d(int $dias, ?string $hora = null): string
{
    $f = date('Y-m-d', strtotime(($dias >= 0 ? '+' : '') . $dias . ' days'));
    return $hora ? "$f $hora:00" : $f;
}

function sembrarCatalogos(): void
{
    $categorias = [
        'Educación' => 'Refuerzo escolar, alfabetización, tutorías y bibliotecas comunitarias.',
        'Medio ambiente' => 'Reforestación, limpieza de fuentes hídricas, reciclaje y educación ambiental.',
        'Salud' => 'Brigadas de salud, campañas de prevención y acompañamiento a pacientes.',
        'Protección animal' => 'Refugios, adopciones, esterilización y bienestar animal.',
        'Adulto mayor' => 'Acompañamiento, recreación y cuidado de personas mayores.',
        'Niñez y juventud' => 'Recreación, lectura, prevención y liderazgo juvenil.',
        'Gestión del riesgo' => 'Prevención y atención de emergencias e inundaciones.',
        'Cultura y deporte' => 'Eventos culturales, deportivos y artísticos comunitarios.',
    ];
    foreach ($categorias as $nombre => $descripcion) {
        DB::insertar('categoria', ['nombre' => $nombre, 'descripcion' => $descripcion]);
    }
    foreach ([
        'Enseñanza y tutorías', 'Primeros auxilios', 'Logística de eventos', 'Diseño gráfico',
        'Manejo de redes sociales', 'Fotografía y video', 'Cocina', 'Programación',
        'Apoyo psicosocial', 'Recreación infantil', 'Idioma inglés', 'Construcción y reparaciones',
        'Jardinería y siembra', 'Atención al público',
    ] as $nombre) {
        DB::insertar('habilidad', ['nombre' => $nombre]);
    }
    DB::insertar('usuario', [
        'Rol_idRol' => 3, 'nombre' => 'Administrador', 'apellido' => 'VoluntaRed', 'email' => 'admin@voluntared.co',
        'contrasena' => password_hash('Admin2026', PASSWORD_BCRYPT), 'telefono' => '3001234567',
        'emailVerificado' => 1, 'fechaCreacion' => d(-300, '08:00'),
    ]);
}

function idCategoria(string $nombre): int
{
    return (int) DB::valor('SELECT idCategoria FROM categoria WHERE nombre = ?', [$nombre]);
}

function idsHabilidades(array $nombres): array
{
    return array_map(static fn ($n) => (int) DB::valor('SELECT idHabilidad FROM habilidad WHERE nombre = ?', [$n]), $nombres);
}

/** Logo SVG sencillo con las iniciales (GD no es necesario). */
function crearLogo(string $archivo, string $iniciales, string $color): string
{
    $dir = __DIR__ . '/../public/uploads/fotos';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120"><rect width="120" height="120" rx="60" fill="$color"/>
<circle cx="60" cy="60" r="46" fill="none" stroke="#ffffff" stroke-opacity=".35" stroke-width="3"/>
<text x="60" y="73" font-family="Segoe UI, Arial, sans-serif" font-size="38" font-weight="700" fill="#ffffff" text-anchor="middle">$iniciales</text></svg>
SVG;
    file_put_contents("$dir/$archivo", $svg);
    return "uploads/fotos/$archivo";
}

/** Documento soporte de demostración en PDF. */
function crearDocumento(string $razon, string $nit): string
{
    $dir = __DIR__ . '/../storage/documentos';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $t = [\App\Services\Exportador::class, 'txt'];
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Helvetica', 'B', 15);
    $pdf->Cell(0, 10, $t('CÁMARA DE COMERCIO DE MONTERÍA'), 0, 1, 'C');
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->Cell(0, 8, $t('Certificado de existencia y representación legal de entidades sin ánimo de lucro'), 0, 1, 'C');
    $pdf->Ln(8);
    $pdf->MultiCell(0, 7, $t("Razón social: $razon\nNIT: $nit\nDomicilio: Montería, Córdoba\nEstado: ACTIVA\n\nDocumento generado automáticamente para la demostración de VoluntaRed. No tiene validez legal."));
    $nombre = 'demo-' . preg_replace('/\D/', '', $nit) . '.pdf';
    $pdf->Output('F', "$dir/$nombre");
    return "documentos/$nombre";
}

function sembrarDemo(): void
{
    // ------------------------------------------------------------ fundaciones
    $fundaciones = [
        'sembrando' => ['Fundación Sembrando Futuro', '900456789-1', 'Laura', 'Martínez', 'laura@sembrandofuturo.org', '3104567890', 'Montería', 'Calle 29 # 4-55, barrio Centro',
            'Trabajamos desde 2012 por la educación de niños y jóvenes de los barrios vulnerables de Montería con refuerzo escolar, bibliotecas comunitarias y actividades recreativas.',
            'Garantizar que cada niño de nuestra comunidad tenga acompañamiento escolar y espacios seguros para aprender y jugar.', 'https://sembrandofuturo.org', 'verificada', -280, 'SF', '#1f7a4d'],
        'rio' => ['Fundación Río Sinú Verde', '901234567-3', 'Carlos', 'Pérez', 'carlos@riosinuverde.org', '3012345678', 'Montería', 'Carrera 1 # 41-20, Ronda del Sinú',
            'Organización ambiental dedicada a la conservación de la cuenca del río Sinú mediante jornadas de limpieza, reforestación con especies nativas y educación ambiental en colegios.',
            'Proteger el río Sinú y sus ecosistemas con la participación activa de la comunidad.', 'https://riosinuverde.org', 'verificada', -270, 'RS', '#2a9d8f'],
        'huellitas' => ['Corporación Huellitas de Córdoba', '900987654-2', 'Andrea', 'López', 'andrea@huellitascordoba.org', '3157894561', 'Cereté', 'Calle 12 # 15-30, Cereté',
            'Refugio y centro de bienestar animal que rescata, esteriliza y da en adopción perros y gatos en situación de calle en Cereté y Montería.',
            'Reducir el abandono animal a través del rescate, la esterilización y la adopción responsable.', null, 'verificada', -260, 'HC', '#e8772e'],
        'abuelos' => ['Fundación Abuelos Felices', '900111222-5', 'María Fernanda', 'Ruiz', 'mfruiz@abuelosfelices.org', '3205551234', 'Montería', 'Transversal 9 # 22-14, barrio La Castellana',
            'Hogar de día que brinda acompañamiento, alimentación, recreación y atención básica en salud a más de 60 adultos mayores de Montería.',
            'Dignificar la vejez con amor, compañía y cuidado.', null, 'verificada', -250, 'AF', '#6d597a'],
        'manos' => ['Fundación Manos que Ayudan', '901555666-0', 'Jorge', 'Díaz', 'jorge@manosqueayudan.org', '3016667788', 'Sahagún', 'Calle 8 # 10-21, Sahagún',
            'Brigadas de salud y entrega de medicamentos en zonas rurales del departamento de Córdoba.', null, null, 'pendiente', -3, 'MA', '#457b9d'],
        'brisas' => ['Asociación Brisas del Sinú', '900333444-8', 'Pedro', 'Suárez', 'contacto@brisasdelsinu.org', '3112223344', 'Lorica', 'Calle Real # 3-10, Lorica',
            'Asociación comunitaria de pescadores y familias ribereñas.', null, null, 'rechazada', -40, 'BS', '#b5838d'],
    ];
    $F = [];
    foreach ($fundaciones as $clave => [$razon, $nit, $nombre, $apellido, $email, $tel, $ciudad, $dir, $desc, $mision, $web, $estado, $dias, $ini, $color]) {
        $idU = DB::insertar('usuario', [
            'Rol_idRol' => 2, 'nombre' => $nombre, 'apellido' => $apellido, 'email' => $email,
            'contrasena' => password_hash('Fundacion2026', PASSWORD_BCRYPT), 'telefono' => $tel,
            'foto' => $estado === 'verificada' ? crearLogo("logo-$clave.svg", $ini, $color) : null,
            'emailVerificado' => 1, 'fechaCreacion' => d($dias, '10:15'),
        ]);
        $idF = DB::insertar('fundacion', [
            'Usuario_idUsuario' => $idU, 'nit' => $nit, 'razonSocial' => $razon, 'descripcion' => $desc, 'mision' => $mision,
            'direccion' => $dir, 'ciudad' => $ciudad, 'sitioWeb' => $web, 'documentoSoporte' => crearDocumento($razon, $nit),
            'estadoVerificacion' => $estado,
            'fechaVerificacion' => $estado === 'pendiente' ? null : d($dias + 2, '15:00'),
            'motivoRechazo' => $estado === 'rechazada' ? 'El certificado adjunto está vencido (expedido hace más de 30 días) y no corresponde al NIT registrado.' : null,
        ]);
        $F[$clave] = ['u' => $idU, 'f' => $idF];
    }

    // ------------------------------------------------------------ voluntarios
    $voluntarios = [
        'ana' => ['Ana Sofía', 'Gómez', 'ana.gomez@correo.com', '3001112233', '2001-04-12', 'fines_de_semana', 'Montería',
            'Estudiante de Licenciatura en Matemáticas de la Universidad de Córdoba. Me encanta enseñar y trabajar con niños.',
            ['Educación', 'Niñez y juventud'], ['Enseñanza y tutorías', 'Recreación infantil', 'Idioma inglés'], -230],
        'juan' => ['Juan Pablo', 'Torres', 'juanp.torres@correo.com', '3002223344', '1998-09-03', 'tardes', 'Montería',
            'Ingeniero ambiental. Participo en jornadas de limpieza y siembra desde hace tres años.',
            ['Medio ambiente', 'Gestión del riesgo'], ['Jardinería y siembra', 'Logística de eventos', 'Fotografía y video'], -220],
        'valentina' => ['Valentina', 'Herrera', 'valentina.herrera@correo.com', '3003334455', '2000-01-25', 'flexible', 'Cereté',
            'Estudiante de Medicina Veterinaria y Zootecnia. Rescato y cuido animales en mi tiempo libre.',
            ['Protección animal', 'Salud'], ['Primeros auxilios', 'Atención al público'], -210],
        'santiago' => ['Santiago', 'Ramírez', 'santiago.ramirez@correo.com', '3004445566', '2003-06-30', 'noches', 'Montería',
            'Entrenador de fútbol infantil y diseñador gráfico aficionado.',
            ['Cultura y deporte', 'Niñez y juventud'], ['Recreación infantil', 'Manejo de redes sociales', 'Diseño gráfico'], -200],
        'daniela' => ['Daniela', 'Castro', 'daniela.castro@correo.com', '3005556677', '1995-11-18', 'mananas', 'Montería',
            'Psicóloga. Me interesa el acompañamiento emocional a adultos mayores y pacientes.',
            ['Adulto mayor', 'Salud'], ['Apoyo psicosocial', 'Primeros auxilios', 'Cocina'], -190],
        'mateo' => ['Mateo', 'Vargas', 'mateo.vargas@correo.com', '3006667788', '1999-02-14', 'flexible', 'Montería',
            'Desarrollador de software. Quiero enseñar programación y matemáticas a jóvenes.',
            ['Educación'], ['Programación', 'Enseñanza y tutorías'], -120],
        'camila' => ['Camila', 'Rojas', 'camila.rojas@correo.com', '3007778899', '2002-08-09', 'fines_de_semana', 'Lorica',
            'Comunicadora social. Apoyo con fotografía y redes a causas ambientales y animalistas.',
            ['Medio ambiente', 'Protección animal'], ['Fotografía y video', 'Manejo de redes sociales'], -160],
        'andres' => ['Andrés', 'Morales', 'andres.morales@correo.com', '3008889900', '1993-03-21', 'fines_de_semana', 'Montería',
            'Socorrista de la Defensa Civil y maestro de obra.',
            ['Gestión del riesgo', 'Salud'], ['Primeros auxilios', 'Construcción y reparaciones', 'Logística de eventos'], -170],
    ];
    $V = [];
    foreach ($voluntarios as $clave => [$nombre, $apellido, $email, $tel, $nac, $disp, $ciudad, $bio, $cats, $habs, $dias]) {
        $idU = DB::insertar('usuario', [
            'Rol_idRol' => 1, 'nombre' => $nombre, 'apellido' => $apellido, 'email' => $email,
            'contrasena' => password_hash('Voluntario2026', PASSWORD_BCRYPT), 'telefono' => $tel,
            'emailVerificado' => 1, 'fechaCreacion' => d($dias, '19:30'),
        ]);
        $idP = DB::insertar('perfil_voluntario', [
            'Usuario_idUsuario' => $idU, 'fechaNacimiento' => $nac, 'biografia' => $bio,
            'disponibilidad' => $disp, 'ciudad' => $ciudad, 'departamento' => 'Córdoba',
        ]);
        foreach ($cats as $c) {
            DB::insertar('voluntario_has_categoria', ['PerfilVoluntario_id' => $idP, 'Categoria_idCategoria' => idCategoria($c)]);
        }
        foreach (idsHabilidades($habs) as $h) {
            DB::insertar('voluntario_has_habilidad', ['PerfilVoluntario_id' => $idP, 'Habilidad_idHabilidad' => $h]);
        }
        $V[$clave] = $idU;
    }
    // Voluntario recién registrado sin perfil y una cuenta suspendida (para la demostración del administrador).
    $V['luis'] = DB::insertar('usuario', [
        'Rol_idRol' => 1, 'nombre' => 'Luis', 'apellido' => 'Fernández', 'email' => 'luis.fernandez@correo.com',
        'contrasena' => password_hash('Voluntario2026', PASSWORD_BCRYPT), 'emailVerificado' => 1, 'fechaCreacion' => d(-1, '11:00'),
    ]);
    DB::insertar('usuario', [
        'Rol_idRol' => 1, 'nombre' => 'Kevin', 'apellido' => 'Pineda', 'email' => 'kevin.pineda@correo.com',
        'contrasena' => password_hash('Voluntario2026', PASSWORD_BCRYPT), 'emailVerificado' => 1, 'activo' => 0, 'fechaCreacion' => d(-90, '09:00'),
    ]);

    // ------------------------------------------------------------ oportunidades
    // [clave, fundación, categoría, título, descripción, requisitos, modalidad, ubicación, ciudad, inicio, fin, hIni, hFin, cupos, estado, publicada(días), habilidades]
    $ops = [
        ['refuerzo', 'sembrando', 'Educación', 'Refuerzo escolar en matemáticas para niños de primaria',
            "Acompaña a niños de 3.º a 5.º de primaria del barrio La Granja en sus tareas de matemáticas. Trabajamos en grupos pequeños con material didáctico preparado por la fundación.\n\nCada voluntario tendrá a cargo de 3 a 4 niños durante el periodo.",
            'Mayor de 16 años. Paciencia y gusto por enseñar. Asistir mínimo a 4 de las 6 sesiones.', 'presencial', 'Sede comunitaria barrio La Granja, Calle 50 # 12-30', 'Montería',
            d(16), d(51), '08:00', '11:00', 10, 'abierta', -6, ['Enseñanza y tutorías', 'Recreación infantil']],
        ['lectura', 'sembrando', 'Niñez y juventud', 'Club de lectura infantil «Leer es volar»',
            'Sesiones de lectura en voz alta, cuentacuentos y manualidades con niños de 5 a 10 años en la biblioteca comunitaria.',
            'Gusto por la lectura y el trabajo con niños.', 'presencial', 'Biblioteca comunitaria Sembrando Futuro, Calle 29 # 4-55', 'Montería',
            d(-82), d(-54), '09:00', '12:00', 6, 'finalizada', -100, ['Recreación infantil', 'Enseñanza y tutorías']],
        ['ingles', 'sembrando', 'Educación', 'Tutorías virtuales de inglés para bachillerato',
            'Clases de conversación y refuerzo de inglés para estudiantes de 10.º y 11.º que se preparan para las pruebas Saber 11. Las sesiones son por videollamada.',
            'Nivel de inglés B1 o superior. Computador con cámara y buena conexión.', 'virtual', null, 'Montería',
            d(12), d(54), '18:00', '19:30', 5, 'abierta', -3, ['Idioma inglés', 'Enseñanza y tutorías']],
        ['futbol', 'sembrando', 'Cultura y deporte', 'Torneo de fútbol por la paz',
            'Organización y arbitraje de un torneo relámpago de fútbol infantil con equipos de cinco barrios de Montería. Necesitamos apoyo en logística, hidratación, arbitraje y registro fotográfico.',
            null, 'presencial', 'Cancha sintética del barrio Mocarí', 'Montería',
            d(44), d(44), '07:00', '13:00', 12, 'abierta', -2, ['Logística de eventos', 'Recreación infantil', 'Fotografía y video']],
        ['limpieza', 'rio', 'Medio ambiente', 'Jornada de limpieza de la ronda del río Sinú',
            'Recolección de residuos sólidos en la margen izquierda del río Sinú, clasificación del material reciclable y registro de los residuos encontrados para el informe ambiental.',
            'Llevar gorra, bloqueador y botella de agua reutilizable. La fundación entrega guantes y bolsas.', 'presencial', 'Avenida Primera con calle 41, frente al muelle turístico', 'Montería',
            d(24), d(24), '07:00', '12:00', 30, 'abierta', -9, ['Jardinería y siembra', 'Logística de eventos']],
        ['siembra', 'rio', 'Medio ambiente', 'Siembra de 500 árboles nativos en el parque lineal',
            'Reforestación con especies nativas (ceiba, roble, guayacán) en el parque lineal Ronda del Sinú. Incluye charla de educación ambiental.',
            'Mayor de 14 años (menores con autorización).', 'presencial', 'Parque lineal Ronda del Sinú, sector norte', 'Montería',
            d(-138), d(-137), '06:30', '11:30', 25, 'finalizada', -160, ['Jardinería y siembra']],
        ['reciclaje', 'rio', 'Medio ambiente', 'Taller de reciclaje y compostaje para colegios',
            'Talleres prácticos de separación en la fuente y compostaje en cuatro instituciones educativas oficiales. Parte del contenido se dicta virtualmente.',
            null, 'mixta', 'Institución Educativa La Ribera y otras sedes', 'Montería',
            d(-37), d(-22), '14:00', '17:00', 6, 'finalizada', -58, ['Enseñanza y tutorías', 'Jardinería y siembra']],
        ['simulacro', 'rio', 'Gestión del riesgo', 'Simulacro comunitario de inundaciones',
            'Apoyo a la Defensa Civil en el simulacro de evacuación por creciente del río Sinú en el barrio Rancho Grande: señalización de rutas, puntos de encuentro y atención de participantes.',
            'Preferiblemente con curso de primeros auxilios.', 'presencial', 'Barrio Rancho Grande, punto de encuentro Parque Principal', 'Montería',
            d(38), d(38), '08:00', '12:00', 15, 'abierta', -5, ['Primeros auxilios', 'Logística de eventos']],
        ['adopcion', 'huellitas', 'Protección animal', 'Jornada de adopción y esterilización de mascotas',
            'Apoyo en la jornada de adopción responsable y esterilización gratuita: registro de asistentes, acompañamiento de las mascotas en recuperación y orientación a las familias adoptantes.',
            'No tener alergias a perros o gatos.', 'presencial', 'Parque Simón Bolívar', 'Cereté',
            d(31), d(31), '08:00', '16:00', 12, 'abierta', -4, ['Atención al público', 'Primeros auxilios']],
        ['paseadores', 'huellitas', 'Protección animal', 'Paseadores voluntarios para el refugio',
            'Paseos y socialización de los perros del refugio tres mañanas por semana durante el último trimestre del año.',
            'Compromiso mínimo de dos mañanas por semana.', 'presencial', 'Refugio Huellitas, vereda Mateo Gómez', 'Cereté',
            d(-21), d(70), '07:00', '09:00', 4, 'cerrada', -42, ['Atención al público']],
        ['vacunacion', 'huellitas', 'Salud', 'Campaña de vacunación antirrábica',
            'Apoyo a la Secretaría de Salud de Cereté en la vacunación casa a casa de perros y gatos.',
            null, 'presencial', 'Barrios del sur de Cereté', 'Cereté',
            d(-159), d(-159), '07:00', '15:00', 10, 'finalizada', -180, ['Atención al público', 'Primeros auxilios']],
        ['acompanamiento', 'abuelos', 'Adulto mayor', 'Acompañamiento a adultos mayores en el hogar de día',
            'Conversación, lectura, juegos de mesa y caminatas con los abuelos del hogar de día. Cada voluntario acompaña a un pequeño grupo durante la mañana.',
            'Calidez, paciencia y puntualidad.', 'presencial', 'Hogar de día Abuelos Felices, Transversal 9 # 22-14', 'Montería',
            d(10), d(100), '09:00', '11:00', 8, 'abierta', -8, ['Apoyo psicosocial', 'Atención al público']],
        ['navidad', 'abuelos', 'Adulto mayor', 'Celebración navideña con los abuelos',
            'Organización de la novena y la cena navideña para 60 adultos mayores: decoración, preparación de alimentos, música y entrega de regalos.',
            null, 'presencial', 'Hogar de día Abuelos Felices', 'Montería',
            d(57), d(57), '15:00', '20:00', 15, 'abierta', -1, ['Cocina', 'Logística de eventos']],
        ['brigada', 'abuelos', 'Salud', 'Brigada de salud y toma de tensión',
            'Jornada de toma de tensión arterial, glucometría y orientación en hábitos saludables para adultos mayores.',
            'Estudiantes o profesionales del área de la salud.', 'presencial', 'Hogar de día Abuelos Felices', 'Montería',
            d(-222), d(-222), '07:00', '13:00', 6, 'finalizada', -245, ['Primeros auxilios', 'Apoyo psicosocial']],
        ['manualidades', 'abuelos', 'Cultura y deporte', 'Taller de manualidades para abuelos',
            'Taller de tejido y pintura para los abuelos del hogar de día.', null, 'presencial', 'Hogar de día Abuelos Felices', 'Montería',
            d(-12), d(-12), '14:00', '17:00', 5, 'cancelada', -30, ['Diseño gráfico']],
    ];
    $O = [];
    foreach ($ops as [$clave, $fund, $cat, $titulo, $desc, $req, $mod, $ubic, $ciudad, $ini, $fin, $hi, $hf, $cupos, $estado, $pub, $habs]) {
        $id = DB::insertar('oportunidad', [
            'Fundacion_idFundacion' => $F[$fund]['f'], 'Categoria_idCategoria' => idCategoria($cat), 'titulo' => $titulo,
            'descripcion' => $desc, 'requisitos' => $req, 'modalidad' => $mod, 'ubicacion' => $ubic, 'ciudad' => $ciudad,
            'fechaInicio' => $ini, 'fechaFin' => $fin, 'horaInicio' => $hi, 'horaFin' => $hf,
            'cuposTotales' => $cupos, 'cuposDisponibles' => $cupos, 'estado' => $estado, 'fechaPublicacion' => d($pub, '10:00'),
        ]);
        foreach (idsHabilidades($habs) as $h) {
            DB::insertar('oportunidad_has_habilidad', ['Oportunidad_idOportunidad' => $id, 'Habilidad_idHabilidad' => $h]);
        }
        $O[$clave] = ['id' => $id, 'pub' => $pub, 'fu' => $F[$fund]['u']];
    }

    // ------------------------------------------------------------ postulaciones
    // [oportunidad, voluntario, estado, mensaje, días después de publicada]
    $posts = [
        ['refuerzo', 'ana', 'aceptada', 'Estudio Licenciatura en Matemáticas y tengo experiencia dando clases particulares.', 1],
        ['refuerzo', 'mateo', 'pendiente', 'Me gustaría apoyar con ejercicios de lógica y matemáticas.', 4],
        ['refuerzo', 'santiago', 'pendiente', null, 5],
        ['lectura', 'ana', 'aceptada', '¡Me encanta leerles a los niños!', 2],
        ['lectura', 'santiago', 'aceptada', null, 3],
        ['lectura', 'camila', 'aceptada', 'Puedo apoyar también con fotografías de las sesiones.', 4],
        ['lectura', 'mateo', 'rechazada', null, 10],
        ['ingles', 'ana', 'pendiente', 'Tengo nivel B2 certificado.', 1],
        ['futbol', 'santiago', 'pendiente', 'Soy entrenador de fútbol infantil, puedo arbitrar.', 1],
        ['limpieza', 'juan', 'aceptada', 'Llevo la cámara para el registro de la jornada.', 1],
        ['limpieza', 'camila', 'aceptada', null, 2],
        ['limpieza', 'andres', 'pendiente', null, 6],
        ['siembra', 'juan', 'aceptada', null, 2],
        ['siembra', 'camila', 'aceptada', null, 3],
        ['siembra', 'andres', 'aceptada', null, 5],
        ['reciclaje', 'juan', 'aceptada', null, 3],
        ['reciclaje', 'mateo', 'aceptada', 'Puedo dictar la parte virtual.', 4],
        ['simulacro', 'andres', 'aceptada', 'Soy socorrista de la Defensa Civil.', 1],
        ['simulacro', 'juan', 'pendiente', null, 2],
        ['adopcion', 'valentina', 'aceptada', 'Estudio Veterinaria y he apoyado jornadas de esterilización.', 1],
        ['adopcion', 'camila', 'pendiente', null, 3],
        ['paseadores', 'valentina', 'aceptada', null, 1],
        ['paseadores', 'camila', 'aceptada', null, 2],
        ['paseadores', 'daniela', 'aceptada', null, 3],
        ['paseadores', 'juan', 'aceptada', null, 4],
        ['paseadores', 'andres', 'pendiente', null, 6],
        ['vacunacion', 'valentina', 'aceptada', null, 2],
        ['vacunacion', 'daniela', 'aceptada', null, 4],
        ['acompanamiento', 'daniela', 'pendiente', 'Soy psicóloga y he trabajado con adultos mayores.', 2],
        ['acompanamiento', 'ana', 'cancelada', null, 3],
        ['brigada', 'daniela', 'aceptada', null, 3],
        ['brigada', 'andres', 'aceptada', null, 5],
        ['manualidades', 'santiago', 'cancelada', null, 2],
    ];
    $P = [];
    foreach ($posts as [$op, $vol, $estado, $msg, $dias]) {
        $fecha = d($O[$op]['pub'] + $dias, sprintf('%02d:%02d', 8 + $dias % 12, ($dias * 7) % 60));
        $P["$op.$vol"] = DB::insertar('postulacion', [
            'Usuario_idUsuario' => $V[$vol], 'Oportunidad_idOportunidad' => $O[$op]['id'], 'mensaje' => $msg, 'estado' => $estado,
            'fechaPostulacion' => $fecha,
            'fechaRespuesta' => $estado === 'pendiente' ? null : d($O[$op]['pub'] + $dias + 1, '17:00'),
        ]);
    }
    // Cupos disponibles = totales - aceptadas
    DB::consulta(
        "UPDATE oportunidad o SET cuposDisponibles = cuposTotales - (SELECT COUNT(*) FROM postulacion p
         WHERE p.Oportunidad_idOportunidad = o.idOportunidad AND p.estado = 'aceptada')"
    );

    // ------------------------------------------------------------ participación y evaluaciones
    // [postulación, asistió, horas, observación, calif. voluntario→fundación, comentario, calif. fundación→voluntario, comentario]
    $parts = [
        ['lectura.ana', true, 12, 'Muy creativa con las actividades de lectura.', 5, 'Excelente organización y un equipo muy acogedor. Los niños son maravillosos.', 5, 'Ana fue puntual y comprometida en todas las sesiones.'],
        ['lectura.santiago', true, 9, null, 4, 'Muy buena experiencia, solo faltó algo de material.', 4, 'Buen manejo del grupo de niños.'],
        ['lectura.camila', false, 0, 'No asistió a las sesiones y no avisó.', null, null, null, null],
        ['siembra.juan', true, 10, 'Lideró uno de los grupos de siembra.', 5, 'Jornada muy bien planificada y con un gran impacto.', 5, 'Excelente liderazgo y conocimiento ambiental.'],
        ['siembra.camila', true, 10, 'Hizo el registro fotográfico de la jornada.', 4, 'Muy bonita jornada, el horario fue exigente por el calor.', 5, 'Sus fotos fueron clave para el informe.'],
        ['siembra.andres', true, 8, null, null, null, 4, 'Muy colaborador con la logística.'],
        ['vacunacion.valentina', true, 8, null, 5, 'Aprendí muchísimo, el equipo veterinario es excelente.', 5, 'Gran conocimiento y trato con los animales.'],
        ['vacunacion.daniela', true, 8, null, 4, 'Bien organizada, aunque la jornada fue larga.', null, null],
        ['brigada.daniela', true, 6, 'Apoyó en la orientación psicosocial.', 5, 'Los abuelos son encantadores, volveré.', 5, 'Muy empática con los adultos mayores.'],
        ['brigada.andres', true, 6, null, 4, 'Buena experiencia.', null, null],
    ];
    foreach ($parts as [$clave, $asistio, $horas, $obs, $cv, $comV, $cf, $comF]) {
        [$op, $vol] = explode('.', $clave);
        $fechaFin = DB::valor('SELECT fechaFin FROM oportunidad WHERE idOportunidad = ?', [$O[$op]['id']]);
        $base = strtotime($fechaFin);
        $idPa = DB::insertar('participacion', [
            'Postulacion_idPostulacion' => $P[$clave], 'asistio' => (int) $asistio, 'horasCumplidas' => $horas,
            'observacion' => $obs, 'fechaRegistro' => date('Y-m-d 18:00:00', $base + 86400),
        ]);
        if ($cv) {
            DB::insertar('evaluacion', [
                'Participacion_idParticipacion' => $idPa, 'evaluador_id' => $V[$vol], 'evaluado_id' => $O[$op]['fu'],
                'calificacion' => $cv, 'comentario' => $comV, 'fecha' => date('Y-m-d 20:00:00', $base + 2 * 86400),
            ]);
        }
        if ($cf) {
            DB::insertar('evaluacion', [
                'Participacion_idParticipacion' => $idPa, 'evaluador_id' => $O[$op]['fu'], 'evaluado_id' => $V[$vol],
                'calificacion' => $cf, 'comentario' => $comF, 'fecha' => date('Y-m-d 19:00:00', $base + 86400),
            ]);
        }
    }

    // ------------------------------------------------------------ conversaciones
    $chats = [
        ['ana', 'sembrando', 'refuerzo', [
            ['v', 'Hola, buenas tardes. ¿Las sesiones de refuerzo son todos los sábados?', -5, '15:10'],
            ['f', '¡Hola, Ana! Sí, todos los sábados de 8:00 a 11:00 a. m. durante cinco semanas.', -5, '16:02'],
            ['v', 'Perfecto. ¿Debo llevar algún material?', -5, '16:05'],
            ['f', 'No es necesario, nosotros entregamos las guías y los útiles. Solo trae mucha energía 😊', -4, '09:30'],
            ['v', '¡Genial! Allá estaré. Muchas gracias.', -4, '09:41'],
        ]],
        ['juan', 'rio', 'limpieza', [
            ['v', 'Buenos días. ¿Hay punto de hidratación durante la jornada de limpieza?', -2, '08:20'],
            ['f', 'Hola, Juan Pablo. Sí, tendremos un punto de hidratación y refrigerio a las 10:00 a. m.', -2, '10:45'],
            ['v', 'Excelente, ¿puedo llevar a un amigo que no está registrado?', -1, '18:12'],
        ]],
        ['valentina', 'huellitas', 'adopcion', [
            ['f', 'Hola, Valentina. Te aceptamos en la jornada de adopción. ¿Podrías llegar a las 7:30 a. m. para ayudar con el montaje?', -2, '11:00'],
            ['v', '¡Claro que sí! Allá estaré a las 7:30.', -2, '12:15'],
        ]],
    ];
    foreach ($chats as [$vol, $fund, $op, $mensajes]) {
        $idC = DB::insertar('conversacion', [
            'voluntario_id' => $V[$vol], 'fundacion_id' => $F[$fund]['u'], 'Oportunidad_idOportunidad' => $O[$op]['id'],
            'fechaCreacion' => d($mensajes[0][2], $mensajes[0][3]),
        ]);
        $n = count($mensajes);
        foreach ($mensajes as $i => [$quien, $texto, $dias, $hora]) {
            DB::insertar('mensaje', [
                'Conversacion_idConversacion' => $idC, 'emisor_id' => $quien === 'v' ? $V[$vol] : $F[$fund]['u'],
                'contenido' => $texto, 'leido' => $i < $n - 1 ? 1 : 0, 'fechaEnvio' => d($dias, $hora),
            ]);
        }
    }

    // ------------------------------------------------------------ notificaciones
    $notifs = [
        [$V['ana'], 'respuesta', '¡Tu postulación fue aceptada!', 'Fundación Sembrando Futuro aceptó tu postulación a «Refuerzo escolar en matemáticas para niños de primaria».', '/postulaciones', 0, -4],
        [$V['ana'], 'oportunidad', 'Nueva oportunidad para ti', 'Fundación Sembrando Futuro publicó «Tutorías virtuales de inglés para bachillerato» en Montería, afín a tus intereses.', '/oportunidades/' . $O['ingles']['id'], 0, -3],
        [$V['ana'], 'mensaje', 'Nuevo mensaje de Fundación Sembrando Futuro', 'No es necesario, nosotros entregamos las guías y los útiles.', '/mensajes/1', 1, -4],
        [$V['ana'], 'evaluacion', 'Recibiste una evaluación', 'Fundación Sembrando Futuro te calificó con 5 de 5 estrellas por «Club de lectura infantil «Leer es volar»».', '/historial', 1, -50],
        [$F['sembrando']['u'], 'postulacion', 'Nueva postulación', 'Mateo Vargas se postuló a «Refuerzo escolar en matemáticas para niños de primaria».', '/fundacion/oportunidades/' . $O['refuerzo']['id'] . '/postulaciones', 0, -2],
        [$F['sembrando']['u'], 'postulacion', 'Nueva postulación', 'Santiago Ramírez se postuló a «Torneo de fútbol por la paz».', '/fundacion/oportunidades/' . $O['futbol']['id'] . '/postulaciones', 0, -1],
        [$F['rio']['u'], 'mensaje', 'Nuevo mensaje de Juan Pablo Torres', 'Excelente, ¿puedo llevar a un amigo que no está registrado?', '/mensajes/2', 0, -1],
        [$F['rio']['u'], 'postulacion', 'Nueva postulación', 'Andrés Morales se postuló a «Jornada de limpieza de la ronda del río Sinú».', '/fundacion/oportunidades/' . $O['limpieza']['id'] . '/postulaciones', 0, -3],
        [1, 'sistema', 'Nueva fundación por verificar', 'Fundación Manos que Ayudan (NIT 901555666-0) se registró y espera la revisión de su documento soporte.', '/admin/fundaciones', 0, -3],
        [$F['brisas']['u'], 'sistema', 'Verificación rechazada', 'La verificación de Asociación Brisas del Sinú fue rechazada. Motivo: El certificado adjunto está vencido.', '/fundacion/perfil', 0, -38],
    ];
    foreach ($notifs as [$u, $tipo, $titulo, $contenido, $enlace, $leida, $dias]) {
        DB::insertar('notificacion', [
            'Usuario_idUsuario' => $u, 'tipo' => $tipo, 'titulo' => $titulo, 'contenido' => $contenido,
            'enlace' => $enlace, 'leida' => $leida, 'fechaCreacion' => d($dias, '12:00'),
        ]);
    }
}
