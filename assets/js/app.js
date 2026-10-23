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

        // Horário só é liberado depois de escolher o período, limitado à faixa dele
        const aplicarPeriodo = (inicial) => {
            const op = periodo.selectedOptions[0];
            const ok = !!periodo.value;
            ini.disabled = fim.disabled = !ok;
            if (!ok) { ini.value = fim.value = ''; return; }
            [ini, fim].forEach((c) => { c.min = op.dataset.inicio; c.max = op.dataset.fim; });
            const fora = (v) => !v || v < op.dataset.inicio || v > op.dataset.fim;
            if (!inicial || fora(ini.value)) ini.value = op.dataset.inicio;
            if (!inicial || fora(fim.value)) fim.value = op.dataset.fim;
        };
        periodo.addEventListener('change', () => { aplicarPeriodo(false); validarHoras(); });
        aplicarPeriodo(true);

        const validarHoras = () => {
            fim.setCustomValidity(ini.value && fim.value && ini.value >= fim.value
                ? 'O término deve ser depois do início.' : '');
        };
        ini.addEventListener('input', validarHoras);
        fim.addEventListener('input', validarHoras);

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
