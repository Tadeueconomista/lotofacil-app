import sys
import json
import urllib.parse
import os
import re
import ast
import random
from collections import Counter

# 🔍 Lê entrada via stdin
entrada_raw = sys.stdin.read().strip()

# 🐞 Grava entrada para debug
with open("debug.txt", "w", encoding="utf-8") as f:
    f.write("Entrada bruta:\n" + entrada_raw + "\n")

# UTF-8 para saída
try:
    sys.stdout.reconfigure(encoding='utf-8')
except AttributeError:
    pass

# 🔧 Decodifica campo 'numeros'
params = urllib.parse.parse_qs(entrada_raw)
numeros_json = params.get("numeros", ["[]"])[0]

# 🔧 Corrige entrada malformada
try:
    dados = json.loads(numeros_json)
except Exception:
    try:
        dados = ast.literal_eval(numeros_json)
        if not isinstance(dados, list):
            raise ValueError("Não é uma lista")
    except Exception as e2:
        print(json.dumps({"erro": f"❌ Erro ao interpretar conjunto: {str(e2)}"}))
        exit()

# 🔒 Validação básica
numeros_gerados = [int(n) for n in dados if isinstance(n, int) or str(n).isdigit()]
numeros_gerados = [n for n in numeros_gerados if 1 <= n <= 25]

# 📂 Carrega concursos históricos
def carregar_concursos(caminho='lotofacil_combinacoes_convertido.json'):
    base = os.path.dirname(os.path.abspath(__file__))
    caminho_absoluto = os.path.join(base, caminho)

    if not os.path.exists(caminho_absoluto):
        return {"erro": f"❌ Arquivo '{caminho_absoluto}' não encontrado"}

    try:
        with open(caminho_absoluto, 'r', encoding='utf-8') as f:
            bruto = json.load(f)
    except Exception as e:
        return {"erro": f"❌ Erro ao carregar JSON: {str(e)}"}

    concursos = []
    for numero, dezenas in bruto.items():
        if isinstance(dezenas, list):
            dezenas_int = [int(n) for n in dezenas if str(n).isdigit()]
            if len(dezenas_int) == 15:
                concursos.append({
                    "numero": int(numero),
                    "numeros": dezenas_int
                })
    return concursos

# 📊 Avaliação do conjunto
def avaliar_conjunto(numeros, concursos):
    if len(numeros) != 15:
        return {"erro": "Conjunto inválido: envie exatamente 15 números."}

    iguais = 0
    parciais = 0
    sequencias_comuns = set()
    premiados = []

    for concurso in concursos:
        sorteados = set(concurso["numeros"])
        acertos = len(set(numeros) & sorteados)

        if acertos == 15:
            iguais += 1
            premiados.append({ "numero": concurso["numero"], "acertos": acertos })
        elif acertos == 14:
            parciais += 1
            premiados.append({ "numero": concurso["numero"], "acertos": acertos })
        elif acertos >= 11:
            parciais += 1

        sequencia = sorted(sorteados)
        for i in range(len(sequencia) - 2):
            trio = tuple(sequencia[i:i+3])
            if all(n in numeros for n in trio):
                sequencias_comuns.add(trio)

    total = len(concursos)
    nota = round((parciais / total) * 10, 2) if total else 0
    chance_15 = round((iguais / total) * 100, 4) if total else 0
    chance_14 = round((parciais / total) * 100, 4) if total else 0

    return {
        "nota": nota,
        "chance_15": chance_15,
        "chance_14": chance_14,
        "concursos_iguais": iguais,
        "concursos_parciais": parciais,
        "sequencias_comuns": [list(seq) for seq in sequencias_comuns],
        "concursos_premiados": premiados,
        "alertas": gerar_alertas(iguais, sequencias_comuns)
    }

# 📈 Frequência das dezenas
def calcular_frequencia(concursos):
    todas = []
    for c in concursos:
        todas.extend(c["numeros"])
    return dict(Counter(todas))

# ➕ Soma total
def calcular_soma(numeros):
    return sum(numeros)

# ⚖️ Pares e ímpares
def contar_pares_impares(numeros):
    pares = len([n for n in numeros if n % 2 == 0])
    impares = len(numeros) - pares
    return { "pares": pares, "impares": impares }

# 🧩 Distribuição por linha e coluna
def distribuir_linha_coluna(numeros):
    linhas = {i: 0 for i in range(1, 6)}
    colunas = {i: 0 for i in range(1, 6)}
    for n in numeros:
        linha = ((n - 1) // 5) + 1
        coluna = ((n - 1) % 5) + 1
        linhas[linha] += 1
        colunas[coluna] += 1
    return { "linhas": linhas, "colunas": colunas }

# 🎯 Sugestões baseadas no conjunto digitado
def gerar_sugestoes_personalizadas(numeros, concursos, quantidade=3):
    if len(numeros) != 15:
        return []

    freq = calcular_frequencia(concursos)
    mais_frequentes = sorted(freq.items(), key=lambda x: x[1], reverse=True)
    dezenas_quentes = [int(n) for n, _ in mais_frequentes if int(n) not in numeros]

    trios = set()
    for i in range(len(numeros) - 2):
        trio = tuple(sorted(numeros[i:i+3]))
        trios.add(trio)

    sugestoes = []
    for _ in range(quantidade):
        base = set()
        trios_selecionados = random.sample(list(trios), min(len(trios), 2))
        for trio in trios_selecionados:
            base.update(trio)

        faltam = 15 - len(base)
        if faltam > 0:
            candidatos = [d for d in dezenas_quentes if d not in base]
            if len(candidatos) >= faltam:
                base.update(random.sample(candidatos, faltam))
            else:
                base.update(candidatos)

        sugestoes.append(sorted(base))

    return sugestoes

# ⚠️ Alertas
def gerar_alertas(iguais, sequencias):
    alertas = []
    if iguais == 0:
        alertas.append("✅ Nunca ocorreu esse conjunto completo")
    if sequencias:
        alertas.append(f"🔍 Sequências comuns detectadas: {len(sequencias)}")
    if not sequencias:
        alertas.append("⚠️ Conjunto sem trios sequenciais comuns")
    return alertas

# 🚀 Execução principal
try:
    concursos = carregar_concursos()
    if isinstance(concursos, dict) and "erro" in concursos:
        print(json.dumps(concursos))
        exit()

    resposta = {}

    if len(numeros_gerados) == 15:
        resposta["avaliacao"] = avaliar_conjunto(numeros_gerados, concursos)
        resposta["soma_total"] = calcular_soma(numeros_gerados)
        resposta["pares_impares"] = contar_pares_impares(numeros_gerados)
        resposta["distribuicao"] = distribuir_linha_coluna(numeros_gerados)
        resposta["frequencia"] = calcular_frequencia(concursos)
        resposta["sugestoes"] = gerar_sugestoes_personalizadas(numeros_gerados, concursos)
    else:
        resposta["erro"] = "❌ Conjunto inválido: não contém 15 números válidos."

    print(json.dumps(resposta, ensure_ascii=False, indent=2))

except Exception as e:
    print(json.dumps({ "erro": f"❌ Falha interna: {str(e)}" }))