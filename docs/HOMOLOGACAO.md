# Homologação Scriptcase/Oracle — 0.1.0

**Situação: versão 0.1.0 aprovada pelo mantenedor; projeto concluído em 05/11/2026.**
O mantenedor considerou suficientes os testes realizados e solicitou finalizar
sem validações adicionais. Os cenários sem resultado registrado neste documento
ficam dispensados como condição de encerramento; não são considerados executados.
O roteiro abaixo permanece como referência para futuras instalações.

Em 05/11/2026, o mantenedor informou que executou
o diagnóstico na homologação com sucesso e, em seguida, publicou a biblioteca
também em produção. Não foi informado resultado de execução em produção nem
repetição de todos os cenários na homologação publicada. A reversão não foi testada.

Em 04/11/2026, o mantenedor confirmou
PHP 8.1.28, `mbstring` e OCI8 na aplicação de teste. Após os ajustes de carregamento,
informou `Metodo criarConexaoOracle: OK`, `Classe BancoOracle: OK` e
`Construcao sem SQL: OK`. O mecanismo exato que atualizou a distribuição externa
ainda não foi detalhado. O diagnóstico posterior na homologação publicada passou.
O mantenedor também confirmou a consulta com bind em DUAL:
`Consulta com bind: OK`, com uma linha `['VALOR' => 'TESTE_BIND_123']`.
O mantenedor confirmou também a configuração ISO-8859-1 no Scriptcase e no
ADOdb (`WE8ISO8859P1`), além dos testes de bind com acentos/apóstrofo e leitura
do texto de referência Oracle. O teste de INSERT/UPDATE/DELETE com rollback
também passou no desenvolvimento. O commit da Blank foi confirmado em outra
sessão Oracle. O mantenedor dispensou o teste de Formulário. Em 05/11/2026,
confirmou que a Grid carregou e tudo funcionou. Depois confirmou o diagnóstico
na homologação publicada e a publicação da biblioteca em produção.
Os testes automatizados continuam usando substitutos controlados.

Evidências de charset: texto PHP `61e7e36f`, ADOdb `WE8ISO8859P1`, `NLS_LANG`
não definido, retorno bruto `61e7e36f7cbf7cbf`, sessão Scriptcase `ISO-8859-1`
e charset HTML `iso-8859-1`. O euro e o traço da referência foram substituídos
por `bf` no caminho Oracle/cliente, antes da biblioteca. Para esta aplicação,
usar `criarConexaoOracle($adodb, 'ISO-8859-1', 'ISO-8859-1')`.

O teste de leitura com esse par retornou `Bind com acentos e apostrofo: OK`,
`Leitura de acentos do Oracle: OK` e `TEXTO = D'Ávila - ação, café e órgão`.
Esse teste de leitura isolado valida o bind desses caracteres. A persistência
foi verificada no teste de commit abaixo; caracteres ausentes do ISO-8859-1
continuam fora do repertório dessa conexão.

O teste [escrita-rollback](../scriptcase/exemplos/escrita-rollback.php.txt) retornou
INSERT/UPDATE com acentos e apóstrofo OK, DELETE OK e nenhuma linha depois do
rollback solicitado pelo evento. IDs: **828940774 e 828940775**. A leitura posterior
na aplicação passou; a conferência desses IDs em sessão independente não foi informada.
O teste [escrita-commit](../scriptcase/exemplos/escrita-commit.php.txt) também
passou. O mantenedor informou ID **1056298063**, ORIGEM `BLANK_COMMIT` e descrição
`D'Ávila - ação, café e órgão`, encontrados pela
[consulta em outra sessão](../scriptcase/sql/verificar-commit.sql). A persistência
do commit solicitado pelo evento está confirmada nesse cenário.

O mantenedor solicitou desconsiderar o teste de Formulário e avançar. Esse
cenário está dispensado nesta homologação, sem resultado de aprovação. O roteiro
foi preservado como referência opcional. A [leitura na Grid](GRID-HOMOLOGACAO.md),
reutilizando o registro já confirmado, foi executada pelo mantenedor, que informou:
“carregou e tudo funcionou”. O resultado confirma o cenário executado no
desenvolvimento; não foram detalhados a exportação ou o volume para paginação.
O diagnóstico de carregamento da aplicação publicada
`teste_conexao_oracle_scriptcase_B` passou em homologação, segundo o mantenedor.
A biblioteca também foi publicada em produção. Não há resultado de execução
em produção registrado nesta conversa.

