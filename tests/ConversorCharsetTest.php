<?php

declare(strict_types=1);

namespace Semae\ScriptcaseOracle\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\ScriptcaseOracle\ConversorCharset;
use UnexpectedValueException;

final class ConversorCharsetTest extends TestCase
{
    #[DataProvider('paresDeCharsets')]
    public function testConverteNosDoisSentidos(
        string $charsetConexao,
        string $charsetAplicacao,
        string $textoConexao,
        string $textoAplicacao
    ): void {
        $conversor = new ConversorCharset($charsetConexao, $charsetAplicacao);

        self::assertSame($textoConexao, $conversor->paraConexao($textoAplicacao));
        self::assertSame($textoAplicacao, $conversor->paraAplicacao($textoConexao));
        self::assertSame('', $conversor->paraConexao(''));
        self::assertSame('', $conversor->paraAplicacao(''));
    }

    public static function paresDeCharsets(): iterable
    {
        $textos = [
            'UTF-8' => "AÇÃO, café, D'Ávila ?\0\r\n",
            'ISO-8859-1' => "A\xC7\xC3O, caf\xE9, D'\xC1vila ?\0\r\n",
            'Windows-1252' => "A\xC7\xC3O, caf\xE9, D'\xC1vila ?\0\r\n",
        ];

        foreach ($textos as $conexao => $textoConexao) {
            foreach ($textos as $aplicacao => $textoAplicacao) {
                yield "$aplicacao para $conexao" => [$conexao, $aplicacao, $textoConexao, $textoAplicacao];
            }
        }
    }

    public function testConverteCaracteresExclusivosDoWindows1252(): void
    {
        $conversor = new ConversorCharset('Windows-1252', 'UTF-8');

        self::assertSame("\x80 \x96 \x93\x94", $conversor->paraConexao('€ – “”'));
        self::assertSame('€ – “”', $conversor->paraAplicacao("\x80 \x96 \x93\x94"));
    }

    public function testIso88591NaoEInterpretadoComoWindows1252(): void
    {
        $conversor = new ConversorCharset('ISO-8859-1', 'UTF-8');

        self::assertSame("\xC2\x80", $conversor->paraAplicacao("\x80"));
        self::assertSame("\x80", $conversor->paraConexao("\xC2\x80"));
    }

    #[DataProvider('textosInvalidos')]
    public function testRejeitaTextoInvalidoOuNaoRepresentavel(
        string $conexao,
        string $aplicacao,
        string $direcao,
        string $texto
    ): void {
        $conversor = new ConversorCharset($conexao, $aplicacao);

        try {
            $conversor->$direcao($texto);
            self::fail('Era esperado bloqueio do texto.');
        } catch (UnexpectedValueException $falha) {
            self::assertNull($falha->getPrevious());
            self::assertStringNotContainsString('SEGREDO', $falha->getMessage());
        }
    }

    public static function textosInvalidos(): iterable
    {
        foreach (['UTF-8', 'Windows-1252', 'ISO-8859-1'] as $charset) {
            foreach (["\xC3\x28", "\xC0\xAF", "\xED\xA0\x80", "\xF0\x9F", "\xFF"] as $invalido) {
                yield [$charset, 'UTF-8', 'paraConexao', 'SEGREDO' . $invalido];
                yield ['UTF-8', $charset, 'paraAplicacao', 'SEGREDO' . $invalido];
            }
        }

        foreach (['Windows-1252', 'ISO-8859-1'] as $charset) {
            yield [$charset, 'UTF-8', 'paraConexao', 'SEGREDO 😀'];
            yield ['UTF-8', $charset, 'paraAplicacao', 'SEGREDO 😀'];
        }

        yield ['ISO-8859-1', 'UTF-8', 'paraConexao', 'SEGREDO €'];
        yield ['UTF-8', 'ISO-8859-1', 'paraAplicacao', 'SEGREDO –'];
        yield ['ISO-8859-1', 'Windows-1252', 'paraConexao', "SEGREDO \x80"];
        yield ['Windows-1252', 'ISO-8859-1', 'paraAplicacao', "SEGREDO \x80"];
        yield ['Windows-1252', 'ISO-8859-1', 'paraConexao', "SEGREDO \x80"];
        yield ['ISO-8859-1', 'Windows-1252', 'paraAplicacao', "SEGREDO \x80"];
    }

    #[DataProvider('charsetsInvalidos')]
    public function testRejeitaConfiguracaoInvalida(string $conexao, string $aplicacao): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ConversorCharset($conexao, $aplicacao);
    }

    public static function charsetsInvalidos(): iterable
    {
        yield from BancoOracleTest::charsetsInvalidos();
    }

    public function testNaoAlteraConfiguracaoGlobalDeConversao(): void
    {
        $substituto = mb_substitute_character();
        $encoding = mb_internal_encoding();
        try {
            mb_substitute_character('none');
            mb_internal_encoding('ISO-8859-1');
            $conversor = new ConversorCharset('Windows-1252', 'UTF-8');
            self::assertSame("A\xC7\xC3O", $conversor->paraConexao('AÇÃO'));

            try {
                $conversor->paraConexao('SEGREDO 😀');
                self::fail('A substituição global não pode permitir perda silenciosa.');
            } catch (UnexpectedValueException) {
                self::assertSame('none', mb_substitute_character());
                self::assertSame('ISO-8859-1', mb_internal_encoding());
            }
        } finally {
            mb_substitute_character($substituto);
            mb_internal_encoding($encoding);
        }
    }
}
