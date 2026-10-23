<?php
require_once __DIR__ . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
csrf_verificar();

$usuario = trim($_POST['usuario'] ?? '');
$senha   = $_POST['senha'] ?? '';

$st = db()->prepare('SELECT * FROM usuarios WHERE usuario = ?');
$st->execute([$usuario]);
$u = $st->fetch();

if (!$u || !password_verify($senha, $u['senha_hash'])) {
    $_SESSION['login_erro'] = 'Usuário ou senha inválidos.';
    redirect('index.php');
}
if (!$u['ativo']) {
    $_SESSION['login_erro'] = 'Usuário inativo. Procure um administrador.';
    redirect('index.php');
}

session_regenerate_id(true);
$_SESSION['usuario'] = ['id' => (int)$u['id'], 'nome' => $u['nome'], 'perfil' => $u['perfil']];

redirect($u['perfil'] === 'admin' ? 'admin/solicitacoes.php' : 'solicitante/minhas.php');
