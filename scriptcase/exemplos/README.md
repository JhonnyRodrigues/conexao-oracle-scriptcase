# Exemplos para eventos Scriptcase

Os arquivos `.php.txt` contêm trechos para colar no editor de eventos, sem tags
PHP. Campos como `{ID}` são sintaxe do Scriptcase e não são PHP executável fora
do código gerado. O exemplo genérico de Blank usa aplicação UTF-8 e conexão
Windows-1252; ajuste ambos conforme o ambiente. A Grid, o Formulário opcional e os testes
`consulta-acentos`, `escrita-rollback` e `escrita-commit` usam ISO-8859-1 nos dois argumentos,
conforme a configuração e os resultados da aplicação `TESTE_CONEXAO` do mantenedor.

Habilite `conexaoOracle.php` em **Programação → Bibliotecas Internas → Projeto**
na aplicação. Os exemplos chamam `$this->criarConexaoOracle(...)`, pois a flag
incorpora a função como método. O autoload externo é incluído dentro desse método,
no momento da primeira chamada. O diagnóstico correto da flag usa `method_exists`,
não `function_exists`.

| Aplicação | Evento | Arquivo |
|---|---|---|
| Blank de referência | onExecute, contratos da API e consultas sem escrita | [blank-referencia-api.php.txt](blank-referencia-api.php.txt) |
| Aplicação piloto | Evento de diagnóstico | [diagnostico-carregamento.php.txt](diagnostico-carregamento.php.txt) |
| Aplicação piloto | Primeira leitura, somente ASCII | [consulta-bind.php.txt](consulta-bind.php.txt) |
| Aplicação piloto | Verificação de bytes e charset | [diagnostico-charset.php.txt](diagnostico-charset.php.txt) |
| Aplicação piloto | Leitura ISO-8859-1, acentos e apóstrofo | [consulta-acentos.php.txt](consulta-acentos.php.txt) |
| Blank | onExecute, escritas e rollback em homologação | [escrita-rollback.php.txt](escrita-rollback.php.txt) |
| Blank | onExecute, commit em homologação | [escrita-commit.php.txt](escrita-commit.php.txt) |
| Blank | onExecute | [blank-onExecute.php.txt](blank-onExecute.php.txt) |
| Formulário | onAfterInsert | [formulario-onAfterInsert.php.txt](formulario-onAfterInsert.php.txt) |
| Grid | onScriptInit | [grid-onScriptInit.php.txt](grid-onScriptInit.php.txt) |

A Blank de referência pode ser mantida como `EXEMPLO_CONEXAO_ORACLE`: habilite
a interna, use a conexão Oracle e cole `blank-referencia-api.php.txt` no
`onExecute`, sem tags PHP. Mantenha a aplicação em ISO-8859-1 no ambiente já
validado. Ela documenta os contratos em comentários, executa duas consultas em
`DUAL` e mostra os retornos com escape HTML. Não exige tabelas próprias;
as escritas e o controle transacional são apenas referências comentadas.

O diagnóstico não executa SQL. A primeira leitura usa `DUAL` e um bind ASCII,
sem tabelas próprias nem escrita; os charsets UTF-8/UTF-8 desse teste não comprovam
os charsets efetivos do ambiente. Confirme-os antes de testar textos com acentos.

O diagnóstico de charset lê uma referência criada no Oracle e mostra os bytes
recebidos diretamente pelo ADOdb, antes de qualquer conversão da biblioteca.
Também mostra apenas a propriedade pública `charSet` e `NLS_LANG`, sem expor o
objeto de conexão ou credenciais. Não muda a configuração nem detecta o charset
automaticamente. Os acentos comuns não distinguem ISO-8859-1 de Windows-1252
sozinhos, e os símbolos podem sofrer conversão no banco/cliente; interprete o
resultado junto das configurações efetivas do ambiente.

