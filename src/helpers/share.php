<?php
/**
 * Helper de compartir
 * ────────────────────
 * Construye los metadatos necesarios para compartir un animal en redes
 * sociales y para generar la plantilla de Stories en cliente. No accede
 * a la base de datos (eso es responsabilidad del modelo) ni renderiza
 * HTML (eso es responsabilidad de la vista o del partial share-modal).
 */

require_once __DIR__ . '/url.php';

/**
 * Devuelve la URL absoluta del host actual (esquema + dominio).
 * Ej: https://gocatch.com  ó  http://localhost
 */
function share_host_url(): string
{
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

/**
 * Convierte una ruta relativa (interna del sitio) en URL absoluta.
 */
function share_absolute_url(string $path): string
{
    if (preg_match('#^https?://#i', $path)) return $path;
    return share_host_url() . '/' . ltrim($path, '/');
}

/**
 * Construye los datos necesarios para compartir un animal.
 *
 * @param array $animal     Fila del animal (proveniente de AnimalModel::getById)
 * @param array $fotos      Galería de fotos (AnimalModel::getGaleria)
 * @param string $edad_txt  Texto formateado de la edad (ej: "2 años")
 * @return array {
 *   url:        URL absoluta de la ficha
 *   title:      Título corto para meta tags y para el sistema operativo al compartir
 *   desc:       Descripción corta para meta tags
 *   nombre:     Nombre del animal
 *   edad:       Edad formateada
 *   ciudad:     Ciudad de la protectora
 *   protectora: Nombre de la protectora
 *   foto:       Ruta relativa de la foto principal (o logo si no hay)
 *   foto_abs:   URL absoluta de la foto principal (para Open Graph)
 *   estado:     Estado del animal (DISPONIBLE / RESERVADO / ...)
 * }
 */
function share_build_data(array $animal, array $fotos, string $edad_txt): array
{
    $id        = (int)($animal['id_animal'] ?? 0);
    $nombre    = $animal['nombre'] ?? 'Animal';
    $foto_rel  = !empty($fotos) && !empty($fotos[0]['ruta'])
                    ? $fotos[0]['ruta']
                    : '/img/logo.svg';

    $desc_partes = [];
    if (!empty($animal['especie'])) $desc_partes[] = ucfirst($animal['especie']);
    if (!empty($animal['raza']))    $desc_partes[] = $animal['raza'];
    $desc_partes[] = $edad_txt;
    if (!empty($animal['ciudad'])) $desc_partes[] = $animal['ciudad'];

    return [
        'url'        => share_absolute_url('/src/view/ficha-animal.php?id=' . $id),
        'title'      => $nombre . ' busca un hogar · Go Catch',
        'desc'       => implode(' · ', array_filter($desc_partes)),
        'nombre'     => $nombre,
        'edad'       => $edad_txt,
        'ciudad'     => $animal['ciudad'] ?? '',
        'protectora' => $animal['nombre_protectora'] ?? '',
        'foto'       => $foto_rel,
        'foto_abs'   => share_absolute_url($foto_rel),
        'estado'     => $animal['estado'] ?? '',
    ];
}

/**
 * Imprime las meta tags Open Graph y Twitter Card para que las redes
 * sociales muestren un preview rico al pegar el enlace.
 */
function share_render_meta_tags(array $share): void
{
    $title = htmlspecialchars($share['title']);
    $desc  = htmlspecialchars($share['desc']);
    $img   = htmlspecialchars($share['foto_abs']);
    $url   = htmlspecialchars($share['url']);
    ?>
    <meta property="og:type"        content="website">
    <meta property="og:title"       content="<?= $title ?>">
    <meta property="og:description" content="<?= $desc ?>">
    <meta property="og:image"       content="<?= $img ?>">
    <meta property="og:url"         content="<?= $url ?>">
    <meta property="og:site_name"   content="Go Catch">

    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= $title ?>">
    <meta name="twitter:description" content="<?= $desc ?>">
    <meta name="twitter:image"       content="<?= $img ?>">
    <?php
}

/**
 * Serializa los datos de share en JSON seguro para inyectar en una etiqueta
 * <script type="application/json"> que será leída por share-animal.js.
 */
function share_to_json(array $share): string
{
    return json_encode($share, JSON_UNESCAPED_UNICODE
        | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
}
