<?php
header('Content-Type: application/json');

// 🔧 Função para limpar e validar dezenas
function limparDezenas($lista) {
  $limpas = [];
  foreach ($lista as $item) {
    $n = preg_replace('/\D/', '', $item);
    $n = intval($n);
    if ($n >= 1 && $n <= 25 && !in_array($n, $limpas)) {
      $limpas[] = $n;
    }
  }
  return $limpas;
}

// 📊 Funções auxiliares
function calcularMoldura($numeros) {
  $moldura = [1, 2, 3, 5, 6, 10, 11, 15, 16, 20, 21, 23, 24, 25];
  return count(array_intersect($numeros, $moldura));
}

function contarPares($numeros) {
  return count(array_filter($numeros, fn($n) => $n % 2 === 0));
}

function calcularMediaFrequencia($numeros, $frequencia) {
  $total = array_sum(array_map(fn($n) => $frequencia[$n] ?? 0, $numeros));
  return round($total / count($numeros), 2);
}

function gerarTrios($numeros) {
  $trios = [];
  for ($i = 0; $i < count($numeros) - 2; $i++) {
    $trios[] = "{$numeros[$i]}-{$numeros[$i+1]}-{$numeros[$i+2]}";
  }
  return $trios;
}

// 🔍 Função para gerar um jogo estratégico
function gerarJogo($maisFrequentes, $dezenasUltimo, $frequencia) {
  shuffle($maisFrequentes);
  $selecionados = array_slice($maisFrequentes, 0, 10);

  shuffle($dezenasUltimo);
  $ultimosSelecionados = array_slice($dezenasUltimo, 0, 5);

  $jogoBruto = array_merge($selecionados, $ultimosSelecionados);
  $jogo = limparDezenas($jogoBruto);
  $jogo = array_unique($jogo);
  sort($jogo);

  // Completa se necessário
  if (count($jogo) < 15) {
    foreach ($maisFrequentes as $n) {
      if (!in_array($n, $jogo)) {
        $jogo[] = $n;
        if (count($jogo) === 15) break;
      }
    }
  }

  if (count($jogo) !== 15) return null;

  // Estatísticas
  $dados = [
    "numeros" => $jogo,
    "repetidos" => array_values(array_intersect($jogo, $dezenasUltimo)),
    "frequentes" => array_values(array_intersect($jogo, array_slice($maisFrequentes, 0, 20))),
    "moldura" => calcularMoldura($jogo),
    "pares" => contarPares($jogo),
    "soma" => array_sum($jogo),
    "mediaFreq" => calcularMediaFrequencia($jogo, $frequencia),
    "trios" => gerarTrios($jogo),
    "aprovado" => true,
    "motivos" => []
  ];

  // 🧪 Peneira estatística
  if ($dados["moldura"] < 8 || $dados["moldura"] > 12) {
    $dados["aprovado"] = false;
    $dados["motivos"][] = "Moldura fora do padrão";
  }
  if ($dados["pares"] < 5 || $dados["pares"] > 10) {
    $dados["aprovado"] = false;
    $dados["motivos"][] = "Distribuição de pares desequilibrada";
  }
  if ($dados["soma"] < 170 || $dados["soma"] > 230) {
    $dados["aprovado"] = false;
    $dados["motivos"][] = "Soma fora da faixa ideal";
  }
  if ($dados["mediaFreq"] < 10) {
    $dados["aprovado"] = false;
    $dados["motivos"][] = "Média de frequência baixa";
  }

  return $dados;
}

// 📂 Carrega o histórico
$arquivo = 'lotofacil_combinacoes_convertido.json';
if (!file_exists($arquivo)) {
  echo json_encode(["erro" => "Arquivo de concursos não encontrado."]);
  exit;
}

$conteudo = file_get_contents($arquivo);
$concursos = json_decode($conteudo, true);
if (!$concursos || !is_array($concursos)) {
  echo json_encode(["erro" => "Erro ao ler os concursos."]);
  exit;
}

// 🔍 Último concurso e frequência
$chaves = array_keys($concursos);
$ultimo = max($chaves);
$dezenasUltimo = limparDezenas($concursos[$ultimo]);

$frequencia = [];
foreach ($concursos as $dezenas) {
  $limpas = limparDezenas($dezenas);
  foreach ($limpas as $n) {
    $frequencia[$n] = ($frequencia[$n] ?? 0) + 1;
  }
}
arsort($frequencia);
$maisFrequentes = array_keys($frequencia);

// 🔁 Gera múltiplos jogos
$jogos = [];
$tentativas = 0;
while (count($jogos) < 10 && $tentativas < 30) {
  $jogo = gerarJogo($maisFrequentes, $dezenasUltimo, $frequencia);
  if ($jogo && $jogo["aprovado"]) {
    $jogos[] = $jogo;
  }
  $tentativas++;
}

echo json_encode(["jogos" => $jogos]);
?>