-- Executar uma vez no banco de homologacao, no schema usado pela TESTE_CONEXAO.
-- Executar separadamente dos eventos de teste: DDL Oracle tem commit implicito.
CREATE TABLE SC_ORACLE_LIB_TESTE (
    ID NUMBER(10) PRIMARY KEY,
    ORIGEM VARCHAR2(20) NOT NULL,
    DESCRICAO VARCHAR2(200),
    DATA_REFERENCIA DATE,
    VALOR NUMBER(12, 2)
);
