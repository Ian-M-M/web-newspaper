<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $html_template="./index.html";
    $conn = new mysqli("localhost", "root", "", "newspaper",3306);
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    $fecha = $_POST["fecha"];

    // Buscar si existe una edición con esa fecha
    $stmt = $conn->prepare("SELECT ID FROM edicion WHERE Fecha = ?");
    $stmt->bind_param("s", $fecha);
    $stmt->execute();
    $stmt->bind_result($edicion_id);
    $stmt->fetch();
    $stmt->close();

    if (!$edicion_id) {
        echo "No se encontró una edición con esa fecha.";
        exit;
    }

    // Obtener todas las noticias asociadas a esa edición
    $result = $conn->query("SELECT ID, Titular, Descripcion, isPrimaria FROM noticias WHERE FK_ID = $edicion_id");

    $noticias = [];
    while ($row = $result->fetch_assoc()) {
        $img_path = "imagenes/{$edicion_id}_" . count($noticias) . ".txt";
        $img_url = file_exists($img_path) ? file_get_contents($img_path) : "";
        $row["Imagen"] = $img_url;
        $noticias[] = $row;
    }

    // Cargar el archivo HTML actual
    $html = file_get_contents("$html_template");

    // Verifica si existe el marcador <!-- FECHA -->
    if (preg_match('/<!-- FECHA -->/', $html)) {
        // Primera vez: reemplaza el marcador por la fecha
        $html = preg_replace('/<!-- FECHA -->/', $fecha, $html);
    } 
    // Si ya hay una fecha con formato aaaa-mm-dd 
    elseif (preg_match('/\d{4}-\d{2}-\d{2}/', $html)) {
        // Segunda vez o más: reemplaza la fecha existente por la nueva
        $html = preg_replace('/\d{4}-\d{2}-\d{2}/', $fecha, $html, 1);
    }


    $secundarias = 0;

    foreach ($noticias as $i => $noticia) {
        $titulo = htmlspecialchars($noticia["Titular"]);
        $descripcion = htmlspecialchars($noticia["Descripcion"]);
        $imagen = htmlspecialchars($noticia["Imagen"]);

        if ($noticia["isPrimaria"]) {
            // Actualiza noticia principal
            // Imagen principal
            $html = preg_replace(
                '/(<img[^>]+alt="Noticia principal"[^>]+src=")[^"]+/',
                "$1$imagen",
                $html
            );

            // Título principal
            $html = preg_replace(
                '/<a\s[^>]*class="[^"]*\btext-2xl\b[^"]*"[^>]*>.*?<\/a>/is',
                "<a href=\"#\" class=\"text-2xl font-bold block mb-2 hover:underline\">$titulo</a>",
                $html,
                1
            );

            // Descripción principal
            $html = preg_replace(
                '/<!-- DESCRIPCION_PRINCIPAL -->.*?<\/p>/',
                "<!-- DESCRIPCION_PRINCIPAL -->$descripcion</p>",
                $html
            );

            
        } else {
            // Noticias secundarias: usa comentarios para ubicar
            $index = ++$secundarias;
            $html = preg_replace_callback('/<!-- Noticia secundaria ' . $index . ' -->(.*?)<\/div>\s*<\/div>/s', function ($matches) use ($titulo, $descripcion, $imagen, $index) {
                $seccion = $matches[1];
                $seccion = preg_replace('/<img[^>]+src="[^"]+/', "<img src=\"$imagen\"", $seccion);
                $seccion = preg_replace(
                    '/<a\s[^>]*class="[^"]*\btext-xl\b[^"]*"[^>]*>.*?<\/a>/is',
                    "<a href=\"#\" class=\"text-xl font-semibold block mb-1 hover:underline\">$titulo</a>",
                    $seccion
                );
                $seccion = preg_replace('/<p class="text-sm">.*?<\/p>/', "<p class=\"text-sm\">$descripcion</p>", $seccion);
                return "<!-- Noticia secundaria $index -->$seccion</div></div>";
            }, $html, 1);

        }
    }

    // Guardar cambios en el archivo
    file_put_contents("$html_template", $html);
    echo "Actualización completada.";
    $conn->close();
}
?>

<!-- Formulario para seleccionar fecha de edición -->
<form method="POST" class="max-w-md mx-auto mt-10 bg-white p-6 rounded shadow">
    <label class="block mb-4">Fecha de edición:
        <input type="date" name="fecha" required class="w-full p-2 border rounded">
    </label>
    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Actualizar $html_template</button>
</form>
