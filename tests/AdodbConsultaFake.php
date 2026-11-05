<?php

declare(strict_types=1);

namespace Semae\ScriptcaseOracle\Tests;

use Throwable;

class AdodbConsultaFake
{
    public int|false $fetchMode = 3;
    public mixed $resultado = [];
    public ?Throwable $falha = null;
    public array $consultas = [];
    public array $modosNasConsultas = [];
    public array $alteracoesDeModo = [];
    public mixed $resultadoExecute = true;
    public ?Throwable $falhaExecute = null;
    public array $execucoes = [];
    public mixed $numeroErro = 0;
    public mixed $mensagemErro = '';
    public ?Throwable $falhaErrorNo = null;
    public ?Throwable $falhaErrorMsg = null;
    public ?Throwable $falhaSetFetchMode = null;
    public array $diagnosticos = [];

    public function SetFetchMode(int|false $modo): int|false
    {
        $this->alteracoesDeModo[] = $modo;
        $anterior = $this->fetchMode;
        $this->fetchMode = $modo;

        if ($this->falhaSetFetchMode !== null) {
            throw $this->falhaSetFetchMode;
        }

        // O ADOdb retorna o modo global quando o estado original é false.
        return $anterior === false ? 3 : $anterior;
    }

    public function GetAll(string $sql, array $parametros = []): mixed
    {
        $this->consultas[] = [$sql, $parametros];
        $this->modosNasConsultas[] = $this->fetchMode;

        if ($this->falha !== null) {
            throw $this->falha;
        }

        return $this->resultado;
    }

    public function Execute(string $sql, array $parametros = []): mixed
    {
        $this->execucoes[] = [$sql, $parametros];

        if ($this->falhaExecute !== null) {
            throw $this->falhaExecute;
        }

        return $this->resultadoExecute;
    }

    public function ErrorNo(): mixed
    {
        $this->diagnosticos[] = 'ErrorNo';

        if ($this->falhaErrorNo !== null) {
            throw $this->falhaErrorNo;
        }

        return $this->numeroErro;
    }

    public function ErrorMsg(): mixed
    {
        $this->diagnosticos[] = 'ErrorMsg';

        if ($this->falhaErrorMsg !== null) {
            throw $this->falhaErrorMsg;
        }

        return $this->mensagemErro;
    }
}
