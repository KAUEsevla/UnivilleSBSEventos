<?php
// Campos do formulário de solicitação (usado pelo solicitante e pelo administrador).
// Espera: $s (valores atuais) e $erros.

// Opções: itens ativos + o item já selecionado (mesmo que tenha sido inativado depois)
$opcoes = function (string $tabela, $selecionado): array {
    $st = db()->prepare("SELECT * FROM $tabela WHERE ativo = 1 OR id = ? ORDER BY nome");
    $st->execute([(int)$selecionado]);
    return $st->fetchAll();
};
$cursos    = $opcoes('cursos_areas', $s['curso_area_id'] ?? 0);
$temas     = $opcoes('temas', $s['tema_id'] ?? 0);
$ambientes = $opcoes('ambientes', $s['ambiente_id'] ?? 0);
$sel = fn($a, $b) => (string)$a === (string)$b ? 'selected' : '';
// tema_id NULL (salvo ou vindo do POST) = tema "Outro"
$temaOutro = is_array($s) && array_key_exists('tema_id', $s) && $s['tema_id'] === null;
?>
<?php if (!empty($erros)): ?>
    <div class="alerta alerta-erro">
        <strong>Corrija os itens abaixo:</strong>
        <ul><?php foreach ($erros as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<fieldset>
    <legend>Evento</legend>
    <div class="grade">
        <label class="col-2">Nome do evento *
            <input type="text" name="titulo" maxlength="200" required value="<?= h($s['titulo'] ?? '') ?>">
        </label>
        <label>Curso/Área responsável *
            <select name="curso_area_id" required>
                <option value="">Selecione...</option>
                <?php foreach ($cursos as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $sel($c['id'], $s['curso_area_id'] ?? '') ?>>
                        <?= h($c['nome']) ?> (CC <?= h($c['centro_custo']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Tema *
            <select name="tema_id" id="tema" required>
                <option value="">Selecione...</option>
                <?php foreach ($temas as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $sel($t['id'], $s['tema_id'] ?? '') ?>><?= h($t['nome']) ?></option>
                <?php endforeach; ?>
                <option value="outro" <?= $temaOutro ? 'selected' : '' ?>>Outro</option>
            </select>
        </label>
        <label class="col-2" id="temaOutroWrap" <?= $temaOutro ? '' : 'hidden' ?>>Descreva o tema *
            <input type="text" name="tema_outro" id="temaOutro" maxlength="150" value="<?= h($s['tema_outro'] ?? '') ?>">
        </label>
        <label class="col-2">Palestrante/Ministrante <span class="dica">(opcional)</span>
            <input type="text" name="palestrante" maxlength="200" value="<?= h($s['palestrante'] ?? '') ?>">
        </label>
    </div>
</fieldset>

<fieldset>
    <legend>Local, data e horário</legend>
    <div class="grade">
        <label class="col-2">Ambiente *
            <select name="ambiente_id" id="ambiente" required>
                <option value="">Selecione...</option>
                <?php foreach ($ambientes as $a): ?>
                    <option value="<?= $a['id'] ?>" <?= $sel($a['id'], $s['ambiente_id'] ?? '') ?>>
                        <?= h($a['nome']) ?> — <?= h($a['localizacao']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Data *
            <input type="date" name="data_evento" id="dataEvento" required value="<?= h($s['data_evento'] ?? '') ?>"
                <?= eh_admin() ? '' : 'min="' . date('Y-m-d') . '"' ?>>
        </label>
        <label>Período *
            <select name="periodo" id="periodo" required>
                <option value="">Selecione...</option>
                <?php foreach (PERIODOS as $k => $p): ?>
                    <option value="<?= $k ?>" data-inicio="<?= $p['inicio'] ?>" data-fim="<?= $p['fim'] ?>" <?= $sel($k, $s['periodo'] ?? '') ?>>
                        <?= $p['rotulo'] ?> (<?= $p['inicio'] ?> às <?= $p['fim'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <!-- opções montadas pelo app.js conforme o período escolhido -->
        <label>Horário de início *
            <select name="hora_inicio" id="horaInicio" required data-valor="<?= h(hora_br($s['hora_inicio'] ?? '')) ?>"></select>
        </label>
        <label>Horário de término *
            <select name="hora_fim" id="horaFim" required data-valor="<?= h(hora_br($s['hora_fim'] ?? '')) ?>"></select>
        </label>
    </div>
    <div id="ocupados" class="ocupados" data-ignorar="<?= (int)($s['id'] ?? 0) ?>" hidden></div>
</fieldset>

<fieldset>
    <legend>Recursos necessários</legend>
    <div class="checks">
        <label class="check"><input type="checkbox" name="rec_datashow" <?= !empty($s['rec_datashow']) ? 'checked' : '' ?>> Datashow/Projetor</label>
        <label class="check"><input type="checkbox" name="rec_microfone" <?= !empty($s['rec_microfone']) ? 'checked' : '' ?>> Microfone</label>
        <label class="check"><input type="checkbox" name="rec_som" <?= !empty($s['rec_som']) ? 'checked' : '' ?>> Som</label>
        <label class="check"><input type="checkbox" name="rec_outros" id="recOutros" <?= !empty($s['rec_outros']) ? 'checked' : '' ?>> Outros</label>
    </div>
    <label id="outrosDescWrap" <?= empty($s['rec_outros']) ? 'hidden' : '' ?>>Descreva os outros recursos *
        <textarea name="rec_outros_desc" id="outrosDesc" rows="3" maxlength="500"><?= h($s['rec_outros_desc'] ?? '') ?></textarea>
    </label>
</fieldset>
