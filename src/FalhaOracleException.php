<?php

declare(strict_types=1);

namespace Semae\ScriptcaseOracle;

use RuntimeException;

final class FalhaOracleException extends RuntimeException
{
    public function __construct(
        string $mensagemSegura,
        private readonly ?string $codigoOracle = null
    ) {
        parent::__construct($mensagemSegura);
    }

    public function getCodigoOracle(): ?string
    {
        return $this->codigoOracle;
    }
}
