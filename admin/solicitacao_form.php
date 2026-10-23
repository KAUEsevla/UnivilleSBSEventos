<?php
// Administrador: cria evento (já aprovado, sem passar por aprovação) ou edita qualquer solicitação
require_once __DIR__ . '/../config/config.php';
$eu = exigir_login('admin');

$id = (int)($_GET['id'] ?? 0);
$s = ['status' => 'aprovado'];
$original = null;
if ($id) {
    $st = db()->prepare('SELECT s.*, u.nome AS solicitante FROM solicitacoes s JOIN usuarios u ON u.id = s.solicitante_id WHERE s.id = ?');
    $st->execute([$id]);
    $original = $st->fetch();
    if (!$original) {
        flash('erro', 'Solicitação não encontrada.');
        redirect('admin/solicitacoes.php');
    }
    $s = $original;
}
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $status = $id ? ($_POST['status'] ?? '') : 'aprovado';
    if (!isset(STATUS[$status])) $status = 'pendente';
    $obs = trim($_POST['observacao_admin'] ?? '');

    // conflito de horário só impede quando o evento ficará aprovado
    [$dados, $erros] = solicitacao_ler_post(true, $id ?: null, $status === 'aprovado');
    if (in_array($status, ['reprovado', 'revisao'], true) && $obs === '') {
        $erros[] = 'Informe o motivo/observação para reprovar ou solicitar ajuste.';
    }

    if (!$erros) {
        $dados['status'] = $status;
        $dados['observacao_admin'] = $obs !== '' ? $obs : null;
        $pdo = db();
        if (!$id) {
            $dados['solicitante_id'] = $eu['id'];
            $dados['aprovador_id'] = $eu['id'];
            $cols = implode(', ', array_keys($dados));
            $marc = implode(', ', array_fill(0, count($dados), '?'));
            $pdo->prepare("INSERT INTO solicitacoes ($cols, decidido_em) VALUES ($marc, NOW())")->execute(array_values($dados));
            flash('sucesso', 'Evento cadastrado e aprovado.');
        } else {
            $sets = implode(', ', array_map(fn($c) => "$c = ?", array_keys($dados)));
            $vals = array_values($dados);
            if ($status !== $original['status']) {
                // mudança de situação registra o administrador como responsável pela decisão
                $sets .= $status === 'pendente'
                    ? ', aprovador_id = NULL, decidido_em = NULL'
                    : ', aprovador_id = ?, decidido_em = NOW()';
                if ($status !== 'pendente') $vals[] = $eu['id'];
            }
            $vals[] = $id;
            $pdo->prepare("UPDATE solicitacoes SET $sets WHERE id = ?")->execute($vals);
            flash('sucesso', 'Solicitação atualizada.');
        }
        redirect('admin/solicitacoes.php?status=' . $status);
    }
    $s = array_merge($s, $dados, ['status' => $status, 'observacao_admin' => $obs]);
}

$titulo = $id ? 'Editar solicitação' : 'Novo evento';
$menu = 'solicitacoes';
require __DIR__ . '/../includes/layout_topo.php';
?>
<div class="cabecalho-pagina">
    <h1><?= h($titulo) ?></h1>
    <a class="btn btn-contorno" href="<?= url('admin/solicitacoes.php') ?>">← Voltar</a>
</div>

<form method="post" class="card formulario">
    <?= csrf_campo() ?>
    <?php if ($id): ?>
        <p class="sub">
            Solicitante: <strong><?= h($original['solicitante']) ?></strong> ·
            criada em <?= date('d/m/Y H:i', strtotime($original['criado_em'])) ?>
        </p>
    <?php else: ?>
        <p class="sub">Eventos cadastrados pelo administrador já entram como <strong>Aprovados</strong>.</p>
    <?php endif; ?>

    <?php require __DIR__ . '/../includes/form_solicitacao.php'; ?>

    <?php if ($id): ?>
        <fieldset>
            <legend>Situação</legend>
            <div class="grade">
                <label>Situação
                    <select name="status">
                        <?php foreach (STATUS as $k => $rot): ?>
                            <option value="<?= $k ?>" <?= $s['status'] === $k ? 'selected' : '' ?>><?= $rot ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="col-2">Motivo / observação para o solicitante
                    <textarea name="observacao_admin" rows="3"><?= h($s['observacao_admin'] ?? '') ?></textarea>
                </label>
            </div>
        </fieldset>
    <?php endif; ?>

    <div class="form-acoes">
        <a class="btn btn-contorno" href="<?= url('admin/solicitacoes.php') ?>">Cancelar</a>
        <button class="btn btn-primario" type="submit">Salvar</button>
    </div>
</form>
<?php require __DIR__ . '/../includes/layout_rodape.php'; ?>
