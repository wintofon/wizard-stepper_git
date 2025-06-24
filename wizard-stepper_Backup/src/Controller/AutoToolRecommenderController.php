<?php
declare(strict_types=1);

use PDO;
use RuntimeException;

/**
 * Archivo: src/Controller/AutoToolRecommenderController.php
 *
 * Controlador para el modo “Auto” del wizard CNC.
 * - Maneja la sesión segura.
 * - Verifica el estado y progreso del wizard.
 * - Recupera datos de una fresa específica (tool_id, tool_table).
 * - Verifica que los pasos previos (material+sestrategia) se hayan completado.
 */

class AutoToolRecommenderController
{
    /**
     * Tablas permitidas para herramientas.
     */
    private const ALLOWED_TABLES = [
        'tools_sgs',
        'tools_maykestag',
        'tools_schneider',
        'tools_generico',
    ];

    /**
     * Inicia o reanuda la sesión de forma segura.
     *
     * @return void
     * @throws RuntimeException
     */
    public static function initSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $ok = session_start([
                'cookie_secure'   => true,
                'cookie_httponly' => true,
                'cookie_samesite' => 'Strict',
            ]);
            if (!$ok) {
                throw new RuntimeException('No se pudo iniciar la sesión de forma segura.');
            }
        }
    }

    /**
     * Verifica que el usuario completó pasos 1 y 2 (material + estrategia).
     * Si no, redirige al paso correspondiente.
     *
     * @return void
     */
    public static function checkStep12(): void
    {
        self::initSession();
        if (empty($_SESSION['material_id']) || !is_numeric($_SESSION['material_id'])) {
            header('Location: /wizard-stepper/load-step.php?step=2');
            exit;
        }
        if (empty($_SESSION['strategy_id']) || !is_numeric($_SESSION['strategy_id'])) {
            header('Location: /wizard-stepper/load-step.php?step=3');
            exit;
        }
    }

    /**
     * Verifica que el usuario haya llegado al menos al progreso mínimo ($minProgress).
     * Si no, redirige al índice del wizard.
     *
     * @param int $minProgress
     * @return void
     */
    public static function enforceWizardProgress(int $minProgress): void
    {
        self::initSession();
        $state    = $_SESSION['wizard_state'] ?? '';
        $progress = (int)($_SESSION['wizard_progress'] ?? 0);

        if ($state !== 'wizard' || $progress < $minProgress) {
            header('Location: /wizard-stepper/index.php');
            exit;
        }
    }

    /**
     * Obtiene los datos de una herramienta por su tabla y tool_id.
     *
     * @param PDO    $pdo
     * @param string $toolTable
     * @param int    $toolId
     * @return array<string,mixed>|null
     */
    public static function getToolById(PDO $pdo, string $toolTable, int $toolId): ?array
    {
        if (!in_array($toolTable, self::ALLOWED_TABLES, true) || $toolId <= 0) {
            return null;
        }

        $sql = "
            SELECT
              t.tool_id,
              t.name                 AS tool_name,
              t.diameter_mm,
              t.shank_diameter_mm,
              t.flute_length_mm,
              t.cut_length_mm,
              t.flute_count,
              t.series_id,
              t.tool_code,
              t.image,
              s.code                 AS serie_code,
              b.name                 AS brand_name
            FROM {$toolTable} AS t
            JOIN series  AS s ON t.series_id = s.id
            JOIN brands  AS b ON s.brand_id   = b.id
            WHERE t.tool_id = ?
            LIMIT 1
        ";

        try {
            $stmt = $pdo->prepare($sql);
            if ($stmt === false) {
                return null;
            }
            $stmt->execute([$toolId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            // En modo DEBUG, podrías: dbg("Error PDO:", $e->getMessage());
            return null;
        }
    }
}
