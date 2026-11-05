# Estado atual — projeto concluído; versão 0.1.0 aprovada

Atualização: 05/11/2026. Versão aprovada: 0.1.0.

Os quatro microciclos do [plano](../planos/PLANO.md) têm implementação local
concluída. Após os testes reais e a publicação da biblioteca em produção,
o mantenedor solicitou finalizar o projeto e dispensou validações adicionais.
A versão 0.1.0 está aprovada por sua decisão em 05/11/2026. As evidências e os
limites permanecem no [registro](../../docs/HOMOLOGACAO.md); cenários dispensados
não devem ser apresentados como executados. As seções históricas abaixo
preservam o contexto das decisões anteriores.

## Publicação informada em 05/11/2026

O mantenedor executou o diagnóstico de carregamento da aplicação
`teste_conexao_oracle_scriptcase_B` na homologação publicada e informou que
deu tudo certo. Em seguida, publicou a biblioteca também no ambiente de produção.
Esse retorno confirma o diagnóstico em homologação e a publicação em produção;
não informa execução em produção, repetição de todos os cenários publicados,
hash da entrega instalada ou teste de reversão. Os registros históricos abaixo
descrevem as etapas anteriores e suas pendências naquele momento.

## Limpeza e versionamento do repositório

O mantenedor autorizou revisar, limpar e criar o commit final. Foram removidos
128 arquivos gerados em `.phpunit.cache/`, incluindo pacotes temporários dos
testes. Fontes, testes, exemplos e documentação foram preservados. As três
entregas em `dist/` permanecem como arquivo de distribuição e reversão, fora do
Git, assim como `vendor/` e caches. A versão publicada permanece `0.1.0`;
a promoção para `1.0.0` corresponde à adoção de uma API pública estável.
Na revisão, Composer e lock foram validados e o pacote final passou novamente
na verificação de integridade e carregamento isolado. Não houve mudança de código
de execução desde a suíte aprovada de 216 testes e 870 asserções.

## Entrega final de 05/11/2026

- Versão aprovada: **0.1.0**.
- Pasta: `dist/0.1.0-20261105T193141Z-5eb87b/`, preservando as entregas anteriores.
- [Instruções do pacote final](../../dist/0.1.0-20261105T193141Z-5eb87b/README.md).
- SHA-256 de `manifest.json`:
  `28D9C7B89C8F9FBDCBDAECDB48F35E561DC5DB88B2CBA835A95D67992B630A42`.
- Verificação final: 216 testes e 870 asserções aprovados; lint dos 20 arquivos
  PHP aprovado; integridade e carregamento isolado do pacote aprovados.
- Documentação de aprovação incluída no pacote. Nenhuma nova cópia no servidor
  foi feita pelo agente; o pacote identifica o arquivo local de encerramento.

## Correção de 04/11/2026

O mantenedor iniciou o teste na aplicação `TESTE_CONEXAO` e confirmou PHP 8.1.28,
`mbstring`, OCI8 e conexão ADOdb disponível. A flag da biblioteca interna recusou
o código com a mensagem de que instruções fora das funções exigem `sc_include`.
O teste anterior com `function_exists` também era inadequado para esse modo:
no código gerado de referência do SGI, a função da interna é método da aplicação.

A interna agora contém somente `criarConexaoOracle`, sem `if` nem inclusão fora
da função. `sc_include_library` está dentro da fábrica. A flag deve ser habilitada
na aplicação, e o evento usa `$this->criarConexaoOracle(...)`. O diagnóstico usa
`method_exists($this, 'criarConexaoOracle')` e verifica a classe após chamar a
fábrica, sem executar SQL. README, instalação, exemplos e plano foram alinhados.

O novo teste falhou inicialmente por código executável no corpo da classe e
por carregamento externo já na declaração da interna. Após a correção, a suíte
passou: **216 testes e 870 asserções**. O teste PHP simula a incorporação como
método; o gerador real do Scriptcase ainda deve ser exercitado pelo mantenedor.

Pendência independente: o mantenedor encontrou `vendor/autoload.php` em
`devel/conf/sys/libraries/semae/conexaoOracleScriptcase`, mas não em
`app/DEV/_lib/libraries/sys/semae/conexaoOracleScriptcase`. A correção da flag
não comprova a sincronização desses arquivos. Não assumir como procedimento
normal uma cópia manual de `vendor` por aplicação nem considerar a publicação
homologada antes de verificar a biblioteca externa completa no destino.

