<?php
require_once __DIR__ . '/../config/config.php';
$eu = exigir_login('admin');

// ---------- Decisão (aprovar / reprovar / ajustar) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $id      = (int)($_POST['id'] ?? 0);
    $decisao = $_POST['decisao'] ?? '';
    $motivo  = trim($_POST['motivo'] ?? '');
    $volta   = 'admin/solicitacoes.php?status=' . urlencode($_POST['filtro'] ?? 'pendente');

    $mapa = ['aprovar' => 'aprovado', 'reprovar' => 'reprovado', 'ajustar' => 'revisao'];
    if (!isset($mapa[$decisao])) {
        flash('erro', 'Decisão inválida.');
        redirect($volta);
    }
    if ($decisao !== 'aprovar' && $motivo === '') {
        flash('erro', 'O motivo é obrigatório para reprovar ou solicitar ajuste.');
        redirect($volta);
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT * FROM solicitacoes WHERE id = ? FOR UPDATE');
        $st->execute([$id]);
        $s = $st->fetch();

        // Quem decidir primeiro se torna o aprovador: só decide se ainda estiver aberta
        if (!$s || !in_array($s['status'], ['pendente', 'revisao'], true)) {
            $pdo->rollBack();
            flash('erro', 'Esta solicitação já foi decidida por outro administrador.');
            redirect($volta);
        }

        if ($decisao === 'aprovar') {
            // trava o ambiente para evitar duas aprovações simultâneas no mesmo período
            $pdo->prepare('SELECT id FROM ambientes WHERE id = ? FOR UPDATE')->execute([$s['ambiente_id']]);
            $c = conflito_agenda((int)$s['ambiente_id'], $s['data_evento'], $s['periodo'], (int)$s['id']);
            if ($c) {
                $pdo->rollBack();
                flash('erro', 'Não é possível aprovar. ' . mensagem_conflito($c));
                redirect($volta);
            }
        }

        $pdo->prepare(
            'UPDATE solicitacoes SET status = ?, observacao_admin = ?, aprovador_id = ?, decidido_em = NOW() WHERE id = ?'
        )->execute([$mapa[$decisao], $motivo !== '' ? $motivo : null, $eu['id'], $id]);
        $pdo->commit();
        flash('sucesso', 'Solicitação "' . $s['titulo'] . '" marcada como ' . STATUS[$mapa[$decisao]] . '.');
    } catch (Throwable $t) {
        $pdo->rollBack();
        flash('erro', 'Erro ao registrar a decisão.');
    }
    redirect($volta);
}

// ---------- Listagem ----------
$filtros = ['pendente' => 'Pendentes', 'revisao' => 'Em revisão', 'aprovado' => 'Aprovados', 'reprovado' => 'Reprovados', 'todos' => 'Todos'];
$filtro = $_GET['status'] ?? 'pendente';
if (!isset($filtros[$filtro])) $filtro = 'pendente';

$sql = "SELECT s.*, u.nome AS solicitante, c.nome AS curso, a.nome AS ambiente, a.localizacao,
               ap.nome AS aprovador
          FROM solicitacoes s
          JOIN usuarios u      ON u.id = s.solicitante_id
          JOIN cursos_areas c  ON c.id = s.curso_area_id
          JOIN ambientes a     ON a.id = s.ambiente_id
          LEFT JOIN usuarios ap ON ap.id = s.aprovador_id";
$p = [];
if ($filtro !== 'todos') {
    $sql .= ' WHERE s.status = ?';
    $p[] = $filtro;
}
$sql .= ' ORDER BY s.criado_em, s.id';
$st = db()->prepare($sql);
$st->execute($p);
$lista = $st->fetchAll();

$titulo = 'Solicitações';
$menu = 'solicitacoes';
require __DIR__ . '/../includes/layout_topo.php';
?>
<div class="cabecalho-pagina">
    <h1>Solicitações</h1>
    <a class="btn btn-primario" href="<?= url('admin/solicitacao_form.php') ?>">+ Novo evento</a>
</div>

<div class="filtros">
    <?php foreach ($filtros as $k => $rot): ?>
        <a class="chip <?= $filtro === $k ? 'ativo' : '' ?>" href="?status=<?= $k ?>"><?= $rot ?></a>
    <?php endforeach; ?>
</div>

<div class="card">
    <?php if (!$lista): ?>
        <p class="vazio">Nenhuma solicitação encontrada.</p>
    <?php else: ?>
        <table class="tabela tabela-responsiva">
            <thead>
            <tr>
                <th>Solicitante</th>
                <th>Curso/Área responsável</th>
                <th>Situação</th>
                <th>Evento</th>
                <th>Data</th>
                <th>Hora</th>
                <th>Ambiente</th>
                <th>Localização</th>
                <th class="acoes">Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($lista as $s): ?>
                <tr>
                    <td data-label="Solicitante"><?= h($s['solicitante']) ?><br><small>Criada em <?= date('d/m/Y H:i', strtotime($s['criado_em'])) ?></small></td>
                    <td data-label="Curso/Área"><?= h($s['curso']) ?></td>
                    <td data-label="Situação" data-ordem="<?= h(STATUS[$s['status']] ?? $s['status']) ?>">
                        <?= badge_status($s['status']) ?>
                        <?php if ($s['aprovador']): ?><br><small>por <?= h($s['aprovador']) ?></small><?php endif; ?>
                    </td>
                    <td data-label="Evento"><strong><?= h($s['titulo']) ?></strong></td>
                    <td data-label="Data" data-ordem="<?= h($s['data_evento']) ?>"><?= data_br($s['data_evento']) ?></td>
                    <td data-label="Hora"><?= hora_br($s['hora_inicio']) ?> – <?= hora_br($s['hora_fim']) ?></td>
                    <td data-label="Ambiente"><?= h($s['ambiente']) ?></td>
                    <td data-label="Localização"><?= h($s['localizacao']) ?></td>
                    <td class="acoes">
                        <?php if (in_array($s['status'], ['pendente', 'revisao'], true)): ?>
                            <button type="button" class="btn btn-sm btn-primario" data-decisao
                                    data-id="<?= $s['id'] ?>" data-titulo="<?= h($s['titulo']) ?>">Decisão</button>
                        <?php endif; ?>
                        <a class="btn btn-sm btn-contorno" href="<?= url('admin/solicitacao_form.php?id=' . $s['id']) ?>">Editar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Pop-up de decisão -->
<div class="modal" id="modalDecisao" hidden>
    <div class="modal-caixa">
        <form method="post" id="formDecisao">
            <?= csrf_campo() ?>
            <input type="hidden" name="id" id="decisaoId">
            <input type="hidden" name="filtro" value="<?= h($filtro) ?>">
            <input type="hidden" name="decisao" id="decisaoTipo">
            <h2>Decisão</h2>
            <p class="sub" id="decisaoTitulo"></p>
            <label>Motivo / observação <span class="dica">(obrigatório para Reprovar e Ajustar)</span>
                <textarea name="motivo" id="decisaoMotivo" rows="4" maxlength="2000"></textarea>
            </label>
            <div class="modal-acoes">
                <button type="button" class="btn btn-contorno" data-fechar-modal>Cancelar</button>
                <button type="submit" class="btn btn-alerta" data-tipo="ajustar">Ajustar</button>
                <button type="submit" class="btn btn-perigo" data-tipo="reprovar">Reprovar</button>
                <button type="submit" class="btn btn-primario" data-tipo="aprovar">Aprovar</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/layout_rodape.php'; ?>
