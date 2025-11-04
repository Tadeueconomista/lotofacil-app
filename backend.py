from flask import Flask, request, jsonify
from flask_cors import CORS
import random

app = Flask(__name__)
CORS(app)  # ✅ Permite chamadas do frontend sem bloqueio de origem

@app.route('/status', methods=['GET'])
def status():
    return jsonify({"status": "ok"}), 200

@app.route('/sortear-logico', methods=['POST'])
def sortear_logico():
    try:
        dados = request.get_json()
        jogo_fixo = dados.get("jogo_fixo", [])
        frequentes = dados.get("frequentes", [])
        previstos = dados.get("previstos", [])
        quantidade = int(dados.get("quantidade", 9))

        if not isinstance(jogo_fixo, list) or len(jogo_fixo) != 15:
            return jsonify({"status": "erro", "mensagem": "Jogo fixo inválido"}), 400

        todos = list(range(1, 26))
        jogos = []

        for _ in range(quantidade):
            candidatos = [n for n in todos if n not in jogo_fixo]
            random.shuffle(candidatos)

            jogo = jogo_fixo[:5]  # usa parte do fixo
            jogo += candidatos[:10]  # completa com aleatórios

            jogo = sorted(set(jogo))[:15]  # garante 15 únicos
            jogos.append({"numeros": jogo})

        return jsonify({"status": "ok", "jogos": jogos}), 200

    except Exception as e:
        return jsonify({"status": "erro", "mensagem": str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)