<?php
/**
 * File: debug.php
 *
 * Main responsibility: Part of the CNC Wizard Stepper.
 * Related files: See others in this project.
 * @TODO Extend documentation.
 */
/**
 * Helper ultra-liviano de depuración.
 *  – dbg('texto', $array) → error_log('[DBG] ...')
 */

if (!function_exists('dbg')) {
    function dbg(...$msg): void
    {
        $line = [];
        foreach ($msg as $m) {
            $line[] = is_scalar($m) ? $m : json_encode($m, JSON_UNESCAPED_UNICODE);
        }
        error_log('[DBG] ' . implode(' ', $line));
    }
}
