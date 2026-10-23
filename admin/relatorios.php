<?php
// Relatórios em CSV: o administrador escolhe período, situações e colunas
require_once __DIR__ . '/../config/config.php';
exigir_login('admin');

// coluna => [rótulo, expressão SQL]
$colunas = [
    'id'           => ['Nº', 's.id'],
    'solicitante'  => ['Solicitante', 'u.nome'],
    'curso'        => ['Curso/Área', 'c.nome'],
    'centro_custo' => ['Centro de custo', 'c.centro_custo'],
    'tema'         => ['Tema', 'COALESCE(t.nome, s.tema_outro)'],
    'titulo'       => ['Evento', 's.titulo'],
    'palestrante'  => ['Palestrante/Ministrante', 's.palestrante'],
    'data_evento'  => ['Data do evento', "DATE_FORMAT(s.data_evento, '%d/%m/%Y')"],
    'periodo'      => ['Período', 's.periodo'],
    'hora_inicio'  => ['Hora início', "TIME_FORMAT(s.hora_inicio, '%H:%i')"],
    'hora_fim'     => ['Hora fim', "TIME_FORMAT(s.hora_fim, '%H:%i')"],
    'ambiente'     => ['Ambiente', 'a.nome'],
    'localizacao'  => ['Localização', 'a.localizacao'],
    'datashow'     => ['Datashow/Projetor', "IF(s.rec_datashow, 'Sim', 'Não')"],
    'microfone'    => ['Microfone', "IF(s.rec_microfone, 'Sim', 'Não')"],
    'som'          => ['Som', "IF(s.rec_som, 'Sim', 'Não')"],
    'outros'       => ['Outros recursos', "IF(s.rec_outros, s.rec_outros_desc, '')"],
    'status'       => ['Situação', 's.status'],
    'observacao'   => ['Motivo/Observação', 's.observacao_admin'],
    'aprovador'    => ['Aprovador', 'ap.nome'],
    'decidido_em'  => ['Data da decisão', "DATE_FORMAT(s.decidido_em, '%d/%m/%Y %H:%i')"],
    'criado_em'    => ['Criada em', "DATE_FORMAT(s.criado_em, '%d/%m/%Y %H:%i')"],
];
$padrao = ['solicitante', 'curso', 'tema', 'titulo', 'data_evento', 'hora_inicio', 'hora_fim', 'ambiente', 'localizacao', 'status'];

$de      = $_GET['de'] ?? date('Y-m-01');
$ate     = $_GET['ate'] ?? date('Y-m-t');
$base    = ($_GET['base'] ?? 'evento') === 'criacao' ? 'criacao' : 'evento';
$sel     = array_values(array_intersect(array_keys($colunas), (array)($_GET['col'] ?? $padrao)));
$status  = array_values(array_intersect(array_keys(STATUS), (array)($_GET['status'] ?? array_keys(STATUS))));
$erros   = [];

if (isset($_GET['gerar'])) {
    $valida = fn($d) => ($x = DateTime::createFromFormat('Y-m-d', $d)) && $x->format('Y-m-d') === $d;
    if (!$valida($de) || !$valida($ate)) $erros[] = 'Informe um período válido.';
    elseif ($de > $ate) $erros[] = 'A data inicial deve ser anterior ou igual à final.';
    if (!$sel) $erros[] = 'Selecione pelo menos uma coluna.';
    if (!$status) $erros[] = 'Selecione pelo menos uma situação.';

    if (!$erros) {
        $campos = implode(', ', array_map(fn($k) => $colunas[$k][1] . " AS `$k`", $sel));
        $filtroData = $base === 'criacao' ? 'DATE(s.criado_em)' : 's.data_evento';
        $marc = implode(', ', array_fill(0, count($status), '?'));
        $st = db()->prepare(
            "SELECT $campos
               FROM solicitacoes s
               JOIN usuarios u       ON u.id = s.solicitante_id
               JOIN cursos_areas c   ON c.id = s.curso_area_id
               LEFT JOIN temas t     ON t.id = s.tema_id
               JOIN ambientes a      ON a.id = s.ambiente_id
               LEFT JOIN usuarios ap ON ap.id = s.aprovador_id
              WHERE $filtroData BETWEEN ? AND ? AND s.status IN ($marc)
              ORDER BY s.data_evento, s.hora_inicio"
        );
        $st->execute([$de, $ate, ...$status]);

        $nome = 'relatorio_eventos_' . str_replace('-', '', $de) . '_' . str_replace('-', '', $ate) . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nome . '"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM para o Excel reconhecer acentos
        fputcsv($out, array_map(fn($k) => $colunas[$k][0], $sel), ';');
        while ($r = $st->fetch()) {
            if (isset($r['status']))  $r['status'] = STATUS[$r['status']] ?? $r['status'];
            if (isset($r['periodo'])) $r['periodo'] = PERIODOS[$r['periodo']]['rotulo'] ?? $r['periodo'];
            fputcsv($out, array_values($r), ';');
        }
        fclose($out);
        exit;
    }
}

$titulo = 'Relatórios';
$menu = 'relatorios';
require __DIR__ . '/../includes/layout_topo.php';
?>
<div class="cabecalho-pagina">
    <h1>Relatórios</h1>
</div>

<?php if ($erros): ?>
    <div class="alerta alerta-erro"><ul><?php foreach ($erros as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="get" class="card formulario" id="formRelatorio">
    <input type="hidden" name="gerar" value="1">
    <fieldset>
        <legend>Período</legend>
        <div class="grade grade-3">
            <label>De *
                <input type="date" name="de" required value="<?= h($de) ?>">
            </label>
            <label>Até *
                <input type="date" name="ate" required value="<?= h($ate) ?>">
            </label>
            <label>Considerar a data
                <select name="base">
                    <option value="evento" <?= $base === 'evento' ? 'selected' : '' ?>>do evento</option>
                    <option value="criacao" <?= $base === 'criacao' ? 'selected' : '' ?>>de criação da solicitação</option>
                </select>
            </label>
        </div>
        <div class="atalhos-periodo">
            <button type="button" class="chip" data-periodo="mes">Mês atual</button>
            <button type="button" class="chip" data-periodo="mes-anterior">Mês anterior</button>
            <button type="button" class="chip" data-periodo="trimestre">Próximos 3 meses</button>
            <button type="button" class="chip" data-periodo="ano">Ano atual</button>
        </div>
    </fieldset>

    <fieldset>
        <legend>Situação</legend>
        <div class="checks">
            <?php foreach (STATUS as $k => $rot): ?>
                <label class="check"><input type="checkbox" name="status[]" value="<?= $k ?>" <?= in_array($k, $status, true) ? 'checked' : '' ?>> <?= $rot ?></label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <fieldset>
        <legend>Colunas</legend>
        <div class="atalhos-periodo">
            <button type="button" class="chip" data-colunas="todas">Marcar todas</button>
            <button type="button" class="chip" data-colunas="nenhuma">Desmarcar todas</button>
        </div>
        <div class="checks checks-colunas">
            <?php foreach ($colunas as $k => [$rot]): ?>
                <label class="check"><input type="checkbox" name="col[]" value="<?= $k ?>" <?= in_array($k, $sel, true) ? 'checked' : '' ?>> <?= h($rot) ?></label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <div class="form-acoes">
        <button class="btn btn-primario" type="submit">⬇ Baixar CSV</button>
    </div>
</form>
<?php require __DIR__ . '/../includes/layout_rodape.php'; ?>
