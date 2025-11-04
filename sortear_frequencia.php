<?php
// [1] Caminhos protegidos para Python e script
$python = escapeshellarg("C:\\Python313\\python.exe");
$script = escapeshellarg("C:\\xampp\\htdocs\\SorteioApp\\avaliar_conjunto.py");
$comando = "$python $script";

// [2] Executa o script Python
$saida = shell_exec($comando);

// [3] Log para depuração
file_put_contents("log_saida.txt", $saida);

// [4] Define cabeçalho JSON
header('Content-Type: application/json');

// [5] Verifica se houve resposta
if (!$saida || trim($saida) === "") {
    echo json_encode(["erro" => "❌ Backend não respondeu ou retornou vazio."]);
    exit;
}

// [6] Tenta decodificar a saída como JSON
$dados = json_decode($saida, true);

// [7] Valida estrutura do JSON
if (json_last_error() !== JSON_ERROR_NONE || !is_array($dados)) {
    echo json_encode(["erro" => "❌ Resposta do Python não é um JSON válido."]);
    exit;
}

// [8] Verifica se o campo 'jogo' existe e tem 15 números
if (!isset($dados['jogo']) || !is_array($dados['jogo']) || count($dados['jogo']) !== 15) {
    echo json_encode(["erro" => "❌ Jogo incompleto ou inválido."]);
    exit;
}

// [9] Retorna os dados corretamente
echo json_encode($dados);
?>