<?php
/**
 * Configuración general de VoluntaRed.
 * Cualquier valor puede sobrescribirse con una variable de entorno (VR_*),
 * lo que permite usar otra base de datos en las pruebas o en producción.
 */

$env = static fn (string $clave, $defecto) => getenv($clave) !== false ? getenv($clave) : $defecto;

return [
    'app' => [
        'nombre'       => 'VoluntaRed',
        // 'local' habilita la bandeja de correos de desarrollo (/dev/correos)
        'entorno'      => $env('VR_ENTORNO', 'local'),
        'zona_horaria' => 'America/Bogota',
        // Clave para firmar los JWT y los enlaces de verificación (mínimo 32 caracteres).
        'clave'        => $env('VR_CLAVE', 'vr-2026-cambie-esta-clave-en-produccion-9f2c7a1e5b8d4f60a3e1c7b9d2f4a6e8'),
    ],

    'db' => [
        'host'    => $env('VR_DB_HOST', '127.0.0.1'),
        'puerto'  => (int) $env('VR_DB_PUERTO', 3306),
        'nombre'  => $env('VR_DB_NOMBRE', 'voluntared'),
        'usuario' => $env('VR_DB_USUARIO', 'root'),
        'clave'   => $env('VR_DB_CLAVE', ''),
    ],

    'jwt' => [
        'cookie'           => 'vr_token',
        'expiracion_horas' => 8,
    ],

    'correo' => [
        // 'log': guarda los correos en storage/correos (desarrollo).
        // 'smtp': los envía por el servidor SMTP configurado abajo.
        'driver'           => $env('VR_CORREO_DRIVER', 'log'),
        'host'             => $env('VR_SMTP_HOST', 'smtp.gmail.com'),
        'puerto'           => (int) $env('VR_SMTP_PUERTO', 587),
        'usuario'          => $env('VR_SMTP_USUARIO', ''),
        'clave'            => $env('VR_SMTP_CLAVE', ''),
        'cifrado'          => $env('VR_SMTP_CIFRADO', 'tls'),
        'remitente'        => $env('VR_SMTP_REMITENTE', 'no-responder@voluntared.co'),
        'nombre_remitente' => 'VoluntaRed',
    ],

    // Firebase Authentication: solo se usa para «Continuar con Google».
    // Son datos públicos de la app web (no son secretos); vaciar project_id oculta el botón.
    'firebase' => [
        'api_key'     => $env('VR_FIREBASE_API_KEY', 'AIzaSyA72DQNWn8pBPtBI_gq4x5qBLsYmaP76bU'),
        'auth_domain' => $env('VR_FIREBASE_AUTH_DOMAIN', 'voluntared-unicordoba.firebaseapp.com'),
        'project_id'  => $env('VR_FIREBASE_PROJECT_ID', 'voluntared-unicordoba'),
        'app_id'      => $env('VR_FIREBASE_APP_ID', '1:443720951413:web:5eaec498ec86b54d6c7a3c'),
    ],

    'seguridad' => [
        'max_intentos'       => 5,   // intentos fallidos antes del bloqueo temporal
        'minutos_bloqueo'    => 15,
        'minutos_codigo'     => 15,  // vigencia del código de restablecimiento
        'horas_verificacion' => 48,  // vigencia del enlace de verificación de correo
    ],

    'subidas' => [
        'max_mb' => 5,
    ],

    'paginacion' => 9,
];
