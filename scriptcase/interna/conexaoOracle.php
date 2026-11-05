<?php

function criarConexaoOracle(
    object $adodb,
    string $charsetConexao,
    string $charsetAplicacao
): \Semae\ScriptcaseOracle\BancoOracle {
    sc_include_library(
        'sys',
        'semae',
        'conexaoOracleScriptcase/autoload.php',
        true,
        true
    );

    return new \Semae\ScriptcaseOracle\BancoOracle($adodb, $charsetConexao, $charsetAplicacao);
}
