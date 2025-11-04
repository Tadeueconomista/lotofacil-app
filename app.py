from flask import Flask, request, jsonify
from flask_cors import CORS
import random

app = Flask(__name__)
CORS(app)

@app.route('/sortear-logico', methods=['POST'])
def sortear_logico():
    try:
        dados = request.get_json(force=True)
    except Exception as e:
        return jsonify({
            'erro': '❌ JSON inválido ou não enviado corretamente.',
            'detalhes': str(e)
        }), 400

    jogo_fixo = dados.get('jogo_fixo', [])
    frequentes = dados.get('frequentes', [])
    previstos = dados.get('previstos', [])

    try:
        jogo_fixo = list(set(int(n) for n in jogo_fixo if 1 <= int(n) <= 25))
    except:
        return jsonify({'erro': '❌ jogo_fixo contém valores inválidos.'}), 400

    if len(jogo_fixo) > 15:
        return jsonify({'erro': '❌ jogo_fixo não pode ter mais que 15 números.'}), 400

    todos_numeros = set(range(1, 26))
    numeros_disponiveis = list(todos_numeros - set(jogo_fixo))
    random.shuffle(numeros_disponiveis)

    jogo_gerado = jogo_fixo.copy()
    while len(jogo_gerado) < 15 and numeros_disponiveis:
        jogo_gerado.append(numeros_disponiveis.pop())

    jogo_gerado.sort()

    return jsonify({
        'jogo': jogo_gerado,
        'fixo': sorted(jogo_fixo),
        'frequentes': frequentes,
        'previstos': previstos,
        'status': '✅ Jogo gerado com sucesso.'
    })

if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=5000)