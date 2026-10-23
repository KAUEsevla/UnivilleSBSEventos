<?php
// Solicitante: vê somente as próprias solicitações
require_once __DIR__ . '/../config/config.php';
$eu = exigir_login('solicitante');

$filtros = ['todos' => 'Todas', 'pendente' => 'Pendentes', 'revisao' => 'Em revisão', 'aprovado' => 'Aprovadas', 'reprovado' => 'Reprovadas'];
$filtro = $_GET['status'] ?? 'todos';
if (!isset($filtros[$filtro])) $filtro = 'todos';

$sql = "SELECT s.*, c.nome AS curso, COALESCE(t.nome, s.tema_outro) AS tema, a.nome AS ambiente, a.localizacao
          FROM solicitacoes s
          JOIN cursos_areas c ON c.id = s.curso_area_id
          LEFT JOIN temas t   ON t.id = s.tema_id
          JOIN ambientes a    ON a.id = s.ambiente_id
         WHERE s.solicitante_id = ?";
$p = [$eu['id']];
if ($filtro !== 'todos') {
    $sql .= ' AND s.status = ?';
    $p[] = $filtro;
}
$sql .= ' ORDER BY s.criado_em, s.id';
$st = db()->prepare($sql);
$st->execute($p);
$lista = $st->fetchAll();

$titulo = 'Minhas solicitações';
$menu = 'minhas';
require __DIR__ . '/../includes/layout_topo.php';
?>
<div class="cabecalho-pagina">
    <h1>Minhas solicitações</h1>
    <a class="btn btn-primario" href="<?= url('solicitante/form.php') ?>">+ Nova solicitação</a>
</div>

<div class="filtros">
    <?php foreach ($filtros as $k => $rot): ?>
        <a class="chip <?= $filtro === $k ? 'ativo' : '' ?>" href="?status=<?= $k ?>"><?= $rot ?></a>
    <?php endforeach; ?>
</div>

<div class="card">
    <?php if (!$lista): ?>
        <p class="vazio">Você ainda não possui solicitações<?= $filtro !== 'todos' ? ' com essa situação' : '' ?>.</p>
    <?php else: ?>
        <table class="tabela tabela-responsiva">
            <thead>
            <tr>
                <th>Evento</th>
                <th>Curso/Área</th>
                <th>Data</th>
                <th>Hora</th>
                <th>Ambiente</th>
                <th>Situação</th>
                <th class="acoes">Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($lista as $s): ?>
                <tr>
                    <td data-label="Evento"><strong><?= h($s['titulo']) ?></strong><br><small><?= h($s['tema']) ?></small></td>
                    <td data-label="Curso/Área"><?= h($s['curso']) ?></td>
                    <td data-label="Data" data-ordem="<?= h($s['data_evento']) ?>"><?= data_br($s['data_evento']) ?></td>
                    <td data-label="Hora"><?= hora_br($s['hora_inicio']) ?> – <?= hora_br($s['hora_fim']) ?></td>
                    <td data-label="Ambiente"><?= h($s['ambiente']) ?><br><small><?= h($s['localizacao']) ?></small></td>
                    <td data-label="Situação" data-ordem="<?= h(STATUS[$s['status']] ?? $s['status']) ?>">
                        <?= badge_status($s['status']) ?>
                        <?php if (in_array($s['status'], ['revisao', 'reprovado'], true) && $s['observacao_admin']): ?>
                            <div class="observacao"><strong><?= $s['status'] === 'revisao' ? 'Ajuste solicitado:' : 'Motivo:' ?></strong> <?= nl2br(h($s['observacao_admin'])) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="acoes">
                        <?php if (in_array($s['status'], ['pendente', 'revisao'], true)): ?>
                            <a class="btn btn-sm btn-primario" href="<?= url('solicitante/form.php?id=' . $s['id']) ?>">Editar</a>
                        <?php else: ?>
                            <span class="dica">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/layout_rodape.php'; ?>
