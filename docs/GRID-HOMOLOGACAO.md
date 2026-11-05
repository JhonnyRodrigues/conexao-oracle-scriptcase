# Teste de leitura na Grid

**Resultado em 05/11/2026:** o mantenedor informou “carregou e tudo funcionou”.
O cenário executado passou no desenvolvimento. Exportação e paginação não foram
detalhadas separadamente. Posteriormente, o mantenedor confirmou o diagnóstico
da Blank na homologação publicada e a publicação da biblioteca em produção;
o resultado da Grid publicada não foi informado separadamente.

O mantenedor dispensou o teste de Formulário. O roteiro abaixo é somente de leitura
na Grid, usando o registro já confirmado na Blank, ID `1056298063`.

1. Use uma Grid de homologação com a mesma conexão Oracle e aplicação
   `ISO-8859-1`. Se criar uma nova, pode chamá-la `TESTE_CONEXAO_GRID`, com SQL:

   ```sql
   SELECT ID, ORIGEM, DESCRICAO
   FROM SC_ORACLE_LIB_TESTE
   ```

2. Habilite `conexaoOracle.php` pela flag de bibliotecas internas do projeto.
3. No **onScriptInit**, cole
   [grid-onScriptInit.php.txt](../scriptcase/exemplos/grid-onScriptInit.php.txt).
4. Gere e execute. O evento consulta pela biblioteca o ID `1056298063`, verifica
   a descrição e a preservação do modo de busca. Em sucesso, não imprime saída
   adicional: a Grid continua seu fluxo normal.
5. Confira a listagem e a ordenação. Se houver exportação habilitada, exporte
   para CSV e confira o registro. A paginação só pode ser exercitada se houver
   linhas suficientes; não registre essa verificação como feita com apenas uma linha.

Informe se a Grid carregou, se a descrição ficou preservada e se a ordenação e
a exportação funcionaram. Uma falha na consulta ou na preservação do estado
interrompe a requisição com mensagem controlada.

Esse evento realiza uma consulta complementar por execução do `onScriptInit`.
Não transfira o código para `onRecord`, pois isso pode repetir a consulta para
cada linha. Não há novas tabelas, escrita, commit ou rollback neste roteiro.
