<?php
// 🧠 Disparo do Apache automaticamente via .bat (somente se não estiver ativo)
$userProfile = getenv("USERPROFILE");
$batPath = $userProfile . "\\Desktop\\inicia_apache.bat";

// Testa se o Apache está ouvindo na porta 80
$apacheOnline = @fsockopen('localhost', 80);
if (!$apacheOnline && file_exists($batPath)) {
    shell_exec('start "" "' . $batPath . '"');
}
@fclose($apacheOnline);

// Define o cabeçalho como JSON
header('Content-Type: application/json');

// Usa o número do concurso se for passado, senão busca o último
$concurso = isset($_GET['concurso']) ? intval($_GET['concurso']) : null;
$url = $concurso
  ? "https://servicebus2.caixa.gov.br/portaldeloterias/api/lotofacil/$concurso"
  : "https://servicebus2.caixa.gov.br/portaldeloterias/api/lotofacil";

// Requisição cURL à API da Caixa
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

// Executa e obtém resposta
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Validação da resposta
if ($response === false || $httpCode !== 200) {
  echo json_encode(["error" => "Erro na requisição à API."]);
  exit;
}

// Decodifica JSON
$data = json_decode($response, true);

// Verifica se os dados são válidos
if (isset($data["listaDezenas"]) && isset($data["numero"])) {
  $resultado = [
    "numero" => $data["numero"],
    "dezenas" => $data["listaDezenas"],
    "data"   => $data["dataApuracao"] ?? null,
    "local"  => $data["localSorteio"] ?? null
  ];

  // 💾 Grava no arquivo lotofacil_combinacoes_convertido.json
  $arquivo = "lotofacil_combinacoes_convertido.json";
  $historico = [];

  if (file_exists($arquivo)) {
    $conteudo = file_get_contents($arquivo);
    $historico = json_decode($conteudo, true);
    if (!is_array($historico)) {
      $historico = [];
    }
  }

  // Atualiza ou adiciona o concurso
  $historico[$data["numero"]] = array_map('intval', $data["listaDezenas"]);

  // Salva o novo histórico
  file_put_contents($arquivo, json_encode($historico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

  // Retorna para o frontend
  echo json_encode($resultado);
} else {
  echo json_encode(["error" => "Concurso não encontrado ou inválido."]);
}
?>