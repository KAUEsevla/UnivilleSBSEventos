<?php
// =========================================================
// Funções auxiliares
// =========================================================

// Períodos e faixas de horário permitidas
const PERIODOS = [
    'matutino'   => ['rotulo' => 'Matutino',   'inicio' => '08:00', 'fim' => '11:00'],
    'vespertino' => ['rotulo' => 'Vespertino', 'inicio' => '13:00', 'fim' => '17:00'],
    'noturno'    => ['rotulo' => 'Noturno',    'inicio' => '19:00', 'fim' => '22:30'],
];

const STATUS = [
    'pendente'  => 'Pendente',
    'aprovado'  => 'Aprovado',
    'reprovado' => 'Reprovado',
    'revisao'   => 'Em revisão',
];

function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function url(string $caminho = ''): string
{
    return BASE_URL . '/' . ltrim($caminho, '/');
}

function redirect(string $caminho): void
{
    header('Location: ' . url($caminho));
    exit;
}

// ---------- Mensagens flash ----------
function flash(string $tipo, string $msg): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'msg' => $msg];
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ---------- CSRF ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function csrf_verificar(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Requisição inválida (token expirado). Volte e tente novamente.');
    }
}

// ---------- Autenticação ----------
function usuario_atual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function eh_admin(): bool
{
    return (usuario_atual()['perfil'] ?? '') === 'admin';
}

function exigir_login(?string $perfil = null): array
{
    $u = usuario_atual();
    if (!$u) {
        flash('erro', 'Faça login para continuar.');
        redirect('index.php');
    }
    // Confere se o usuário continua ativo
    $st = db()->prepare('SELECT ativo, perfil FROM usuarios WHERE id = ?');
    $st->execute([$u['id']]);
    $row = $st->fetch();
    if (!$row || !$row['ativo']) {
        session_destroy();
        session_start();
        flash('erro', 'Seu usuário está inativo.');
        redirect('index.php');
    }
    if ($perfil && $row['perfil'] !== $perfil) {
        redirect($row['perfil'] === 'admin' ? 'admin/solicitacoes.php' : 'solicitante/minhas.php');
    }
    return $u;
}

// ---------- Formatação ----------
function data_br(?string $d): string
{
    return $d ? date('d/m/Y', strtotime($d)) : '';
}

function hora_br(?string $t): string
{
    return $t ? substr($t, 0, 5) : '';
}

function badge_status(string $s): string
{
    return '<span class="badge badge-' . h($s) . '">' . h(STATUS[$s] ?? $s) . '</span>';
}

function recursos_texto(array $s): string
{
    $r = [];
    if ($s['rec_datashow'])  $r[] = 'Datashow/Projetor';
    if ($s['rec_microfone']) $r[] = 'Microfone';
    if ($s['rec_som'])       $r[] = 'Som';
    if ($s['rec_outros'])    $r[] = 'Outros: ' . $s['rec_outros_desc'];
    return $r ? implode(', ', $r) : '—';
}

// ---------- Cadastros auxiliares ----------
function listar_ativos(string $tabela): array
{
    $permitidas = ['cursos_areas', 'temas', 'ambientes'];
    if (!in_array($tabela, $permitidas, true)) return [];
    return db()->query("SELECT * FROM $tabela WHERE ativo = 1 ORDER BY nome")->fetchAll();
}

// ---------- Regras de agenda ----------
/**
 * Retorna o evento APROVADO que conflita com o período informado no mesmo ambiente,
 * ou null se o horário estiver livre.
 */
function conflito_agenda(int $ambienteId, string $data, string $ini, string $fim, ?int $ignorarId = null): ?array
{
    $sql = "SELECT id, titulo, hora_inicio, hora_fim FROM solicitacoes
            WHERE status = 'aprovado' AND ambiente_id = ? AND data_evento = ?
              AND hora_inicio < ? AND hora_fim > ?";
    $p = [$ambienteId, $data, $fim, $ini];
    if ($ignorarId) {
        $sql .= ' AND id <> ?';
        $p[] = $ignorarId;
    }
    $st = db()->prepare($sql . ' LIMIT 1');
    $st->execute($p);
    return $st->fetch() ?: null;
}

