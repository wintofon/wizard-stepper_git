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

/**
 * Emit a safe error block for step6 without breaking the DOM.
 * Always returns HTTP 200 and wraps the message in <div class="step6">.
 */
if (!function_exists('step6Error')) {
    function step6Error(string $msg): never
    {
        global $embedded;

        http_response_code(200);

        if (!$embedded) {
            echo "<!DOCTYPE html><html lang=\"es\"><head>".
                 "<meta charset=\"utf-8\"><title>Step6 Error".
                 "</title></head><body>";
        }

        echo '<div class="step6"><div class="alert alert-danger m-3">'.
             htmlspecialchars($msg, ENT_QUOTES).
             '</div></div>';

        if (!$embedded) {
            echo '</body></html>';
        }
        exit;
    }
}
