import { readFile, writeFile } from 'fs/promises';
import { existsSync } from 'fs';
import fetch from 'node-fetch';

async function atualizarJSON() {
  const url = 'https://raw.githubusercontent.com/rafaballerini/Loteria/main/api/lotofacil.json';

  try {
    const res = await fetch(url);
    if (!res.ok) throw new Error("Erro ao buscar dados da fonte gratuita");
    const lista = await res.json();

    const caminho = './lotofacil_combinacoes_convertido.json';
    let atual = {};

    if (existsSync(caminho)) {
      const conteudo = await readFile(caminho, 'utf8');
      atual = JSON.parse(conteudo);
    }

    let novos = 0;

    for (const item of lista) {
      const concurso = item.concurso;
      const dezenas = item.dezenas.map(n => parseInt(n));

      if (!atual[concurso]) {
        atual[concurso] = dezenas;
        novos++;
        console.log(`✅ Adicionado concurso ${concurso}`);
      }
    }

    if (novos > 0) {
      await writeFile(caminho, JSON.stringify(atual, null, 2));
      console.log(`🧾 ${novos} concursos adicionados ao JSON.`);
    } else {
      console.log("ℹ️ Nenhum concurso novo para adicionar.");
    }
  } catch (err) {
    console.error("❌ Erro ao atualizar JSON:", err.message);
    process.exit(1);
  }
}

atualizarJSON();