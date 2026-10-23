<?php
$cfg = [
    'tabela'   => 'temas',
    'titulo'   => 'Temas',
    'singular' => 'Tema',
    'menu'     => 'temas',
    'pagina'   => 'admin/temas.php',
    'vinculo'  => 'tema_id', // coluna em solicitacoes que usa este cadastro
    'campos'   => [
        'nome' => ['Nome', 'Ex.: Tecnologia e Inovação'],
    ],
];
require __DIR__ . '/../includes/cadastro_simples.php';
