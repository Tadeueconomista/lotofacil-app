import { readFile, writeFile } from 'fs/promises';
import { existsSync } from 'fs';
import fetch from 'node-fetch';

async function atualizarJSON() {
  const url = 'https://servicebus2.caixa.gov.br/portaldeloterias/api/lotofacil';

  try {
    const res = await fetch(url, {
      headers: {
        'Accept': 'application/json',
        'User-Agent': 'Mozilla/5.0'
      }
    });

    if (!res.ok) {
      const texto = await res.text();
      throw new Error(`Erro ao buscar dados da API da Caixa: ${res.status} - ${texto}`);
    }

    const dados = await res.json();
    const concurso = String(dados.numero);
    const dezenas = dados.listaDezenas.map(n => parseInt(n));

    const dezenasValidas = dezenas.length === 15 &&
      new Set(dezenas).size === 15 &&
      dezenas.every(n => n >= 1 && n <= 25);

    if (!dezenasValidas) {
      console.warn(`⚠️ Concurso ${concurso} ignorado: dezenas inválidas.`);
      return;
    }

    const caminho = './lotofacil_combinacoes_convertido.json';
    let atual = {};

    if (existsSync(caminho)) {
      const conteudo = await readFile(caminho, 'utf8');
      atual = JSON.parse(conteudo);
    }

    if (!atual[concurso]) {
      atual[concurso] = dezenas;
      const ordenado = Object.keys(atual)
        .sort((a, b) => parseInt(a) - parseInt(b))
        .reduce((obj, key) => {
          obj[key] = atual[key];
          return obj;
        }, {});
      await writeFile(caminho, JSON.stringify(ordenado, null, 2));
      console.log(`[${new Date().toLocaleString()}] ✅ Adicionado concurso ${concurso}`);
    } else {
      console.log(`ℹ️ Concurso ${concurso} já está presente no JSON.`);
    }
  } catch (err) {
    console.error("❌ Erro ao atualizar JSON:", err.message);
    process.exit(1);
  }
}

atualizarJSON();