<?php
$cfg = [
    'tabela'   => 'cursos_areas',
    'titulo'   => 'Cursos/Áreas',
    'singular' => 'Curso/Área',
    'menu'     => 'cursos',
    'pagina'   => 'admin/cursos.php',
    'vinculo'  => 'curso_area_id', // coluna em solicitacoes que usa este cadastro
    'campos'   => [
        'nome'         => ['Nome', 'Ex.: Engenharia Civil'],
        'centro_custo' => ['Centro de custo', 'Ex.: 1020.30'],
    ],
];
require __DIR__ . '/../includes/cadastro_simples.php';
