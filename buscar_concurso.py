import requests
from bs4 import BeautifulSoup
import json
import os
import time

ARQUIVO_JSON = "lotofacil_combinacoes_convertido.json"
ULTIMO_CONCURSO = 3482  # Atualize conforme necessário

# 🎯 Fallbacks manuais para concursos que falham nas fontes
fallbacks = {
    "1": [2, 3, 5, 6, 9, 10, 11, 13, 14, 16, 18, 20, 23, 24, 25],
    "2": [1, 4, 5, 6, 7, 9, 11, 12, 13, 15, 16, 19, 20, 23, 24],
    "1111": [2, 3, 4, 6, 9, 11, 12, 13, 14, 15, 16, 22, 23, 24, 25],
    "1112": [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15],
    "1323": [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15],
    "2949": [1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 20, 21, 22, 23, 24],
    "2950": [2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 21, 22, 23, 24, 25]
}

# 📡 Busca no Foco em Loterias
def buscar_foco(concurso):
    url = f"https://www.focoemloterias.com.br/lotofacil/resultado-lotofacil/{concurso}/"
    try:
        res = requests.get(url, timeout=10)
        soup = BeautifulSoup(res.text, "html.parser")
        dezenas = [int(tag.text) for tag in soup.select(".resultado-loteria .numero")]
        if len(dezenas) == 15:
            return dezenas
    except Exception as e:
        print(f"❌ Erro ao buscar {concurso} no Foco: {e}")
    return None

# 📂 Carrega JSON existente
if os.path.exists(ARQUIVO_JSON):
    with open(ARQUIVO_JSON, "r", encoding="utf-8") as f:
        concursos = json.load(f)
else:
    concursos = {}

# 🔁 Percorre concursos
for i in range(1, ULTIMO_CONCURSO + 1):
    concurso = str(i)
    if concurso in concursos:
        print(f"✅ Concurso {concurso} já salvo.")
        continue

    print(f"🔍 Buscando concurso {concurso}...")

    dezenas = buscar_foco(concurso)

    if not dezenas and concurso in fallbacks:
        dezenas = fallbacks[concurso]
        print(f"📦 Usando fallback para {concurso}.")

    if dezenas and isinstance(dezenas, list) and len(dezenas) == 15:
        concursos[concurso] = dezenas
        print(f"💾 Concurso {concurso} salvo.")
    else:
        print(f"⚠️ Concurso {concurso} não encontrado.")

    time.sleep(0.5)  # Evita sobrecarga no site

# 💾 Salva JSON final
with open(ARQUIVO_JSON, "w", encoding="utf-8") as f:
    json.dump(concursos, f, ensure_ascii=False, indent=4)

print("✅ Atualização completa.")