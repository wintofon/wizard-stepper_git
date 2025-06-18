<?php
/** File: handle-step.php */
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
