<?php
// Retorna os eventos aprovados de um ambiente em uma data (cada um reserva o seu período inteiro)
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json; charset=utf-8');

if (!usuario_atual()) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

$ambiente = (int)($_GET['ambiente'] ?? 0);
$data     = $_GET['data'] ?? '';
$ignorar  = (int)($_GET['ignorar'] ?? 0);

if (!$ambiente || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    echo json_encode([]);
    exit;
}

$st = db()->prepare(
    "SELECT titulo, periodo, TIME_FORMAT(hora_inicio, '%H:%i') AS inicio, TIME_FORMAT(hora_fim, '%H:%i') AS fim
       FROM solicitacoes
      WHERE status = 'aprovado' AND ambiente_id = ? AND data_evento = ? AND id <> ?
      ORDER BY hora_inicio"
);
$st->execute([$ambiente, $data, $ignorar]);
echo json_encode($st->fetchAll());
