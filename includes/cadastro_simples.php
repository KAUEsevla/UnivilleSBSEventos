<?php
// Cadastro genérico (Cursos/Áreas, Ambientes, Temas): listar, incluir, editar, ativar/inativar e excluir.
// Antes de incluir defina $cfg = ['tabela', 'titulo', 'singular', 'menu', 'pagina', 'vinculo', 'campos' => [coluna => [rótulo, placeholder]]]
// 'vinculo' é a coluna de solicitacoes que referencia o cadastro: com solicitações vinculadas não é possível excluir.
require_once __DIR__ . '/../config/config.php';
exigir_login('admin');

$tabela = $cfg['tabela'];
$campos = $cfg['campos'];
$vinculo = $cfg['vinculo'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $acao = $_POST['acao'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($acao === 'alternar' && $id) {
        db()->prepare("UPDATE $tabela SET ativo = 1 - ativo WHERE id = ?")->execute([$id]);
        flash('sucesso', 'Situação alterada.');
    } elseif ($acao === 'excluir' && $id) {
        $st = db()->prepare("SELECT COUNT(*) FROM solicitacoes WHERE $vinculo = ?");
        $st->execute([$id]);
        if ($st->fetchColumn() > 0) {
            flash('erro', MSG_VINCULADO, 'Exclusão não permitida');
        } else {
            db()->prepare("DELETE FROM $tabela WHERE id = ?")->execute([$id]);
            flash('sucesso', $cfg['singular'] . ' excluído.');
        }
    } elseif ($acao === 'salvar') {
        $vals = [];
        foreach ($campos as $col => $_) {
            $vals[$col] = trim($_POST[$col] ?? '');
            if ($vals[$col] === '') {
                flash('erro', 'Preencha todos os campos.');
                redirect($cfg['pagina'] . ($id ? "?editar=$id" : '?novo=1'));
            }
        }
        if ($id) {
            $sets = implode(', ', array_map(fn($c) => "$c = ?", array_keys($vals)));
            db()->prepare("UPDATE $tabela SET $sets WHERE id = ?")->execute([...array_values($vals), $id]);
            flash('sucesso', $cfg['singular'] . ' atualizado.');
        } else {
            $cols = implode(', ', array_keys($vals));
            $marc = implode(', ', array_fill(0, count($vals), '?'));
            db()->prepare("INSERT INTO $tabela ($cols) VALUES ($marc)")->execute(array_values($vals));
            flash('sucesso', $cfg['singular'] . ' cadastrado.');
        }
    }
    redirect($cfg['pagina']);
}

$editar = null;
if (!empty($_GET['editar'])) {
    $st = db()->prepare("SELECT * FROM $tabela WHERE id = ?");
    $st->execute([(int)$_GET['editar']]);
    $editar = $st->fetch() ?: null;
}
$mostrarForm = $editar || isset($_GET['novo']);
$lista = db()->query("SELECT x.*, (SELECT COUNT(*) FROM solicitacoes s WHERE s.$vinculo = x.id) AS vinculos
                        FROM $tabela x ORDER BY x.id")->fetchAll();

$titulo = $cfg['titulo'];
$menu = $cfg['menu'];
require __DIR__ . '/layout_topo.php';
?>
<div class="cabecalho-pagina">
    <h1><?= h($cfg['titulo']) ?></h1>
    <?php if (!$mostrarForm): ?>
        <a class="btn btn-primario" href="?novo=1">+ Novo <?= h(mb_strtolower($cfg['singular'])) ?></a>
    <?php endif; ?>
</div>

<?php if ($mostrarForm): ?>
    <form method="post" class="card formulario">
        <?= csrf_campo() ?>
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= (int)($editar['id'] ?? 0) ?>">
        <h2><?= $editar ? 'Editar' : 'Novo' ?> <?= h(mb_strtolower($cfg['singular'])) ?></h2>
        <div class="grade">
            <?php foreach ($campos as $col => [$rot, $ph]): ?>
                <label><?= h($rot) ?> *
                    <input type="text" name="<?= $col ?>" required maxlength="150" placeholder="<?= h($ph) ?>" value="<?= h($editar[$col] ?? '') ?>">
                </label>
            <?php endforeach; ?>
        </div>
        <div class="form-acoes">
            <a class="btn btn-contorno" href="<?= url($cfg['pagina']) ?>">Cancelar</a>
            <button class="btn btn-primario" type="submit">Salvar</button>
        </div>
    </form>
<?php endif; ?>

<div class="card">
    <?php if (!$lista): ?>
        <p class="vazio">Nenhum registro cadastrado.</p>
    <?php else: ?>
        <table class="tabela tabela-responsiva">
            <thead>
            <tr>
                <?php foreach ($campos as [$rot]): ?><th><?= h($rot) ?></th><?php endforeach; ?>
                <th>Situação</th>
                <th class="acoes">Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($lista as $r): ?>
                <tr class="<?= $r['ativo'] ? '' : 'inativo' ?>">
                    <?php foreach ($campos as $col => [$rot]): ?>
                        <td data-label="<?= h($rot) ?>"><?= h($r[$col]) ?></td>
                    <?php endforeach; ?>
                    <td data-label="Situação"><?= $r['ativo'] ? '<span class="badge badge-aprovado">Ativo</span>' : '<span class="badge badge-reprovado">Inativo</span>' ?></td>
                    <td class="acoes">
                        <a class="btn btn-sm btn-contorno" href="?editar=<?= $r['id'] ?>">Editar</a>
                        <form method="post" class="inline">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="acao" value="alternar">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <button class="btn btn-sm <?= $r['ativo'] ? 'btn-contorno-perigo' : 'btn-primario' ?>" type="submit"><?= $r['ativo'] ? 'Inativar' : 'Ativar' ?></button>
                        </form>
                        <form method="post" class="inline" data-excluir="<?= h($r['nome']) ?>" data-vinculado="<?= $r['vinculos'] > 0 ? 1 : 0 ?>">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="acao" value="excluir">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <?= botao_excluir() ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/layout_rodape.php'; ?>
