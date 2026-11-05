-- Executar uma vez em homologacao, no mesmo schema da SC_ORACLE_LIB_TESTE.
-- DDL deve ficar fora dos eventos de teste do formulario.
CREATE TABLE SC_ORACLE_FORM_TESTE (
    ID NUMBER(10) PRIMARY KEY,
    DESCRICAO VARCHAR2(200)
);