## Identificar o ambiente

| Item | Desenvolvimento Scriptcase | Aplicação publicada |
| --- | --- | --- |
| Identificador da entrega e hash do manifesto | A coletar | A coletar |
| Data, responsável e aplicações piloto | A coletar | A coletar |
| Versão do PHP do servidor e `mbstring` | PHP 8.1.28; mbstring OK, informado pelo mantenedor | A coletar |
| Versão do Scriptcase | A coletar | A coletar |
| Versão do ADOdb e driver Oracle utilizado | A coletar | A coletar |
| Versão do OCI8 e cliente Oracle | Extensão OCI8 OK; versões a coletar | A coletar |
| Versão do servidor Oracle | A coletar | A coletar |
| Charset efetivo trocado com o ADOdb | ISO-8859-1; ADOdb WE8ISO8859P1 e bytes confirmados | A coletar |
| Charset da aplicação | ISO-8859-1; sessão Scriptcase, HTML e bytes confirmados | A coletar |
| Tabelas e conexão dedicadas à homologação | Banco de homologação confirmado; SC_ORACLE_LIB_TESTE usada com sucesso nas escritas/rollback | A coletar |

Use a versão do PHP que executa a aplicação, não apenas a versão CLI de outra
máquina. `PHP_VERSION`, `extension_loaded('mbstring')` e `phpversion('oci8')`
ajudam a coletar parte do registro. Obtenha as demais versões pela instalação e
administração dos ambientes; não registre credenciais ou mensagens brutas.

## Preparar tabelas dedicadas

Peça ao responsável pelo banco para criar as tabelas abaixo somente no ambiente
de homologação, em uma conexão que permita testes transacionais. Esse DDL é
manual e não deve ser executado dentro dos testes de commit/rollback, pois DDL
Oracle pode confirmar transações. As tabelas não pertencem à biblioteca.

