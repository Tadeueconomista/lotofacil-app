// ✅ Atualiza concursos da Lotofácil
async function atualizarTodosConcursos() {
  const status = document.getElementById("statusAtualizacao");
  if (status) {
    status.textContent = "⏳ Atualizando concursos...";
    status.style.color = "#4682b4";
    status.style.fontWeight = "bold";
  }

  try {
    // Chama o PHP que executa o Python
    const res = await fetch("/SorteioApp/atualizar_concursos.php", { cache: "no-store" });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);

    const dados = await res.json();
    if (!dados || typeof dados !== "object") throw new Error("Resposta inválida do servidor");

    if (status) {
      if (dados.sucesso) {
        status.textContent = "🎉 Concursos atualizados com sucesso!";
        status.style.color = "#00aa00";
      } else {
        status.textContent = dados.mensagem || "⚠️ Não foi possível atualizar.";
        status.style.color = "#cc6600";
      }
      status.style.fontWeight = "bold";
    }

    // Renderiza dezenas, jogos e array dinâmica
    renderizarConcursoLotofacil(dados);

  } catch (e) {
    if (status) {
      status.textContent = "🚫 Erro ao atualizar concursos.";
      status.style.color = "#cc0000";
      status.style.fontWeight = "bold";
    }
    console.error("Erro:", e);
  }
}

// 🔹 Renderiza dezenas, jogo sugerido e array dinâmica
function renderizarConcursoLotofacil(dados) {
  const numero = dados.ultimoSalvo ?? "desconhecido";
  const data = dados.dataConcurso ?? "";
  const dezenas = Array.isArray(dados.dezenas) ? dados.dezenas : [];
  const jogos = Array.isArray(dados.jogos) ? dados.jogos : [];

  let container = document.getElementById("resultadoDinamico");
  if (!container) {
    container = document.createElement("div");
    container.id = "resultadoDinamico";
    const area = document.getElementById("areaResultados");
    if (area) area.appendChild(container);
  }
  container.innerHTML = "";

  const titulo = document.createElement("h3");
  titulo.textContent = `Concurso nº ${numero}${data ? " - " + data : ""}`;
  container.appendChild(titulo);

  const grid = document.createElement("div");
  grid.classList.add("dezenas-grid");
  dezenas.forEach(n => {
    const span = document.createElement("span");
    span.textContent = String(n).padStart(2, "0");
    grid.appendChild(span);
  });
  container.appendChild(grid);

  if (jogos.length > 0) {
    const ultimoJogo = jogos[0];
    const jogosDiv = document.createElement("div");
    jogosDiv.classList.add("jogo-sugerido");
    jogosDiv.innerHTML = "<strong>🎯 Último jogo sugerido:</strong><br>" +
      ultimoJogo.map(n =>
        `<span style="color:${(dados.melhores || []).includes(n) ? 'blue' : 'gold'}">${String(n).padStart(2,"0")}</span>`
      ).join(" ");
    container.appendChild(jogosDiv);
  }

  const arrayDiv = document.createElement("div");
  arrayDiv.classList.add("array-concurso");
  arrayDiv.innerHTML =
    `<strong>📊 Último concurso nº ${numero}${data ? " - " + data : ""}:</strong><br>` +
    `[ ${dezenas.map(n => String(n).padStart(2,"0")).join(", ")} ]`;
  container.appendChild(arrayDiv);
}