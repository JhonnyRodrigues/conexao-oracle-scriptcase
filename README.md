# conexaoOracleScriptcase

Biblioteca PHP independente para usar a conexão ADOdb fornecida pelo Scriptcase,
enviando SQL e valores separadamente por bind parameters (parâmetros vinculados).

Versão: **0.1.0**, aprovada pelo mantenedor em 05/11/2026. Projeto concluído.
A implementação local dos quatro microciclos de
[PLANO.md](.agents/planos/PLANO.md) está concluída. O mantenedor confirmou os testes
executados em Blank e Grid, dispensou o Formulário e confirmou o diagnóstico de
carregamento na homologação publicada. A biblioteca foi publicada em produção
pelo mantenedor. Ele considerou suficientes os testes realizados e dispensou
validações adicionais para encerrar esta entrega. As evidências e os limites
estão em [HOMOLOGACAO.md](docs/HOMOLOGACAO.md).

## Desenvolvimento

Requisitos: PHP 8.1 ou superior, extensão `mbstring` e Composer. O ADOdb será
fornecido pela aplicação hospedeira; não é instalado como dependência do projeto.

```sh
composer install
composer test
composer lint
composer check-psr4
composer package:build
```

O Composer prepara o autoload. `autoload.php` é o ponto de entrada e pode ser
incluído repetidamente. `package:build` cria uma nova pasta em `dist/`, com o
autoload de produção e sem dependências de teste. Verifique a pasta impressa:

```sh
composer package:verify -- dist/pasta-impressa-pelo-build
```

O preparo preserva as entregas anteriores. O servidor recebe os arquivos prontos,
sem exigir Composer ou ZIP. Consulte a [instalação, atualização e reversão](docs/INSTALACAO.md).

## Uso no Scriptcase

Copie `externa/semae/conexaoOracleScriptcase/` da entrega para a biblioteca externa
pública `semae`. Cadastre `scriptcase/interna/conexaoOracle.php` como biblioteca
interna do projeto com o nome `conexaoOracle.php`. Na aplicação, habilite-a em
**Programação → Bibliotecas Internas → Projeto: DEV** (ou o projeto utilizado).
No evento, chame o método gerado passando a conexão:

```php
$db = $this->criarConexaoOracle($this->Db, 'Windows-1252', 'UTF-8');
```

A interna contém somente a função, que carrega o autoload externo ao ser chamada
e constrói uma nova instância sem executar SQL ou manter instância global.
Para verificar a flag, use `method_exists($this, 'criarConexaoOracle')`; o
Scriptcase incorpora a função como método da aplicação. Antes da primeira chamada,
a classe externa pode ainda não estar carregada. Há um
[diagnóstico sem SQL](scriptcase/exemplos/diagnostico-carregamento.php.txt).
Os [exemplos de Blank, Formulário e Grid](scriptcase/exemplos/README.md)
mostram como tratar falhas no evento hospedeiro. Execute o
[roteiro de homologação](docs/HOMOLOGACAO.md) no desenvolvimento e na aplicação
publicada, usando tabelas dedicadas.

## Consultas disponíveis

Para manter uma aplicação Blank como referência de uso, cole
[blank-referencia-api.php.txt](scriptcase/exemplos/blank-referencia-api.php.txt)
no evento `onExecute`. O arquivo reúne os contratos da API em comentários,
consultas executáveis em `DUAL` e exemplos de escrita comentados.

```php
require_once __DIR__ . '/autoload.php';

$db = new \Semae\ScriptcaseOracle\BancoOracle(
    $adodb,
    'Windows-1252',
    'UTF-8'
);

$linhas = $db->buscarTodos(
    'SELECT ID, DESCRICAO FROM TABELA_EXEMPLO WHERE ID = :id',
    ['id' => 10]
);
```

`$adodb` representa a conexão já existente. Construir o objeto não executa SQL.
`buscarTodos()` usa `GetAll()` em modo associativo e restaura o estado original
da conexão em `finally`, inclusive quando ocorre uma falha. Retorna uma lista de
linhas, preservando os nomes das colunas; uma consulta vazia retorna `[]`.

As chaves dos binds devem seguir `[A-Za-z][A-Za-z0-9_]*`, sem dois-pontos. Os
valores aceitos são `string`, `int`, `float` e `null`. Tipos e nomes inválidos
geram `InvalidArgumentException` antes de chamar o driver. O SQL não é reescrito;
a correspondência entre os marcadores e os parâmetros cabe ao ADOdb/Oracle.

