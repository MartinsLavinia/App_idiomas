<?php
header('Content-Type: application/json');
echo json_encode(['test' => 'API funcionando', 'time' => date('Y-m-d H:i:s')]);
?>