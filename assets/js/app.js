// =========================================================
// Comportamentos de interface
// =========================================================
document.addEventListener('DOMContentLoaded', () => {

    // ---------- Login (página pública) ----------
    const btnLogin = document.getElementById('btnLogin');
    const loginBox = document.getElementById('loginBox');
    if (btnLogin && loginBox) {
        btnLogin.addEventListener('click', (e) => {
            e.stopPropagation();
            loginBox.classList.toggle('aberto');
            if (loginBox.classList.contains('aberto')) loginBox.querySelector('input[name=usuario]').focus();
        });
        loginBox.addEventListener('click', (e) => e.stopPropagation());
        document.addEventListener('click', () => loginBox.classList.remove('aberto'));
    }

    // ---------- Menu lateral no celular ----------
    document.querySelectorAll('[data-toggle-sidebar]').forEach((el) =>
        el.addEventListener('click', () => document.body.classList.toggle('menu-aberto')));

    // ---------- Ordenação das tabelas ----------
    // Clique no cabeçalho: 1º maior → menor, 2º menor → maior, 3º volta à ordem original.
    // Só uma coluna por vez: clicar em outra coluna recomeça por ela.
    // Usa data-ordem da célula quando o texto exibido não ordena bem (ex.: datas dd/mm/aaaa).
    const comparar = new Intl.Collator('pt-BR', { numeric: true, sensitivity: 'base' }).compare;
    document.querySelectorAll('table.tabela').forEach((tabela) => {
        const corpo = tabela.tBodies[0];
        if (!corpo || !tabela.tHead) return;
        const original = [...corpo.rows];
        const cabecalhos = [...tabela.tHead.rows[0].cells];
        const valor = (linha, i) => {
            const c = linha.cells[i];
            return c ? (c.dataset.ordem ?? c.textContent).trim() : '';
        };
        let coluna = -1, sentido = 0; // sentido: 0 original, -1 maior → menor, 1 menor → maior

        const aplicar = () => {
            const linhas = sentido === 0 ? original
                : [...original].sort((a, b) => sentido * comparar(valor(a, coluna), valor(b, coluna)));
            corpo.append(...linhas);
            cabecalhos.forEach((th, i) => th.setAttribute('aria-sort',
                i !== coluna || sentido === 0 ? 'none' : sentido < 0 ? 'descending' : 'ascending'));
        };

        cabecalhos.forEach((th, i) => {
            if (th.classList.contains('acoes')) return;
            th.classList.add('ordenavel');
            th.tabIndex = 0;
            th.setAttribute('aria-sort', 'none');
            th.title = 'Clique para ordenar';
            const alternar = () => {
                if (coluna !== i) { coluna = i; sentido = -1; }
                else sentido = sentido === -1 ? 1 : 0;
                aplicar();
            };
            th.addEventListener('click', alternar);
            th.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); alternar(); }
            });
        });
    });

    // ---------- Pop-up de decisão ----------
    const modal = document.getElementById('modalDecisao');
    if (modal) {
        const form = document.getElementById('formDecisao');
        const motivo = document.getElementById('decisaoMotivo');
        const tipo = document.getElementById('decisaoTipo');

        document.querySelectorAll('[data-decisao]').forEach((btn) => btn.addEventListener('click', () => {
            document.getElementById('decisaoId').value = btn.dataset.id;
            document.getElementById('decisaoTitulo').textContent = btn.dataset.titulo;
            motivo.value = '';
            modal.hidden = false;
            motivo.focus();
        }));

        // guarda qual botão foi clicado (Aprovar / Reprovar / Ajustar)
        form.querySelectorAll('button[data-tipo]').forEach((b) =>
            b.addEventListener('click', () => { tipo.value = b.dataset.tipo; }));

        form.addEventListener('submit', (e) => {
            if (tipo.value !== 'aprovar' && motivo.value.trim() === '') {
                e.preventDefault();
                alert('Informe o motivo para reprovar ou solicitar ajuste.');
                motivo.focus();
            }
        });

        const fechar = () => { modal.hidden = true; };
        modal.querySelector('[data-fechar-modal]').addEventListener('click', fechar);
        modal.addEventListener('click', (e) => { if (e.target === modal) fechar(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') fechar(); });
    }

    // ---------- Formulário de solicitação ----------
    const periodo = document.getElementById('periodo');
    if (periodo) {
        const ini = document.getElementById('horaInicio');
        const fim = document.getElementById('horaFim');

        // Horário só é liberado depois de escolher o período e só lista horários da faixa dele.
        // O término só oferece horários depois do início escolhido.
        const INTERVALO = 15; // minutos entre as opções
        const minutos = (h) => { const [a, b] = h.split(':').map(Number); return a * 60 + b; };
        const hora = (m) => `${String(Math.floor(m / 60)).padStart(2, '0')}:${String(m % 60).padStart(2, '0')}`;

        const preencher = (campo, de, ate, valor) => {
            const horas = [];
            for (let m = minutos(de); m <= minutos(ate); m += INTERVALO) horas.push(hora(m));
            // mantém um horário já salvo fora do intervalo (ex.: 08:05) para não perdê-lo na edição
            if (valor && valor >= de && valor <= ate && !horas.includes(valor)) horas.push(valor), horas.sort();
            campo.innerHTML = '<option value="">Selecione...</option>' +
                horas.map((h) => `<option value="${h}">${h}</option>`).join('');
            campo.value = horas.includes(valor) ? valor : '';
            campo.disabled = false;
        };
        const bloquear = (campo) => { campo.innerHTML = '<option value="">Selecione...</option>'; campo.disabled = true; };

        const aplicarInicio = (valorFim) => {
            const op = periodo.selectedOptions[0];
            if (!periodo.value || !ini.value) return bloquear(fim);
            preencher(fim, hora(minutos(ini.value) + INTERVALO), op.dataset.fim, valorFim ?? fim.value);
        };
        const aplicarPeriodo = (valorIni, valorFim) => {
            const op = periodo.selectedOptions[0];
            if (!periodo.value) { bloquear(ini); bloquear(fim); return; }
            preencher(ini, op.dataset.inicio, hora(minutos(op.dataset.fim) - INTERVALO), valorIni);
            aplicarInicio(valorFim);
        };
        // trocar o período limpa os horários
        periodo.addEventListener('change', () => aplicarPeriodo('', ''));
        ini.addEventListener('change', () => aplicarInicio());
        aplicarPeriodo(ini.dataset.valor, fim.dataset.valor);

        // Tema "Outro" → campo descritivo obrigatório
        const tema = document.getElementById('tema');
        const temaWrap = document.getElementById('temaOutroWrap');
        const temaDesc = document.getElementById('temaOutro');
        const aplicarTema = () => {
            temaWrap.hidden = tema.value !== 'outro';
            temaDesc.required = tema.value === 'outro';
        };
        tema.addEventListener('change', aplicarTema);
        aplicarTema();

        // Outros recursos → campo descritivo obrigatório
        const outros = document.getElementById('recOutros');
        const wrap = document.getElementById('outrosDescWrap');
        const desc = document.getElementById('outrosDesc');
        const aplicarOutros = () => {
            wrap.hidden = !outros.checked;
            desc.required = outros.checked;
        };
        outros.addEventListener('change', aplicarOutros);
        aplicarOutros();

        // Mostra horários já reservados (aprovados) no ambiente/data escolhidos
        const ambiente = document.getElementById('ambiente');
        const data = document.getElementById('dataEvento');
        const caixa = document.getElementById('ocupados');
        const carregarOcupados = async () => {
            if (!ambiente.value || !data.value) { caixa.hidden = true; return; }
            const qs = new URLSearchParams({ ambiente: ambiente.value, data: data.value, ignorar: caixa.dataset.ignorar });
            try {
                const r = await fetch(`${window.BASE_URL}/api/ocupados.php?${qs}`);
                const lista = await r.json();
                caixa.hidden = false;
                caixa.classList.toggle('livre', lista.length === 0);
                caixa.innerHTML = lista.length === 0
                    ? '✔ Nenhum horário reservado neste ambiente nesta data.'
                    : '<strong>Horários já reservados neste ambiente:</strong><ul>' +
                      lista.map((o) => `<li>${o.inicio} às ${o.fim} — ${escapar(o.titulo)}</li>`).join('') + '</ul>';
            } catch { caixa.hidden = true; }
        };
        ambiente.addEventListener('change', carregarOcupados);
        data.addEventListener('change', carregarOcupados);
        carregarOcupados();
    }

    // ---------- Relatórios ----------
    const formRel = document.getElementById('formRelatorio');
    if (formRel) {
        const de = formRel.elements.de;
        const ate = formRel.elements.ate;
        const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

        // atalhos de período
        formRel.querySelectorAll('[data-periodo]').forEach((b) => b.addEventListener('click', () => {
            const h = new Date(), a = h.getFullYear(), m = h.getMonth();
            const faixas = {
                'mes':          [new Date(a, m, 1),     new Date(a, m + 1, 0)],
                'mes-anterior': [new Date(a, m - 1, 1), new Date(a, m, 0)],
                'trimestre':    [new Date(a, m, 1),     new Date(a, m + 3, 0)],
                'ano':          [new Date(a, 0, 1),     new Date(a, 11, 31)],
            };
            const [i, f] = faixas[b.dataset.periodo];
            de.value = iso(i);
            ate.value = iso(f);
        }));

        // marcar/desmarcar todas as colunas
        formRel.querySelectorAll('[data-colunas]').forEach((b) => b.addEventListener('click', () => {
            formRel.querySelectorAll('input[name="col[]"]').forEach((c) => { c.checked = b.dataset.colunas === 'todas'; });
        }));

        formRel.addEventListener('submit', (e) => {
            const falta = !formRel.querySelector('input[name="col[]"]:checked') ? 'Selecione pelo menos uma coluna.'
                : !formRel.querySelector('input[name="status[]"]:checked') ? 'Selecione pelo menos uma situação.'
                : de.value > ate.value ? 'A data inicial deve ser anterior ou igual à final.' : '';
            if (falta) { e.preventDefault(); alert(falta); }
        });
    }
});

function escapar(t) {
    const d = document.createElement('div');
    d.textContent = t;
    return d.innerHTML;
}
