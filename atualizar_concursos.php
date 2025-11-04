<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

$token = 'SEU_TOKEN_AQUI'; // ← substitua pelo seu token real

$arquivoLocal = __DIR__ . '/lotofacil_combinacoes_convertido.json';
$arquivoData  = __DIR__ . '/ultima_atualizacao.txt';
$concursosLocais = [];

$agora = new DateTime();
$limite = new DateTime($agora->format('Y-m-d') . ' 22:00:00');
$precisaAtualizar = true;

if (file_exists($arquivoData)) {
    $ultima = DateTime::createFromFormat('Y-m-d H:i:s', trim(file_get_contents($arquivoData)));
    if ($ultima && $ultima >= $limite) {
        $precisaAtualizar = false;
    }
}

if (file_exists($arquivoLocal)) {
    $jsonLocal = file_get_contents($arquivoLocal);
    $concursosLocais = json_decode($jsonLocal, true);
    if (!is_array($concursosLocais)) $concursosLocais = [];
}

$urlUltimo = "https://apiloterias.com.br/app/v2/resultado?loteria=lotofacil&token=$token&concurso=ultimo";
$dadosUltimo = @file_get_contents($urlUltimo);
$respostaUltimo = json_decode($dadosUltimo, true);

if (!isset($respostaUltimo['numero'])) {
    exec("python buscar_concurso.py ultimo", $saida, $retorno);
    $linha = implode(" ", $saida);
    preg_match('/\b\d{1,4}\b/', $linha, $match);
    $ultimoConcurso = isset($match[0]) ? intval($match[0]) : 0;
} else {
    $ultimoConcurso = intval($respostaUltimo['numero']);
}

if ($ultimoConcurso === 0) {
    echo json_encode(["status" => "❌ Falha ao obter último concurso."]);
    exit;
}

$novos = 0;
$dezenasCorrigidas = [];

if ($precisaAtualizar) {
    for ($i = 1; $i <= $ultimoConcurso; $i++) {
        if (isset($concursosLocais[$i])) continue;

        $url = "https://apiloterias.com.br/app/v2/resultado?loteria=lotofacil&token=$token&concurso=$i";
        $dados = @file_get_contents($url);
        $resposta = json_decode($dados, true);

        if (!isset($resposta['dezenas']) || !is_array($resposta['dezenas'])) {
            exec("python buscar_concurso.py $i", $saida, $retorno);
            $linha = implode(" ", $saida);
            preg_match_all('/\b\d{1,2}\b/', $linha, $dezenasAlt);
            $dezenas = array_map('intval', $dezenasAlt[0]);
        } else {
            $dezenas = $resposta['dezenas'];
        }

        $dezenasLimpa = array_filter(array_map(function($n) {
            $num = intval(preg_replace('/\D/', '', $n));
            return ($num >= 1 && $num <= 25) ? $num : null;
        }, $dezenas));

        if (count($dezenasLimpa) === 15) {
            sort($dezenasLimpa);
            $concursosLocais[$i] = $dezenasLimpa;
            $novos++;
        } else {
            $dezenasCorrigidas[$i] = $dezenasLimpa;
        }
    }

    ksort($concursosLocais);
    file_put_contents($arquivoLocal, json_encode($concursosLocais, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    file_put_contents($arquivoData, date('Y-m-d H:i:s'));
}

echo json_encode([
    "status" => $precisaAtualizar ? "✅ Atualização concluída!" : "⏳ Já atualizado hoje após 22h.",
    "total_concursos" => count($concursosLocais),
    "novos_adicionados" => $novos,
    "ultimo_concurso" => $ultimoConcurso,
    "dezenas" => isset($concursosLocais[$ultimoConcurso]) ? $concursosLocais[$ultimoConcurso] : [],
    "corrigidos_manualmente" => $dezenasCorrigidas
]);