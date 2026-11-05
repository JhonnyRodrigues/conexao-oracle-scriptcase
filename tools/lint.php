<?php

declare(strict_types=1);

$raiz = dirname(__DIR__);
$arquivos = [$raiz . '/autoload.php'];

foreach (['src', 'tests', 'tools', 'scriptcase'] as $diretorio) {
    $caminho = $raiz . '/' . $diretorio;
    if (!is_dir($caminho)) {
        continue;
    }

    $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($caminho));
    foreach ($iterador as $arquivo) {
        if ($arquivo->isFile() && $arquivo->getExtension() === 'php') {
            $arquivos[] = $arquivo->getPathname();
        }
    }
}

foreach ($arquivos as $arquivo) {
    passthru(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($arquivo), $codigo);
    if ($codigo !== 0) {
        exit($codigo);
    }
}
