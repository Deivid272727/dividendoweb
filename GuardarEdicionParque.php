
<?php
session_start();
require_once __DIR__ . '/config/firebase.php';

if (empty($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

// Validar token CSRF
$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    empty($_SESSION['csrf_admin']) ||
    !hash_equals($_SESSION['csrf_admin'], $token)
) {
    http_response_code(403);
    exit('Solicitud no autorizada.');
}

function datoTexto(string $campo): string {
    $valor = $_POST[$campo] ?? '';
    return is_string($valor) ? trim($valor) : '';
}

$id = datoTexto('id');
$nombre = datoTexto('nombre');
$municipio = datoTexto('municipio');
$descripcion = datoTexto('descripcion');
$ubicacion = datoTexto('ubicacion');
$mapsUrl = datoTexto('mapsUrl');

$metaTexto = datoTexto('metaDonaciones');
$meta = filter_var($metaTexto, FILTER_VALIDATE_FLOAT);

// Validar ID de Firebase
if (
    $id === '' ||
    preg_match('/[.#$\[\]\/\x00-\x1F\x7F]/', $id)
) {
    http_response_code(400);
    exit('ID de parque inválido.');
}

// Validar campos
if (
    $nombre === '' ||
    mb_strlen($nombre) > 120 ||
    $municipio === '' ||
    mb_strlen($municipio) > 100 ||
    $descripcion === '' ||
    $ubicacion === '' ||
    $meta === false ||
    !is_finite($meta) ||
    $meta <= 0
) {
    http_response_code(400);
    exit('Revisa los datos del parque.');
}

// Validar URL de Google Maps
if ($mapsUrl !== '') {
    $urlValida = filter_var($mapsUrl, FILTER_VALIDATE_URL);
    $esHttps = strtolower(
        parse_url($mapsUrl, PHP_URL_SCHEME) ?? ''
    ) === 'https';

    if (!$urlValida || !$esHttps) {
        http_response_code(400);
        exit('La URL de Google Maps debe ser HTTPS.');
    }
}

try {
    $database = obtenerFirebaseDatabase();

    // Comprobar permisos en Firebase
    $rol = $database
        ->getReference(
            'users/' . $_SESSION['user_id'] . '/rol'
        )
        ->getValue();

    if ($rol !== 'admin') {
        http_response_code(403);
        exit('Acceso denegado.');
    }

    $referencia = $database
        ->getReference('parques/' . $id);

    // Verificar que el parque existe
    $parque = $referencia->getValue();

    if (!is_array($parque)) {
        http_response_code(404);
        exit('Parque no encontrado.');
    }

    // Actualizar únicamente campos editables
    $referencia->update([
        'nombre' => $nombre,
        'municipio' => $municipio,
        'descripcion' => $descripcion,
        'ubicacion' => $ubicacion,
        'mapsUrl' => $mapsUrl,
        'metaDonaciones' => (float)$meta
    ]);

    header('Location: Admin.php?editado=1');
    exit();

} catch (Throwable $e) {
    error_log(
        'Error editando parque: ' . $e->getMessage()
    );

    http_response_code(500);
    exit('No se pudieron guardar los cambios.');
}
