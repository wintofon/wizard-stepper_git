<?php
session_start();
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="wizard_config.json"');
echo json_encode($_SESSION, JSON_PRETTY_PRINT);
