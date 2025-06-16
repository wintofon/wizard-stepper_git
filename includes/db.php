<?php
// includes/db.php
declare(strict_types=1);

/* ───────── CONFIG DB ───────── */
const DB_HOST = 'localhost';
const DB_NAME = 'cnc_calculador';
const DB_USER = 'root';
const DB_PASS = '';

/**
 * Devuelve una instancia singleton de PDO.
 *
 * @return PDO
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('DB Connection Error: ' . htmlspecialchars($e->getMessage()));
        }
    }
    return $pdo;
}

// Instancia global para compatibilidad con scripts que usan $pdo
$pdo = db();

/* ───────── Utilidades ───────── */

/**
 * Devuelve el nombre de la tabla tools según el ID de marca.
 */
function brandTable(int $brandId): string
{
    return match ($brandId) {
        1 => 'tools_sgs',
        2 => 'tools_maykestag',
        3 => 'tools_schneider',
        4 => 'tools_generico',
        default => 'tools',
    };
}

/**
 * Nombre de la tabla feedrate.
 */
function feedrateTable(): string
{
    return 'feedrate';
}

/**
 * Obtiene la calificación (0–4) de una herramienta para un material dado.
 */
function getToolRating(int $toolId, int $materialId): int
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT rating
          FROM tool_material_rating
         WHERE tool_id = ? AND material_id = ?
    ");
    $stmt->execute([$toolId, $materialId]);
    $rating = $stmt->fetchColumn();
    return $rating !== false ? (int)$rating : 0;
}

/**
 * Lista herramientas recomendadas filtradas por tipo, estrategia y material,
 * ordenadas por rating descendente.
 *
 * @return array<int, array<string,mixed>>
 */
function fetchRecommendedTools(string $tipo, string $estrategia, int $materialId, ?int $brandId = null): array
{
    global $pdo;
    $toolsTb = $brandId ? brandTable($brandId) : 'tools';
    $feedTb  = feedrateTable();

    $sql = "
      SELECT t.*,
             COALESCE(r.rating,0) AS rating,
             f.vc, f.fz_min, f.fz_max, f.ap_slot, f.ae_slot
        FROM {$toolsTb} AS t
        LEFT JOIN tool_material_rating AS r
          ON r.tool_id = t.tool_id
         AND r.material_id = :mat
        JOIN {$feedTb} AS f
          ON f.material_id = :mat
       WHERE t.tipo = :tipo
         AND t.strategy_id = :estr
       ORDER BY rating DESC, t.tool_code ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':mat'  => $materialId,
        ':tipo' => $tipo,
        ':estr' => $estrategia,
    ]);
    return $stmt->fetchAll();
}

/**
 * Obtiene detalles completos de una herramienta (para Step 3c).
 *
 * @return array<string,mixed>
 */
function fetchToolDetails(int $toolId, int $materialId): array
{
    global $pdo;
    $toolsTb = 'tools';
    $feedTb  = feedrateTable();

    $sql = "
      SELECT t.*,
             COALESCE(r.rating,0) AS rating,
             f.vc, f.fz_min, f.fz_max, f.ap_slot, f.ae_slot
        FROM {$toolsTb} AS t
        LEFT JOIN tool_material_rating AS r
          ON r.tool_id = t.tool_id
         AND r.material_id = :mat
        JOIN {$feedTb} AS f
          ON f.material_id = :mat
       WHERE t.tool_id = :tool
       LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':mat'  => $materialId,
        ':tool' => $toolId,
    ]);
    return $stmt->fetch() ?: [];
}