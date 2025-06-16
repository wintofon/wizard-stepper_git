<?php
/**
 * CNCCalculator.php
 *
 * Ubicación: C:\xampp\htdocs\wizard-stepper\src\Utils\CNCCalculator.php
 *
 * Clase de utilidades numéricas para cálculos CNC:
 * - helixAngle(ae, D) → ángulo de compromiso φ
 * - chipThickness(fz, ae, D) → espesor medio de viruta hm
 * - rpm(vc, D) → revoluciones por minuto
 * - feed(rpm, fz, Z) → tasa de avance Vf
 * - mmr(ap, feed, ae) → tasa de remoción de material (MMR)
 * - Fct(...) → fuerza de corte tangencial
 * - potencia(...) → potencia requerida (W y HP)
 *
 * Esta versión añade:
 *  - Validaciones de parámetros
 *  - Manejo de errores / casos límite
 *  - Comentarios detallados para cada paso
 *  - Logging de debug (función dbg stub)
 */

declare(strict_types=1);

class CNCCalculator
{
    /**
     * Calcula el ángulo de compromiso φ en radianes:
     * φ = 2 · asin(min(1, ae / D))
     *
     * @param float $ae Ancho de pasada (mm)
     * @param float $D  Diámetro de la herramienta (mm)
     * @return float    Ángulo φ (rad)
     */
    public static function helixAngle(float $ae, float $D): float
    {
        if ($D <= 0.0) {
            dbg("[helixAngle] Diámetro inválido D={$D}");
            return 0.0;
        }
        $ratio = min(1.0, $ae / $D);
        return 2.0 * asin($ratio);
    }

    /**
     * Calcula el espesor medio de viruta hm:
     * hm = (fz * (1 - cos φ)) / φ    si φ > 0
     * hm = fz                         si φ = 0
     *
     * Para φ muy pequeño (<1e-3), usa aproximación hm ≈ fz * (ae/D)
     *
     * @param float $fz Avance por diente (mm/diente)
     * @param float $ae Ancho de pasada (mm)
     * @param float $D  Diámetro de la herramienta (mm)
     * @return float    Espesor medio hm (mm)
     */
    public static function chipThickness(float $fz, float $ae, float $D): float
    {
        $phi = self::helixAngle($ae, $D);
        if ($phi === 0.0) {
            return $fz;
        }
        if (abs($phi) < 1e-3) {
            // HM ≈ fz * (ae/D) para φ→0
            return $D > 0.0 ? $fz * ($ae / $D) : $fz;
        }
        return ($fz * (1.0 - cos($phi))) / $phi;
    }

    /**
     * Calcula revoluciones por minuto (RPM) a partir de
     * velocidad de corte Vc (m/min) y diámetro D (mm):
     * rpm = (Vc * 1000) / (π * D)
     *
     * @param float $vc Velocidad de corte (m/min)
     * @param float $D  Diámetro de la herramienta (mm)
     * @return float    RPM (rev/min)
     */
    public static function rpm(float $vc, float $D): float
    {
        if ($D <= 0.0) {
            dbg("[rpm] Diámetro inválido D={$D}");
            return 0.0;
        }
        return ($vc * 1000.0) / (M_PI * $D);
    }

    /**
     * Calcula el avance de herramienta Vf (mm/min):
     * Vf = rpm * fz * Z
     *
     * @param float $rpm RPM (rev/min)
     * @param float $fz  Avance por diente (mm/diente)
     * @param int   $Z   Número de filos
     * @return float     Vf (mm/min)
     */
    public static function feed(float $rpm, float $fz, int $Z): float
    {
        if ($rpm < 0 || $fz < 0 || $Z <= 0) {
            dbg("[feed] Parámetros inválidos rpm={$rpm}, fz={$fz}, Z={$Z}");
            return 0.0;
        }
        return $rpm * $fz * $Z;
    }

    /**
     * Calcula la tasa de remoción de material (MMR) en mm³/min:
     * MMR = ap * feed * ae
     *
     * @param float $ap   Profundidad de pasada (mm)
     * @param float $feed Avance Vf (mm/min)
     * @param float $ae   Ancho de pasada (mm)
     * @return float      MMR (mm³/min)
     */
    public static function mmr(float $ap, float $feed, float $ae): float
    {
        if ($ap < 0 || $feed < 0 || $ae < 0) {
            dbg("[mmr] Parámetros inválidos ap={$ap}, feed={$feed}, ae={$ae}");
            return 0.0;
        }
        return $ap * $feed * $ae;
    }

    /**
     * Calcula la fuerza de corte tangencial Fct (N):
     * Fct = [Kc11 · hm^(–mc) · ap · hm · Z · (1 + coefSeg · tan(alpha))] / cos(phi)
     *
     * @param float $Kc11    Coef. específico de corte (N/mm²)
     * @param float $hm      Espesor medio de viruta (mm)
     * @param float $mc      Exponente mc
     * @param float $ap      Profundidad de pasada (mm)
     * @param int   $Z       Número de filos
     * @param float $coefSeg Coeficiente de seguridad ≥1
     * @param float $alpha   Ángulo de ataque (rad)
     * @param float $phi     Ángulo de compromiso (rad)
     * @return float         Fct (N)
     */
    public static function Fct(
        float $Kc11,
        float $hm,
        float $mc,
        float $ap,
        int   $Z,
        float $coefSeg,
        float $alpha,
        float $phi
    ): float {
        if ($hm <= 0.0 || $ap <= 0.0 || $Z <= 0) {
            return 0.0;
        }
        $force = $Kc11 * pow($hm, -$mc) * $ap * $hm * $Z * (1.0 + $coefSeg * tan($alpha));
        return cos($phi) !== 0.0 ? $force / cos($phi) : $force;
    }

    /**
     * Calcula la potencia de corte:
     * W  = (Fct · vc) / (60 · η)
     * kW = W / 1000
     * HP = kW · 1.341
     *
     * @param float $Fct Fuerza de corte (N)
     * @param float $vc  Velocidad de corte (m/min)
     * @param float $eta Eficiencia (0 < η ≤ 1), por defecto 0.85
     * @return array     [ Potencia_W (int), Potencia_HP (float 2d) ]
     */
    public static function potencia(float $Fct, float $vc, float $eta = 0.85): array
    {
        if ($eta <= 0.0) {
            dbg("[potencia] Eficiencia inválida η={$eta}, usando 1.0");
            $eta = 1.0;
        }
        // W = (Fct·vc) / (60·η)
        $W = ($Fct * $vc) / (60.0 * $eta);
        $kW = $W / 1000.0;
        $HP = $kW * 1.341;
        return [(int)round($W), round($HP, 2)];
    }
}

/**
 * Stub de debug: registra mensajes en el log de errores.
 * En entorno de desarrollo, esto ayuda a trazar cálculos.
 *
 * @param string $msg Mensaje de debug
 */
if (!function_exists('dbg')) {
    function dbg(string $msg): void
    {
        error_log("[CNCCalculator] " . $msg);
    }
}