/**
 * Lê e valida os campos do formulário de solicitação.
 * Retorna [dados, erros].
 */
function solicitacao_ler_post(bool $admin, ?int $idAtual = null, bool $checarConflito = true): array
{
    $d = [
        'titulo'          => trim($_POST['titulo'] ?? ''),
        'curso_area_id'   => (int)($_POST['curso_area_id'] ?? 0),
        'tema_id'         => (int)($_POST['tema_id'] ?? 0),
        'ambiente_id'     => (int)($_POST['ambiente_id'] ?? 0),
        'palestrante'     => trim($_POST['palestrante'] ?? ''),
        'data_evento'     => $_POST['data_evento'] ?? '',
        'periodo'         => $_POST['periodo'] ?? '',
        'hora_inicio'     => $_POST['hora_inicio'] ?? '',
        'hora_fim'        => $_POST['hora_fim'] ?? '',
        'rec_datashow'    => isset($_POST['rec_datashow']) ? 1 : 0,
        'rec_microfone'   => isset($_POST['rec_microfone']) ? 1 : 0,
        'rec_som'         => isset($_POST['rec_som']) ? 1 : 0,
        'rec_outros'      => isset($_POST['rec_outros']) ? 1 : 0,
        'rec_outros_desc' => trim($_POST['rec_outros_desc'] ?? ''),
    ];
    $e = [];

    if ($d['titulo'] === '') $e[] = 'Informe o nome do evento.';
    foreach (['curso_area_id' => ['cursos_areas', 'Curso/Área'], 'tema_id' => ['temas', 'Tema'], 'ambiente_id' => ['ambientes', 'Ambiente']] as $campo => [$tab, $rot]) {
        $st = db()->prepare("SELECT 1 FROM $tab WHERE id = ?");
        $st->execute([$d[$campo]]);
        if (!$st->fetch()) $e[] = "Selecione um(a) $rot válido(a).";
    }

    $dt = DateTime::createFromFormat('Y-m-d', $d['data_evento']);
    if (!$dt || $dt->format('Y-m-d') !== $d['data_evento']) {
        $e[] = 'Informe uma data válida.';
    } elseif (!$admin && $d['data_evento'] < date('Y-m-d')) {
        $e[] = 'A data do evento não pode ser anterior a hoje.';
    }

    if (!isset(PERIODOS[$d['periodo']])) {
        $e[] = 'Selecione o período.';
    } else {
        $p = PERIODOS[$d['periodo']];
        $ini = substr($d['hora_inicio'], 0, 5);
        $fim = substr($d['hora_fim'], 0, 5);
        if (!preg_match('/^\d{2}:\d{2}$/', $ini) || !preg_match('/^\d{2}:\d{2}$/', $fim)) {
            $e[] = 'Informe o horário de início e fim.';
        } elseif ($ini < $p['inicio'] || $fim > $p['fim']) {
            $e[] = "No período {$p['rotulo']} o horário deve estar entre {$p['inicio']} e {$p['fim']}.";
        } elseif ($ini >= $fim) {
            $e[] = 'O horário de início deve ser anterior ao de término.';
        }
        $d['hora_inicio'] = $ini;
        $d['hora_fim'] = $fim;
    }

    if ($d['rec_outros'] && $d['rec_outros_desc'] === '') $e[] = 'Descreva os outros recursos necessários.';
    if (!$d['rec_outros']) $d['rec_outros_desc'] = null;
    if ($d['palestrante'] === '') $d['palestrante'] = null;

    if (!$e && $checarConflito) {
        $c = conflito_agenda($d['ambiente_id'], $d['data_evento'], $d['hora_inicio'], $d['hora_fim'], $idAtual);
        if ($c) {
            $e[] = 'Este ambiente já está reservado nesse horário pelo evento "' . $c['titulo'] . '" ('
                 . hora_br($c['hora_inicio']) . ' às ' . hora_br($c['hora_fim']) . ').';
        }
    }
    return [$d, $e];
}
