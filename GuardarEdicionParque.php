<?php
session_start();

require_once __DIR__ . '/config/firebase.php';
require_once __DIR__ . '/vendor/autoload.php';

use Cloudinary\Cloudinary;

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

// Comprobar si se seleccionó una nueva fotografía
$nuevaImagen = false;
$archivo = null;

if (isset($_FILES['imagenParque'])) {
    $archivo = $_FILES['imagenParque'];

    if (!is_array($archivo)) {
        http_response_code(400);
        exit('Archivo inválido.');
    }

    $errorArchivo = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($errorArchivo !== UPLOAD_ERR_NO_FILE) {
        if ($errorArchivo !== UPLOAD_ERR_OK) {
            http_response_code(400);
            exit('Error al recibir la fotografía.');
        }

        if (
            !isset($archivo['tmp_name'], $archivo['size']) ||
            !is_string($archivo['tmp_name']) ||
            !is_uploaded_file($archivo['tmp_name']) ||
            $archivo['size'] <= 0 ||
            $archivo['size'] > 5 * 1024 * 1024
        ) {
            http_response_code(400);
            exit('La fotografía debe pesar máximo 5 MB.');
        }

        $tipo = (new finfo(FILEINFO_MIME_TYPE))
            ->file($archivo['tmp_name']);

        if (!in_array(
            $tipo,
            ['image/jpeg', 'image/png', 'image/webp'],
            true
        )) {
            http_response_code(400);
            exit('Solo se permiten imágenes JPG, PNG o WebP.');
        }

        // Comprobar que el archivo sea una imagen válida
        if (@getimagesize($archivo['tmp_name']) === false) {
            http_response_code(400);
            exit('La fotografía no es válida.');
        }

        $nuevaImagen = true;
    }
}

try {
    $database = obtenerFirebaseDatabase();

    // Verificar permisos en Firebase
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

    $parque = $referencia->getValue();

    if (!is_array($parque)) {
        http_response_code(404);
        exit('Parque no encontrado.');
    }

    // Datos que siempre se actualizarán
    $datosActualizados = [
        'nombre' => $nombre,
        'municipio' => $municipio,
        'descripcion' => $descripcion,
        'ubicacion' => $ubicacion,
        'mapsUrl' => $mapsUrl,
        'metaDonaciones' => (float)$meta
    ];

    // Solo subir a Cloudinary si hay una imagen nueva
    if ($nuevaImagen) {

        $cloudName = getenv('CLOUDINARY_CLOUD_NAME');
        $apiKey = getenv('CLOUDINARY_API_KEY');
        $apiSecret = getenv('CLOUDINARY_API_SECRET');

        if (!$cloudName || !$apiKey || !$apiSecret) {
            throw new RuntimeException(
                'Falta configurar Cloudinary.'
            );
        }

        $cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $cloudName,
                'api_key' => $apiKey,
                'api_secret' => $apiSecret
            ],
            'url' => ['secure' => true]
        ]);

        $resultado = $cloudinary->uploadApi()->upload(
            $archivo['tmp_name'],
            [
                'folder' => 'dividendo/parques',
                'resource_type' => 'image'
            ]
        );

        if (empty($resultado['secure_url'])) {
            throw new RuntimeException(
                'Cloudinary no devolvió una URL válida.'
            );
        }

        // Guardar la URL nueva
        $datosActualizados['imagenUrl'] =
            $resultado['secure_url'];

        // Guardar el identificador para futuras eliminaciones
        if (!empty($resultado['public_id'])) {
            $datosActualizados['cloudinaryPublicId'] =
                $resultado['public_id'];
        }
    }

    // Guardar cambios en Firebase
    $referencia->update($datosActualizados);

    header('Location: Admin.php?editado=1');
    exit();

} catch (Throwable $e) {

    error_log(
        'Error editando parque: ' . $e->getMessage()
    );

    http_response_code(500);
    exit('No se pudieron guardar los cambios.');
}
