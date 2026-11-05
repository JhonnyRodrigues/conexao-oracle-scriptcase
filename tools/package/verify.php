<?php

declare(strict_types=1);

if (!isset($argv[1]) || !is_dir($argv[1])) {
    fwrite(STDERR, "Uso: composer package:verify -- caminho/da/entrega\n");
    exit(1);
}
$pacote = realpath($argv[1]);
$manifesto = json_decode(file_get_contents($pacote . '/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
$hashes = [];
$iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pacote, FilesystemIterator::SKIP_DOTS));
foreach ($iterador as $arquivo) {
    if ($arquivo->isFile() && $arquivo->getPathname() !== $pacote . DIRECTORY_SEPARATOR . 'manifest.json') {
        $relativo = str_replace('\\', '/', substr($arquivo->getPathname(), strlen($pacote) + 1));
        if ($relativo !== 'manifest.json') {
            $hashes[$relativo] = hash_file('sha256', $arquivo->getPathname());
        }
    }
}
ksort($hashes);
if ($hashes !== ($manifesto['files'] ?? null)) {
    throw new RuntimeException('Os arquivos da entrega divergem do manifesto SHA-256.');
}
$externa = $pacote . '/externa/semae/conexaoOracleScriptcase';
if (trim(file_get_contents($externa . '/VERSION')) !== ($manifesto['version'] ?? null)) {
    throw new RuntimeException('A versão da entrega diverge do manifesto.');
}
if (is_dir($externa . '/vendor/phpunit')) {
    throw new RuntimeException('A entrega não deve conter dependências de desenvolvimento.');
}

passthru(
    escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/smoke.php') . ' ' . escapeshellarg($pacote),
    $codigo
);
if ($codigo !== 0) {
    exit($codigo);
}
echo 'Pacote verificado: versão ' . $manifesto['version'] . ', integridade e carregamento isolado aprovados.' . PHP_EOL;