## Continuação da investigação de carregamento

O mantenedor obteve `Metodo criarConexaoOracle: OK`, seguido de
`Class "Semae\ScriptcaseOracle\BancoOracle" not found`, e forneceu a interna de
e-mail funcionando com `sc_include_library` fora da função. Isso torna inadequada
a afirmação anterior de que a macro fora da função, por si só, seria sempre
incompatível com a flag. O trecho Oracle informado posteriormente estava sem a
chave final da função; foi orientada sua substituição pelo conteúdo completo.

A implementação atual da interna no repositório foi mantida. O diagnóstico de
homologação agora inspeciona a presença e leitura dos arquivos externos, os
autoloads incluídos e o mapeamento do namespace `Semae\ScriptcaseOracle`.
O método existir não comprova que o autoload tenha sido executado ou que as
classes tenham chegado ao diretório de execução. Não atribuir a causa ao Composer,
ao namespace ou à posição da macro sem coletar esses resultados. Nenhum SQL foi
executado por esse diagnóstico.

Em um retorno anterior, o método existia e tentava carregar o autoload externo,
mas o `require` de `app/DEV/_lib/libraries/sys/semae/conexaoOracleScriptcase/vendor/autoload.php`
falha por arquivo ausente. A etapa atual é a distribuição da externa, e não a
detecção do método. A instalação foi complementada com a seleção da pública
`semae` para uso no projeto e o botão de salvar essa seleção, conforme documentação
oficial. Se já estiver selecionada, foi proposto testar uma atualização pela
interface de upload da externa e regenerar a aplicação; a eficácia dessa
atualização no servidor ainda não foi confirmada.

## Avanço manual informado em 04/11/2026

O mantenedor retornou `Metodo criarConexaoOracle: OK`, `Classe BancoOracle: OK`
e `Construcao sem SQL: OK`, com PHP 8.1.28, mbstring e OCI8 disponíveis.
O carregamento e a construção passaram nessa execução. Não foi informado qual
ação completou a distribuição externa; a geração/publicação sem cópia manual
por aplicação ainda precisa ser comprovada. Na sequência foi proposto executar
`scriptcase/exemplos/consulta-bind.php.txt`, uma leitura em `DUAL` com valor ASCII.
Posteriormente, o mantenedor confirmou `Consulta com bind: OK` e a linha
`['VALOR' => 'TESTE_BIND_123']`. A primeira leitura real por `buscarTodos()` com
bind nomeado passou no Oracle. Os charsets efetivos ainda são desconhecidos;
o próximo passo é `scriptcase/exemplos/diagnostico-charset.php.txt`, que observa
bytes brutos de uma referência Oracle antes de qualquer conversão da biblioteca.
Esse diagnóstico chama o ADOdb diretamente apenas para leitura e não muda a
conexão, o modo de busca ou as transações. Os retornos posteriores confirmaram:

- ADOdb `charSet = WE8ISO8859P1`, `NLS_LANG` não definido.
- Bytes de `ação` no PHP: `61e7e36f`; referência bruta: `61e7e36f7cbf7cbf`.
- Sessão Scriptcase `ISO-8859-1` e HTML `iso-8859-1`.
- `Bind com acentos e apostrofo: OK` e `Leitura de acentos do Oracle: OK`.
- Linha retornada: `['TEXTO' => "D'Ávila - ação, café e órgão"]`.

Para a `TESTE_CONEXAO`, conexão e aplicação estão configuradas e validadas em
`ISO-8859-1`. Os símbolos euro/traço da referência sofreram substituição por
`bf` antes da conversão da biblioteca; a biblioteca não recupera esses caracteres.
O teste validado foi preservado em `scriptcase/exemplos/consulta-acentos.php.txt`.
O próximo passo é confirmar o banco de homologação e a existência da tabela
`SC_ORACLE_LIB_TESTE`, antes de executar `scriptcase/exemplos/escrita-rollback.php.txt`.
Esse exemplo está preparado, mas ainda não foi executado pelo mantenedor.
O mantenedor respondeu: a conexão aponta para homologação, mas a tabela ainda
não existe. O SQL de criação foi preparado em `scriptcase/sql/criar-tabela-teste.sql`
para execução manual no mesmo schema, separadamente do evento de rollback.
Depois da criação, o evento de escrita/rollback poderá ser executado na Blank.
Posteriormente, o mantenedor executou o teste e informou:

