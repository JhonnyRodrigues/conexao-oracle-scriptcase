<?php

declare(strict_types=1);

// Processo separado: não carregar o autoload do repositório, pois ocultaria arquivos ausentes no pacote.
$pacote = realpath($argv[1]);
$scriptcaseOracleExterna = $pacote . '/externa/semae/conexaoOracleScriptcase';

function sc_include_library(string $escopo, string $biblioteca, string $arquivo, bool $umaVez, bool $obrigatorio): void
{
    global $scriptcaseOracleExterna;
    if (
        [$escopo, $biblioteca, $arquivo, $umaVez, $obrigatorio]
        !== ['sys', 'semae', 'conexaoOracleScriptcase/autoload.php', true, true]
    ) {
        throw new RuntimeException('Inclusão externa incorreta.');
    }
    require_once $scriptcaseOracleExterna . '/autoload.php';
}

$exigir = static function (bool $condicao): void {
    if (!$condicao) {
        throw new RuntimeException('Falhou a verificação isolada de integração.');
    }
};
require dirname(__DIR__, 2) . '/tests/AdodbConsultaFake.php';
require dirname(__DIR__, 2) . '/tests/AdodbCicloVidaFake.php';
$fonteInterna = file_get_contents($pacote . '/scriptcase/interna/conexaoOracle.php');
$aplicacao = eval('return new class {' . substr($fonteInterna, strlen('<?php')) . '};');
$exigir(method_exists($aplicacao, 'criarConexaoOracle'));
$exigir(!function_exists('criarConexaoOracle'));
$exigir(!class_exists('Semae\\ScriptcaseOracle\\BancoOracle', false));

$adodb = new \Semae\ScriptcaseOracle\Tests\AdodbCicloVidaFake();
$adodb->fetchMode = false;
$banco = $aplicacao->criarConexaoOracle($adodb, 'Windows-1252', 'UTF-8');
$outro = $aplicacao->criarConexaoOracle($adodb, 'Windows-1252', 'UTF-8');
require $scriptcaseOracleExterna . '/autoload.php';
require $scriptcaseOracleExterna . '/autoload.php';
$exigir($banco !== $outro && $adodb->consultas === [] && $adodb->execucoes === []);
$adodb->resultado = [['DESCRICAO' => "A\xC7\xC3O"]];
$exigir($banco->buscarTodos('SELECT DESCRICAO FROM TESTE') === [['DESCRICAO' => 'AÇÃO']]);
$banco->executar('UPDATE TESTE SET DESCRICAO = :texto', ['texto' => 'AÇÃO']);
$exigir($adodb->execucoes[0][1] === ['texto' => "A\xC7\xC3O"]);
$adodb->resultadoExecute = false;
$adodb->numeroErro = 942;
try {
    $banco->executar('DELETE FROM TESTE');
    throw new RuntimeException('A falha do driver foi ignorada.');
} catch (\Semae\ScriptcaseOracle\FalhaOracleException $falha) {
    $exigir($falha->getCodigoOracle() === 'ORA-00942' && $falha->getPrevious() === null);
}
$exigir($adodb->fetchMode === false && $adodb->chamadasNaoPermitidas === []);
foreach (['BancoOracle', 'ConversorCharset', 'FalhaOracleException'] as $classe) {
    $arquivo = (new ReflectionClass('Semae\\ScriptcaseOracle\\' . $classe))->getFileName();
    $exigir(str_starts_with(realpath($arquivo), realpath($scriptcaseOracleExterna) . DIRECTORY_SEPARATOR));
}
$exigir(!class_exists('PHPUnit\\Framework\\TestCase'));
echo "Método da interna, autoload repetido, fábrica, consulta, comando e falha verificados sem Oracle.\n";
