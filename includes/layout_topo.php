<?php
// Layout da área logada. Antes de incluir defina: $titulo e $menu (item ativo).
$u = usuario_atual();
$admin = eh_admin();
$itens = $admin
    ? [
        'solicitacoes' => ['admin/solicitacoes.php', 'Solicitações'],
        'usuarios'     => ['admin/usuarios.php', 'Usuários'],
        'cursos'       => ['admin/cursos.php', 'Cursos/Áreas'],
        'ambientes'    => ['admin/ambientes.php', 'Ambientes'],
        'temas'        => ['admin/temas.php', 'Temas'],
        'relatorios'   => ['admin/relatorios.php', 'Relatórios'],
        'publica'     => ['index.php', 'Agenda pública'],
    ]
    : [
        'minhas'  => ['solicitante/minhas.php', 'Minhas solicitações'],
        'nova'    => ['solicitante/form.php', 'Nova solicitação'],
        'publica' => ['index.php', 'Agenda pública'],
    ];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($titulo ?? '') ?> · <?= h(APP_NOME) ?></title>
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="app">
<header class="topbar">
    <button class="btn-menu" type="button" data-toggle-sidebar aria-label="Abrir menu">☰</button>
    <a class="marca" href="<?= url($admin ? 'admin/solicitacoes.php' : 'solicitante/minhas.php') ?>">
        <img class="logo" src="<?= url('assets/img/logo-univille.png') ?>" alt="Univille"><span class="marca-texto">Eventos</span>
    </a>
    <div class="topbar-usuario">
        <span class="usuario-nome"><?= h($u['nome']) ?> <small>(<?= $admin ? 'Administrador' : 'Solicitante' ?>)</small></span>
        <a class="btn btn-sm btn-claro" href="<?= url('logout.php') ?>">Sair</a>
    </div>
</header>
<aside class="sidebar" id="sidebar">
    <nav>
        <?php foreach ($itens as $chave => [$link, $rotulo]): ?>
            <a href="<?= url($link) ?>" class="<?= ($menu ?? '') === $chave ? 'ativo' : '' ?>">
                <?= h($rotulo) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
<div class="sidebar-fundo" data-toggle-sidebar></div>
<main class="conteudo">
    <?php foreach (flashes() as $f): ?>
        <div class="alerta alerta-<?= h($f['tipo']) ?>"><?= h($f['msg']) ?></div>
    <?php endforeach; ?>
