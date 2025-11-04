<?php
$python = 'C:\Users\Usuário\AppData\Local\Microsoft\WindowsApps\python.exe';
$script = 'C:\\xampp\\htdocs\\SorteioApp\\verificar_backend.py';
$comando = "\"$python\" \"$script\"";

exec($comando, $saida, $codigo);

header('Content-Type: application/json');

if ($codigo === 0) {
    echo json_encode([
        "status" => "ok",
        "mensagem" => "✅ Backend executado com sucesso.",
        "log" => $saida
    ]);
} else {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "❌ Erro ao executar o backend.",
        "log" => $saida
    ]);
}
?>