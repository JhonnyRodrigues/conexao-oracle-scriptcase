# Plano da biblioteca `conexaoOracleScriptcase`

**Status:** projeto concluído; versão 0.1.0 aprovada pelo mantenedor em 05/11/2026, após testes reais e publicação em produção. Validações adicionais dispensadas por sua decisão; evidências preservadas no registro de homologação.
**Data:** 29/10/2026.

Este documento consolida o plano e as decisões posteriores da conversa com o mantenedor. Ele substitui a proposta inicial que exigia distribuição por ZIP.

## 1. Objetivo, localização e limites

Criar uma biblioteca PHP independente para executar consultas e comandos Oracle com **bind parameters**, valores enviados separadamente da estrutura SQL, usando a conexão ADOdb emprestada pelo Scriptcase.

O novo projeto ficará em:

```text
C:\Users\jsilva\repositorios-semae\conexaoOracleScriptcase
```

Terá repositório Git, testes e documentação próprios. O projeto de referência, em `C:\Users\jsilva\repositorios-semae\pca-pncp-backend`, será consultado sem alterações.

As referências iniciais nesse projeto são as classes `ConexaoAdodbEmprestada.php`, `ConversorCharsetAdodbOracle.php` e `FalhaOracleException.php`, em `src/Infrastructure/Oracle/Adodb/`, e os testes correspondentes, incluindo `tests/Infrastructure/Oracle/Adodb/ConexaoAdodbEmprestadaTest.php`. O código existente serve como referência de implementação; as decisões deste plano definem o contrato da nova biblioteca.

A primeira versão atenderá:

- Aplicações **Blank, Formulário e Grid**.
- Consultas de listas pequenas.
- Comandos `INSERT`, `UPDATE` e `DELETE`.
- Parâmetros nomeados.
- Conversão explícita entre ISO-8859-1, Windows-1252 e UTF-8.
- Transações controladas pela aplicação hospedeira.

Ficam adiados: consultas grandes com leitura progressiva, outros bancos, controle transacional pela biblioteca, CLOB/BLOB, parâmetros de saída, ORM, construtor de consultas e atalhos `buscarUma()` e `buscarValor()`.

A biblioteca não incluirá regras, tabelas, configurações ou dependências do PCA/PNCP.

## 2. Organização e instalação no Scriptcase

Seguir o padrão já utilizado com o PHPMailer:

```text
Evento da aplicação
    → biblioteca interna conexaoOracle.php
        → biblioteca externa pública semae
            → pasta conexaoOracleScriptcase
```

**Biblioteca externa:** conterá as classes PHP e um `autoload.php` como ponto de entrada. Será carregada pela biblioteca interna:

```php
sc_include_library(
    "sys",
    "semae",
    "conexaoOracleScriptcase/autoload.php",
    true,
    true
);
```

**Biblioteca interna:** o arquivo `conexaoOracle.php`, no escopo do projeto Scriptcase, disponibilizará:

```php
function criarConexaoOracle(
    object $adodb,
    string $charsetConexao,
    string $charsetAplicacao
): \Semae\ScriptcaseOracle\BancoOracle
```

Essa função carregará o autoload externo dentro do seu corpo e construirá o objeto. A interna conterá somente a declaração da função, para permitir sua habilitação pela flag do Scriptcase. Não buscará `$this->Db` implicitamente, não manterá instância global e não executará SQL.

**Evento da aplicação, após habilitar `conexaoOracle.php` em Bibliotecas Internas do projeto:**

```php
$db = $this->criarConexaoOracle(
    $this->Db,
    'Windows-1252',
    'UTF-8'
);

$linhas = $db->buscarTodos(
    'SELECT ID, DESCRICAO FROM PAC_DEMANDAS WHERE ID_PLANO = :plano',
    ['plano' => {ID_PLANO}]
);
```

