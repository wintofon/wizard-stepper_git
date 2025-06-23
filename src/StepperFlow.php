<?php
/**
 * File: StepperFlow.php
 *
 * Main responsibility: Part of the CNC Wizard Stepper.
 * Related files: See others in this project.
 * @TODO Extend documentation.
 */
/** File: src/StepperFlow.php
 *  Responsibility: define flujos de 7 pasos
 */
namespace IndustrialWizard;

class StepperFlow
{
    public const FLOWS = [
        'manual' => [1,2,3,4,5,6,7],
        'auto'   => [1,2,3,4,5,6,7]
    ];

    public static function get(string $mode): array
    {
        return self::FLOWS[$mode] ?? self::FLOWS['manual'];
    }

    public static function first(): int
    {
        return 1;
    }

    public static function next(string $mode, int $current): ?int
    {
        $flow = self::get($mode);
        $i    = array_search($current, $flow, true);
        if ($i === false || !isset($flow[$i + 1])) {
            return null;
        }
        return $flow[$i + 1];
    }

    public static function isAllowed(int $step, string $mode = 'manual'): bool
    {
        return in_array($step, self::get($mode), true);
    }
}
