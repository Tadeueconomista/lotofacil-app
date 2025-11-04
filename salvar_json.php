<?php
$data = file_get_contents("php://input");
file_put_contents("lotofacil_combinacoes_convertido.json", $data);
echo json_encode(["status" => "ok"]);