- INSERT e UPDATE com acentos e apóstrofo: OK.
- DELETE: OK.
- Rollback solicitado pelo evento: OK.
- Consulta depois do rollback: nenhuma linha do teste.
- IDs: **828940774 e 828940775**.

As escritas e o rollback estão comprovados nesse cenário da Blank em homologação.
O teste comprova a ausência das duas linhas na leitura posterior pela aplicação;
a consulta dos IDs em sessão independente ainda não foi informada. Foi preparado
`scriptcase/exemplos/escrita-commit.php.txt`, que mantém uma linha para consulta
em outra sessão usando `scriptcase/sql/verificar-commit.sql`. O mantenedor
executou esse teste e apresentou a linha persistida em outra sessão:

- ID: **1056298063**.
- ORIGEM: `BLANK_COMMIT`.
- DESCRICAO: `D'Ávila - ação, café e órgão`.

Commit solicitado pelo evento e persistência em outra sessão estão confirmados
nesse cenário da Blank. A próxima etapa é `docs/FORMULARIO-HOMOLOGACAO.md`.
Foram preparados o SQL da tabela principal, a consulta de conferência das duas
tabelas e o evento onAfterInsert com ISO-8859-1 nos dois argumentos, incluindo
um sinalizador de falha intencional para o terceiro cenário. A criação da tabela
de Formulário e os testes reais ainda não foram confirmados. Grid e publicação
também permanecem pendentes; a candidata não está integralmente homologada.

O mantenedor orientou desconsiderar o teste de Formulário e avançar. Os cenários
de Formulário foram dispensados nesta rodada, sem execução ou aprovação presumida.
Os materiais anteriores foram mantidos como referência opcional. A próxima etapa
é `docs/GRID-HOMOLOGACAO.md`, com uma consulta complementar no `onScriptInit`,
ISO-8859-1 nos dois argumentos e o registro já confirmado `1056298063`.
O evento confere o texto e a preservação do modo de busca e deixa a Grid seguir
normalmente, sem imprimir saída no sucesso.

## Retorno da Grid em 05/11/2026

O mantenedor informou: “carregou e tudo funcionou”. O cenário executado na Grid
passou no desenvolvimento, após a consulta complementar e a verificação do modo
de busca em `onScriptInit`. Exportação e paginação não foram detalhadas
separadamente. O teste de Formulário continua dispensado.

Próxima etapa: publicar as aplicações piloto em homologação e conferir o
carregamento da externa completa, incluindo `vendor/`, sem cópia manual por
aplicação. Repetir primeiro o diagnóstico sem SQL e as leituras já validadas.
Ainda não há evidência do funcionamento na publicação ou da reversão de entrega.

## Comportamento entregue

- Pacote `semae/scriptcase-oracle`, namespace `Semae\ScriptcaseOracle`, Composer,
  autoload PSR-4 e ponto de entrada repetível.
- Construção com conexão emprestada e charsets obrigatórios, sem executar SQL.
- `buscarTodos()` com SQL e binds nomeados enviados separadamente ao `GetAll()`.
- Rejeição antecipada de nomes, chaves e tipos inválidos.
- Lista vazia, múltiplas linhas, tipos dos valores e nomes das colunas preservados.
- Modo associativo temporário com restauração exata do estado original.
- `executar()` via `Execute()` para INSERT, UPDATE e DELETE, com retorno `void`
  e sem tratar o retorno normal como confirmação da transação.
- Falhas por retorno `false` ou exceção traduzidas para `FalhaOracleException`,
  com mensagem controlada, código Oracle disponível ou ausente e sem encadear a
  exceção original.
- Testes de ausência de chamadas transacionais, fechamento, acesso a propriedades
  internas e alteração do modo global, inclusive com transação ativa do hospedeiro.
- `ConversorCharset` integrado às duas operações: SQL e binds textuais da aplicação
  para a conexão e valores textuais retornados para a aplicação.
- Todas as nove combinações entre ISO-8859-1, Windows-1252 e UTF-8, com validação
  do texto de origem e bloqueio de perdas por verificação de ida e volta.
- Falhas de codificação geram `UnexpectedValueException`, sem conteúdo sensível
  na mensagem, antes de chamar o driver na entrada e após restaurar o modo na saída.
