<?php

declare(strict_types=1);

namespace Semae\ScriptcaseOracle\Tests;

use Error;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Semae\ScriptcaseOracle\BancoOracle;
use Semae\ScriptcaseOracle\FalhaOracleException;
use stdClass;
use Throwable;

final class BancoOracleFalhasTest extends TestCase
{
    #[DataProvider('codigosOracle')]
    public function testExpoeApenasCodigoOracleControlado(
        mixed $numero,
        mixed $mensagem,
        ?string $esperado
    ): void {
        foreach (['buscarTodos', 'executar'] as $operacao) {
            $adodb = new AdodbConsultaFake();
            $adodb->resultado = false;
            $adodb->resultadoExecute = false;
            $adodb->numeroErro = $numero;
            $adodb->mensagemErro = $mensagem;
            $banco = new BancoOracle($adodb, 'UTF-8', 'UTF-8');

            try {
                $banco->$operacao('SELECT SQL_SECRETO FROM TESTE WHERE ID = :id', ['id' => 'BIND_SECRETO']);
                self::fail('Era esperada falha controlada.');
            } catch (FalhaOracleException $falha) {
                self::assertSame($esperado, $falha->getCodigoOracle());
                self::assertSame($this->mensagemSegura($operacao), $falha->getMessage());
                self::assertNull($falha->getPrevious());
                self::assertSame(3, $adodb->fetchMode);
            }
        }
    }

    public static function codigosOracle(): iterable
    {
        yield 'inteiro' => [942, 'DRIVER_SECRETO', 'ORA-00942'];
        yield 'string numérica' => ['1', 'DRIVER_SECRETO', 'ORA-00001'];
        yield 'negativo' => [-1400, '', 'ORA-01400'];
        yield 'string negativa' => ['-000942', '', 'ORA-00942'];
        yield 'limite de cinco dígitos' => [99999, '', 'ORA-99999'];
        yield 'prioridade do número' => [942, 'ORA-00001: DRIVER_SECRETO', 'ORA-00942'];
        yield 'fallback mensagem' => [0, 'ORA-01008: DRIVER_SECRETO', 'ORA-01008'];
        yield 'código no ErrorNo textual' => ['ORA-00942: DRIVER_SECRETO', '', 'ORA-00942'];
        yield 'ausente' => [0, 'DRIVER_SECRETO', null];
        yield 'tipos inesperados' => [new stdClass(), ['ORA-00942'], null];
        yield 'booleano' => [true, '', null];
        yield 'float' => [942.0, '', null];
        yield 'número grande' => [100000, '', null];
        yield 'inteiro mínimo' => [PHP_INT_MIN, '', null];
        yield 'string enorme' => [str_repeat('9', 40), '', null];
        yield 'zero não é erro' => ['00000', 'ORA-00000: sucesso', null];
        yield 'sem truncar seis dígitos' => [0, 'ORA-123456: DRIVER_SECRETO', null];
        yield 'sem aceitar prefixo inválido' => [0, 'XORA-00942: DRIVER_SECRETO', null];
        yield 'sem converter SQL bruto em número' => ['942 SQL_SECRETO', '', null];
    }

    #[DataProvider('excecoesDoDriver')]
    public function testTraduzThrowableSemEncadearMensagemBruta(Throwable $erro, ?string $codigo): void
    {
        foreach (['buscarTodos', 'executar'] as $operacao) {
            $adodb = new AdodbConsultaFake();
            $adodb->falha = $erro;
            $adodb->falhaExecute = $erro;
            $banco = new BancoOracle($adodb, 'UTF-8', 'UTF-8');

            try {
                $banco->$operacao('SELECT SQL_SECRETO FROM TESTE', ['id' => 'BIND_SECRETO']);
                self::fail('Era esperada falha controlada.');
            } catch (FalhaOracleException $falha) {
                self::assertSame($this->mensagemSegura($operacao), $falha->getMessage());
                self::assertSame($codigo, $falha->getCodigoOracle());
                self::assertNull($falha->getPrevious());
                self::assertSame(3, $adodb->fetchMode);
            }
        }
    }

    public static function excecoesDoDriver(): iterable
    {
        yield [new RuntimeException('DRIVER_SECRETO'), null];
        yield [new RuntimeException('ORA-00942: SQL_SECRETO BIND_SECRETO'), 'ORA-00942'];
        yield [new Error('ORA-03113: DRIVER_SECRETO'), 'ORA-03113'];
    }