A instalação será feita copiando os arquivos preparados para a pasta da biblioteca externa, seguindo o procedimento já utilizado pela equipe. **ZIP não será uma exigência.**

Cada entrega terá versão identificada, começando em `0.1.0`, com os arquivos anteriores preservados para reversão. Composer será usado no desenvolvimento e na preparação do autoload, sem exigir sua execução no servidor. O ADOdb será fornecido pelo Scriptcase.

## 3. Contratos e comportamento

Adotar PHP 8.1 como base, PSR-4, PSR-12, namespace `Semae\ScriptcaseOracle` e pacote Composer `semae/scriptcase-oracle`. A conversão exigirá a extensão `mbstring`.

A classe pública será `BancoOracle`:

```php
public function __construct(
    object $adodb,
    string $charsetConexao,
    string $charsetAplicacao
);

public function buscarTodos(
    string $sql,
    array $parametros = []
): array;

public function executar(
    string $sql,
    array $parametros = []
): void;
```

### Consultas e comandos

- `buscarTodos()` retornará uma lista de linhas associativas. Consulta sem resultados retornará `[]`.
- Os nomes das colunas serão preservados conforme entregues pelo driver. Os exemplos usarão aliases explícitos quando necessário.
- `executar()` retornará normalmente quando o driver aceitar o comando. Não informará quantidade de linhas afetadas nem representará uma confirmação da transação.
- Usar `GetAll()` e `Execute()` do ADOdb. Consultas terão modo associativo temporário, com restauração exata do modo original em `finally`, inclusive em falhas. Não alterar variáveis globais do ADOdb.
- Não criar conexão, alterar credenciais, acessar `_connectionID`, executar `Close()` na conexão ou substituir o driver.

### Parâmetros

O SQL usará `:nome`, e o array usará a chave sem os dois-pontos:

```php
['nome' => $valor]
```

Aceitar nomes no formato `[A-Za-z][A-Za-z0-9_]*` e valores `string`, `int`, `float` ou `null`. Rejeitar chaves numéricas e tipos não suportados antes de chamar o driver.

Não substituir marcadores textualmente nem criar parser SQL. Divergências entre os binds e o SQL serão tratadas pelo driver e traduzidas como falhas.

Não converter automaticamente datas, números ou decimais. Os exemplos mostrarão formatos e conversões SQL explícitos quando necessários.

### Codificações

As duas configurações serão obrigatórias e aceitarão exatamente:

- `ISO-8859-1`
- `Windows-1252`
- `UTF-8`

`charsetConexao` representará a codificação efetivamente trocada com o ADOdb, sem dedução automática a partir do armazenamento Oracle.

`charsetAplicacao` valerá para entrada e saída da instância:

- Converter SQL e parâmetros textuais da aplicação para a conexão.
- Converter os textos retornados para a codificação da aplicação.
- Preservar valores não textuais.
- Validar UTF-8 e impedir conversões com perda por verificação de ida e volta.
- Não detectar codificação automaticamente nem substituir caracteres silenciosamente.

### Transações e falhas

A biblioteca não chamará `BeginTrans()`, `CommitTrans()` ou `RollbackTrans()`. Não tentará detectar ou assumir propriedade de uma transação existente.

Falhas do driver, por exceção ou retorno `false`, serão traduzidas para `FalhaOracleException`, com mensagem controlada e método:

```php
public function getCodigoOracle(): ?string;
```

SQL, binds e mensagens brutas do driver não serão expostos nem encadeados na exceção pública.

Configuração e parâmetros inválidos usarão `InvalidArgumentException`; falhas de codificação usarão `UnexpectedValueException`.

A biblioteca não exibirá mensagens nem gravará logs automaticamente. O evento hospedeiro tratará a falha e decidirá como interromper a operação.

## 4. Implementação em microciclos pedagógicos

Usar **Red → Green → Refactor**: escrever um teste que falhe pelo motivo esperado, implementar o mínimo e refatorar somente quando houver melhoria concreta.

