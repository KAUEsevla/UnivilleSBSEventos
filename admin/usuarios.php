<?php
// Usuários: somente administradores cadastram, editam e inativam (não há autocadastro)
require_once __DIR__ . '/../config/config.php';
$eu = exigir_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $acao = $_POST['acao'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($acao === 'alternar' && $id) {
        if ($id === $eu['id']) {
            flash('erro', 'Você não pode inativar o seu próprio usuário.');
        } else {
            db()->prepare('UPDATE usuarios SET ativo = 1 - ativo WHERE id = ?')->execute([$id]);
            flash('sucesso', 'Situação do usuário alterada.');
        }
        redirect('admin/usuarios.php');
    }

    if ($acao === 'salvar') {
        $nome    = trim($_POST['nome'] ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $perfil  = ($_POST['perfil'] ?? '') === 'admin' ? 'admin' : 'solicitante';
        $senha   = $_POST['senha'] ?? '';
        $volta   = 'admin/usuarios.php' . ($id ? "?editar=$id" : '?novo=1');

        $erro = null;
        if ($nome === '' || $usuario === '') $erro = 'Nome e usuário são obrigatórios.';
        elseif (!preg_match('/^[A-Za-z0-9._-]{3,60}$/', $usuario)) $erro = 'Usuário deve ter de 3 a 60 caracteres (letras, números, ponto, hífen ou _).';
        elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $erro = 'E-mail inválido.';
        elseif (!$id && strlen($senha) < 6) $erro = 'A senha deve ter pelo menos 6 caracteres.';
        elseif ($id && $senha !== '' && strlen($senha) < 6) $erro = 'A nova senha deve ter pelo menos 6 caracteres.';
        elseif ($id === $eu['id'] && $perfil !== 'admin') $erro = 'Você não pode remover o seu próprio perfil de administrador.';
        else {
            $st = db()->prepare('SELECT id FROM usuarios WHERE usuario = ? AND id <> ?');
            $st->execute([$usuario, $id]);
            if ($st->fetch()) $erro = 'Já existe um usuário com esse login.';
        }
        if ($erro) {
            flash('erro', $erro);
            $_SESSION['form_usuario'] = $_POST;
            redirect($volta);
        }

        $emailDb = $email !== '' ? $email : null;
        if ($id) {
            db()->prepare('UPDATE usuarios SET nome = ?, usuario = ?, email = ?, perfil = ? WHERE id = ?')
                ->execute([$nome, $usuario, $emailDb, $perfil, $id]);
            if ($senha !== '') {
                db()->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?')
                    ->execute([password_hash($senha, PASSWORD_DEFAULT), $id]);
            }
            if ($id === $eu['id']) $_SESSION['usuario']['nome'] = $nome;
            flash('sucesso', 'Usuário atualizado.');
        } else {
            db()->prepare('INSERT INTO usuarios (nome, usuario, email, senha_hash, perfil) VALUES (?, ?, ?, ?, ?)')
                ->execute([$nome, $usuario, $emailDb, password_hash($senha, PASSWORD_DEFAULT), $perfil]);
            flash('sucesso', 'Usuário cadastrado.');
        }
        redirect('admin/usuarios.php');
    }
}

$editar = null;
if (!empty($_GET['editar'])) {
    $st = db()->prepare('SELECT id, nome, usuario, email, perfil FROM usuarios WHERE id = ?');
    $st->execute([(int)$_GET['editar']]);
    $editar = $st->fetch() ?: null;
}
$mostrarForm = $editar || isset($_GET['novo']);
$f = array_merge($editar ?? ['perfil' => 'solicitante'], $_SESSION['form_usuario'] ?? []);
unset($_SESSION['form_usuario']);

$lista = db()->query('SELECT id, nome, usuario, email, perfil, ativo FROM usuarios ORDER BY ativo DESC, nome')->fetchAll();

$titulo = 'Usuários';
$menu = 'usuarios';
require __DIR__ . '/../includes/layout_topo.php';
?>
<div class="cabecalho-pagina">
    <h1>Usuários</h1>
    <?php if (!$mostrarForm): ?>
        <a class="btn btn-primario" href="?novo=1">+ Novo usuário</a>
    <?php endif; ?>
</div>

<?php if ($mostrarForm): ?>
    <form method="post" class="card formulario" autocomplete="off">
        <?= csrf_campo() ?>
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= (int)($editar['id'] ?? 0) ?>">
        <h2><?= $editar ? 'Editar usuário' : 'Novo usuário' ?></h2>
        <div class="grade">
            <label class="col-2">Nome completo *
                <input type="text" name="nome" required maxlength="150" value="<?= h($f['nome'] ?? '') ?>">
            </label>
            <label>Usuário (login) *
                <input type="text" name="usuario" required maxlength="60" value="<?= h($f['usuario'] ?? '') ?>">
            </label>
            <label>E-mail
                <input type="email" name="email" maxlength="150" value="<?= h($f['email'] ?? '') ?>">
            </label>
            <label>Perfil *
                <select name="perfil">
                    <option value="solicitante" <?= ($f['perfil'] ?? '') === 'solicitante' ? 'selected' : '' ?>>Solicitante</option>
                    <option value="admin" <?= ($f['perfil'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
                </select>
            </label>
            <label><?= $editar ? 'Nova senha <span class="dica">(deixe em branco para manter)</span>' : 'Senha *' ?>
                <input type="password" name="senha" minlength="6" autocomplete="new-password" <?= $editar ? '' : 'required' ?>>
            </label>
        </div>
        <div class="form-acoes">
            <a class="btn btn-contorno" href="<?= url('admin/usuarios.php') ?>">Cancelar</a>
            <button class="btn btn-primario" type="submit">Salvar</button>
        </div>
    </form>
<?php endif; ?>

<div class="card">
    <table class="tabela tabela-responsiva">
        <thead>
        <tr><th>Nome</th><th>Usuário</th><th>E-mail</th><th>Perfil</th><th>Situação</th><th class="acoes">Ações</th></tr>
        </thead>
        <tbody>
        <?php foreach ($lista as $r): ?>
            <tr class="<?= $r['ativo'] ? '' : 'inativo' ?>">
                <td data-label="Nome"><?= h($r['nome']) ?></td>
                <td data-label="Usuário"><?= h($r['usuario']) ?></td>
                <td data-label="E-mail"><?= h($r['email'] ?: '—') ?></td>
                <td data-label="Perfil"><?= $r['perfil'] === 'admin' ? 'Administrador' : 'Solicitante' ?></td>
                <td data-label="Situação"><?= $r['ativo'] ? '<span class="badge badge-aprovado">Ativo</span>' : '<span class="badge badge-reprovado">Inativo</span>' ?></td>
                <td class="acoes">
                    <a class="btn btn-sm btn-contorno" href="?editar=<?= $r['id'] ?>">Editar</a>
                    <?php if ((int)$r['id'] !== $eu['id']): ?>
                        <form method="post" class="inline">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="acao" value="alternar">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <button class="btn btn-sm <?= $r['ativo'] ? 'btn-perigo' : 'btn-primario' ?>" type="submit"><?= $r['ativo'] ? 'Inativar' : 'Ativar' ?></button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/layout_rodape.php'; ?>
