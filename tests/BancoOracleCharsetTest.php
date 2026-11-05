<?php

declare(strict_types=1);

namespace Semae\ScriptcaseOracle\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\ScriptcaseOracle\BancoOracle;
use UnexpectedValueException;

final class BancoOracleCharsetTest extends TestCase
{
    #[DataProvider('paresDeCharsets')]
    public function testConverteTextosEPreservaTiposNasDuasOperacoes(
        string $conexao,
        string $aplicacao,
        string $textoConexao,
        string $textoAplicacao
    ): void {
        $adodb = new AdodbCicloVidaFake();
        $adodb->resultado = [['Descricao' => $textoConexao, 'ID' => 1, 'VALOR' => 2.5, 'NULO' => null]];
        $banco = new BancoOracle($adodb, $conexao, $aplicacao);
        $sql = 'SELECT DESCRICAO FROM TESTE WHERE NOME = :nome';
        $binds = ['nome' => $textoAplicacao, 'id' => 1, 'valor' => 2.5, 'nulo' => null, 'numero' => '001,23'];
        $bindsEsperados = ['nome' => $textoConexao, 'id' => 1, 'valor' => 2.5, 'nulo' => null, 'numero' => '001,23'];

        self::assertSame([
            ['Descricao' => $textoAplicacao, 'ID' => 1, 'VALOR' => 2.5, 'NULO' => null],
        ], $banco->buscarTodos($sql, $binds));
        $banco->executar('UPDATE TESTE SET NOME = :nome', $binds);

        self::assertSame([[$sql, $bindsEsperados]], $adodb->consultas);
        self::assertSame([['UPDATE TESTE SET NOME = :nome', $bindsEsperados]], $adodb->execucoes);
        self::assertSame($textoAplicacao, $binds['nome']);
        self::assertSame($textoConexao, $adodb->resultado[0]['Descricao']);
        self::assertSame([], $adodb->chamadasNaoPermitidas);
        self::assertSame([], $adodb->diagnosticos);
    }

    public static function paresDeCharsets(): iterable
    {
        yield from ConversorCharsetTest::paresDeCharsets();
    }

    #[DataProvider('entradasInvalidas')]
    public function testFalhaDeEntradaNaoChamaDriver(
        string $conexao,
        string $aplicacao,
        string $sql,
        array $binds
    ): void {
        foreach (['buscarTodos', 'executar'] as $operacao) {
            $adodb = new AdodbConsultaFake();
            $banco = new BancoOracle($adodb, $conexao, $aplicacao);

            try {
                $banco->$operacao($sql, $binds);
                self::fail('Era esperada falha de codificação antes de chamar o driver.');
            } catch (UnexpectedValueException $falha) {
                self::assertNull($falha->getPrevious());
                self::assertStringNotContainsString('SEGREDO', $falha->getMessage());
                self::assertSame([], $adodb->execucoes);
                self::assertSame([], $adodb->consultas);
                self::assertSame([], $adodb->alteracoesDeModo);
                self::assertSame([], $adodb->diagnosticos);
            }
        }
    }

    public static function entradasInvalidas(): iterable
    {
        foreach (['UTF-8', 'Windows-1252', 'ISO-8859-1'] as $destino) {
            yield [$destino, 'UTF-8', "SELECT '\xC3\x28 SEGREDO' FROM DUAL", []];
            yield [$destino, 'UTF-8', 'SELECT :texto FROM DUAL', ['texto' => "\xFF SEGREDO"]];
        }
        yield ['Windows-1252', 'UTF-8', "SELECT 'SEGREDO 😀' FROM DUAL", []];
        yield ['ISO-8859-1', 'UTF-8', 'SELECT :texto FROM DUAL', ['texto' => 'SEGREDO €']];
        yield ['ISO-8859-1', 'Windows-1252', 'SELECT :texto FROM DUAL', ['texto' => "SEGREDO \x80"]];
    }

    #[DataProvider('saidasInvalidas')]
    public function testFalhaDeSaidaPreservaModoDaConexao(
        string $conexao,
        string $aplicacao,
        string $texto
    ): void {
        $adodb = new AdodbCicloVidaFake();
        $adodb->fetchMode = false;
        $adodb->resultado = [['TEXTO' => $texto]];
        $banco = new BancoOracle($adodb, $conexao, $aplicacao);

        try {
            $banco->buscarTodos('SELECT TEXTO FROM TESTE');
            self::fail('Era esperada falha de codificação na leitura.');
        } catch (UnexpectedValueException $falha) {
            self::assertNull($falha->getPrevious());
            self::assertStringNotContainsString('SEGREDO', $falha->getMessage());
            self::assertFalse($adodb->fetchMode);
            self::assertCount(1, $adodb->consultas);
            self::assertSame([], $adodb->diagnosticos);
            self::assertSame([], $adodb->chamadasNaoPermitidas);
        }
    }

    public static function saidasInvalidas(): iterable
    {
        foreach (['UTF-8', 'Windows-1252', 'ISO-8859-1'] as $aplicacao) {
            yield ['UTF-8', $aplicacao, "\xFF SEGREDO"];
        }
        yield ['UTF-8', 'Windows-1252', 'SEGREDO 😀'];
        yield ['UTF-8', 'ISO-8859-1', 'SEGREDO €'];
        yield ['Windows-1252', 'ISO-8859-1', "SEGREDO \x80"];
    }

    public function testNaoDetectaCharsetAutomaticamenteNemAlteraNomesDasColunas(): void
    {
        $adodb = new AdodbConsultaFake();
        $coluna = "Descri\xE7\xE3o";
        $adodb->resultado = [[$coluna => "\xC3\xA9", 'BOOLEANO' => false]];
        $banco = new BancoOracle($adodb, 'Windows-1252', 'UTF-8');

        // Os mesmos bytes são tratados conforme a configuração explícita, mesmo parecendo UTF-8.
        self::assertSame([[$coluna => "\xC3\x83\xC2\xA9", 'BOOLEANO' => false]], $banco->buscarTodos('SELECT 1'));
    }
}
