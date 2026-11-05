<?php

declare(strict_types=1);

namespace Semae\ScriptcaseOracle\Tests;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Semae\ScriptcaseOracle\BancoOracle;
use Semae\ScriptcaseOracle\FalhaOracleException;

final class ScriptcaseBibliotecaInternaTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testCarregaExternaRepetidamenteEConstroiInstanciasSemExecutarSql(): void
    {
        $arquivo = dirname(__DIR__) . '/scriptcase/interna/conexaoOracle.php';
        self::assertFileExists($arquivo);
        require __DIR__ . '/fixtures/macros-inclusao.php';
        require_once $arquivo;
        require_once $arquivo;
        self::assertSame(0, $GLOBALS['scriptcaseOracleInclusoes'] ?? 0);

        $adodb = new AdodbCicloVidaFake();
        $primeira = \criarConexaoOracle($adodb, 'Windows-1252', 'UTF-8');
        $segunda = \criarConexaoOracle($adodb, 'Windows-1252', 'UTF-8');

        self::assertInstanceOf(BancoOracle::class, $primeira);
        self::assertNotSame($primeira, $segunda);
        self::assertSame([], $adodb->consultas);
        self::assertSame([], $adodb->execucoes);
        self::assertSame([], $adodb->chamadasNaoPermitidas);
        self::assertSame(2, $GLOBALS['scriptcaseOracleInclusoes']);

        $adodb->resultadoExecute = false;
        $adodb->numeroErro = 942;
        $this->expectException(FalhaOracleException::class);
        $primeira->executar('DELETE FROM TESTE WHERE ID = :id', ['id' => 1]);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testFonteInternaPodeSerUsadaComoMetodoSemExecutarCodigoAoSerDeclarada(): void
    {
        require __DIR__ . '/fixtures/macros-inclusao.php';
        $fonte = file_get_contents(dirname(__DIR__) . '/scriptcase/interna/conexaoOracle.php');

        // A flag incorpora funções como métodos: código executável no corpo da classe é inválido.
        // Isto verifica a forma PHP compatível; não substitui o compilador real do Scriptcase.
        $aplicacao = eval('return new class {' . substr($fonte, strlen('<?php')) . '};');

        self::assertTrue(method_exists($aplicacao, 'criarConexaoOracle'));
        self::assertFalse(function_exists('criarConexaoOracle'));
        self::assertSame(0, $GLOBALS['scriptcaseOracleInclusoes'] ?? 0);

        $adodb = new AdodbCicloVidaFake();
        $primeira = $aplicacao->criarConexaoOracle($adodb, 'UTF-8', 'UTF-8');
        $segunda = $aplicacao->criarConexaoOracle($adodb, 'UTF-8', 'UTF-8');

        self::assertInstanceOf(BancoOracle::class, $primeira);
        self::assertNotSame($primeira, $segunda);
        self::assertSame(2, $GLOBALS['scriptcaseOracleInclusoes']);
        self::assertSame([], $adodb->consultas);
        self::assertSame([], $adodb->execucoes);
        self::assertSame([], $adodb->chamadasNaoPermitidas);
    }
}
