<?php
header("Content-Type: application/json; charset=utf-8");

$arquivoJson = "lotofacil_combinacoes_convertido.json";
$scriptPython = "buscar_concurso.py";
$status = [];

// Verifica se o parâmetro foi enviado
if (!isset($_GET["concurso"])) {
    echo json_encode(["erro" => "Concurso não informado"]);
    exit;
}

// Força o concurso como string
$concurso = strval(trim($_GET["concurso"]));
if (!is_numeric($concurso)) {
    echo json_encode(["erro" => "Concurso inválido"]);
    exit;
}

// Executa o script Python
$comando = escapeshellcmd("python \"$scriptPython\" $concurso");
$retorno = shell_exec($comando);

// Se não houver retorno, marca como erro
if (trim($retorno) === "") {
    $status[$concurso] = "erro";
    echo json_encode(["status" => $status]);
    exit;
}

// Interpreta o retorno do Python (ex: 1323: 01 02 03 ...)
if (preg_match("/^$concurso:\s+([\d\s]+)/", $retorno, $match)) {
    $dezenas = array_map(function($d) {
        return intval(floatval($d));
    }, preg_split('/\s+/', trim($match[1])));

    // Carrega o JSON existente
    $concursos = [];
    if (file_exists($arquivoJson)) {
        $concursos = json_decode(file_get_contents($arquivoJson), true);
    }

    // Verifica se o concurso já está salvo
    if (isset($concursos[$concurso])) {
        $status[$concurso] = "existente";
    } else {
        $concursos[$concurso] = $dezenas;
        file_put_contents($arquivoJson, json_encode($concursos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $status[$concurso] = "salvo";
    }
} else {
    $status[$concurso] = "erro";
}

// Retorna o status para o JavaScript
echo json_encode(["status" => $status]);