<?php
/**
 * Funciones auxiliares globales usadas por controladores y vistas.
 */

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Flash;

/** Escapa texto para imprimirlo en HTML (previene XSS). */
function e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function config(string $clave, mixed $defecto = null): mixed
{
    return Config::get($clave, $defecto);
}

/** Ruta base de la aplicación (vacía con el servidor embebido, /voluntared en XAMPP). */
function base_path_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    if (PHP_SAPI === 'cli' && empty($_SERVER['SCRIPT_NAME'])) {
        return $base = '';
    }
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $base = rtrim($script, '/');
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    // Si el .htaccess raíz reescribe /voluntared/* hacia /voluntared/public/*, se oculta "/public".
    if ($base !== '' && str_ends_with($base, '/public') && !str_starts_with($uri, $base)) {
        $base = substr($base, 0, -7);
    }
    return $base;
}

function url(string $ruta = '', array $query = []): string
{
    $ruta = '/' . ltrim($ruta, '/');
    $q = $query ? '?' . http_build_query(array_filter($query, static fn ($v) => $v !== null && $v !== '')) : '';
    return base_path_url() . ($ruta === '/' ? '/' : $ruta) . ($q === '?' ? '' : $q);
}

function asset(string $ruta): string
{
    $archivo = __DIR__ . '/../public/assets/' . ltrim($ruta, '/');
    $version = is_file($archivo) ? '?v=' . filemtime($archivo) : '';
    return base_path_url() . '/assets/' . ltrim($ruta, '/') . $version;
}

/** URL pública de un archivo subido (foto o logo). */
function upload_url(?string $ruta): ?string
{
    if (!$ruta) {
        return null;
    }
    if (preg_match('#^https?://#', $ruta)) {
        return $ruta;
    }
    return base_path_url() . '/' . ltrim($ruta, '/');
}

function redirect(string $ruta, array $query = []): never
{
    $destino = preg_match('#^https?://#', $ruta) ? $ruta : url($ruta, $query);
    header('Location: ' . $destino);
    exit;
}

function back(): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($ref && parse_url($ref, PHP_URL_HOST) === parse_url('http://' . $host, PHP_URL_HOST)) {
        header('Location: ' . $ref);
        exit;
    }
    redirect('/');
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function old(string $campo, mixed $defecto = ''): mixed
{
    return Flash::old($campo, $defecto);
}

function usuario_actual(): ?array
{
    return Auth::user();
}

function es_rol(string ...$roles): bool
{
    $u = Auth::user();
    return $u !== null && in_array($u['rol'], $roles, true);
}

function input(string $clave, mixed $defecto = null): mixed
{
    $valor = $_POST[$clave] ?? $_GET[$clave] ?? $defecto;
    return is_string($valor) ? trim($valor) : $valor;
}

/** Mensaje de error de validación para un campo (usado en formularios). */
function error_campo(string $campo): ?string
{
    return Flash::errores()[$campo] ?? null;
}

function clase_invalida(string $campo): string
{
    return error_campo($campo) ? ' is-invalid' : '';
}

// ---------------------------------------------------------------- formatos

const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
const MESES_CORTOS = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

function fecha(?string $valor, bool $conHora = false): string
{
    if (!$valor) {
        return '—';
    }
    $t = strtotime($valor);
    $txt = date('j', $t) . ' de ' . MESES[(int) date('n', $t) - 1] . ' de ' . date('Y', $t);
    return $conHora ? $txt . ', ' . date('g:i a', $t) : $txt;
}

function fecha_corta(?string $valor): string
{
    if (!$valor) {
        return '—';
    }
    $t = strtotime($valor);
    return date('j', $t) . ' ' . MESES_CORTOS[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
}

function hora(?string $valor): string
{
    return $valor ? date('g:i a', strtotime($valor)) : '—';
}

function hace(?string $valor): string
{
    if (!$valor) {
        return '';
    }
    $seg = time() - strtotime($valor);
    return match (true) {
        $seg < 60 => 'hace un momento',
        $seg < 3600 => 'hace ' . intdiv($seg, 60) . ' min',
        $seg < 86400 => 'hace ' . intdiv($seg, 3600) . ' h',
        $seg < 604800 => 'hace ' . intdiv($seg, 86400) . ' d',
        default => fecha_corta($valor),
    };
}

function horas(float|string|null $valor): string
{
    $n = (float) $valor;
    return rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',') . ' h';
}

function iniciales(string $nombre, ?string $apellido = null): string
{
    $a = mb_substr($nombre, 0, 1);
    $b = $apellido ? mb_substr($apellido, 0, 1) : mb_substr(explode(' ', $nombre)[1] ?? '', 0, 1);
    return mb_strtoupper($a . $b);
}

