<?php

declare(strict_types=1);

namespace Semae\ScriptcaseOracle;

use InvalidArgumentException;
use Throwable;

final class BancoOracle
{
    // Valor de ADODB_FETCH_ASSOC; não define nem modifica constantes globais.
    private const MODO_ASSOCIATIVO = 2;

    private readonly ConversorCharset $conversor;

    public function __construct(
        private readonly object $adodb,
        string $charsetConexao,
        string $charsetAplicacao
    ) {
        $this->conversor = new ConversorCharset($charsetConexao, $charsetAplicacao);

        foreach (['GetAll', 'Execute', 'SetFetchMode', 'ErrorNo', 'ErrorMsg'] as $metodo) {
            if (!method_exists($adodb, $metodo) || !is_callable([$adodb, $metodo])) {
                throw new InvalidArgumentException('A conexão ADOdb não oferece o método ' . $metodo . '.');
            }
        }

        $propriedades = get_object_vars($adodb);
        if (
            !array_key_exists('fetchMode', $propriedades)
            || (!is_int($propriedades['fetchMode']) && $propriedades['fetchMode'] !== false)
        ) {
            throw new InvalidArgumentException('A conexão ADOdb deve disponibilizar fetchMode público.');
        }
    }

    /**
     * @param array<string, string|int|float|null> $parametros
     * @return list<array<string, mixed>>
     */
    public function buscarTodos(string $sql, array $parametros = []): array
    {
        [$sql, $parametros] = $this->prepararEntrada($sql, $parametros);

        $modoOriginal = $this->adodb->fetchMode;

        try {
            $this->adodb->SetFetchMode(self::MODO_ASSOCIATIVO);
            $linhas = $this->adodb->GetAll($sql, $parametros);
        } catch (Throwable $erro) {
            throw $this->criarFalha('Falha ao consultar dados no Oracle.', $erro);
        } finally {
            // SetFetchMode() pode retornar o modo global quando o original é false.
            // Guardar e restaurar a propriedade preserva o estado exato da conexão.
            $this->adodb->fetchMode = $modoOriginal;
        }

        if ($linhas === false) {
            throw $this->criarFalha('Falha ao consultar dados no Oracle.');
        }

        if (!is_array($linhas)) {
            throw new FalhaOracleException('Falha ao consultar dados no Oracle.');
        }

        foreach ($linhas as $indice => $linha) {
            if (!is_array($linha)) {
                throw new FalhaOracleException('Falha ao consultar dados no Oracle.');
            }

            foreach ($linha as $coluna => $valor) {
                if (!is_string($coluna)) {
                    throw new FalhaOracleException('Falha ao consultar dados no Oracle.');
                }

                if (is_string($valor)) {
                    $linhas[$indice][$coluna] = $this->conversor->paraAplicacao($valor);
                }
            }
        }

        return array_values($linhas);
    }

    /** @param array<string, string|int|float|null> $parametros */
    public function executar(string $sql, array $parametros = []): void
    {
        [$sql, $parametros] = $this->prepararEntrada($sql, $parametros);

        try {
            $resultado = $this->adodb->Execute($sql, $parametros);
        } catch (Throwable $erro) {
            throw $this->criarFalha('Falha ao executar comando Oracle.', $erro);
        }

        if ($resultado === false) {
            throw $this->criarFalha('Falha ao executar comando Oracle.');
        }
    }

    /**
     * @param array<string, string|int|float|null> $parametros
     * @return array{string, array<string, string|int|float|null>}
     */
    private function prepararEntrada(string $sql, array $parametros): array
    {
        $this->validarParametros($parametros);

        $sql = $this->conversor->paraConexao($sql);
        foreach ($parametros as $nome => $valor) {
            if (is_string($valor)) {
                $parametros[$nome] = $this->conversor->paraConexao($valor);
            }
        }

        return [$sql, $parametros];
    }

    private function validarParametros(array $parametros): void
    {
        foreach ($parametros as $nome => $valor) {
            if (!is_string($nome) || preg_match('/\A[A-Za-z][A-Za-z0-9_]*\z/', $nome) !== 1) {
                throw new InvalidArgumentException('Nome de parâmetro inválido. Use nomes sem dois-pontos.');
            }

            if (!is_string($valor) && !is_int($valor) && !is_float($valor) && $valor !== null) {
                throw new InvalidArgumentException('Valor de parâmetro deve ser string, int, float ou null.');
            }
        }
    }

    private function criarFalha(string $mensagemSegura, ?Throwable $erro = null): FalhaOracleException
    {
        return new FalhaOracleException($mensagemSegura, $this->codigoOracle($erro));
    }

    private function codigoOracle(?Throwable $erro): ?string
    {
        foreach (['ErrorNo', 'ErrorMsg'] as $metodo) {
            try {
                $valor = $this->adodb->$metodo();
            } catch (Throwable) {
                // Uma falha ao obter diagnóstico não deve substituir a falha da operação.
                continue;
            }

            if (
                $metodo === 'ErrorNo'
                && (is_int($valor) || is_string($valor))
                && preg_match('/\A-?0*([1-9][0-9]{0,4})\z/', (string) $valor, $partes) === 1
            ) {
                return 'ORA-' . str_pad($partes[1], 5, '0', STR_PAD_LEFT);
            }

            $codigo = $this->extrairCodigoOracle($valor);
            if ($codigo !== null) {
                return $codigo;
            }
        }

        return $this->extrairCodigoOracle($erro?->getMessage());
    }

    private function extrairCodigoOracle(mixed $valor): ?string
    {
        if (
            is_string($valor)
            && preg_match('/\bORA-([0-9]{5})(?![0-9])/', $valor, $partes) === 1
            && $partes[1] !== '00000'
        ) {
            return 'ORA-' . $partes[1];
        }

        return null;
    }
}
