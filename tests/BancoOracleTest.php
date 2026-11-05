<?php

declare(strict_types=1);

namespace Semae\ScriptcaseOracle\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Semae\ScriptcaseOracle\BancoOracle;
use Semae\ScriptcaseOracle\FalhaOracleException;
use stdClass;

final class BancoOracleTest extends TestCase
{
    public function testEnviaSqlEBindsSeparadamenteEPreservaLinhasEColunas(): void
    {
        $adodb = new AdodbConsultaFake();
        $adodb->resultado = [
            ['ID' => 1, 'Descricao' => "D'Ávila", 'VALOR' => 12.5, 'OBS' => null],
            ['ID' => 2, 'Descricao' => 'Outro', 'VALOR' => 0, 'OBS' => ''],
        ];
        $banco = new BancoOracle($adodb, 'UTF-8', 'UTF-8');
        self::assertSame([], $adodb->consultas);
        self::assertSame([], $adodb->alteracoesDeModo);

        $sql = 'SELECT ID, DESCRICAO AS "Descricao", VALOR, OBS FROM TESTE WHERE NOME = :nome';
        $parametros = ['nome' => "D'Ávila' OR 1 = 1 --"];

        self::assertSame($adodb->resultado, $banco->buscarTodos($sql, $parametros));
        self::assertSame([[$sql, $parametros]], $adodb->consultas);
        self::assertSame([2], $adodb->modosNasConsultas);
        self::assertSame(3, $adodb->fetchMode);
    }

    public function testRetornaListaVaziaSemParametros(): void
    {
        $adodb = new AdodbConsultaFake();
        $banco = new BancoOracle($adodb, 'UTF-8', 'UTF-8');

        self::assertSame([], $banco->buscarTodos('SELECT ID FROM TESTE'));
        self::assertSame([['SELECT ID FROM TESTE', []]], $adodb->consultas);
    }

    public function testPreservaTiposDosBindsENaoInterpretaMarcadores(): void
    {
        $adodb = new AdodbConsultaFake();
        $banco = new BancoOracle($adodb, 'UTF-8', 'UTF-8');
        $sql = "SELECT ':literal', '?' FROM TESTE WHERE ID = :id OR PAI = :id -- :comentario";
        $parametros = ['id' => 7, 'a_2' => 12.5, 'Texto' => '001,23', 'nulo' => null];

        $banco->buscarTodos($sql, $parametros);

        // Até a divergência entre SQL e binds deve chegar intacta ao driver.
        self::assertSame([[$sql, $parametros]], $adodb->consultas);
    }

    #[DataProvider('parametrosInvalidos')]
    public function testRejeitaBindsInvalidosAntesDeTocarNoDriver(array $parametros): void
    {
        $adodb = new AdodbConsultaFake();
        $banco = new BancoOracle($adodb, 'UTF-8', 'UTF-8');

        try {
            $banco->buscarTodos('SELECT :valor FROM DUAL', $parametros);
            self::fail('Era esperada rejeição dos parâmetros.');
        } catch (InvalidArgumentException) {
            self::assertSame([], $adodb->consultas);
            self::assertSame([], $adodb->alteracoesDeModo);
            self::assertSame(3, $adodb->fetchMode);
        }
    }

    public static function parametrosInvalidos(): iterable
    {
        yield 'posicional' => [[0 => 'valor']];
        yield 'chave numérica textual' => [['123' => 'valor']];
        yield 'dois pontos' => [[':valor' => 1]];
        yield 'vazio' => [['' => 1]];
        yield 'começa com número' => [['1valor' => 1]];
        yield 'começa com sublinhado' => [['_valor' => 1]];
        yield 'hífen' => [['meu-valor' => 1]];
        yield 'espaço' => [['meu valor' => 1]];
        yield 'acento' => [['descrição' => 1]];
        yield 'quebra de linha' => [["valor\n" => 1]];
        yield 'booleano verdadeiro' => [['valor' => true]];
        yield 'booleano falso' => [['valor' => false]];
        yield 'array' => [['valor' => []]];
        yield 'objeto' => [['valor' => new stdClass()]];
    }

    public function testRejeitaRecursoComoBind(): void
    {
        $recurso = fopen('php://memory', 'r+');
        try {
            $this->testRejeitaBindsInvalidosAntesDeTocarNoDriver(['valor' => $recurso]);
        } finally {
            fclose($recurso);
        }
    }

    #[DataProvider('charsetsValidos')]
    public function testAceitaCharSetsExatosSemExecutarConsulta(string $charset): void
    {
        $adodb = new AdodbConsultaFake();
        new BancoOracle($adodb, $charset, $charset);

        self::assertSame([], $adodb->consultas);
    }

    public static function charsetsValidos(): iterable
    {
        yield ['UTF-8'];
        yield ['Windows-1252'];
        yield ['ISO-8859-1'];
    }

