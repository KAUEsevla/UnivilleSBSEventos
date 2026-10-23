<?php
// Solicitante: nova solicitação ou edição (somente Pendente ou Em revisão)
require_once __DIR__ . '/../config/config.php';
$eu = exigir_login('solicitante');

$id = (int)($_GET['id'] ?? 0);
$s = [];
if ($id) {
    $st = db()->prepare('SELECT * FROM solicitacoes WHERE id = ? AND solicitante_id = ?');
    $st->execute([$id, $eu['id']]);
    $s = $st->fetch();
    if (!$s) {
        flash('erro', 'Solicitação não encontrada.');
        redirect('solicitante/minhas.php');
    }
    if (!in_array($s['status'], ['pendente', 'revisao'], true)) {
        flash('erro', 'Somente solicitações pendentes ou em revisão podem ser editadas.');
        redirect('solicitante/minhas.php');
    }
}
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    [$dados, $erros] = solicitacao_ler_post(false, $id ?: null);

    if (!$erros) {
        if ($id) {
            // reenvio após ajuste volta para a fila de aprovação
            $dados['status'] = 'pendente';
            $sets = implode(', ', array_map(fn($c) => "$c = ?", array_keys($dados)));
            $st = db()->prepare("UPDATE solicitacoes SET $sets, aprovador_id = NULL, decidido_em = NULL
                                  WHERE id = ? AND solicitante_id = ? AND status IN ('pendente','revisao')");
            $st->execute([...array_values($dados), $id, $eu['id']]);
            if ($st->rowCount() === 0) {
                flash('erro', 'A solicitação foi decidida por um administrador antes de você salvar e não pode mais ser editada.');
            } else {
                flash('sucesso', 'Solicitação atualizada e enviada para aprovação.');
            }
        } else {
            $dados['solicitante_id'] = $eu['id'];
            $cols = implode(', ', array_keys($dados));
            $marc = implode(', ', array_fill(0, count($dados), '?'));
            db()->prepare("INSERT INTO solicitacoes ($cols) VALUES ($marc)")->execute(array_values($dados));
            flash('sucesso', 'Solicitação enviada! Aguarde a aprovação de um administrador.');
        }
        redirect('solicitante/minhas.php');
    }
    $s = array_merge($s ?: [], $dados);
}

$titulo = $id ? 'Editar solicitação' : 'Nova solicitação';
$menu = $id ? 'minhas' : 'nova';
require __DIR__ . '/../includes/layout_topo.php';
?>
<div class="cabecalho-pagina">
    <h1><?= h($titulo) ?></h1>
    <a class="btn btn-contorno" href="<?= url('solicitante/minhas.php') ?>">← Voltar</a>
</div>

<?php if ($id && $s['status'] === 'revisao' && $s['observacao_admin']): ?>
    <div class="alerta alerta-aviso">
        <strong>Ajuste solicitado pelo administrador:</strong><br>
        <?= nl2br(h($s['observacao_admin'])) ?>
    </div>
<?php endif; ?>

<form method="post" class="card formulario">
    <?= csrf_campo() ?>
    <?php require __DIR__ . '/../includes/form_solicitacao.php'; ?>
    <div class="form-acoes">
        <a class="btn btn-contorno" href="<?= url('solicitante/minhas.php') ?>">Cancelar</a>
        <button class="btn btn-primario" type="submit"><?= $id ? 'Salvar e reenviar' : 'Enviar solicitação' ?></button>
    </div>
</form>
<?php require __DIR__ . '/../includes/layout_rodape.php'; ?>
