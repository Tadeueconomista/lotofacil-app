<?php
header('Content-Type: application/json');

// Opcional: aceitar apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["erro" => "Método inválido. Use POST."]);
    exit;
}

// Caminho do script Python
$comando = '"C:\\Python313\\python.exe" "C:\\xampp\\htdocs\\SorteioApp\\trios_comuns.py"';

// Executa o comando
$saida = shell_exec($comando);

// Verifica se houve retorno
if (!$saida || trim($saida) === "") {
    echo json_encode(["erro" => "Python não retornou dados."]);
    exit;
}

// Tenta decodificar o JSON
$json = json_decode($saida, true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($json)) {
    echo json_encode(["erro" => "Resposta inválida do Python."]);
    exit;
}

// Verifica se a chave esperada existe
if (!isset($json['trios_comuns']) || !is_array($json['trios_comuns'])) {
    echo json_encode(["erro" => "Formato inesperado na resposta do Python."]);
    exit;
}

// Retorna o JSON para o frontend
echo json_encode($json);