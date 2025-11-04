<?php
header('Content-Type: application/json');

if (!isset($_POST['numeros'])) {
    echo json_encode(["erro" => "❌ Nenhum conjunto foi enviado."]);
    exit;
}

$numeros_json = $_POST['numeros'];
$numeros_array = json_decode($numeros_json, true);

if (!is_array($numeros_array) || count($numeros_array) !== 15) {
    echo json_encode(["erro" => "❌ Conjunto inválido ou incompleto."]);
    exit;
}

$entrada = json_encode(["numeros" => $numeros_array]);

$comando = '"C:\\Python313\\python.exe" "C:\\xampp\\htdocs\\SorteioApp\\avaliar_conjunto.py"';
$processo = proc_open($comando, [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w']
], $pipes);

if (is_resource($processo)) {
    fwrite($pipes[0], $entrada);
    fclose($pipes[0]);

    $saida = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $erro = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    proc_close($processo);

    if (!$saida || trim($saida) === "") {
        echo json_encode(["erro" => "❌ Python não retornou dados."]);
        exit;
    }

    $json_test = json_decode($saida, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(["erro" => "❌ Resposta inválida do Python."]);
        exit;
    }

    echo $saida;
    exit;
}

echo json_encode(["erro" => "❌ Falha ao executar o script Python."]);