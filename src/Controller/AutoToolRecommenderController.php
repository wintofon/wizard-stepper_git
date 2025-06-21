<?php
/**
 * File: AutoToolRecommenderController.php
 *
 * Main responsibility: Part of the CNC Wizard Stepper.
 *
 * Called by: views/steps/auto/step3.php
 * Important session keys handled:
 *   - $_SESSION['wizard_state']    Current wizard state
 *   - $_SESSION['wizard_progress'] Wizard progress counter
 *   - $_SESSION['material_id']     Selected material ID
 *   - $_SESSION['strategy_id']     Selected strategy ID
 *   - $_SESSION['thickness']       Material thickness
 * @TODO Extend documentation.
 */
declare(strict_types=1);

namespace App\Controller;

use PDO;
use PDOException;
use RuntimeException;
require_once __DIR__ . '/../Utils/Session.php';

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
        startSecureSession();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('No se pudo iniciar la sesión de forma segura.');
        }
    }

    /**
     * Verifica que el usuario se encuentra en el flujo correcto y que los
     * pasos 1 y 2 fueron completados. Lanza RuntimeException si falta algún
     * dato necesario.
     *
     * @return void
     * @throws RuntimeException
     */
    public static function checkStep(): void
    {
        self::initSession();

        // $_SESSION['wizard_state'] is set by wizard.php when the user
        // enters the wizard flow
        if (($_SESSION['wizard_state'] ?? '') !== 'wizard') {
            throw new RuntimeException('Estado de wizard inválido');
        }

        // check that $_SESSION['material_id'] was stored in a previous step
        if (empty($_SESSION['material_id']) || !is_numeric($_SESSION['material_id'])) {
            throw new RuntimeException('Falta material_id en la sesión');
        }

        // $_SESSION['strategy_id'] is defined when selecting the machining strategy
        if (empty($_SESSION['strategy_id']) || !is_numeric($_SESSION['strategy_id'])) {
            throw new RuntimeException('Falta strategy_id en la sesión');
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
            header('Location: ' . asset('public/load-step.php?step=2'));
            exit;
        }
        if (empty($_SESSION['strategy_id']) || !is_numeric($_SESSION['strategy_id'])) {
            header('Location: ' . asset('public/load-step.php?step=3'));
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
        // wizard.php stores the current state and progress in session
        $state    = $_SESSION['wizard_state'] ?? '';
        $progress = (int)($_SESSION['wizard_progress'] ?? 0);

        if ($state !== 'wizard' || $progress < $minProgress) {
            header('Location: ' . asset('wizard.php'));
            exit;
        }
    }

    /**
     * Obtiene algunos valores de la sesión necesarios para la vista de Paso 3.
     * Devuelve material_id, strategy_id y thickness como entero/float.
     *
     * @return array{material_id:int,strategy_id:int,thickness:float}
     * @throws RuntimeException si falta algún dato en la sesión
     */
    public static function getSessionData(): array
    {
        self::initSession();

        // These keys are written during previous wizard steps
        $required = ['material_id', 'strategy_id', 'thickness'];
        foreach ($required as $key) {
            if (!isset($_SESSION[$key]) || $_SESSION[$key] === '') {
                throw new RuntimeException("Falta {$key} en la sesión");
            }
        }

        return [
            'material_id' => (int)$_SESSION['material_id'],
            'strategy_id' => (int)$_SESSION['strategy_id'],
            'thickness'   => (float)$_SESSION['thickness'],
        ];
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

// Alias global para retrocompatibilidad
\class_alias(__NAMESPACE__ . '\\AutoToolRecommenderController', 'AutoToolRecommenderController');
