<?php
$cfg = [
    'tabela'   => 'ambientes',
    'titulo'   => 'Ambientes',
    'singular' => 'Ambiente',
    'menu'     => 'ambientes',
    'pagina'   => 'admin/ambientes.php',
    'campos'   => [
        'nome'        => ['Nome', 'Ex.: Auditório principal'],
        'localizacao' => ['Localização', 'Ex.: 3º andar - Bloco A'],
    ],
];
require __DIR__ . '/../includes/cadastro_simples.php';
