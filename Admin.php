
<?php
session_start();
if (empty($_SESSION['csrf_admin'])) {
    $_SESSION['csrf_admin'] = bin2hex(random_bytes(32));
}
require_once __DIR__ . '/config/firebase.php';

// Verificar que exista una sesión
if (empty($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

$id_usuario = $_SESSION['user_id'];

try {
    $database = obtenerFirebaseDatabase();

    // Consultar el rol directamente desde Firebase
    $rol = $database
        ->getReference('users/' . $id_usuario . '/rol')
        ->getValue();

    // Solamente los administradores pueden entrar
    if ($rol !== 'admin') {
        http_response_code(403);
        exit("Acceso denegado. No tienes permisos de administrador.");
    }

    $_SESSION['user_rol'] = 'admin';

} catch (Throwable $e) {
    error_log(
        'Error verificando administrador: ' . $e->getMessage()
    );

    http_response_code(500);
    exit("No se pudieron verificar los permisos.");
}

// Temporalmente mantenemos MySQL para el resto del panel
require_once 'bd/conexion.php';

// Obtener parques desde Firebase
$parques = $database
    ->getReference('parques')
    ->getValue() ?? [];



// Procesar el formulario de agregar parque
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['agregar_parque'])) {

    // Validar token CSRF
    $tokenRecibido = $_POST['csrf_token'] ?? '';

    if (!is_string($tokenRecibido) ||
        !hash_equals($_SESSION['csrf_admin'], $tokenRecibido)) {
        http_response_code(403);
        exit('Solicitud no autorizada.');
    }

    // Obtener datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $municipio = trim($_POST['municipio'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $imagenUrl = trim($_POST['imagenUrl'] ?? '');
    $mapsUrl = trim($_POST['mapsUrl'] ?? '');

    $meta = filter_var(
        $_POST['metaDonaciones'] ?? null,
        FILTER_VALIDATE_FLOAT
    );

    $latitudTexto = trim($_POST['latitud'] ?? '');
    $longitudTexto = trim($_POST['longitud'] ?? '');

    
$municipiosPermitidos = [
    'Abasolo', 'Agualeguas', 'Los Aldamas', 'Allende',
    'Anáhuac', 'Apodaca', 'Aramberri', 'Bustamante',
    'Cadereyta Jiménez', 'El Carmen', 'Cerralvo',
    'Ciénega de Flores', 'China', 'Doctor Arroyo',
    'Doctor Coss', 'Doctor González', 'Galeana',
    'García', 'General Bravo', 'General Escobedo',
    'General Terán', 'General Treviño',
    'General Zaragoza', 'General Zuazua',
    'Guadalupe', 'Los Herreras', 'Hidalgo',
    'Higueras', 'Hualahuises', 'Iturbide',
    'Juárez', 'Lampazos de Naranjo', 'Linares',
    'Marín', 'Melchor Ocampo', 'Mier y Noriega',
    'Mina', 'Montemorelos', 'Monterrey', 'Parás',
    'Pesquería', 'Los Ramones', 'Rayones',
    'Sabinas Hidalgo', 'Salinas Victoria',
    'San Nicolás de los Garza',
    'San Pedro Garza García', 'Santa Catarina',
    'Santiago', 'Vallecillo', 'Villaldama'
];


    // Validaciones
    if (
        $nombre === '' ||
        mb_strlen($nombre) > 120 ||
        $descripcion === '' ||
        $ubicacion === '' ||
        !in_array($municipio, $municipiosPermitidos, true) ||
        $meta === false ||
        !is_finite($meta) ||
        $meta <= 0
    ) {
        http_response_code(400);
        exit('Revisa los datos del parque.');
    }

    foreach ([$imagenUrl, $mapsUrl] as $url) {
        if ($url !== '' &&
            (!filter_var($url, FILTER_VALIDATE_URL) ||
             !in_array(
                 strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''),
                 ['https'],
                 true
             ))) {
            http_response_code(400);
            exit('Las URL deben ser HTTPS válidas.');
        }
    }

    $latitud = null;
    $longitud = null;

    if ($latitudTexto !== '') {
        $latitud = filter_var($latitudTexto, FILTER_VALIDATE_FLOAT);
        if ($latitud === false || $latitud < -90 || $latitud > 90) {
            http_response_code(400);
            exit('Latitud inválida.');
        }
    }

    if ($longitudTexto !== '') {
        $longitud = filter_var($longitudTexto, FILTER_VALIDATE_FLOAT);
        if ($longitud === false || $longitud < -180 || $longitud > 180) {
            http_response_code(400);
            exit('Longitud inválida.');
        }
    }

    try {
        // Crear referencia con ID único
        $referencia = $database
            ->getReference('parques')
            ->push();

        $idParque = $referencia->getKey();

        // Mantener la estructura que utiliza Android
        $nuevoParque = [
            'id' => $idParque,
            'nombre' => $nombre,
            'municipio' => $municipio,
            'descripcion' => $descripcion,
            'ubicacion' => $ubicacion,
            'imagenUrl' => $imagenUrl,
            'mapsUrl' => $mapsUrl,
            'metaDonaciones' => (float)$meta,
            'montoRecaudado' => 0
        ];

        if ($latitud !== null) {
            $nuevoParque['latitud'] = $latitud;
        }

        if ($longitud !== null) {
            $nuevoParque['longitud'] = $longitud;
        }

        // Guardar en Firebase
        $referencia->set($nuevoParque);

        // Evitar duplicados al actualizar la página
        header('Location: Admin.php?agregado=1');
        exit();

    } catch (Throwable $e) {
        error_log(
            'Error agregando parque: ' . $e->getMessage()
        );

        http_response_code(500);
        exit('No se pudo guardar el parque.');
    }
}
?>


