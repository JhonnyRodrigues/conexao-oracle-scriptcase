<?php

declare(strict_types=1);

namespace Semae\ScriptcaseOracle\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\ScriptcaseOracle\BancoOracle;
use stdClass;

final class BancoOracleEscritaTest extends TestCase
{
    #[DataProvider('comandos')]
    public function testExecutaComSqlEBindsSeparados(string $sql, array $parametros): void
    {
        $adodb = new AdodbConsultaFake();
        $banco = new BancoOracle($adodb, 'UTF-8', 'UTF-8');
        self::assertSame([], $adodb->execucoes);

        self::assertNull($banco->executar($sql, $parametros));

        self::assertSame([[$sql, $parametros]], $adodb->execucoes);
        self::assertSame([], $adodb->consultas);
        self::assertSame([], $adodb->alteracoesDeModo);
        self::assertSame([], $adodb->diagnosticos);
    }

    public static function comandos(): iterable
    {
        yield 'insert' => [
            'INSERT INTO TESTE (ID, NOME, VALOR, OBS) VALUES (:id, :nome, :valor, :obs)',
            ['id' => 1, 'nome' => "D'Ávila'); DELETE FROM TESTE --", 'valor' => 12.5, 'obs' => null],
        ];
        yield 'update' => ['UPDATE TESTE SET NOME = :nome WHERE ID = :id', ['nome' => '', 'id' => 1]];
        yield 'delete' => ['DELETE FROM TESTE WHERE ID = :id', ['id' => 1]];
        yield 'sem binds' => ['DELETE FROM TESTE', []];
    }

    #[DataProvider('retornosAceitos')]
    public function testNaoUsaRetornoDoDriverComoQuantidadeDeLinhas(mixed $retorno): void
    {
        $adodb = new AdodbConsultaFake();
        $adodb->resultadoExecute = $retorno;
        $banco = new BancoOracle($adodb, 'UTF-8', 'UTF-8');

        self::assertNull($banco->executar('UPDATE TESTE SET VALOR = 1'));
        self::assertCount(1, $adodb->execucoes);
    }

    public static function retornosAceitos(): iterable
    {
        yield 'sucesso booleano' => [true];
        yield 'recordset' => [new stdClass()];
        yield 'zero não é false' => [0];
    }

    #[DataProvider('parametrosInvalidos')]
    public function testRejeitaBindsAntesDeExecutar(array $parametros): void
    {
        $adodb = new AdodbConsultaFake();
        $banco = new BancoOracle($adodb, 'UTF-8', 'UTF-8');

        try {
            $banco->executar('DELETE FROM TESTE WHERE ID = :id', $parametros);
            self::fail('Era esperada rejeição dos binds.');
        } catch (InvalidArgumentException) {
            self::assertSame([], $adodb->execucoes);
            self::assertSame([], $adodb->diagnosticos);
            self::assertSame([], $adodb->alteracoesDeModo);
        }
    }

    public static function parametrosInvalidos(): iterable
    {
        yield from BancoOracleTest::parametrosInvalidos();
    }

    public function testConverteSqlEBindsAntesDeExecutar(): void
    {
        $adodb = new AdodbConsultaFake();
        $banco = new BancoOracle($adodb, 'Windows-1252', 'UTF-8');

        $banco->executar("UPDATE TESTE SET NOME = :nome WHERE TIPO = 'ação'", ['nome' => 'café']);

        self::assertSame([
            ["UPDATE TESTE SET NOME = :nome WHERE TIPO = 'a\xE7\xE3o'", ['nome' => "caf\xE9"]],
        ], $adodb->execucoes);
    }
}
