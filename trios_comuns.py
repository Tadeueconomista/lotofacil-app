import json
import os
from collections import Counter

def resposta_erro(mensagem):
    print(json.dumps({"erro": f"❌ Erro: {mensagem}"}))
    exit()

def carregar_concursos(caminho='lotofacil_combinacoes_convertido.json'):
    base_dir = os.path.dirname(os.path.abspath(__file__))
    caminho_absoluto = os.path.join(base_dir, caminho)

    if not os.path.exists(caminho_absoluto):
        resposta_erro(f"Arquivo '{caminho_absoluto}' não encontrado.")

    try:
        with open(caminho_absoluto, 'r', encoding='utf-8') as arquivo:
            bruto = json.load(arquivo)
    except Exception as e:
        resposta_erro(f"Erro ao ler JSON: {str(e)}")

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

def trios_mais_comuns(concursos, minimo_ocorrencias=3):
    contador_trios = Counter()
    total_concursos = len(concursos)

    for concurso in concursos:
        numeros = sorted(concurso["numeros"])
        for i in range(len(numeros) - 2):
            trio = tuple(numeros[i:i+3])
            contador_trios[trio] += 1

    trios_frequentes = [
        {
            "trio": list(trio),
            "ocorrencias": ocorrencias,
            "porcentagem": round((ocorrencias / total_concursos) * 100, 2)
        }
        for trio, ocorrencias in contador_trios.items()
        if ocorrencias >= minimo_ocorrencias
    ]

    trios_ordenados = sorted(trios_frequentes, key=lambda x: x["ocorrencias"], reverse=True)
    return trios_ordenados

try:
    concursos = carregar_concursos()
    resultado = trios_mais_comuns(concursos)

    if not resultado:
        print(json.dumps({"trios_comuns": [], "mensagem": "⚠️ Nenhum trio encontrado com os critérios atuais."}))
    else:
        print(json.dumps({"trios_comuns": resultado}, ensure_ascii=False, indent=2))

except Exception as e:
    resposta_erro(f"Falha interna: {str(e)}")