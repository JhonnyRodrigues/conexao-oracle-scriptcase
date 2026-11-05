-- Executar em OUTRA sessao Oracle, no mesmo banco/schema de homologacao.
-- Localizar no resultado o ID exibido pela Blank no teste escrita-commit.
-- Podem aparecer registros de outras execucoes; conferir o ID desta execucao.
SELECT ID, ORIGEM, DESCRICAO
FROM SC_ORACLE_LIB_TESTE
WHERE ORIGEM = 'BLANK_COMMIT'
ORDER BY ID;
