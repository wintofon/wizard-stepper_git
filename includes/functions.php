// 📄 includes/functions.php
// Ruta: project_root/includes/functions.php
// Descripción: Contiene funciones genéricas de utilidad para todo el proyecto.
<?php
/**
 * Carga la conexión a la base de datos y otras funciones generales.
 */
require_once __DIR__ . '/db.php';

/**
 * Renderiza una respuesta JSON para peticiones Ajax.
 *
 * @param mixed $data Datos a retornar en formato JSON
 */
function respond_json($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Obtiene un valor de la sesión si existe, o null en caso contrario.
 *
 * @param string $key Clave de sesión
 * @return mixed|null
 */
function session_get(string $key) {
    return $_SESSION[$key] ?? null;
}
