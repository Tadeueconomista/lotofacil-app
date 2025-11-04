const fs = require("fs");

const original = JSON.parse(fs.readFileSync("lotofacil_combinacoes.json", "utf8"));
const convertido = {};

for (const [key, dezenas] of Object.entries(original)) {
  convertido[key] = dezenas.map(d => parseInt(d));
}

fs.writeFileSync("lotofacil_combinacoes_convertido.json", JSON.stringify(convertido, null, 2), "utf8");

console.log("✅ Arquivo convertido salvo como lotofacil_combinacoes_convertido.json");