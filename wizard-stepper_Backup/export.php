<?php
session_start();
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="wizard_config.txt"');
foreach ($_SESSION as $k => $v) {
    echo "$k: " . (is_array($v) ? json_encode($v) : $v) . "\n";
}
