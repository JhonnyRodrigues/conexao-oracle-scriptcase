<?php

declare(strict_types=1);

function sc_include_library(
    string $escopo,
    string $biblioteca,
    string $arquivo,
    bool $umaVez,
    bool $obrigatorio
): void {
    if (
        [$escopo, $biblioteca, $arquivo, $umaVez, $obrigatorio]
        !== ['sys', 'semae', 'conexaoOracleScriptcase/autoload.php', true, true]
    ) {
        throw new RuntimeException('Parâmetros de inclusão incompatíveis com o contrato Scriptcase.');
    }

    $GLOBALS['scriptcaseOracleInclusoes'] = ($GLOBALS['scriptcaseOracleInclusoes'] ?? 0) + 1;
    require_once dirname(__DIR__, 2) . '/autoload.php';
}
