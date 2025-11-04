<?php
header('Content-Type: application/json');

// 🧠 Dispara o Apache via .bat se necessário
$userProfile = getenv("USERPROFILE");
$batPath = $userProfile . "\\Desktop\\inicia_apache.bat";
$apacheOnline = @fsockopen('localhost', 80);
if (!$apacheOnline && file_exists($batPath)) {
    shell_exec('start "" "' . $batPath . '"');
}
@fclose($apacheOnline);

// 📦 Local do arquivo JSON onde ficam os concursos salvos
$arquivoJson = 'lotofacil_combinacoes.json';

// 📂 Carrega os concursos existentes
$dadosExistentes = file_exists($arquivoJson)
    ? json_decode(file_get_contents($arquivoJson), true)
    : [];
if (!is_array($dadosExistentes)) {
    $dadosExistentes = [];
}

// 📊 Descobre o último concurso já salvo
$ultimaChave = array_key_last($dadosExistentes);
$ultimoSalvo = $ultimaChave ? intval($ultimaChave) : 0;

// 🔄 Busca os dados mais recentes da API principal
$urlAtual = "https://servicebus2.caixa.gov.br/portaldeloterias/api/lotofacil";
$chAtual = curl_init($urlAtual);
curl_setopt($chAtual, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chAtual, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($chAtual, CURLOPT_SSL_VERIFYHOST, false);
$responseAtual = curl_exec($chAtual);
curl_close($chAtual);

// 🎯 Decodifica a resposta atual
$dados = json_decode($responseAtual, true);
if (!isset($dados['numero']) || !isset($dados['listaDezenas']) || !is_array($dados['listaDezenas'])) {
    echo json_encode(["status" => false, "mensagem" => "Resposta inválida da API."]);
    exit;
}

$numeroAtual = intval($dados['numero']);
$dezenasAtuais = $dados['listaDezenas'];
$novos = [];

// 🔁 Busca concursos não salvos
for ($i = $ultimoSalvo + 1; $i <= $numeroAtual; $i++) {
    $urlConc = "https://servicebus2.caixa.gov.br/portaldeloterias/api/lotofacil/$i";
    $chConc = curl_init($urlConc);
    curl_setopt($chConc, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chConc, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($chConc, CURLOPT_SSL_VERIFYHOST, false);
    $respConc = curl_exec($chConc);
    curl_close($chConc);

    $info = json_decode($respConc, true);
    if (isset($info['listaDezenas']) && is_array($info['listaDezenas'])) {
        $dadosExistentes[$i] = $info['listaDezenas'];
        $novos[] = $i;
    }
}

// 💾 Salva os novos concursos no JSON
file_put_contents($arquivoJson, json_encode($dadosExistentes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// 📢 Retorna dados para o frontend
echo json_encode([
    "status" => true,
    "numero" => $numeroAtual,
    "dezenas" => $dezenasAtuais,
    "salvos" => $novos,
    "mensagem" => count($novos) > 0
        ? "Novos concursos salvos com sucesso."
        : "Nenhum concurso novo encontrado."
]);
?>