- Biblioteca interna `scriptcase/interna/conexaoOracle.php`, com inclusão da
  externa pública `semae` e fábrica sem SQL ou instância global.
- `composer package:build` prepara a biblioteca externa com autoload de produção
  em uma pasta nova, identificada por versão, data UTC e sufixo, preservando as
  entregas anteriores. O servidor não exige Composer; ZIP não é obrigatório.
- `composer package:verify` confere os hashes SHA-256 e executa carregamento
  isolado, sem o autoload de desenvolvimento, simulando as inclusões e o ADOdb.
- Exemplos de eventos para Blank/Formulário/Grid e documentação de instalação,
  atualização, reversão, tabelas dedicadas e registro de homologação.

## Decisões

O SQL não é interpretado nem recebe valores por substituição. Essa separação
permite que o driver vincule os valores sem incorporá-los à estrutura da consulta.

A restauração do modo e uma tradução mínima de falhas foram incluídas junto da
consulta para que o primeiro incremento já seja seguro sobre a conexão emprestada.
As três classes de produção têm as responsabilidades previstas no plano:
acesso à conexão, conversão de textos e representação da falha Oracle.

O modo é guardado diretamente da propriedade pública `fetchMode`. No ADOdb,
`SetFetchMode()` pode retornar o modo global quando a propriedade original é
`false`. Usar somente esse retorno não restauraria o estado exato. Referência:
[implementação oficial de SetFetchMode](https://github.com/ADOdb/ADOdb/blob/master/adodb.inc.php).

O microciclo 3 substituiu o bloqueio temporário entre charsets diferentes pelo
conversor. A validação dos nomes dos charsets e da presença de `mbstring` está
concentrada nele. O `BancoOracle` continua responsável pelos tipos dos binds e
pelo formato das linhas; só entrega strings ao conversor. Nomes de colunas e
valores não textuais permanecem intactos.

O texto de origem é validado antes de qualquer conversão, inclusive no caso de
charsets iguais. Para charsets diferentes, a conversão inversa deve reproduzir
os mesmos bytes. Isso impede substituições silenciosas sem depender da opção
global `mb_substitute_character`. A codificação é sempre explícita: ISO-8859-1
e Windows-1252 não são tratados como equivalentes nem detectados automaticamente.

O microciclo 2 reutiliza a validação de entrada nas consultas e nos comandos.
Não há substituição de marcadores, contagem de binds ou interpretação do SQL.
O construtor exige os métodos `GetAll`, `Execute`, `SetFetchMode`, `ErrorNo` e
`ErrorMsg`, além da propriedade pública `fetchMode`; métodos transacionais não
fazem parte desse contrato.

A tradução de erros procura primeiro o código em `ErrorNo()`, depois em
`ErrorMsg()` e, por último, na mensagem da exceção recebida. Inteiros negativos
e strings numéricas são normalizados sem converter números grandes por coerção.
Somente códigos não nulos no formato `ORA-` com cinco dígitos são expostos. Uma
falha nos diagnósticos não substitui a operação que falhou. Respostas de consulta
malformadas usam código `null`, evitando um código residual de operação anterior.

A biblioteca interna deixa as exceções propagarem para o evento, que deve
interromper o fluxo em caso de falha. Ela não administra transações; os exemplos
deixam essa decisão com a Blank ou com a transação do Formulário. Os trechos
`.php.txt` são fonte para o compilador Scriptcase, não PHP autônomo.

O preparo usa Composer apenas para gerar um autoload de produção isolado. A
verificação do pacote roda em outro processo, evitando que o autoload já carregado
no repositório esconda arquivos ausentes na distribuição. Um manifesto registra
versão, data UTC e hashes dos arquivos. Ele detecta alterações acidentais e não
substitui assinatura de origem ou homologação.

## Evidências de validação

No microciclo 1, o primeiro teste foi executado antes da classe e falhou pelo motivo esperado:
`Class "Semae\ScriptcaseOracle\BancoOracle" not found` (fase Red). Após implementar
o comportamento, a suíte passou (fase Green). Não houve necessidade de introduzir
abstrações adicionais na fase Refactor.

No microciclo 2, os testes de escrita falharam inicialmente por ausência de
`BancoOracle::executar()` e passaram após a implementação. O primeiro teste de
extração do código falhou porque retornava `null` em vez de `ORA-00942`; passou
após implementar a tradução. A refatoração concentrou a validação de entrada e a
criação das falhas sem introduzir novas classes de produção.

No microciclo 3, o teste do conversor falhou inicialmente porque a classe não
existia. Após implementá-la, seus 58 testes passaram. O teste de integração
falhou pelo bloqueio temporário de charsets diferentes; após integrar o conversor
e substituir os dois testes desse bloqueio, a suíte completa passou.

No microciclo 4, o teste da biblioteca interna falhou inicialmente porque o
arquivo ainda não existia e passou após a implementação. Os testes também
preparam e verificam uma entrega isolada, rejeitam sobrescrita do destino e
detectam alteração de arquivo pelo manifesto.

Verificações locais do microciclo 4:

- `composer test`: 215 testes, 859 asserções, todos aprovados.
- `composer lint`: 20 arquivos PHP próprios aprovados, incluindo a interna.
- `composer check-psr4`: autoload otimizado gerado sem violações PSR-4.
- `composer validate --no-check-publish`: manifesto e lock válidos; aviso não
  impeditivo de licença ainda não especificada.
- Processo separado com `php -n`, sem `mbstring`: construção do conversor
  rejeitada com `InvalidArgumentException`, conforme esperado.
- `composer package:build`: entrega candidata gerada com autoload de produção.
- `composer package:verify`: integridade SHA-256, versão e carregamento isolado
  aprovados para a entrega abaixo. Links locais do repositório e do pacote
  também foram verificados.

## Entrega corrigida para testar a flag

- Pasta: `dist/0.1.0-20261104T135014Z-528afa/`.
- [Instruções da entrega corrigida](../../dist/0.1.0-20261104T135014Z-528afa/README.md).
- Versão candidata mantida em `0.1.0`; 24 arquivos no manifesto.
- SHA-256 de `manifest.json`:
  `6ED55AAAF831201AD476D3F977F352666C5683CF087FAE048BB6FF1A64534799`.
- Suíte: 216 testes e 870 asserções. Lint, PSR-4 e `package:verify` aprovados.
- Próximo teste manual: substituir a interna cadastrada pelo arquivo corrigido,
  habilitar a flag, gerar e executar o diagnóstico de método/fábrica sem SQL.

## Entrega anterior preservada

- Pasta: `dist/0.1.0-20261029T205620Z-e6b352/`.
- [Instruções da entrega](../../dist/0.1.0-20261029T205620Z-e6b352/README.md).
- Versão: `0.1.0`; manifesto com 23 arquivos, além do próprio `manifest.json`.
- SHA-256 de `manifest.json`:
  `A8D30782EFD266428A090B0A177C86307FA07998E787F7DBA7803096E52815AA`.
- `dist/` é gerado e ignorado pelo Git. Preserve esta pasta fora do caminho
  ativo e use a verificação antes de copiar a biblioteca externa completa.

## Limites da validação

Ambiente local: PHP CLI 8.4.7, PHPUnit 10.5.66 e `mbstring` disponível. O Composer
resolveu as dependências com a plataforma configurada em PHP 8.1.0. Os testes
automatizados não executaram um interpretador PHP 8.1 nem conexão real com Oracle.
Os testes manuais informados pelo mantenedor executam PHP 8.1.28 no Scriptcase
e já exercitaram carregamento, consultas, escritas e transações reais na Blank,
além da consulta complementar na Grid. O diagnóstico de carregamento também
passou na homologação publicada. A execução em produção e a reversão não têm
resultado registrado; o Formulário foi dispensado.

O repositório Git foi inicialmente preparado sem commits. As mensagens de propriedade
divergente emitidas pelo Git dentro do sandbox não impediram a validação do
autoload. Para a inspeção local, foi usada uma exceção de diretório apenas na
invocação de `git status`, sem alterar a configuração global.

## Continuidade

Projeto concluído, versão 0.1.0 aprovada pelo mantenedor. Não há novas validações
exigidas para encerrar esta entrega. Blank, Grid e diagnóstico na homologação
publicada tiveram resultados positivos; a biblioteca foi publicada em produção.
O mantenedor dispensou os testes adicionais. O registro de homologação distingue
os resultados efetivamente informados dos cenários não executados.
O primeiro commit integra o fechamento autorizado pelo mantenedor. O agente
não acessou o servidor remoto; as cópias e os testes no Scriptcase/Oracle foram
executados pelo mantenedor.
