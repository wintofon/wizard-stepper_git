<?php
/**
 * File: Step6Service.php
 *
 * Simple service to handle input retrieval and CNC calculations
 * for the standalone step6.php example.
 */

declare(strict_types=1);

class Step6Service
{
    private \PDO $pdo;
    private array $session;

    public function __construct(\PDO $pdo, array &$session)
    {
        $this->pdo = $pdo;
        $this->session =& $session; // unused for now but kept for future use
    }

    /**
     * Performs validation and calculations returning data for the view.
     *
     * @return array<string,mixed>
     */
    public function calculate(): array
    {
        // default parameters (normally fetched from DB or session)
        $defaults = [
            'diameter'   => 10.0,   // mm
            'flutes'     => 2,
            'rpm_min'    => 1000,
            'rpm_max'    => 24000,
            'feed_max'   => 3000,   // mm/min
            'thickness'  => 5.0,    // mm
            'Kc11'       => 1800.0, // N/mm^2
            'mc'         => 0.25,
            'coef_seg'   => 1.2,
            'rack_rad'   => deg2rad(5.0),
        ];

        $fz     = isset($_POST['fz']) ? (float)$_POST['fz'] : 0.05;
        $vc     = isset($_POST['vc']) ? (float)$_POST['vc'] : 200.0;
        $ae     = isset($_POST['ae']) ? (float)$_POST['ae'] : $defaults['diameter'] / 2;
        $passes = isset($_POST['passes']) ? (int)$_POST['passes'] : 1;
        $mcVal  = isset($_POST['mc']) ? (float)$_POST['mc'] : $defaults['mc'];

        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($fz <= 0) {
                $errors[] = 'fz debe ser > 0';
            }
            if ($vc <= 0) {
                $errors[] = 'vc debe ser > 0';
            }
            if ($ae <= 0) {
                $errors[] = 'ae debe ser > 0';
            }
        }

        $result = null;
        if (!$errors) {
            $D       = $defaults['diameter'];
            $Z       = $defaults['flutes'];
            $rpmMin  = $defaults['rpm_min'];
            $rpmMax  = $defaults['rpm_max'];
            $frMax   = $defaults['feed_max'];
            $coefSeg = $defaults['coef_seg'];
            $Kc11    = $defaults['Kc11'];
            $mc      = $mcVal;
            $alpha   = $defaults['rack_rad'];
            $eta     = 0.85;

            $rpmCalc = ($vc * 1000.0) / (M_PI * $D);
            $rpm     = (int) round(max($rpmMin, min($rpmCalc, $rpmMax)));
            $feed    = min($rpm * $fz * $Z, $frMax);

            $phi = 2 * asin(min(1.0, $ae / $D));
            $hm  = $phi !== 0.0 ? ($fz * (1 - cos($phi)) / $phi) : $fz;

            $ap  = $defaults['thickness'] / max(1, $passes);
            $mmr = round(($ap * $feed * $ae) / 1000.0, 2);

            $Fct = $Kc11 * pow($hm, -$mc) * $ap * $fz * $Z * (1 + $coefSeg * tan($alpha));
            $kW  = ($Fct * $vc) / (60000.0 * $eta);
            $W   = (int) round($kW * 1000.0);
            $HP  = round($kW * 1.341, 2);

            $result = [
                'rpm'   => $rpm,
                'feed'  => $feed,
                'hp'    => $HP,
                'watts' => $W,
                'mmr'   => $mmr,
                'hm'    => $hm,
                'ap'    => $ap,
            ];
        }

        return [
            'defaults' => $defaults,
            'inputs'   => [
                'fz'     => $fz,
                'vc'     => $vc,
                'ae'     => $ae,
                'passes' => $passes,
                'mc'     => $mcVal,
            ],
            'errors'  => $errors,
            'result'  => $result,
        ];
    }
}

