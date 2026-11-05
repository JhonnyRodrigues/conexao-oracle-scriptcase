<?php

declare(strict_types=1);

namespace Semae\ScriptcaseOracle;

use InvalidArgumentException;
use UnexpectedValueException;

final class ConversorCharset
{
    private const CHARSETS = ['ISO-8859-1', 'Windows-1252', 'UTF-8'];

    public function __construct(
        private readonly string $charsetConexao,
        private readonly string $charsetAplicacao
    ) {
        foreach ([$charsetConexao, $charsetAplicacao] as $charset) {
            if (!in_array($charset, self::CHARSETS, true)) {
                throw new InvalidArgumentException('Charset não suportado.');
            }
        }

        if (!function_exists('mb_check_encoding') || !function_exists('mb_convert_encoding')) {
            throw new InvalidArgumentException('A extensão mbstring é obrigatória para converter textos.');
        }
    }

    public function paraConexao(string $texto): string
    {
        return $this->converter($texto, $this->charsetAplicacao, $this->charsetConexao);
    }

    public function paraAplicacao(string $texto): string
    {
        return $this->converter($texto, $this->charsetConexao, $this->charsetAplicacao);
    }

    private function converter(string $texto, string $origem, string $destino): string
    {
        if (!mb_check_encoding($texto, $origem)) {
            throw new UnexpectedValueException('Texto não contém uma sequência válida em ' . $origem . '.');
        }

        if ($origem === $destino) {
            return $texto;
        }

        $convertido = mb_convert_encoding($texto, $destino, $origem);
        $restaurado = mb_convert_encoding($convertido, $origem, $destino);

        // A ida e volta deve recuperar os mesmos bytes; substituições implicam perda.
        if (!mb_check_encoding($convertido, $destino) || $restaurado !== $texto) {
            throw new UnexpectedValueException('Texto não pode ser representado em ' . $destino . ' sem perda.');
        }

        return $convertido;
    }
}