function recortar(?string $texto, int $largo = 140): string
{
    $texto = trim((string) $texto);
    return mb_strlen($texto) > $largo ? rtrim(mb_substr($texto, 0, $largo)) . '…' : $texto;
}

// ---------------------------------------------------------------- etiquetas de catálogos

function etiqueta(string $tipo, ?string $valor): string
{
    $mapas = [
        'disponibilidad' => [
            'mananas' => 'Mañanas', 'tardes' => 'Tardes', 'noches' => 'Noches',
            'fines_de_semana' => 'Fines de semana', 'flexible' => 'Flexible',
        ],
        'modalidad' => ['presencial' => 'Presencial', 'virtual' => 'Virtual', 'mixta' => 'Mixta'],
        'estado_oportunidad' => [
            'abierta' => 'Abierta', 'cerrada' => 'Cerrada', 'finalizada' => 'Finalizada', 'cancelada' => 'Cancelada',
        ],
        'estado_postulacion' => [
            'pendiente' => 'Pendiente', 'aceptada' => 'Aceptada', 'rechazada' => 'Rechazada', 'cancelada' => 'Cancelada',
        ],
        'verificacion' => ['pendiente' => 'Pendiente', 'verificada' => 'Verificada', 'rechazada' => 'Rechazada'],
        'rol' => ['voluntario' => 'Voluntario', 'fundacion' => 'Fundación', 'administrador' => 'Administrador'],
    ];
    return $mapas[$tipo][$valor] ?? (string) $valor;
}

function badge_estado(string $tipo, ?string $valor): string
{
    return '<span class="estado estado-' . e($valor) . '">' . e(etiqueta($tipo, $valor)) . '</span>';
}

/** Fecha en bloque de calendario (día y mes) para listados de actividades. */
function bloque_fecha(?string $valor): string
{
    if (!$valor) {
        return '';
    }
    $t = strtotime($valor);
    return '<div class="oport-fecha" aria-hidden="true"><span class="mes">' . MESES_CORTOS[(int) date('n', $t) - 1]
        . '</span><span class="dia">' . date('j', $t) . '</span></div>';
}

/** Estrellas de calificación accesibles (texto alternativo para lectores de pantalla). */
function estrellas(float|string|null $promedio, bool $mostrarNumero = true): string
{
    if ($promedio === null || (float) $promedio <= 0) {
        return '<span class="text-body-secondary small">Sin calificaciones</span>';
    }
    $p = round((float) $promedio, 1);
    $html = '<span class="estrellas" role="img" aria-label="Calificación ' . str_replace('.', ',', (string) $p) . ' de 5">';
    for ($i = 1; $i <= 5; $i++) {
        $icono = $p >= $i ? 'bi-star-fill' : ($p >= $i - 0.5 ? 'bi-star-half' : 'bi-star');
        $html .= '<i class="bi ' . $icono . '" aria-hidden="true"></i>';
    }
    $html .= '</span>';
    if ($mostrarNumero) {
        $html .= ' <span class="small fw-semibold">' . number_format($p, 1, ',', '') . '</span>';
    }
    return $html;
}

function avatar(?string $foto, string $nombre, ?string $apellido = null, string $tam = 'md'): string
{
    $clase = 'avatar avatar-' . $tam;
    if ($foto) {
        return '<img src="' . e(upload_url($foto)) . '" alt="Foto de ' . e(trim($nombre . ' ' . $apellido)) . '" class="' . $clase . '">';
    }
    return '<span class="' . $clase . ' avatar-iniciales" aria-hidden="true">' . e(iniciales($nombre, $apellido)) . '</span>';
}

const DEPARTAMENTOS = [
    'Amazonas', 'Antioquia', 'Arauca', 'Atlántico', 'Bogotá D.C.', 'Bolívar', 'Boyacá', 'Caldas', 'Caquetá',
    'Casanare', 'Cauca', 'Cesar', 'Chocó', 'Córdoba', 'Cundinamarca', 'Guainía', 'Guaviare', 'Huila',
    'La Guajira', 'Magdalena', 'Meta', 'Nariño', 'Norte de Santander', 'Putumayo', 'Quindío', 'Risaralda',
    'San Andrés y Providencia', 'Santander', 'Sucre', 'Tolima', 'Valle del Cauca', 'Vaupés', 'Vichada',
];

/** Imprime un campo de formulario accesible (ver Views/partials/campo.php). */
function campo(array $parametros): void
{
    \App\Core\View::parcial('campo', $parametros);
}