A referência usa [UNISTR](https://docs.oracle.com/en/database/oracle/oracle-database/19/sqlrf/UNISTR.html)
e um CAST para texto comum. O charset do cliente pode ser configurado no
[OCI8](https://www.php.net/manual/en/function.oci-connect.php) e não precisa ser
o mesmo do armazenamento do banco. A propriedade do driver e `NLS_LANG` são
evidências de configuração, não uma alteração da conexão já emprestada.

Para os exemplos de escrita e transações, prepare antes as tabelas dedicadas descritas no
[roteiro de homologação](../../docs/HOMOLOGACAO.md). Os exemplos não executam DDL.
Todas as escritas ficam restritas a essas tabelas de teste. Use IDs diferentes
entre as aplicações e descarte somente os registros dos seus testes.

O teste `escrita-rollback` exige `SC_ORACLE_LIB_TESTE` já criada e uma Blank que
seja dona da transação. Ele insere duas linhas próprias, atualiza a primeira e
exclui a segunda. A primeira permanece até o rollback do evento: se ela persistir
na consulta posterior, a verificação falha. Usar uma segunda linha para DELETE
evita que a exclusão da primeira esconda uma confirmação antecipada. Os IDs são
exibidos para conferência em outra sessão; não execute esse exemplo em produção.
O [SQL de criação da tabela](../sql/criar-tabela-teste.sql) deve ser executado
separadamente, no schema usado pela aplicação de homologação.

Após validar o rollback, `escrita-commit` grava uma linha própria com origem
`BLANK_COMMIT`, solicita commit no evento e confere a leitura na aplicação.
A linha permanece na tabela para conferência por
[outra sessão Oracle](../sql/verificar-commit.sql): localize exatamente o ID
exibido pela Blank. A leitura na mesma sessão não basta para provar a persistência.
Registre o ID antes da limpeza; remova posteriormente somente as linhas dos testes
já conferidos, com transação controlada pelo responsável pela homologação.

Na Blank, o evento controla a transação. No Formulário, o Scriptcase controla a
transação da operação: o exemplo não inicia outra transação nem confirma a
existente. Ele solicita rollback e interrompe a operação se a gravação falhar.
Na Grid, a consulta ocorre na inicialização. Repeti-la em `onRecord` introduz
uma consulta adicional por linha e pode aumentar muito o custo.

O sucesso de um `executar()` não prova que houve commit nem que uma linha foi
afetada. Nos testes transacionais, confira a persistência em outra sessão Oracle
e o efeito do rollback sobre ambas as tabelas do formulário.

O [roteiro do Formulário](../../docs/FORMULARIO-HOMOLOGACAO.md) detalha criação
da aplicação, configuração do ID manual e os três cenários do `onAfterInsert`.
Ele foi dispensado pelo mantenedor nesta rodada e é mantido como referência opcional.
O sinalizador `$forcarRollback` deve ficar `false` no teste normal e de
duplicidade, e `true` somente no cenário de rollback posterior.

O [roteiro da Grid](../../docs/GRID-HOMOLOGACAO.md) reutiliza o ID confirmado no
commit da Blank. O `onScriptInit` verifica a leitura e o modo de busca original,
sem impressão adicional, escrita ou controle de transações.

Para exercitar UPDATE e DELETE em um evento que já possua `$db` e uma transação
controlada pelo hospedeiro, use um ID de teste conhecido:

```php
$db->executar(
    'UPDATE SC_ORACLE_LIB_TESTE SET DESCRICAO = :texto WHERE ID = :id',
    ['texto' => "Teste D'Ávila", 'id' => $idTeste]
);
$db->executar('DELETE FROM SC_ORACLE_LIB_TESTE WHERE ID = :id', ['id' => $idTeste]);
```

As falhas são tratadas pelo evento. Não mostre mensagens brutas do ADOdb, SQL,
binds nem o rastreamento da exceção ao usuário. Se alterar a apresentação, mantenha
a interrupção do fluxo; exibir uma mensagem e continuar pode produzir sucesso falso.
