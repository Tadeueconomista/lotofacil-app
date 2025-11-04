<?php
// Caminho do Python instalado via Microsoft Store
$python = 'C:\\Users\\Usuário\\AppData\\Local\\Microsoft\\WindowsApps\\python.exe';

// Caminho do script Python que você quer executar
$script = 'C:\\xampp\\htdocs\\SorteioApp\\verificar_backend.py';

// Monta o comando com aspas para proteger espaços e caracteres especiais
$comando = "\"$python\" \"$script\"";

// Executa o comando e captura saída e código de retorno
exec($comando, $saida, $codigo);

// Define o tipo de resposta como JSON
header('Content-Type: application/json');

// Retorna resposta estruturada
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