<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel de Administración</title>

    <link rel="stylesheet" href="css/admin.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>

<body>

<?php
/* Si ya tienes un navbar o sidebar inclúyelo aquí */
// include 'navbar.php';
?>

<div class="admin-container">

<?php if (isset($_GET['agregado'])): ?>
            <p style="color:#00e676; font-weight:bold;">
                ¡Parque agregado correctamente en Firebase!
            </p>
        <?php endif; ?>

        <?php if (isset($_GET['editado'])): ?>
            <p style="color:#00e676; font-weight:bold;">
                ¡Parque actualizado correctamente!
            </p>
        <?php endif; ?>
        <?php if (isset($_GET['eliminado'])): ?>
    <p style="color:#00e676; font-weight:bold;">
        ¡Parque eliminado correctamente!
    </p>
<?php endif; ?>
   <div class="header">

    <div>

        <h1>
            <i class="fa-solid fa-user-shield"></i>
            Panel de Administración
        </h1>

        <p>
            Bienvenido
            <span class="usuario">
                <?php echo $_SESSION['user_name']; ?>
            </span>
        </p>

    </div>

   <div class="header-buttons">

    <a href="index.php" class="btn-regresar">

        <i class="fa-solid fa-arrow-left"></i>

        Regresar

    </a>

    <button id="toggle-dark" class="modo-btn">

        <i class="fa-solid fa-moon"></i>

    </button>

</div>

</div>

    </div>

    <div class="card">

        <h2>
            <i class="fa-solid fa-tree"></i>
            Agregar Parque
        </h2>

        
<form method="POST"
      id="formAgregarParque"
      enctype="multipart/form-data">
      
<input
    type="hidden"
    name="csrf_token"
    value="<?php echo htmlspecialchars($_SESSION['csrf_admin'], ENT_QUOTES, 'UTF-8'); ?>"
>
    <div class="grupo">
        <label>Nombre del parque</label>
        <input type="text" name="nombre" maxlength="120" required>
    </div>

    <div class="grupo">
        <label>Municipio</label>
        <select name="municipio" required>
    <option value="">Selecciona un municipio</option>
    <option value="Abasolo">Abasolo</option>
    <option value="Agualeguas">Agualeguas</option>
    <option value="Los Aldamas">Los Aldamas</option>
    <option value="Allende">Allende</option>
    <option value="Anáhuac">Anáhuac</option>
    <option value="Apodaca">Apodaca</option>
    <option value="Aramberri">Aramberri</option>
    <option value="Bustamante">Bustamante</option>
    <option value="Cadereyta Jiménez">Cadereyta Jiménez</option>
    <option value="El Carmen">El Carmen</option>
    <option value="Cerralvo">Cerralvo</option>
    <option value="Ciénega de Flores">Ciénega de Flores</option>
    <option value="China">China</option>
    <option value="Doctor Arroyo">Doctor Arroyo</option>
    <option value="Doctor Coss">Doctor Coss</option>
    <option value="Doctor González">Doctor González</option>
    <option value="Galeana">Galeana</option>
    <option value="García">García</option>
    <option value="General Bravo">General Bravo</option>
    <option value="General Escobedo">General Escobedo</option>
    <option value="General Terán">General Terán</option>
    <option value="General Treviño">General Treviño</option>
    <option value="General Zaragoza">General Zaragoza</option>
    <option value="General Zuazua">General Zuazua</option>
    <option value="Guadalupe">Guadalupe</option>
    <option value="Los Herreras">Los Herreras</option>
    <option value="General Zaragoza">Hidalgo</option>
    <option value="Higueras">Higueras</option>
    <option value="Hualahuises">Hualahuises</option>
    <option value="Iturbide">Iturbide</option>
    <option value="Juárez">Juárez</option>
    <option value="Lampazos de Naranjo">Lampazos de Naranjo</option>
    <option value="Linares">Linares</option>
    <option value="Marín">Marín</option>
    <option value="Melchor Ocampo">Melchor Ocampo</option>
    <option value="Mier y Noriega">Mier y Noriega</option>
    <option value="Mina">Mina</option>
    <option value="Montemorelos">Montemorelos</option>
    <option value="Monterrey">Monterrey</option>
    <option value="Parás">Parás</option>
    <option value="Pesquería">Pesquería</option>
    <option value="Los Ramones">Los Ramones</option>
    <option value="Rayones">Rayones</option>
    <option value="Sabinas Hidalgo">Sabinas Hidalgo</option>
    <option value="Salinas Victoria">Salinas Victoria</option>
    <option value="San Nicolás de los Garza">San Nicolás de los Garza</option>
    <option value="San Pedro Garza García">San Pedro Garza García</option>
    <option value="Santa Catarina">Santa Catarina</option>
    <option value="Santiago">Santiago</option>
    <option value="Vallecillo">Vallecillo</option>
    <option value="Villaldama">Villaldama</option>
