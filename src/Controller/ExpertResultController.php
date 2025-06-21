<?php
/**
 * File: ExpertResultController.php
 *
 * Main responsibility: Part of the CNC Wizard Stepper.
 *
 * Called by: views/steps/step6.php
 * Important session keys read:
 *   - $_SESSION['tool_table']    Current tool table
 *   - $_SESSION['tool_id']       Selected tool identifier
 *   - $_SESSION['material']      Material ID
 *   - $_SESSION['trans_id']      Machine/operation ID
 *   - $_SESSION['rpm_min']       Minimum RPM allowed
 *   - $_SESSION['rpm_max']       Maximum RPM allowed
 *   - $_SESSION['fr_max']        Maximum feed rate
 *   - $_SESSION['thickness']     Material thickness
 *   - $_SESSION['hp']            Available machine horsepower
 * @TODO Extend documentation.
 */
/**
 * ExpertResultController.php
 *
 * Ubicación: C:\xampp\htdocs\wizard-stepper_git\src\Controller\ExpertResultController.php
 *
 * Controlador robusto y debugable para recopilar todos los parámetros técnicos
 * usados en la vista Paso 6 del Wizard CNC.
 */

declare(strict_types=1);

namespace App\Controller;

use ToolModel;
use ConfigModel;
use CNCCalculator;

if (!function_exists('dbg')) {
    /**
     * Registra mensajes de debug en el log de errores.
     *
     * @param string $msg
     */
    function dbg(string $msg): void
    {
        error_log('[ExpertResultController] ' . $msg);
    }
}

class ExpertResultController
{
    /**
     * Recopila y calcula todos los parámetros técnicos para la vista Paso 6.
     *
     * @param \PDO  $pdo     Conexión a la BD
     * @param array $session Copia de $_SESSION
     * @return array<string,mixed>
     * @throws \RuntimeException si falta dato o hay error interno
     */
    public static function getResultData(\PDO $pdo, array $session): array
    {
        // 1) Validar datos de sesión
        // These session keys are populated across earlier wizard steps
        $required = [
            'tool_table','tool_id','material','trans_id',
            'rpm_min','rpm_max','fr_max','thickness','hp'
        ];
        // Abort if any mandatory session value is missing
        foreach ($required as $key) {
            if (empty($session[$key])) {
                dbg("Sesión incompleta: falta {$key}");
                throw new \RuntimeException("Sesión incompleta: {$key}");
            }
        }

        // 2) Extraer parámetros
        // Values come from $_SESSION as passed by the step 5 form
        $tbl        = (string)$session['tool_table'];
        $toolId     = (int)$session['tool_id'];
        $materialId = (int)$session['material'];
        $transId    = (int)$session['trans_id'];
        $rpmMin     = (float)$session['rpm_min'];   // limits configured on step 5
        $rpmMax     = (float)$session['rpm_max'];   // limits configured on step 5
        $frMax      = (float)$session['fr_max'];    // feed rate maximum
        $thickness  = (float)$session['thickness']; // part thickness
        $hpAvail    = (float)$session['hp'];        // machine horsepower

        try {
            // 3) Datos de la herramienta
            $tool = ToolModel::getTool($pdo, $tbl, $toolId);
            if (!$tool) {
                dbg("Herramienta no encontrada: {$tbl}[{$toolId}]");
                throw new \RuntimeException('Herramienta no encontrada.');
            }
            $diameter   = (float)($tool['diameter_mm'] ?? 0);
            $fluteCount = (int)  ($tool['flute_count']  ?? 0);
            $rackRad    = deg2rad((float)($tool['rack_angle'] ?? 0)); // rad

            // 4) Datos de material
            $matTbl = str_replace('tools_', 'toolsmaterial_', $tbl);
            $mat = ToolModel::getMaterialData($pdo, $matTbl, $toolId, $materialId);
            if (!$mat) {
                dbg("Sin datos de material en {$matTbl} para fresa {$toolId}");
                throw new \RuntimeException('Datos de material no disponibles.');
            }
            $vcMin0 = (float)$mat['vc_m_min'];
            $fzMin0 = (float)$mat['fz_min_mm'];
            $fzMax0 = (float)$mat['fz_max_mm'];
            $apSlot = (float)$mat['ap_slot_mm'];
            $aeSlot = (float)$mat['ae_slot_mm'];
            $vcMax0 = $vcMin0 * 1.25;

            // 5) Coeficientes globales
            $Kc11    = ConfigModel::getKc11($pdo, $materialId);
            $mc      = ConfigModel::getMc($pdo, $materialId);
            $coefSeg = ConfigModel::getCoefSeg($pdo, $transId);

            // 6) Cálculos base
            // Apply machining formulas to compute initial feed and speed
            $fz0      = (($fzMin0 + $fzMax0) / 2) * $coefSeg;
            $vc0      = $vcMin0;
            $passes0  = 1;
            $ap0      = $passes0 > 0 ? round($thickness / $passes0, 3) : 0.0;
            $ae0      = $aeSlot;

            $rpmCalc0 = CNCCalculator::rpm($vc0, $diameter);
            $rpm0     = (int) round(min(max($rpmCalc0, $rpmMin), $rpmMax));

            $feed0    = min(CNCCalculator::feed($rpm0, $fz0, $fluteCount), $frMax);
            $mmrBase  = CNCCalculator::mmr($ap0, $feed0, $ae0);

            // 7) Empaquetar resultados
            return [
                'diameter'     => $diameter,
                'flute_count'  => $fluteCount,
                'rack_rad'     => $rackRad,
                'vc_min0'      => $vcMin0,
                'vc_max0'      => $vcMax0,
                'fz_min0'      => $fzMin0,
                'fz_max0'      => $fzMax0,
                'ap_slot'      => $apSlot,
                'ae_slot'      => $aeSlot,
                'Kc11'         => $Kc11,
                'mc'           => $mc,
                'coef_seg'     => $coefSeg,
                'fz0'          => $fz0,
                'vc0'          => $vc0,
                'passes0'      => $passes0,
                'ap0'          => $ap0,
                'ae0'          => $ae0,
                'rpm_calc0'    => $rpmCalc0,
                'rpm0'         => $rpm0,
                'feed0'        => $feed0,
                'mmr_base'     => $mmrBase,
                'rpm_min'      => $rpmMin,
                'rpm_max'      => $rpmMax,
                'fr_max'       => $frMax,
                'hp_avail'     => $hpAvail,
            ];

        } catch (\Throwable $e) {
            dbg("Excepción en getResultData: " . $e->getMessage());
            throw $e;
        }
    }
}

// Alias global
\class_alias(__NAMESPACE__ . '\\ExpertResultController', 'ExpertResultController');
