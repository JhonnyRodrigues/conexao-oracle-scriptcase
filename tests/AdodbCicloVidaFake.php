<?php

declare(strict_types=1);

namespace Semae\ScriptcaseOracle\Tests;

use LogicException;

final class AdodbCicloVidaFake extends AdodbConsultaFake
{
    public bool $transacaoAtiva = false;
    public array $chamadasNaoPermitidas = [];
    public array $acessosNaoPermitidos = [];

    public function __call(string $nome, array $argumentos): mixed
    {
        // Captura BeginTrans/CommitTrans/RollbackTrans/Close e outras APIs fora do contrato.
        $this->chamadasNaoPermitidas[] = $nome;

        throw new LogicException('A biblioteca tentou gerenciar a conexão emprestada.');
    }

    public function __get(string $nome): mixed
    {
        $this->acessosNaoPermitidos[] = ['leitura', $nome];

        return null;
    }

    public function __set(string $nome, mixed $valor): void
    {
        $this->acessosNaoPermitidos[] = ['escrita', $nome];
    }

    public function __isset(string $nome): bool
    {
        $this->acessosNaoPermitidos[] = ['isset', $nome];

        return false;
    }
}
