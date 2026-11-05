<?php

declare(strict_types=1);

namespace Semae\ScriptcaseOracle\Tests;

use PHPUnit\Framework\TestCase;

final class PacoteTest extends TestCase
{
    public function testPreparaEntregaIsoladaPreservaAnteriorEDetectaAlteracao(): void
    {
        $raiz = dirname(__DIR__);
        $destino = $raiz . '/.phpunit.cache/pacotes/' . bin2hex(random_bytes(6));
        [$codigo, $saida] = $this->rodar($raiz . '/tools/package/build.php', $destino);
        self::assertSame(0, $codigo, $saida);
        self::assertFileExists($destino . '/externa/semae/conexaoOracleScriptcase/vendor/autoload.php');
        self::assertFileExists($destino . '/scriptcase/interna/conexaoOracle.php');
        self::assertDirectoryDoesNotExist($destino . '/externa/semae/conexaoOracleScriptcase/vendor/phpunit');

        [$codigo, $saida] = $this->rodar($raiz . '/tools/package/verify.php', $destino);
        self::assertSame(0, $codigo, $saida);

        $manifesto = file_get_contents($destino . '/manifest.json');
        [$codigo] = $this->rodar($raiz . '/tools/package/build.php', $destino);
        self::assertNotSame(0, $codigo);
        self::assertSame($manifesto, file_get_contents($destino . '/manifest.json'));

        file_put_contents($destino . '/externa/semae/conexaoOracleScriptcase/VERSION', "alterado\n");
        [$codigo, $saida] = $this->rodar($raiz . '/tools/package/verify.php', $destino);
        self::assertNotSame(0, $codigo);
        self::assertStringContainsString('SHA-256', $saida);
    }

    private function rodar(string $script, string $destino): array
    {
        $processo = proc_open(
            [PHP_BINARY, $script, $destino],
            [1 => ['pipe', 'w'], 2 => ['redirect', 1]],
            $pipes
        );
        self::assertIsResource($processo);
        $saida = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        return [proc_close($processo), $saida];
    }
}
