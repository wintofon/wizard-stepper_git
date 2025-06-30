<?php
/**
 * ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
 * ┃  ExpertResultController.php  –  ¡Versión ÉPICA 2025! ┃
 * ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
 *
 * - Calcula todos los parámetros técnicos que Step 6 necesita.
 * - Asegura que NUNCA falte ni una sola clave esperada por el JS.
 * - Si algo sale mal, deja un registro en el log con prefijo [ExpertResultController].
 *
 *  Autor:  Tú, maestro CNC  🛠️⚡
 */

declare(strict_types=1);

namespace App\Controller;

use ToolModel;
use ConfigModel;
use CNCCalculator;

if (!function_exists('dbg')) {
    function dbg(string $msg): void
    {   error_log('[ExpertResultController] ' . $msg); }
}

class ExpertResultController
{
    /**
     * Devuelve un súper-array con TODO lo que el front-end demanda.
     *
     * @param \PDO  $pdo
     * @param array $session  Copia de $_SESSION
     * @return array<string,mixed>
     * @throws \RuntimeException
     */
    public static function getResultData(\PDO $pdo, array $session): array
    {
        /* ───────────────────── 1) Validación de sesión ───────────────────── */
        $required = [
            'tool_table','tool_id','material','trans_id',
            'rpm_min','rpm_max','fr_max','thickness','hp'
        ];
        foreach ($required as $key) {
            if (empty($session[$key])) {
                dbg("Sesión incompleta: falta {$key}");
                throw new \RuntimeException("Sesión incompleta: {$key}");
            }
        }

        /* ───────────────────── 2) Datos básicos ─────────────────────────── */
        $tbl        = (string)$session['tool_table'];
        $toolId     = (int)   $session['tool_id'];
        $materialId = (int)   $session['material'];
        $transId    = (int)   $session['trans_id'];

        $rpmMin     = (float) $session['rpm_min'];
        $rpmMax     = (float) $session['rpm_max'];
        $frMax      = (float) $session['fr_max'];
        $thickness  = (float) $session['thickness'];
        $hpAvail    = (float) $session['hp'];

        /* ───────────────────── 3) Herramienta ───────────────────────────── */
        $tool = ToolModel::getTool($pdo, $tbl, $toolId);
        if (!$tool) {
            dbg("Herramienta no encontrada: {$tbl}[{$toolId}]");
            throw new \RuntimeException('Herramienta no encontrada.');
        }
        $diameter   = (float)($tool['diameter_mm']   ?? 0);
        $fluteCount = (int)  ($tool['flute_count']   ?? 0);
        $rackRad    = deg2rad((float)($tool['rack_angle'] ?? 0)); // radianes

        /* ───────────────────── 4) Material ──────────────────────────────── */
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

        /* ───────────────────── 5) Coeficientes globales ─────────────────── */
        $Kc11       = ConfigModel::getKc11($pdo, $materialId);
        $mc         = ConfigModel::getMc($pdo, $materialId);
        $coefSeg    = ConfigModel::getCoefSeg($pdo, $transId);
        $angleRamp  = ConfigModel::getAngleRamp($pdo, $materialId);
        $eta     = 1.0;                       // eficiencia global (100 %) – ajústalo si hace falta

        /* ───────────────────── 6) Punto base (rpm, feed, etc.) ──────────── */
        $fz0      = (($fzMin0 + $fzMax0) / 2) * $coefSeg;      // fz medio × coef seguridad
        $vc0      = $vcMin0;
        $passes0  = 1;
        $ap0      = round($thickness / $passes0, 3);
        $ae0      = $aeSlot;

        $rpmCalc0 = CNCCalculator::rpm($vc0, $diameter);
        $rpm0     = (int) round(min(max($rpmCalc0, $rpmMin), $rpmMax));

        $feed0    = min(CNCCalculator::feed($rpm0, $fz0, $fluteCount), $frMax);
        $mmrBase  = CNCCalculator::mmr($ap0, $feed0, $ae0);

        /* ───────────────────── 7) ¡PAQUETE ÉPICO! ───────────────────────── */
        return [
            // — Datos geométricos —
            'diameter'       => $diameter,
            'diameter_mm'    => $diameter,      // alias que exige el JS
            'flute_count'    => $fluteCount,
            'rack_rad'       => $rackRad,

            // — Límites / máquina —
            'rpm_min'        => $rpmMin,
            'rpm_max'        => $rpmMax,
            'fr_max'         => $frMax,
            'feed_max'       => $frMax,          // alias para el JS
            'thickness'      => $thickness,
            'hp_avail'       => $hpAvail,
            'eta'            => $eta,            // eficiencia %

            // — Material —
            'vc_min0'        => $vcMin0,
            'vc_max0'        => $vcMax0,
            'fz_min0'        => $fzMin0,
            'fz_max0'        => $fzMax0,
            'ap_slot'        => $apSlot,
            'ae_slot'        => $aeSlot,
            'angle_ramp'     => $angleRamp,

            // — Coeficientes —
            'Kc11'           => $Kc11,
            'mc'             => $mc,
            'coef_seg'       => $coefSeg,

            // — Punto base calculado —
            'fz0'            => $fz0,
            'vc0'            => $vc0,
            'passes0'        => $passes0,
            'ap0'            => $ap0,
            'ae0'            => $ae0,
            'rpm_calc0'      => $rpmCalc0,
            'rpm0'           => $rpm0,
            'feed0'          => $feed0,
            'mmr_base'       => $mmrBase,
        ];
    }
}

/*  Para que se pueda invocar como ExpertResultController fuera del
    namespace (legacy).  */
\class_alias(__NAMESPACE__ . '\\ExpertResultController', 'ExpertResultController');