O mantenedor confirmou que a `TESTE_CONEXAO` aponta para homologação. A tabela
`SC_ORACLE_LIB_TESTE` inicialmente não existia e depois foi usada com sucesso no
teste de escrita/rollback. Para reproduzir a preparação em outro ambiente, execute
[criar-tabela-teste.sql](../scriptcase/sql/criar-tabela-teste.sql), no mesmo schema
usado pela aplicação. A tabela de Formulário abaixo só será necessária se o
roteiro opcional dispensado pelo mantenedor for retomado.
O [commit implícito de DDL](https://docs.oracle.com/en/database/oracle/oracle-database/19/tdddg/data-definition-language-ddl-statements.html)
é a razão de criar as tabelas em uma etapa separada dos testes de rollback.

```sql
CREATE TABLE SC_ORACLE_LIB_TESTE (
    ID NUMBER(10) PRIMARY KEY,
    ORIGEM VARCHAR2(20) NOT NULL,
    DESCRICAO VARCHAR2(200),
    DATA_REFERENCIA DATE,
    VALOR NUMBER(12, 2)
);

CREATE TABLE SC_ORACLE_FORM_TESTE (
    ID NUMBER(10) PRIMARY KEY,
    DESCRICAO VARCHAR2(200)
);
```

Não use tabelas operacionais. Reserve IDs distintos por cenário e registre-os
para conferir o estado em uma segunda sessão Oracle independente. A limpeza
final deve remover somente os IDs usados pelo teste, com decisão transacional
explícita do responsável; não há limpeza automática da biblioteca.

## Executar e registrar

Use os [exemplos](../scriptcase/exemplos/README.md) após seguir a
[instalação](INSTALACAO.md). Para cada linha, registre data, resultado, responsável,
ID de teste e evidência sem dados sensíveis. As evidências de desenvolvimento
já recebidas estão no início deste documento; os demais cenários e a repetição
na publicação permanecem sem resultado específico, exceto pelo diagnóstico de
carregamento confirmado pelo mantenedor.

O carregamento e a construção sem SQL passaram no desenvolvimento, conforme
retorno do mantenedor em 04/11/2026. A primeira leitura proposta foi a
[consulta com bind em DUAL](../scriptcase/exemplos/consulta-bind.php.txt), sem escrita
e usando somente ASCII. O diagnóstico de carregamento passou na homologação
publicada; a repetição da consulta nesse destino não foi informada separadamente.

Essa consulta inicial passou no desenvolvimento: uma linha associativa com
`VALOR = TESTE_BIND_123`, conforme retorno do mantenedor. O
[diagnóstico de charset](../scriptcase/exemplos/diagnostico-charset.php.txt)
e os testes posteriores de acentos e escrita também passaram, conforme os
registros acima.

| Cenário | Evidência esperada |
| --- | --- |
| Habilitação da interna | Marcar a flag da interna sem mensagem de código fora das funções; `method_exists($this, 'criarConexaoOracle')` retorna true e a fábrica pode ser chamada repetidamente. |
| Distribuição da externa | Após gerar e publicar, `src/` e `vendor/` completos estão no destino; o autoload é carregado pela fábrica sem cópia manual por aplicação. |
| Blank: leitura | Lista vazia e múltiplas linhas; aliases preservados; apóstrofos e texto semelhante a SQL retornam como valores literais. |
| Blank: rollback | Execute com `$confirmar = false`; a sessão do teste lê sua escrita, outra sessão não vê a linha e a linha não persiste após rollback. |
| Blank: commit | Repita com `$confirmar = true` e outro ID; a outra sessão vê a linha somente após o commit do evento. |
| UPDATE/DELETE | Com ID de teste, confira alteração, exclusão e efeitos de commit/rollback em segunda sessão. |
| Datas e decimais | Confira a data e `1234.56`, enviados como strings com conversões SQL e NLS explícitos no exemplo Blank. |
| Formulário: sucesso | Dispensado pelo mantenedor; não executado. Roteiro opcional preservado. |
| Formulário: erro da biblioteca | Dispensado pelo mantenedor; não executado. |
| Formulário: rollback posterior | Dispensado pelo mantenedor; não executado. |
| Sem commit antecipado | Observe a segunda sessão durante uma pausa de depuração depois de `executar()` e antes do fim do evento; ela não deve ver a nova escrita. |
| Grid | Funcionamento confirmado pelo mantenedor em 05/11/2026 após a consulta em `onScriptInit`. Exportação e paginação não foram detalhadas separadamente. |
| Falha controlada | Provoque um erro em SQL de teste; confirme código Oracle quando disponível e ausência de SQL, binds ou mensagem bruta na interface. |
| Charsets | Confira acentos nos dois sentidos; use `€` e `–` com Windows-1252/UTF-8 e verifique rejeição em ISO-8859-1; emoji deve ser rejeitado em destinos de byte único. |
| Texto inválido | Envie UTF-8 malformado em um bind e verifique rejeição antes de chegar ao driver. |
| Publicação | Repita os cenários na aplicação publicada com a pasta externa completa e versões registradas. |
| Reversão | Restaure uma entrega preservada e repita os testes piloto; registre a entrega restaurada. |

Para o Formulário, o controle transacional dos eventos na mesma conexão é do
Scriptcase, conforme a [referência oficial de macros](https://www.scriptcase.net/docs/en_us/v9/manual/14-macros/02-macros/).
Não acrescente commit ou início de transação ao exemplo de sucesso. Ao provocar
uma falha, use a mensagem controlada e a interrupção indicadas no exemplo, sem
redirecionamento que possa antecipar a confirmação. A validação real deve provar
esse comportamento na versão instalada.

## Aceitação

| Decisão | Registro |
| --- | --- |
| Testes locais | Disponíveis em `.agents/estado_atual/estado_atual.md` no repositório; anexar saída da entrega avaliada. |
| Homologação no desenvolvimento | Blank e Grid com resultados positivos informados pelo mantenedor; Formulário dispensado. Demais cenários sem evidência específica continuam sem resultado registrado. |
| Homologação na publicação | Diagnóstico de carregamento aprovado pelo mantenedor em 05/11/2026; demais cenários não detalhados separadamente. |
| Produção | Publicação da biblioteca informada pelo mantenedor em 05/11/2026; execução nesse ambiente não informada. |
| Reversão | Não executada; validação adicional dispensada pelo mantenedor para encerramento. |
| Aprovador, data e entrega aprovada | Mantenedor do projeto, em 05/11/2026; versão 0.1.0. Aprovação expressa na conversa ao solicitar finalizar o projeto por considerar os testes suficientes. |

A pasta de distribuição não inclui o estado interno de agentes; guarde junto das
evidências as saídas dos testes do repositório. Comece por aplicações piloto e
amplie a adoção somente depois da aprovação registrada. Consultas grandes,
leitura progressiva, CLOB/BLOB, parâmetros de saída e identificação dinâmica de
tabelas/colunas por binds continuam fora do contrato da versão 0.1.0.
