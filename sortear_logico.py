import sys
import json
import os
import random
import time

# 📥 Recebe os dados do PHP via stdin
entrada = sys.stdin.read()
try:
    dados = json.loads(entrada)
except json.JSONDecodeError:
    print(json.dumps({"erro": "❌ JSON malformado recebido"}))
    sys.exit()

# ✅ Sanitização e validação do jogo fixo
def limpar_dezenas(lista):
    limpas = []
    for item in lista:
        try:
            n = int(float(str(item).strip()))
            if 1 <= n <= 25 and n not in limpas:
                limpas.append(n)
        except:
            continue
    return limpas

jogo_fixo = limpar_dezenas(dados.get("jogo_fixo") or dados.get("numeros") or [])

if len(jogo_fixo) != 15:
    print(json.dumps({"erro": "❌ Jogo fixo inválido ou incompleto"}))
    sys.exit()

# 📂 Caminho do arquivo de concursos
base_dir = os.path.dirname(os.path.abspath(__file__))
caminho_json = os.path.join(base_dir, 'lotofacil_combinacoes_convertido.json')

if not os.path.exists(caminho_json):
    print(json.dumps({"erro": "❌ Arquivo de concursos não encontrado"}))
    sys.exit()

# 📄 Carrega os concursos
try:
    with open(caminho_json, 'r', encoding='utf-8') as f:
        bruto = json.load(f)
except Exception as e:
    print(json.dumps({"erro": f"❌ Erro ao ler o arquivo: {str(e)}"}))
    sys.exit()

# 🧠 Extrai concursos válidos
concursos = [dezenas for dezenas in bruto.values() if isinstance(dezenas, list) and all(isinstance(n, int) and 1 <= n <= 25 for n in dezenas)]

# 📊 Frequência e previsão
frequencia = [0] * 26
for concurso in concursos:
    for n in concurso:
        frequencia[n] += 1

mais_frequentes = sorted(range(1, 26), key=lambda x: frequencia[x], reverse=True)[:15]
previstos = sorted(range(1, 26), key=lambda x: frequencia[x] + random.random() * 5, reverse=True)[:10]

# 🔍 Filtros estatísticos
def contar_sequencias_longas(jogo):
    ordenado = sorted(jogo)
    count = 1
    sequencias = 0
    for i in range(1, len(ordenado)):
        if ordenado[i] == ordenado[i - 1] + 1:
            count += 1
        else:
            if count >= 4:
                sequencias += 1
            count = 1
    if count >= 4:
        sequencias += 1
    return sequencias

def distribuicao_linha_coluna(jogo):
    linhas = [[], [], [], [], []]
    colunas = [[], [], [], [], []]
    for n in jogo:
        linha = (n - 1) // 5
        coluna = (n - 1) % 5
        linhas[linha].append(n)
        colunas[coluna].append(n)
    linhas_ok = sum(1 for l in linhas if 2 <= len(l) <= 4) >= 3
    colunas_ok = sum(1 for c in colunas if 2 <= len(c) <= 4) >= 3
    return linhas_ok and colunas_ok

def analisar_jogo(jogo):
    moldura_base = [1, 2, 3, 4, 5, 6, 10, 15, 20, 21, 22, 23, 24, 25]
    moldura = [n for n in jogo if n in moldura_base]
    pares = [n for n in jogo if n % 2 == 0]
    soma = sum(jogo)
    return {
        "moldura": len(moldura),
        "pares": len(pares),
        "soma": soma
    }

# 🎲 Geração e avaliação
def gerar_jogo():
    return sorted(random.sample(range(1, 26), 15))

def avaliar(jogo):
    acertos = len(set(jogo) & set(jogo_fixo))
    freq = len(set(jogo) & set(mais_frequentes))
    prev = len(set(jogo) & set(previstos))
    estat = analisar_jogo(jogo)
    distrib_ok = distribuicao_linha_coluna(jogo)
    sequencias = contar_sequencias_longas(jogo)

    relevante = (
        acertos >= 10 or
        (freq >= 7 and prev >= 5 and 5 <= estat["moldura"] <= 10 and distrib_ok and sequencias <= 2)
    )

    origem = "🎯 por acertos" if acertos >= 12 else "📈 por tendência"
    return {
        "numeros": jogo,
        "acertos": acertos,
        "origem": origem,
        "status": "🟢 relevante" if relevante else "⚪ comum"
    }

# ⏱️ Loop de geração
jogos_relevantes = []
inicio = time.time()
tentativas = 0

while len(jogos_relevantes) < 20 and time.time() - inicio < 30:
    jogo = gerar_jogo()
    resultado = avaliar(jogo)
    tentativas += 1
    if resultado["status"] == "🟢 relevante":
        jogos_relevantes.append(resultado)

# 📤 Saída final
print(json.dumps({
    "jogos": jogos_relevantes,
    "tentativas": tentativas,
    "mais_frequentes": mais_frequentes,
    "previstos": previstos
}, ensure_ascii=False, indent=2))