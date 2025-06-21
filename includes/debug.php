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
 *  – dbg('texto', $array) → console.log + <pre id="debug"> (si existe)
 */

if (!function_exists('dbg')) {
    function dbg(...$msg): void
    {
        $line = [];
        foreach ($msg as $m) {
            $line[] = is_scalar($m) ? $m : json_encode($m, JSON_UNESCAPED_UNICODE);
        }
        $text = '[DBG] '.implode(' ', $line);
        $safeJs = json_encode($text);

        // A) consola JS
        echo "<script>console.log($safeJs);</script>";

        // B) caja <pre id="debug"> (si está en el DOM)
        static $once = false;
        if (!$once) {
            echo <<<TAG
<script>
window.__DBG = t => {
  const box = document.getElementById('debug');
  if (box) box.textContent = t + '\\n' + box.textContent;
};
</script>
TAG;
            $once = true;
        }
        echo "<script>window.__DBG($safeJs);</script>";
    }
}