    #[DataProvider('charsetsInvalidos')]
    public function testRejeitaCharsetInvalido(string $conexao, string $aplicacao): void
    {
        $this->expectException(InvalidArgumentException::class);
        new BancoOracle(new AdodbConsultaFake(), $conexao, $aplicacao);
    }

    public static function charsetsInvalidos(): iterable
    {
        yield ['', 'UTF-8'];
        yield ['utf-8', 'UTF-8'];
        yield ['UTF8', 'UTF-8'];
        yield ['UTF-8', 'windows-1252'];
        yield ['UTF-8', 'UTF-16'];
        yield [' UTF-8', 'UTF-8'];
    }

    public function testRejeitaObjetoSemContratoDeConsultaAdodb(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new BancoOracle(new stdClass(), 'UTF-8', 'UTF-8');
    }

    public function testConverteSqlBindsEResultadosEntreCharsetsDiferentes(): void
    {
        $adodb = new AdodbConsultaFake();
        $banco = new BancoOracle($adodb, 'Windows-1252', 'UTF-8');
        $adodb->resultado = [['TEXTO' => "a\xE7\xE3o"]];

        $linhas = $banco->buscarTodos("SELECT 'ação' AS TEXTO FROM TESTE WHERE NOME = :nome", ['nome' => 'café']);

        self::assertSame([['TEXTO' => 'ação']], $linhas);
        self::assertSame([
            ["SELECT 'a\xE7\xE3o' AS TEXTO FROM TESTE WHERE NOME = :nome", ['nome' => "caf\xE9"]],
        ], $adodb->consultas);
    }

    #[DataProvider('modosDeBusca')]
    public function testRestauraExatamenteModoOriginalInclusiveFalse(int|false $modo): void
    {
        $adodb = new AdodbConsultaFake();
        $adodb->fetchMode = $modo;
        $banco = new BancoOracle($adodb, 'UTF-8', 'UTF-8');

        $banco->buscarTodos('SELECT ID FROM TESTE');

        self::assertSame([2], $adodb->modosNasConsultas);
        self::assertSame($modo, $adodb->fetchMode);
    }

    public static function modosDeBusca(): iterable
    {
        yield [false];
        yield [-1];
        yield [0];
        yield [1];
        yield [2];
        yield [3];
    }

    #[DataProvider('resultadosInvalidos')]
    public function testRejeitaRespostaInvalidaSemExporDados(mixed $resultado): void
    {
        $adodb = new AdodbConsultaFake();
        $adodb->fetchMode = false;
        $adodb->resultado = $resultado;
        $banco = new BancoOracle($adodb, 'UTF-8', 'UTF-8');

        try {
            $banco->buscarTodos('SELECT SEGREDO FROM TESTE');
            self::fail('Era esperada falha controlada.');
        } catch (FalhaOracleException $falha) {
            self::assertSame('Falha ao consultar dados no Oracle.', $falha->getMessage());
            self::assertNull($falha->getPrevious());
            self::assertSame(false, $adodb->fetchMode);
        }
    }

    public static function resultadosInvalidos(): iterable
    {
        yield [false];
        yield [true];
        yield [null];
        yield ['SEGREDO'];
        yield [[null]];
        yield [[['ID' => 1], 'SEGREDO']];
        yield [[[0 => 1, 'ID' => 1]]];
    }

    public function testTraduzExcecaoDoDriverERestauraModo(): void
    {
        $adodb = new AdodbConsultaFake();
        $adodb->falha = new RuntimeException('SQL e senha SEGREDO');
        $banco = new BancoOracle($adodb, 'UTF-8', 'UTF-8');

        try {
            $banco->buscarTodos('SELECT SEGREDO FROM TESTE');
            self::fail('Era esperada falha controlada.');
        } catch (FalhaOracleException $falha) {
            self::assertSame('Falha ao consultar dados no Oracle.', $falha->getMessage());
            self::assertNull($falha->getPrevious());
            self::assertSame(3, $adodb->fetchMode);
        }
    }

    public function testReindexaListaSemAlterarNomesDasColunas(): void
    {
        $adodb = new AdodbConsultaFake();
        $adodb->resultado = [8 => ['Id' => 1], 20 => ['Id' => 2]];
        $banco = new BancoOracle($adodb, 'UTF-8', 'UTF-8');

        self::assertSame([['Id' => 1], ['Id' => 2]], $banco->buscarTodos('SELECT ID AS "Id" FROM TESTE'));
    }

    public function testPermiteCarregarPontoDeEntradaRepetidamente(): void
    {
        require dirname(__DIR__) . '/autoload.php';
        require dirname(__DIR__) . '/autoload.php';

        self::assertTrue(class_exists(BancoOracle::class));
    }
}
