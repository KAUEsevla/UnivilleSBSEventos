<?php
// Página pública: eventos aprovados do mês atual até 2 meses à frente
require_once __DIR__ . '/config/config.php';

$inicio = date('Y-m-01');
$fim    = date('Y-m-t', strtotime('first day of +2 month'));

$st = db()->prepare(
    "SELECT s.*, COALESCE(t.nome, s.tema_outro) AS tema, a.nome AS ambiente, a.localizacao
       FROM solicitacoes s
       LEFT JOIN temas t   ON t.id = s.tema_id
       JOIN ambientes a    ON a.id = s.ambiente_id
      WHERE s.status = 'aprovado' AND s.data_evento BETWEEN ? AND ?
      ORDER BY s.data_evento, s.hora_inicio"
);
$st->execute([$inicio, $fim]);
$eventos = $st->fetchAll();

$meses = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$u = usuario_atual();
$abrirLogin = !empty($_SESSION['login_erro']);
$loginErro = $_SESSION['login_erro'] ?? '';
unset($_SESSION['login_erro']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agenda de Eventos · <?= h(APP_NOME) ?></title>
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="publica">
<header class="topbar">
    <span class="marca"><img class="logo" src="<?= url('assets/img/logo-univille.png') ?>" alt="Univille"><span class="marca-texto">Eventos</span></span>
    <div class="login-wrap">
        <?php if ($u): ?>
            <a class="btn btn-sm btn-claro" href="<?= url($u['perfil'] === 'admin' ? 'admin/solicitacoes.php' : 'solicitante/minhas.php') ?>">Meu painel</a>
        <?php else: ?>
            <button type="button" class="btn-login" id="btnLogin" aria-label="Entrar" title="Entrar">
                <svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor" aria-hidden="true"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.42 0-8 2.24-8 5v3h16v-3c0-2.76-3.58-5-8-5Z"/></svg>
            </button>
            <form class="login-box <?= $abrirLogin ? 'aberto' : '' ?>" id="loginBox" method="post" action="<?= url('login.php') ?>">
                <?= csrf_campo() ?>
                <h3>Entrar</h3>
                <?php if ($loginErro): ?><div class="alerta alerta-erro"><?= h($loginErro) ?></div><?php endif; ?>
                <label>Usuário <input type="text" name="usuario" required autocomplete="username"></label>
                <label>Senha <input type="password" name="senha" required autocomplete="current-password"></label>
                <button class="btn btn-primario btn-bloco" type="submit">Entrar</button>
            </form>
        <?php endif; ?>
    </div>
</header>

<main class="conteudo-publico">
    <?php foreach (flashes() as $f): ?>
        <?= flash_html($f) ?>
    <?php endforeach; ?>

    <div class="cabecalho-pagina">
        <h1>Agenda de Eventos</h1>
        <p class="sub">
            <?= $meses[(int)date('n', strtotime($inicio))] ?> a <?= $meses[(int)date('n', strtotime($fim))] ?> de <?= date('Y', strtotime($fim)) ?>
        </p>
    </div>

    <div class="card">
        <?php if (!$eventos): ?>
            <p class="vazio">Nenhum evento aprovado para o período.</p>
        <?php else: ?>
            <table class="tabela tabela-responsiva">
                <thead>
                <tr>
                    <th>Evento</th>
                    <th>Tema</th>
                    <th>Início</th>
                    <th>Local</th>
                    <th>Palestrante/Ministrante</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($eventos as $e): ?>
                    <tr>
                        <td data-label="Evento"><strong><?= h($e['titulo']) ?></strong></td>
                        <td data-label="Tema"><?= h($e['tema']) ?></td>
                        <td data-label="Início" data-ordem="<?= h($e['data_evento'] . ' ' . $e['hora_inicio']) ?>"><?= data_br($e['data_evento']) ?><br><small><?= hora_br($e['hora_inicio']) ?> às <?= hora_br($e['hora_fim']) ?></small></td>
                        <td data-label="Local"><?= h($e['ambiente']) ?><br><small><?= h($e['localizacao']) ?></small></td>
                        <td data-label="Palestrante"><?= h($e['palestrante'] ?: '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
