import json

def carregar_jogos_estrategicos(caminho_json):
    try:
        with open(caminho_json, 'r', encoding='utf-8') as arquivo:
            dados = json.load(arquivo)
            return dados.get("jogos", [])
    except Exception as e:
        print(f"❌ Erro ao carregar o arquivo: {e}")
        return []

def analisar_jogo(jogo, index):
    print(f"\n🎲 Jogo {index + 1}: {jogo['numeros']}")
    print(f"🔁 Repetidos do último concurso: {jogo.get('repetidos', [])}")
    print(f"📈 Frequentes: {jogo.get('frequentes', [])}")
    print(f"📊 Moldura: {jogo.get('moldura')}")
    print(f"⚖️ Pares: {jogo.get('pares')}")
    print(f"➕ Soma: {jogo.get('soma')}")
    print(f"📉 Média de frequência: {jogo.get('mediaFreq')}")
    print(f"📡 Trios orbitais: {', '.join(jogo.get('trios', [])[:5]) or 'Nenhum'}")
    
    if jogo.get("aprovado"):
        print("🧪 Peneira estatística: ✅ Aprovado")
    else:
        print("🧪 Peneira estatística: ❌ Reprovado")
        for motivo in jogo.get("motivos", []):
            print(f"   • {motivo}")

def executar_analise(caminho_json):
    jogos = carregar_jogos_estrategicos(caminho_json)
    if not jogos:
        print("⚠️ Nenhum jogo encontrado ou arquivo inválido.")
        return

    print(f"✅ {len(jogos)} jogos estratégicos carregados.\n")
    for i, jogo in enumerate(jogos):
        analisar_jogo(jogo, i)

# 🔧 Caminho do arquivo JSON gerado pelo PHP
executar_analise("estrategia_bitcoin.json")