Binds representam valores. Identificadores dinâmicos, como nomes de colunas,
precisam de opções permitidas pelo código. Valores concatenados previamente ao
SQL continuam oferecendo risco.

## Codificações

Os dois charsets são obrigatórios e aceitam exatamente `ISO-8859-1`,
`Windows-1252` ou `UTF-8`, em qualquer combinação. `charsetConexao` descreve os
bytes efetivamente trocados com o ADOdb; não deve ser deduzido da codificação de
armazenamento do Oracle. `charsetAplicacao` descreve os textos de entrada e de
saída da instância. No exemplo acima, a aplicação usa UTF-8 e a conexão troca
textos em Windows-1252.

O SQL e os valores textuais dos binds são convertidos da aplicação para a
conexão. Os valores textuais das linhas retornadas percorrem o caminho inverso.
Valores não textuais e nomes de colunas são preservados. Prefira aliases ASCII
explícitos quando precisar de nomes de coluna estáveis na aplicação.

O conversor valida o texto na codificação de origem, inclusive quando os dois
charsets são iguais. Quando há conversão, exige que a conversão de volta recupere
os mesmos bytes. UTF-8 inválido e textos não representáveis no destino geram
`UnexpectedValueException`, sem incluir o conteúdo na mensagem. Por exemplo,
Windows-1252 representa `€` e `–`, mas ISO-8859-1 não representa esses caracteres;
um emoji não pode ser gravado em nenhum desses dois charsets.

Falhas de entrada são detectadas antes de chamar o driver. Nas consultas, o modo
de busca já está restaurado quando os valores de saída são convertidos. Não há
detecção automática de charset, substituição silenciosa de caracteres nem
alteração das configurações globais de `mbstring`. A extensão é obrigatória;
configuração inválida gera `InvalidArgumentException`.

## Comandos e falhas

```php
try {
    $db->executar(
        'UPDATE TABELA_EXEMPLO SET DESCRICAO = :descricao WHERE ID = :id',
        ['descricao' => "D'Ávila", 'id' => 10]
    );
} catch (\Semae\ScriptcaseOracle\FalhaOracleException $falha) {
    $codigoOracle = $falha->getCodigoOracle(); // Por exemplo, ORA-00942 ou null.
    throw $falha; // Interrompe este fluxo; o hospedeiro decide o tratamento final.
}
```

`executar()` usa `Execute()`, retorna `void` e aceita os mesmos binds das consultas.
O retorno normal significa que o driver aceitou o comando. Não informa quantidade
de linhas afetadas e não confirma a transação. INSERT, UPDATE e DELETE seguem o
mesmo contrato; datas e decimais exigem formatos e conversões SQL explícitos.

Retorno `false` ou exceção do driver gera `FalhaOracleException`, com mensagem
controlada e sem a exceção original encadeada. O código é obtido de `ErrorNo()`;
na ausência de um código válido, é procurado em `ErrorMsg()` e na mensagem da
exceção recebida. Apenas `ORA-` seguido de cinco dígitos de erro é exposto por
`getCodigoOracle()`. Sem código disponível, retorna `null`. Mensagens brutas, SQL
e binds não são incorporados à mensagem pública. Uma falha nos próprios métodos
de diagnóstico não substitui a falha da operação.

A aplicação hospedeira controla início, commit e rollback. A biblioteca não
inspeciona transações, não fecha a conexão, não exibe mensagens e não grava logs.
O hospedeiro deve tratar a falha e interromper o fluxo que dependia da operação.

## Limites deste incremento

- Respostas de consulta em formato inválido geram `FalhaOracleException` com
  código `null`, para não atribuir à operação um diagnóstico residual do driver.
- A biblioteca não inicia nem confirma transações e não fecha a conexão.
- Esta versão se destina a listas pequenas. Não oferece leitura progressiva,
  CLOB/BLOB, parâmetros de saída ou conversão automática de datas e números.
- Os testes automatizados usam um substituto controlado do ADOdb. O pacote também
  é carregado em processo PHP isolado. Os testes manuais em Blank e Grid foram
  informados pelo mantenedor, assim como o diagnóstico na homologação publicada.
  A execução em produção e a reversão ainda não têm resultado registrado.

As evidências e o próximo ponto de continuidade estão em
[.agents/estado_atual/estado_atual.md](.agents/estado_atual/estado_atual.md).
