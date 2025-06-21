<?php
/**
 * File: wizard_helpers.php
 *
 * Main responsibility: Part of the CNC Wizard Stepper.
 * Related files: See others in this project.
 * @TODO Extend documentation.
 */
// Shared helper stubs for the wizard steps
if (!function_exists('dbg')) {
    function dbg(...$args): void
    {
        // Real implementation may be defined in includes/debug.php
    }
}
