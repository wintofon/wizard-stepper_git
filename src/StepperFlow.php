<?php
/** File: src/StepperFlow.php
 *  Responsibility: define flujos de 6 pasos
 */
namespace IndustrialWizard;

class StepperFlow
{
    public const FLOWS = [
        'manual' => [1,2,3,4,5,6],
        'auto'   => [1,2,3,4,5,6]
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
        return $flow[$i+1] ?? null;
    }

    public static function isAllowed(int $step): bool
    {
        return $step >= 1 && $step <= 6;
    }
}
