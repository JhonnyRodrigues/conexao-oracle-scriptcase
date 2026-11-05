# Teste no Formulário — mesma transação

**Situação: dispensado pelo mantenedor nesta homologação.** O teste não foi
executado nem registrado como aprovado. O roteiro é mantido como referência
opcional; ele não condiciona o avanço para a Grid ou as demais etapas autorizadas.

Pré-requisitos: banco de homologação já confirmado e `SC_ORACLE_LIB_TESTE`
disponível. Os testes de leitura, escritas e rollback na Blank passaram; o
commit da Blank foi confirmado em outra sessão no ID `1056298063`.

## Preparar o Formulário

1. Se a tabela ainda não existir, execute
   [criar-tabela-formulario.sql](../scriptcase/sql/criar-tabela-formulario.sql)
   no mesmo schema, fora de qualquer evento de teste.
2. Crie um **Formulário de registro único** chamado `TESTE_CONEXAO_FORM`, sobre
   `SC_ORACLE_FORM_TESTE`, usando a mesma conexão Oracle da Blank.
3. Configure `ID` como chave digitada na inclusão, sem sequência nem auto
   incremento, e disponibilize os campos `ID` e `DESCRICAO`.
4. Confira `ISO-8859-1` na aplicação e habilite a interna `conexaoOracle.php`
   pela flag das bibliotecas internas do projeto.
5. No **onAfterInsert**, cole
   [formulario-onAfterInsert.php.txt](../scriptcase/exemplos/formulario-onAfterInsert.php.txt).
   Deixe `$forcarRollback = false`, gere e execute.

Nesse evento, o Scriptcase já controla a transação na mesma conexão, conforme
a [referência oficial de macros](https://www.scriptcase.com.br/docs/pt_br/v9/manual/14-macros/02-macros/).
O exemplo solicita rollback apenas em falha. O sucesso segue o fluxo normal do
Formulário; não acrescente início de transação, commit ou redirecionamento ao evento.

## Cenário 1 — inclusão normal

Use IDs novos; os exemplos abaixo assumem que `310001`, `310002` e `310003`
ainda não foram usados nessas tabelas. Confira isso com
[verificar-formulario.sql](../scriptcase/sql/verificar-formulario.sql) antes de começar.
Se já existirem, escolha outros IDs e ajuste as consultas e o preparo.

Inclua pelo Formulário:

- ID: `310001`.
- DESCRICAO: `D'Ávila - ação, café e órgão`.

Em outra sessão Oracle, execute a consulta de verificação. Esperado: duas linhas
do ID `310001`, uma com FONTE `FORMULARIO` e outra com FONTE `BIBLIOTECA`, com
a descrição preservada. Esse é o primeiro resultado a enviar para registro.

## Cenário 2 — falha da biblioteca por duplicidade

Mantenha `$forcarRollback = false`. Prepare somente a tabela complementar, em
outra sessão Oracle de homologação, com um ID ainda ausente da tabela principal:

```sql
INSERT INTO SC_ORACLE_LIB_TESTE (ID, ORIGEM, DESCRICAO)
VALUES (310002, 'PREPARO_DUPLICIDADE', 'Linha preparada para o teste de duplicidade');
COMMIT;
```

Inclua `310002` no Formulário. A segunda inclusão desse ID na tabela complementar
deve falhar. Esperado: mensagem controlada, código `ORA-00001` quando fornecido
pelo driver, e a operação não segue como sucesso. Na consulta externa, a linha
preparada na complementar permanece; nenhuma linha `310002` deve persistir na
principal. Não execute novamente o preparo se essa linha já existir.

## Cenário 3 — rollback posterior à escrita da biblioteca

Altere para `$forcarRollback = true`, salve e gere. Inclua `310003` com outra
descrição. O evento grava a complementar e provoca uma falha intencional antes
de encerrar a operação. Esperado: mensagem de cancelamento e nenhuma linha desse
ID nas duas tabelas, conforme consulta em sessão independente.

Depois, restaure `$forcarRollback = false` e gere novamente. Guarde resultados,
IDs e mensagens de cada cenário. A limpeza deve remover somente os registros
próprios já conferidos; não há exclusão automática nesse roteiro.
