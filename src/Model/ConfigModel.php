<?php
/**
 * File: ConfigModel.php
 *
 * Main responsibility: Part of the CNC Wizard Stepper.
 * Related files: See others in this project.
 * @TODO Extend documentation.
 */
/**
 * ConfigModel.php
 *
 * Ubicación: C:\xampp\htdocs\wizard-stepper_git\src\Model\ConfigModel.php
 *
 * Obtiene parámetros globales de tablas auxiliares:
 *  - kc11 y mc de 'materials'
 *  - coef_security de 'transmissions' (coef_seg)
 *
 * Esta versión incluye:
 *  - Manejo de excepciones con registro de debug
 *  - Validación básica de parámetros de entrada
 *  - Comentarios detallados para mantenimiento
 */

declare(strict_types=1);

class ConfigModel
{
    /**
     * Retorna el valor kc11 (N/mm²) para un material dado.
     * Si no existe o hay error, devuelve un valor por defecto de 1200.0.
     *
     * @param \PDO $pdo          Conexión PDO
     * @param int  $material_id  ID del material
     * @return float             Coeficiente específico de corte Kc11
     */
    public static function getKc11(\PDO $pdo, int $material_id): float
    {
        if ($material_id <= 0) {
            dbg("[ConfigModel::getKc11] material_id inválido: {$material_id}");
            return 1200.0;
        }

        try {
            $stmt = $pdo->prepare("SELECT kc11 FROM materials WHERE material_id = ?");
            $stmt->execute([$material_id]);
            $val = $stmt->fetchColumn();

            if ($val === false) {
                dbg("[ConfigModel::getKc11] material_id={$material_id} no encontrado. Usando valor por defecto.");
                return 1200.0;
            }

            return (float)$val;

        } catch (\PDOException $e) {
            dbg("[ConfigModel::getKc11] PDOException: " . $e->getMessage());
            return 1200.0;
        } catch (\Throwable $e) {
            dbg("[ConfigModel::getKc11] Error inesperado: " . $e->getMessage());
            return 1200.0;
        }
    }

    /**
     * Retorna el valor mc (exponente de espesor) para un material dado.
     * Si no existe o hay error, devuelve un valor por defecto de 0.20.
     *
     * @param \PDO $pdo          Conexión PDO
     * @param int  $material_id  ID del material
     * @return float             Exponente mc
     */
    public static function getMc(\PDO $pdo, int $material_id): float
    {
        if ($material_id <= 0) {
            dbg("[ConfigModel::getMc] material_id inválido: {$material_id}");
            return 0.20;
        }

        try {
            $stmt = $pdo->prepare("SELECT mc FROM materials WHERE material_id = ?");
            $stmt->execute([$material_id]);
            $val = $stmt->fetchColumn();

            if ($val === false) {
                dbg("[ConfigModel::getMc] material_id={$material_id} no encontrado. Usando valor por defecto.");
                return 0.20;
            }

            return (float)$val;

        } catch (\PDOException $e) {
            dbg("[ConfigModel::getMc] PDOException: " . $e->getMessage());
            return 0.20;
        } catch (\Throwable $e) {
            dbg("[ConfigModel::getMc] Error inesperado: " . $e->getMessage());
            return 0.20;
        }
    }

    /**
     * Retorna el coeficiente de seguridad para una transmisión dada.
     * Si no existe o hay error, devuelve un valor por defecto de 1.0.
     * Asegura que el valor sea al menos 1.0.
     *
     * @param \PDO $pdo      Conexión PDO
     * @param int  $trans_id ID de la transmisión
     * @return float         Coeficiente de seguridad (>=1.0)
     */
    public static function getCoefSeg(\PDO $pdo, int $trans_id): float
    {
        if ($trans_id <= 0) {
            dbg("[ConfigModel::getCoefSeg] trans_id inválido: {$trans_id}");
            return 1.0;
        }

        try {
            $stmt = $pdo->prepare("SELECT coef_security FROM transmissions WHERE id = ?");
            $stmt->execute([$trans_id]);
            $val = $stmt->fetchColumn();

            if ($val === false) {
                dbg("[ConfigModel::getCoefSeg] trans_id={$trans_id} no encontrado. Usando valor por defecto.");
                return 1.0;
            }

            $coef = (float)$val;
            if ($coef < 1.0) {
                dbg("[ConfigModel::getCoefSeg] coef_security menor a 1 ({$coef}), forzando a 1.0");
                return 1.0;
            }

            return $coef;

        } catch (\PDOException $e) {
            dbg("[ConfigModel::getCoefSeg] PDOException: " . $e->getMessage());
            return 1.0;
        } catch (\Throwable $e) {
            dbg("[ConfigModel::getCoefSeg] Error inesperado: " . $e->getMessage());
            return 1.0;
        }
    }
}

// Stub de debug: registra mensajes en el log de errores.
// Reemplazar por logger real en producción.
if (!function_exists('dbg')) {
    function dbg(string $msg): void
    {
        error_log($msg);
    }
}
