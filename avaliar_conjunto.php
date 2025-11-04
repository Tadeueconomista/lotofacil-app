<?php
header('Content-Type: application/json');

// 🔍 Recebe o campo 'numeros' via POST
$numerosBruto = $_POST['numeros'] ?? '';

if (!$numerosBruto) {
    echo json_encode(["erro" => "❌ Nenhum conjunto recebido."]);
    exit;
}

// 🔧 Corrige entrada malformada
$numerosCorrigido = str_replace("'", '"', $numerosBruto);
$numerosCorrigido = preg_replace('/,\s*]/', ']', $numerosCorrigido);
$numerosCorrigido = preg_replace('/,\s*}/', '}', $numerosCorrigido);

// 🔍 Tenta decodificar como JSON
$numerosArray = json_decode($numerosCorrigido, true);

// 🔄 Se falhar, tenta como lista PHP
if (!is_array($numerosArray)) {
    $numerosArray = @eval("return $numerosBruto;");
}

// 🔒 Validação final
if (!is_array($numerosArray) || count($numerosArray) !== 15) {
    echo json_encode(["erro" => "❌ Conjunto inválido ou malformado."]);
    exit;
}

// 🔢 Filtra e corrige os números
$numerosLimpos = array_filter(array_map('intval', $numerosArray), function($n) {
    return $n >= 1 && $n <= 25;
});

if (count($numerosLimpos) !== 15) {
    echo json_encode(["erro" => "❌ Conjunto deve conter 15 números válidos entre 1 e 25."]);
    exit;
}

// 🔄 Prepara payload para o Python
$payload = "numeros=" . json_encode(array_values($numerosLimpos));

// 🐍 Executa o script Python
$comando = "python avaliar_conjunto.py";
$processo = proc_open($comando, [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w']
], $pipes);

if (is_resource($processo)) {
    fwrite($pipes[0], $payload);
    fclose($pipes[0]);

    $saida = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $erro = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    proc_close($processo);

    echo $saida ?: json_encode(["erro" => "❌ Sem resposta do Python."]);
} else {
    echo json_encode(["erro" => "❌ Falha ao iniciar o script Python."]);
}