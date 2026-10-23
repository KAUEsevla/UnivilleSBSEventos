<?php
$cfg = [
    'tabela'   => 'cursos_areas',
    'titulo'   => 'Cursos/Áreas',
    'singular' => 'Curso/Área',
    'menu'     => 'cursos',
    'pagina'   => 'admin/cursos.php',
    'campos'   => [
        'nome'         => ['Nome', 'Ex.: Engenharia Civil'],
        'centro_custo' => ['Centro de custo', 'Ex.: 1020.30'],
    ],
];
require __DIR__ . '/../includes/cadastro_simples.php';
