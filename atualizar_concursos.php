<?php
header("Content-Type: application/json; charset=utf-8");

// 🔹 Executa Python
$comando = "\"C:\\Python313\\python.exe\" -u C:\\xampp\\htdocs\\SorteioApp\\atualizar_concursos.py 2>&1";
$retorno = shell_exec($comando);

// 🔹 Log para debug
file_put_contents(__DIR__ . "/saida_python.log", $retorno);

if ($retorno === null || trim($retorno) === "") {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "🚫 O script não retornou nada.",
        "erro" => "Sem saída do Python"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 🔹 Decodifica saída do Python
$json = json_decode($retorno, true);
if ($json === null) {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "🚫 O script não retornou JSON válido.",
        "erro" => "Saída inválida do Python",
        "log" => $retorno
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 🔹 Lê o arquivo acumulado para saber total de concursos
$arquivo = __DIR__ . "/lotofacil_combinacoes_convertido.json";
$totalConcursos = 0;
if (file_exists($arquivo)) {
    $dadosArquivo = json_decode(file_get_contents($arquivo), true);
    if (is_array($dadosArquivo)) {
        $totalConcursos = count(array_filter(array_keys($dadosArquivo), 'is_numeric'));
    }
}

// 🔹 Acrescenta essa info ao retorno
$json["totalConcursos"] = $totalConcursos;

// 🔹 Retorna para o front
echo json_encode($json, JSON_UNESCAPED_UNICODE);