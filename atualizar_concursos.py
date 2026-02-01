import requests
import json
import sys
import io
import os

# 🔹 Força saída UTF-8 (para emojis e acentos no Windows)
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

HEADERS = {
    "Accept": "application/json, text/plain, */*",
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)"
}

ARQUIVO_JSON = "lotofacil_combinacoes_convertido.json"

def get_json(url: str):
    try:
        res = requests.get(url, headers=HEADERS, timeout=5)
        if res.ok:
            return res.json()
    except Exception:
        return None
    return None

# 🔹 Carrega arquivo existente
if os.path.exists(ARQUIVO_JSON):
    with open(ARQUIVO_JSON, "r", encoding="utf-8") as f:
        data_json = json.load(f)
else:
    data_json = {}

# 🔹 Busca último concurso disponível na API
dados = get_json("https://servicebus2.caixa.gov.br/portaldeloterias/api/lotofacil")

if dados and "listaDezenas" in dados:
    ultimo_disponivel = dados.get("numero")
    data_concurso = dados.get("dataApuracao")

    # 🔹 Busca todos os concursos desde o 1 até o último disponível
    for n in range(1, ultimo_disponivel + 1):
        if str(n) not in data_json:  # só baixa se ainda não existir
            url = f"https://servicebus2.caixa.gov.br/portaldeloterias/api/lotofacil/{n}"
            dados_concurso = get_json(url)
            if dados_concurso and "listaDezenas" in dados_concurso:
                try:
                    dezenas = [int(x) for x in dados_concurso["listaDezenas"]]
                except Exception:
                    dezenas = dados_concurso["listaDezenas"]
                data_json[str(n)] = dezenas

    # 🔹 Salva de volta
    with open(ARQUIVO_JSON, "w", encoding="utf-8") as f:
        json.dump(data_json, f, ensure_ascii=False, indent=2)

    # 🔹 Monta saída JSON para o PHP/JS
    saida = {
        "sucesso": True,
        "mensagem": f"✅ Concursos atualizados até {ultimo_disponivel} ({data_concurso})",
        "ultimoSalvo": ultimo_disponivel,
        "dataConcurso": data_concurso,
        "dezenas": data_json[str(ultimo_disponivel)],
        "totalConcursos": len([k for k in data_json.keys() if k.isdigit()])
    }
    print(json.dumps(saida, ensure_ascii=False))

else:
    saida = {
        "sucesso": False,
        "mensagem": "❌ Não foi possível atualizar",
        "dezenas": []
    }
    print(json.dumps(saida, ensure_ascii=False))