import requests
import json
import os
import time
import re

ARQUIVO_JSON = "lotofacil_combinacoes_convertido.json"

# Carrega JSON existente ou inicia vazio
try:
    if os.path.exists(ARQUIVO_JSON):
        with open(ARQUIVO_JSON, "r", encoding="utf-8") as f:
            content = f.read().strip()
            concursos = json.loads(content) if content else {}
    else:
        concursos = {}
except Exception as e:
    print(f"⚠️ Erro ao carregar JSON: {e}")
    concursos = {}

# Corrige e valida dezenas
def corrigir_dezenas(dezenas_raw):
    dezenas = []
    for item in dezenas_raw:
        item = re.sub(r"[^\d]", "", str(item))
        if item.isdigit():
            n = int(item)
            if 1 <= n <= 25 and n not in dezenas:
                dezenas.append(n)
    return dezenas

def concurso_valido(dezenas):
    return (
        isinstance(dezenas, list)
        and len(dezenas) == 15
        and len(set(dezenas)) == 15
        and all(isinstance(d, int) and 1 <= d <= 25 for d in dezenas)
    )

# Busca o último concurso disponível
def obter_ultimo_concurso():
    try:
        res = requests.get("https://servicebus2.caixa.gov.br/portaldeloterias/api/lotofacil", timeout=10)
        dados = res.json()
        if "numero" in dados:
            return int(dados["numero"])
        else:
            print("⚠️ Estrutura inesperada na resposta da API da Caixa.")
            return max(map(int, concursos.keys())) if concursos else 1
    except Exception as e:
        print(f"❌ Erro ao obter último concurso: {e}")
        return max(map(int, concursos.keys())) if concursos else 1

# Busca resultado de um concurso específico
def buscar_concurso_caixa(numero):
    url = f"https://servicebus2.caixa.gov.br/portaldeloterias/api/lotofacil/{numero}"
    try:
        res = requests.get(url, timeout=10)
        dados = res.json()
        dezenas_raw = dados.get("listaDezenas", [])
        dezenas = corrigir_dezenas(dezenas_raw)
        if concurso_valido(dezenas):
            return dezenas
    except Exception as e:
        print(f"❌ Erro ao buscar concurso {numero}: {e}")
    return None

# Busca todos os concursos
ULTIMO_CONCURSO = obter_ultimo_concurso()

for i in range(1, ULTIMO_CONCURSO + 1):
    concurso = str(i)
    if concurso in concursos:
        print(f"✅ Concurso {concurso} já salvo.")
        continue

    print(f"🔍 Buscando concurso {concurso}...")

    dezenas = buscar_concurso_caixa(concurso)
    print(f"🔎 Dezenas lidas para {concurso}: {dezenas}")

    if concurso_valido(dezenas):
        concursos[concurso] = dezenas
        print(f"💾 Concurso {concurso} salvo.")
    else:
        print(f"⚠️ Concurso {concurso} inválido ou incompleto.")

    time.sleep(0.3)

# Salva JSON final
with open(ARQUIVO_JSON, "w", encoding="utf-8") as f:
    json.dump(concursos, f, ensure_ascii=False, indent=4)

print("✅ Atualização completa.")