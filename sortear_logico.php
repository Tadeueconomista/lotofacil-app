<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 🔐 Recebe os números via POST
$numeros_json = $_POST['numeros'] ?? '[]';
$numeros_array = json_decode($numeros_json, true);

// 🧼 Sanitiza e valida os números
function limparDezenas($lista) {
    $limpas = [];
    foreach ($lista as $item) {
        $n = is_numeric($item) ? intval($item) : preg_replace('/\D/', '', $item);
        $n = intval($n);
        if ($n >= 1 && $n <= 25 && !in_array($n, $limpas)) {
            $limpas[] = $n;
        }
    }
    return $limpas;
}

$numeros_validos = is_array($numeros_array) ? limparDezenas($numeros_array) : [];

if (count($numeros_validos) !== 15) {
    echo json_encode(["erro" => "❌ Jogo fixo inválido ou incompleto."]);
    exit;
}

// 🐍 Executa o Python
$entrada = json_encode(["numeros" => array_values($numeros_validos)]);
$pythonPath = 'C:\\Python313\\python.exe';
$scriptPath = 'C:\\xampp\\htdocs\\SorteioApp\\sortear_logico.py';
$comando = "\"$pythonPath\" \"$scriptPath\"";

$processo = proc_open($comando, [
    0 => ['pipe', 'r'], // entrada
    1 => ['pipe', 'w'], // saída
    2 => ['pipe', 'w']  // erro
], $pipes);

if (!is_resource($processo)) {
    echo json_encode(["erro" => "❌ Falha ao executar o script Python."]);
    exit;
}

fwrite($pipes[0], $entrada);
fclose($pipes[0]);

stream_set_blocking($pipes[1], true);
stream_set_blocking($pipes[2], true);

$saida = stream_get_contents($pipes[1]);
$erro = stream_get_contents($pipes[2]);

fclose($pipes[1]);
fclose($pipes[2]);
proc_close($processo);

// 🧠 Valida saída
if (!$saida || trim($saida) === "") {
    echo json_encode(["erro" => "❌ Python não retornou dados."]);
    exit;
}

$dados = json_decode($saida, true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($dados['jogos']) || !is_array($dados['jogos'])) {
    echo json_encode(["erro" => "❌ Resposta inválida do Python."]);
    exit;
}

// 💾 Salva os jogos em JSON exclusivo
$arquivoJogos = __DIR__ . '/jogos_logicos_gerados.json';
$jogosExistentes = file_exists($arquivoJogos) ? json_decode(file_get_contents($arquivoJogos), true) : [];

$jogosExistentes[] = [
    "timestamp" => date("Y-m-d H:i:s"),
    "entrada" => array_values($numeros_validos),
    "jogos" => $dados['jogos']
];

file_put_contents($arquivoJogos, json_encode($jogosExistentes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// ✅ Retorna os jogos
echo json_encode([
    "status" => "✅ Jogos gerados com sucesso!",
    "entrada" => array_values($numeros_validos),
    "jogos" => $dados['jogos']
]);
?>