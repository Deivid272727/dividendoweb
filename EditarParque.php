<?php
session_start();
if (empty($_SESSION['csrf_admin'])) {
    $_SESSION['csrf_admin'] = bin2hex(random_bytes(32));
}
require_once __DIR__ . '/config/firebase.php';

// Verificar sesión
if (empty($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}

try {
    $database = obtenerFirebaseDatabase();

    // Verificar rol directamente en Firebase
    $rol = $database
        ->getReference('users/' . $_SESSION['user_id'] . '/rol')
        ->getValue();

    if ($rol !== 'admin') {
        http_response_code(403);
        exit('Acceso denegado.');
    }

    // Obtener ID del parque
    $id = $_GET['id'] ?? '';

    if (!is_string($id) ||
        $id === '' ||
        preg_match('/[.#$\[\]\/]/', $id)) {
        http_response_code(400);
        exit('ID de parque inválido.');
    }

    // Consultar parque
    $parque = $database
        ->getReference('parques/' . $id)
        ->getValue();

    if (!is_array($parque)) {
        http_response_code(404);
        exit('Parque no encontrado.');
    }

} catch (Throwable $e) {
    error_log('Error al consultar parque: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo cargar el parque.');
}

function escapar($valor): string {
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Parque | Dividendo</title>
    <link rel="stylesheet" href="Css/admin.css">
</head>

<body>
    <div class="admin-container">

        <h1>Editar Parque</h1>

        <p>
            Modificando:
            <strong><?= escapar($parque['nombre'] ?? '') ?></strong>
        </p>

<form method="POST" action="GuardarEdicionParque.php" enctype="multipart/form-data">
        <input
    type="hidden"
    name="csrf_token"
    value="<?= escapar($_SESSION['csrf_admin']) ?>"
>
            <input
                type="hidden"
                name="id"
                value="<?= escapar($id) ?>"
            >

            <div class="grupo">
                <label>Nombre del parque</label>
                <input
                    type="text"
                    name="nombre"
                    maxlength="120"
                    value="<?= escapar($parque['nombre'] ?? '') ?>"
                    required
                >
            </div>

            
<div class="grupo">
    <label>Municipio</label>

    <?php
    $municipios = [
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

    $municipioActual = $parque['municipio'] ?? '';
    ?>

    <select name="municipio" required>
        <option value="">Selecciona un municipio</option>

        <?php foreach ($municipios as $municipio): ?>
            <option
                value="<?= escapar($municipio) ?>"
                <?= $municipioActual === $municipio ? 'selected' : '' ?>
            >
                <?= escapar($municipio) ?>
            </option>
        <?php endforeach; ?>

    </select>
</div>


            <div class="grupo">
                <label>Descripción</label>
                <textarea
                    name="descripcion"
                    rows="4"
                    required
                ><?= escapar($parque['descripcion'] ?? '') ?></textarea>
            </div>

            <div class="grupo">
                <label>Ubicación</label>
                <input
                    type="text"
                    name="ubicacion"
                    value="<?= escapar($parque['ubicacion'] ?? '') ?>"
                    required
                >
            </div>

<div class="grupo">
    <label>Fotografía actual</label>

    <?php if (!empty($parque['imagenUrl'])): ?>
        <div>
            <img
                src="<?= escapar($parque['imagenUrl']) ?>"
                alt="Fotografía actual del parque"
                style="width:250px;max-width:100%;border-radius:12px;margin-top:10px;"
            >
        </div>
    <?php else: ?>
        <p>Este parque no tiene fotografía.</p>
    <?php endif; ?>
</div>

<div class="grupo">
    <label for="imagenParque">Cambiar fotografía</label>

    <input
        type="file"
        name="imagenParque"
        id="imagenParque"
        accept="image/jpeg,image/png,image/webp"
    >

    <small>
        Opcional. Si no seleccionas una fotografía,
        se conservará la actual. Máximo 5 MB.
    </small>

    <img
        id="vistaPrevia"
        alt="Vista previa de la nueva fotografía"
        style="display:none;width:250px;max-width:100%;margin-top:15px;border-radius:12px;"
    >
</div>


            <div class="grupo">
                <label>Meta de donaciones ($ MXN)</label>
                <input
                    type="number"
                    name="metaDonaciones"
                    min="1"
                    step="0.01"
                    value="<?= escapar($parque['metaDonaciones'] ?? 0) ?>"
                    required
                >
            </div>

            <div class="grupo">
                <label>URL de Google Maps</label>
                <input
                    type="url"
                    name="mapsUrl"
                    value="<?= escapar($parque['mapsUrl'] ?? '') ?>"
                >
            </div>

            <button type="submit" class="guardar">
                Guardar cambios
            </button>

            <a href="Admin.php">
                Cancelar
            </a>

        </form>

    </div>

<script>
document.getElementById('imagenParque').addEventListener('change', function () {
    const archivo = this.files[0];
    const vista = document.getElementById('vistaPrevia');

    if (!archivo) {
        vista.style.display = 'none';
        vista.removeAttribute('src');
        return;
    }

    const tiposPermitidos = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    if (
        !tiposPermitidos.includes(archivo.type) ||
        archivo.size > 5 * 1024 * 1024
    ) {
        alert('Selecciona una imagen JPG, PNG o WebP de máximo 5 MB.');
        this.value = '';
        vista.style.display = 'none';
        vista.removeAttribute('src');
        return;
    }

    vista.src = URL.createObjectURL(archivo);
    vista.style.display = 'block';
});
</script>

</body>
</html>