| Microciclo | Entrega observável |
|---|---|
| **1 — Base e consultas** | Estrutura do projeto, autoload, construção do objeto, validação dos binds e `buscarTodos()` com testes. |
| **2 — Escritas e falhas** | `executar()`, tradução de erros, preservação do modo de busca e comprovação de que a biblioteca não controla transações nem fecha a conexão. |
| **3 — Codificações** | Conversão simétrica entre os três charsets, validação e bloqueio de perdas em entrada e saída. |
| **4 — Integração e homologação** | Biblioteca interna, arquivos para instalação externa, exemplos, documentação e validação em Blank, Formulário e Grid. |

**Ao final de cada microciclo, pausar obrigatoriamente.** Apresentar o comportamento entregue, os testes executados e uma explicação breve das decisões antes de avançar.

Manter a estrutura pequena: classe de acesso, conversor e exceção. Aproveitar as soluções e os testes aplicáveis do adaptador existente, sem transportar funcionalidades fora do escopo.

Não criar commits sem solicitação explícita.

## 5. Testes, aceitação e continuidade

### Validação local

Cobrir:

- SQL e binds enviados separadamente, incluindo apóstrofos e entradas com aparência de SQL injection.
- Consulta vazia, múltiplas linhas e preservação dos nomes das colunas.
- Parâmetros válidos e rejeição de nomes, chaves e tipos inválidos.
- Falhas retornadas como `false`, exceções do driver e respostas em formato inesperado.
- Código Oracle disponível ou ausente, sem exposição de informações sensíveis.
- Restauração do modo de busca após sucesso e falha.
- Ausência de chamadas transacionais e fechamento da conexão.
- Conversões entre os três charsets, acentos, UTF-8 inválido e caracteres não representáveis.
- Carregamento repetido do ponto de entrada sem redefinições de classes ou funções.

Executar a suíte relacionada a cada incremento e, ao concluí-lo, suíte completa, lint PHP e validação PSR-4.

### Homologação no Scriptcase

**Ajuste do mantenedor em 04/11/2026:** o teste de Formulário foi dispensado
nesta rodada, com orientação de avançar. Os itens correspondentes abaixo
permanecem como referência original; não foram executados nem aprovados.

Registrar versões de PHP, Scriptcase, ADOdb, OCI8 e Oracle, além da codificação efetiva da conexão.

Validar:

- **Blank:** consulta e escrita com transação controlada pelo hospedeiro.
- **Formulário:** escrita da biblioteca participando do commit e rollback da operação do formulário, sem confirmação antecipada.
- **Grid:** consulta em evento e continuidade do funcionamento da aplicação após a chamada.
- Biblioteca interna carregando a externa no ambiente de desenvolvimento e na aplicação publicada.

As escritas de teste usarão uma tabela dedicada em homologação. A biblioteca interna deverá tratar falhas sem permitir que o fluxo continue como se a operação tivesse sido concluída.

### Documentação e adoção

Documentar instalação, atualização, reversão, configuração de charset e exemplos por tipo de aplicação. Explicar que:

- Binds recebem valores; identificadores dinâmicos precisam de opções permitidas pelo código.
- Valores concatenados anteriormente ao SQL continuam oferecendo risco.
- Consultas grandes permanecem fora desta versão.
- Uma consulta adicional por registro da Grid pode aumentar o custo da operação.

A adoção começará por aplicações piloto. A versão será aceita após testes locais e homologação aprovados, com versão e evidências registradas.

### Continuidade

Este arquivo preserva o plano consolidado para consulta futura. Os quatro microciclos estão concluídos e a versão 0.1.0 foi aprovada pelo mantenedor em 05/11/2026, com dispensa de validações adicionais. As evidências e os limites estão em [estado_atual.md](../estado_atual/estado_atual.md) e [docs/HOMOLOGACAO.md](../../docs/HOMOLOGACAO.md).
