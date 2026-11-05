<?php

declare(strict_types=1);

$raiz = dirname(__DIR__, 2);
$versao = trim(file_get_contents($raiz . '/VERSION'));
if (preg_match('/\A[0-9]+\.[0-9]+\.[0-9]+\z/', $versao) !== 1) {
    throw new RuntimeException('VERSION deve conter uma versão no formato X.Y.Z.');
}

$destino = $argv[1] ?? $raiz . '/dist/' . $versao . '-' . gmdate('Ymd\THis\Z') . '-' . bin2hex(random_bytes(3));
if (file_exists($destino)) {
    throw new RuntimeException('O destino já existe. Escolha uma pasta nova para preservar a entrega anterior.');
}
if (!mkdir($destino, 0777, true)) {
    throw new RuntimeException('Não foi possível criar a pasta da entrega.');
}
$destino = realpath($destino);
$externa = $destino . '/externa/semae/conexaoOracleScriptcase';

$copiar = static function (string $origem, string $arquivoDestino): void {
    $pasta = dirname($arquivoDestino);
    if (!is_dir($pasta) && !mkdir($pasta, 0777, true)) {
        throw new RuntimeException('Não foi possível preparar a pasta de destino.');
    }
    if (!copy($origem, $arquivoDestino)) {
        throw new RuntimeException('Não foi possível copiar um arquivo da entrega.');
    }
};

foreach (['autoload.php', 'VERSION'] as $arquivo) {
    $copiar($raiz . '/' . $arquivo, $externa . '/' . $arquivo);
}
foreach (['src' => $externa . '/src', 'scriptcase' => $destino . '/scriptcase', 'docs' => $destino . '/docs'] as $origem => $alvo) {
    $base = $raiz . '/' . $origem;
    $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
    foreach ($iterador as $arquivo) {
        if ($arquivo->isFile()) {
            $relativo = substr($arquivo->getPathname(), strlen($base) + 1);
            $copiar($arquivo->getPathname(), $alvo . '/' . $relativo);
        }
    }
}
file_put_contents(
    $destino . '/README.md',
    "# conexaoOracleScriptcase {$versao}\n\n"
        . "Consulte o registro de homologacao para a aprovacao e os limites desta versao.\n\n"
        . "- [Instalacao, atualizacao e reversao](docs/INSTALACAO.md)\n"
        . "- [Roteiro e registro de homologacao](docs/HOMOLOGACAO.md)\n"
        . "- [Exemplos de eventos](scriptcase/exemplos/README.md)\n"
);

$composer = json_decode(file_get_contents($raiz . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
unset($composer['require-dev'], $composer['autoload-dev'], $composer['scripts'], $composer['config']);
$composer['version'] = $versao;
file_put_contents(
    $externa . '/composer.json',
    json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n"
);

// Gera somente o autoload de produção em uma pasta separada, sem alterar vendor/ do projeto.
putenv('COMPOSER_ROOT_VERSION=' . $versao);
passthru(
    'composer dump-autoload --no-dev --optimize --strict-psr --working-dir=' . escapeshellarg($externa),
    $codigo
);
if ($codigo !== 0) {
    throw new RuntimeException('Falha ao gerar autoload. A pasta incompleta foi preservada e não deve ser instalada.');
}

$hashes = [];
$iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($destino, FilesystemIterator::SKIP_DOTS));
foreach ($iterador as $arquivo) {
    if ($arquivo->isFile()) {
        $relativo = str_replace('\\', '/', substr($arquivo->getPathname(), strlen($destino) + 1));
        $hashes[$relativo] = hash_file('sha256', $arquivo->getPathname());
    }
}
ksort($hashes);
file_put_contents(
    $destino . '/manifest.json',
    json_encode(
        ['version' => $versao, 'created_at_utc' => gmdate('c'), 'files' => $hashes],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    ) . "\n"
);

echo 'Pacote preparado: ' . $destino . PHP_EOL;
