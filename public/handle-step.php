<?php
/**
 * File: handle-step.php
 *
 * Main responsibility: Part of the CNC Wizard Stepper.
 * Related files: See others in this project.
 * @TODO Extend documentation.
 */
/** File: handle-step.php */
// Unificar BASE_URL con el valor utilizado por wizard.php
if (!getenv('BASE_URL')) {
    $base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
    putenv('BASE_URL=' . $base);
}
require_once __DIR__ . '/../src/Config/AppConfig.php';
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/StepperFlow.php';

use IndustrialWizard\StepperFlow;

header('Content-Type: application/json');

$step = filter_input(INPUT_POST,'step',FILTER_VALIDATE_INT);
$mode = $_SESSION['tool_mode'] ?? ($_POST['tool_mode'] ?? 'manual');
if (!$step || !StepperFlow::isAllowed($step, $mode)) {
    echo json_encode(['success'=>false,'error'=>'Paso inválido']);
    exit;
}


// Guardar modo si es paso 1
if ($step === 1 && isset($_POST['tool_mode'])) {
    $_SESSION['tool_mode'] = $_POST['tool_mode'];
    $mode = $_POST['tool_mode'];
}

// Guardar datos genéricos
foreach ($_POST as $k => $v) {
    if ($k !== 'step') {
        $_SESSION[$k] = $v;
    }
}

// Avanzar
$next = StepperFlow::next($mode, $step);
$_SESSION['wizard_progress'] = $next ?? $step;

echo json_encode(['success'=>true,'next'=>$next]);