</select>
    </div>

    <div class="grupo">
        <label>Descripción</label>
        <textarea name="descripcion" rows="4" required></textarea>
    </div>

    <div class="grupo">
        <label>Ubicación</label>
        <input type="text" name="ubicacion" required>
    </div>

    <div class="grupo">
        <label>Meta de donación ($ MXN)</label>
        <input type="number"
               name="metaDonaciones"
               min="1"
               step="0.01"
               required>
    </div>

    <div class="grupo">
    <label>Fotografía del parque</label>

    <input
        type="file"
        name="imagenParque"
        id="imagenParque"
        accept="image/jpeg,image/png,image/webp"
        required
    >

    <img
        id="vistaPrevia"
        alt="Vista previa del parque"
        style="display:none; width:250px; max-width:100%;
               margin-top:15px; border-radius:12px;"
    >
</div>

    <div class="grupo">
        <label>URL de Google Maps</label>
        <input type="url" name="mapsUrl">
    </div>

    

    <button class="guardar"
            type="submit"
            name="agregar_parque">
        <i class="fa-solid fa-plus"></i>
        Agregar Parque
    </button>

</form>


    </div>

    <div class="card">

        <h2>

            <i class="fa-solid fa-list"></i>

            Parques registrados

        </h2>

        <table>

            <thead>

                <tr>

                    <th>ID</th>

                    <th>Parque</th>

                    <th>Municipio</th>

                    <th>Meta</th>

                    <th>Recaudado</th>

                    <th>Acciones</th>

                </tr>

            </thead>

            <tbody>

            <?php

          foreach ($parques as $id => $parque) {

            ?>

            
<tr>
    <td>
        <?php echo htmlspecialchars((string)$id); ?>
    </td>

    <td>
        <?php echo htmlspecialchars($parque['nombre'] ?? 'Sin nombre'); ?>
    </td>

    <td>
        <?php echo htmlspecialchars($parque['municipio'] ?? 'Sin asignar'); ?>
    </td>

    <td>
        $<?php echo number_format((float)($parque['metaDonaciones'] ?? 0), 2); ?>
    </td>

    <td>
        $<?php echo number_format((float)($parque['montoRecaudado'] ?? 0), 2); ?>
    </td>

    <td>
        <a
        href="EditarParque.php?id=<?= urlencode((string)$id) ?>"
        class="editar"
    >
        <i class="fa-solid fa-pen-to-square"></i>
        Editar
    </a>
    <form
        method="POST"
        action="EliminarParque.php"
        style="display:inline;"
        onsubmit="return confirm('¿Seguro que deseas eliminar este parque? Esta acción no se puede deshacer.');"
    >
        <input
            type="hidden"
            name="id"
            value="<?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?>"
        >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION['csrf_admin'], ENT_QUOTES, 'UTF-8') ?>"
        >

        <button type="submit" class="eliminar">
            <i class="fa-solid fa-trash"></i>
            Eliminar
        </button>
    </form>
</td>
    </td>
</tr>


            <?php
            }
            ?>

            </tbody>

        </table>

    </div>

</div>

<!-- Tu JavaScript del Dark Mode -->
<script src="admin.js"></script>
<script>
document.getElementById('imagenParque')
    .addEventListener('change', function () {

        const archivo = this.files[0];
        const vista = document.getElementById('vistaPrevia');

        if (!archivo) {
            vista.style.display = 'none';
            return;
        }

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(archivo.type)
            || archivo.size > 5 * 1024 * 1024) {
            alert('Selecciona una imagen JPG, PNG o WebP de máximo 5 MB.');
            this.value = '';
            vista.style.display = 'none';
            return;
        }

        vista.src = URL.createObjectURL(archivo);
        vista.style.display = 'block';

    });
</script>
</body>

</html>