    public function testDiagnosticosQueLancamExcecaoNaoSubstituemFalhaOriginal(): void
    {
        foreach (['buscarTodos', 'executar'] as $operacao) {
            $adodb = new AdodbConsultaFake();
            $adodb->falha = new RuntimeException('ORA-01008: DRIVER_SECRETO');
            $adodb->falhaExecute = $adodb->falha;
            $adodb->falhaErrorNo = new RuntimeException('ErrorNo indisponível: SEGREDO');
            $adodb->falhaErrorMsg = new Error('ErrorMsg indisponível: SEGREDO');
            $banco = new BancoOracle($adodb, 'UTF-8', 'UTF-8');

            try {
                $banco->$operacao('SELECT ID FROM TESTE');
                self::fail('Era esperada falha controlada.');
            } catch (FalhaOracleException $falha) {
                self::assertSame('ORA-01008', $falha->getCodigoOracle());
                self::assertSame($this->mensagemSegura($operacao), $falha->getMessage());
                self::assertNull($falha->getPrevious());
            }
        }
    }

    public function testDiagnosticosIndisponiveisComFalseRetornamCodigoAusente(): void
    {
        $adodb = new AdodbConsultaFake();
        $adodb->resultadoExecute = false;
        $adodb->falhaErrorNo = new RuntimeException('SEGREDO');
        $adodb->falhaErrorMsg = new RuntimeException('SEGREDO');
        $banco = new BancoOracle($adodb, 'UTF-8', 'UTF-8');

        try {
            $banco->executar('DELETE FROM TESTE');
            self::fail('Era esperada falha controlada.');
        } catch (FalhaOracleException $falha) {
            self::assertNull($falha->getCodigoOracle());
            self::assertNull($falha->getPrevious());
        }
    }

    public function testErrorMsgAindaPodeFornecerCodigoQuandoErrorNoFalha(): void
    {
        $adodb = new AdodbConsultaFake();
        $adodb->resultado = false;
        $adodb->falhaErrorNo = new Error('SEGREDO');
        $adodb->mensagemErro = 'ORA-00942: SEGREDO';
        $banco = new BancoOracle($adodb, 'UTF-8', 'UTF-8');

        try {
            $banco->buscarTodos('SELECT ID FROM TESTE');
            self::fail('Era esperada falha controlada.');
        } catch (FalhaOracleException $falha) {
            self::assertSame('ORA-00942', $falha->getCodigoOracle());
        }
    }

    #[DataProvider('modosEFalhas')]
    public function testRestauraModoEmTodasAsFalhas(int|false $modo, string $tipo): void
    {
        $adodb = new AdodbConsultaFake();
        $adodb->fetchMode = $modo;

        if ($tipo === 'false') {
            $adodb->resultado = false;
        } elseif ($tipo === 'excecao') {
            $adodb->falha = new RuntimeException('SEGREDO');
        } elseif ($tipo === 'modo') {
            $adodb->falhaSetFetchMode = new RuntimeException('SEGREDO');
        } else {
            $adodb->resultado = [['ID' => 1], 'formato inválido'];
        }

        $banco = new BancoOracle($adodb, 'UTF-8', 'UTF-8');
        try {
            $banco->buscarTodos('SELECT ID FROM TESTE');
            self::fail('Era esperada falha controlada.');
        } catch (FalhaOracleException $falha) {
            self::assertSame($modo, $adodb->fetchMode);
            self::assertSame('Falha ao consultar dados no Oracle.', $falha->getMessage());
        }
    }

    public static function modosEFalhas(): iterable
    {
        foreach (BancoOracleTest::modosDeBusca() as [$modo]) {
            foreach (['false', 'excecao', 'modo', 'formato'] as $tipo) {
                yield [$modo, $tipo];
            }
        }
    }

    public function testFormatoInvalidoNaoUsaCodigoResidualDeOperacaoAnterior(): void
    {
        $adodb = new AdodbConsultaFake();
        $adodb->resultado = 'formato inválido';
        $adodb->numeroErro = 942;
        $banco = new BancoOracle($adodb, 'UTF-8', 'UTF-8');

        try {
            $banco->buscarTodos('SELECT ID FROM TESTE');
            self::fail('Era esperada falha controlada.');
        } catch (FalhaOracleException $falha) {
            self::assertNull($falha->getCodigoOracle());
            self::assertSame([], $adodb->diagnosticos);
        }
    }

    public function testRejeitaConexaoSemDiagnosticosAdodb(): void
    {
        $adodb = new class {
            public int $fetchMode = 3;

            public function GetAll(): array
            {
                return [];
            }

            public function Execute(): bool
            {
                return true;
            }

            public function SetFetchMode(): int
            {
                return 3;
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ErrorNo');
        new BancoOracle($adodb, 'UTF-8', 'UTF-8');
    }

    private function mensagemSegura(string $operacao): string
    {
        return $operacao === 'buscarTodos'
            ? 'Falha ao consultar dados no Oracle.'
            : 'Falha ao executar comando Oracle.';
    }
}
