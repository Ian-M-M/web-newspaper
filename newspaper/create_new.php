<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $conn = new mysqli("localhost", "root", "", "newspaper", 3306);


    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    // Insertar la edición
    $fecha = $_POST["fecha"];
    $stmt = $conn->prepare("INSERT INTO edicion (Fecha) VALUES (?)");
    $stmt->bind_param("s", $fecha);
    $stmt->execute();
    $edicion_id = $stmt->insert_id;
    $stmt->close();

    // Insertar noticias
    for ($i = 0; $i < 4; $i++) {
        $titular = $_POST["titular"][$i];
        $desc = $_POST["desc"][$i];
        $imagen = $_POST["imagen"][$i];
        $primaria = (isset($_POST["principal"]) && $_POST["principal"] == $i) ? 1 : 0;

        $stmt = $conn->prepare("INSERT INTO noticias (Titular, Descripcion, FK_ID, isPrimaria) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $titular, $desc, $edicion_id, $primaria);
        $stmt->execute();
        $stmt->close();

        // Guardar imagen (simple texto con la URL en este caso)
        // file_put_contents("imagenes/{$edicion_id}_{$i}.txt", $imagen);
    }

    echo "Edición y noticias guardadas con éxito.";
    $conn->close();
}
?>

<!-- Formulario HTML -->
<form method="POST" class="max-w-2xl mx-auto mt-10 space-y-6 p-6 bg-white rounded shadow" style="font-family: sans-serif;">
    <label class="block">Fecha de Edición:
        <input type="date" name="fecha" required class="w-full mt-1 p-2 border rounded">
    </label>

    <?php for ($i = 0; $i < 4; $i++): ?>
    <div class="border p-4 rounded bg-gray-50">
        <h3 class="font-semibold mb-2">Noticia <?= $i + 1 ?></h3>
        <label class="block mb-2">Titular:
            <input type="text" name="titular[]" class="w-full p-2 border rounded" required>
        </label>
        <label class="block mb-2">Descripción:
            <textarea name="desc[]" class="w-full p-2 border rounded" required></textarea>
        </label>
        <label class="block mb-2">Ruta de Imagen:
            <input type="text" name="imagen[]" class="w-full p-2 border rounded" required>
        </label>
        <label class="inline-flex items-center">
            <input type="radio" name="principal" value="<?= $i ?>" class="mr-2"> Marcar como Noticia Principal
        </label>
    </div>
    <?php endfor; ?>

    <button type="submit" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded">Guardar Edición</button>
</form>
