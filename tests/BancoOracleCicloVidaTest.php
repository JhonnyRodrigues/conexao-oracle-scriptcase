<?php

declare(strict_types=1);

namespace Semae\ScriptcaseOracle\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Semae\ScriptcaseOracle\BancoOracle;
use Semae\ScriptcaseOracle\FalhaOracleException;

final class BancoOracleCicloVidaTest extends TestCase
{
    #[DataProvider('operacoesETransacoes')]
    public function testPreservaConexaoETransacaoDoHospedeiro(
        string $operacao,
        string $resultado,
        bool $transacaoAtiva
    ): void {
        $this->expectOutputString('');
        $adodb = new AdodbCicloVidaFake();
        $adodb->fetchMode = false;
        $adodb->transacaoAtiva = $transacaoAtiva;

        if ($resultado === 'false') {
            $adodb->resultado = false;
            $adodb->resultadoExecute = false;
        } elseif ($resultado === 'excecao') {
            $adodb->falha = new RuntimeException('ORA-00942: SEGREDO');
            $adodb->falhaExecute = $adodb->falha;
        }

        $globalExistia = array_key_exists('ADODB_FETCH_MODE', $GLOBALS);
        $modoGlobal = $GLOBALS['ADODB_FETCH_MODE'] ?? null;
        $GLOBALS['ADODB_FETCH_MODE'] = 1;

        try {
            $banco = new BancoOracle($adodb, 'UTF-8', 'UTF-8');
            try {
                $banco->$operacao('SELECT ID FROM TESTE');
                self::assertSame('sucesso', $resultado);
            } catch (FalhaOracleException) {
                self::assertNotSame('sucesso', $resultado);
            }

            // Inclui o descarte da instância: o dono da conexão continua sendo o hospedeiro.
            unset($banco);

            self::assertSame([], $adodb->chamadasNaoPermitidas);
            self::assertSame([], $adodb->acessosNaoPermitidos);
            self::assertSame($transacaoAtiva, $adodb->transacaoAtiva);
            self::assertFalse($adodb->fetchMode);
            self::assertSame(1, $GLOBALS['ADODB_FETCH_MODE']);

            if ($resultado === 'sucesso') {
                self::assertSame([], $adodb->diagnosticos);
            }
        } finally {
            if ($globalExistia) {
                $GLOBALS['ADODB_FETCH_MODE'] = $modoGlobal;
            } else {
                unset($GLOBALS['ADODB_FETCH_MODE']);
            }
        }
    }

    public static function operacoesETransacoes(): iterable
    {
        foreach (['buscarTodos', 'executar'] as $operacao) {
            foreach (['sucesso', 'false', 'excecao'] as $resultado) {
                foreach ([false, true] as $transacaoAtiva) {
                    yield [$operacao, $resultado, $transacaoAtiva];
                }
            }
        }
    }
}
