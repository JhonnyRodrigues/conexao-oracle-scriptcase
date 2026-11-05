# Instalação, atualização e reversão

Versão: **0.1.0**, aprovada pelo mantenedor em 05/11/2026.
Os resultados e as dispensas estão no [registro de homologação](HOMOLOGACAO.md).

## Preparar no desenvolvimento

Requisitos: PHP 8.1 ou superior, `mbstring` e Composer disponível no PATH.
O projeto não instala ADOdb ou OCI8: a conexão é fornecida pelo Scriptcase.

```sh
composer install
composer test
composer lint
composer check-psr4
composer package:build
```

O último comando imprime a pasta criada, por exemplo
`dist/0.1.0-20261029T180000Z-a1b2c3`. O sufixo identifica a preparação, em UTC;
a versão da biblioteca permanece em `VERSION`. Cada chamada cria uma pasta nova,
preservando as anteriores. Também é possível fornecer um destino ainda inexistente:

```sh
composer package:build -- dist/minha-entrega-0.1.0
composer package:verify -- dist/minha-entrega-0.1.0
```

O preparo copia os fontes e gera o autoload Composer de produção em uma pasta
isolada, sem alterar o `vendor/` de desenvolvimento e sem instalar PHPUnit no
pacote. Não exige rede para resolver dependências de produção, pois não há
pacotes externos de execução. Se falhar, a pasta incompleta fica preservada;
não instale uma pasta sem a verificação aprovada.

Estrutura da entrega:

```text
entrega/
  externa/semae/conexaoOracleScriptcase/
    autoload.php
    VERSION
    composer.json
    src/
    vendor/                # autoload de produção, já preparado
  scriptcase/interna/conexaoOracle.php
  scriptcase/exemplos/
  docs/
  manifest.json            # versão, data UTC e hashes SHA-256
  README.md
```

`package:verify` confere os hashes e executa outro processo PHP usando apenas o
autoload da entrega. Ele simula a macro de inclusão e o ADOdb para testar a
fábrica incorporada como método de uma classe, autoload repetido, consulta, comando e falha. Isso não compila macros
Scriptcase nem abre conexão Oracle. O manifesto detecta alterações acidentais;
não é uma assinatura de origem.

## Instalar no Scriptcase

1. Confirme PHP 8.1+, `mbstring`, ADOdb e OCI8 no ambiente que realmente executa
   a aplicação. Meça o charset efetivamente trocado com o ADOdb e o charset da
   aplicação; o charset de armazenamento do Oracle não basta.
2. Em Ferramentas → Bibliotecas externas, use a biblioteca pública `semae`.
   Copie a pasta completa `externa/semae/conexaoOracleScriptcase` da entrega para
   dentro dela, mantendo o nome `conexaoOracleScriptcase` e todas as subpastas,
   inclusive `vendor/`. Use o procedimento de cópia já adotado pela equipe; ZIP
   não é obrigatório. Nenhum Composer será executado no servidor.
   Na lista de bibliotecas externas, confirme também que a pública `semae` está
   selecionada para uso no projeto atual e clique em **Salvar as bibliotecas do
   projeto** se houver alteração. Essa seleção é diferente da flag da interna
   na aplicação e é descrita na
   [documentação de bibliotecas externas](https://www.scriptcase.com.br/docs/pt_br/v9/manual/07-ferramentas/04-bibliotecas-externas/).
3. Em Ferramentas → Bibliotecas internas, no escopo do projeto, cadastre o arquivo
   `scriptcase/interna/conexaoOracle.php` com o nome `conexaoOracle.php`. Ao usar
   um editor que já abre PHP, cole o conteúdo sem duplicar a tag `<?php`.
4. Na aplicação, em **Programação → Bibliotecas Internas → Projeto: DEV** (ou o
   nome do projeto utilizado), marque `conexaoOracle.php`. A interna fornecida
   contém apenas a função e carrega a externa dentro dela. Esse é o formato
   escolhido para esta biblioteca; a mensagem observada no Scriptcase não prova
   que toda macro fora de função seja proibida, pois a interna de e-mail do
   mantenedor usa esse formato e funciona. No evento, chame o método:

   ```php
   $db = $this->criarConexaoOracle($this->Db, 'Windows-1252', 'UTF-8');
   ```

5. Gere o código das aplicações piloto e publique incluindo a biblioteca externa.
   Confira no destino publicado que `conexaoOracleScriptcase/autoload.php`,
   `src/`, `vendor/` e `VERSION` foram enviados. Repita a homologação tanto no
   desenvolvimento quanto na aplicação publicada.

A interna carrega a externa dentro de `criarConexaoOracle()`, com
`sc_include_library('sys', 'semae', 'conexaoOracleScriptcase/autoload.php', true, true)`.
A função `criarConexaoOracle()` apenas constrói uma nova instância: não procura
`$this->Db`, não guarda instância global, não executa SQL e não captura falhas para
continuar o fluxo. O evento decide como apresentar a falha e interromper a operação.

O [diagnóstico de carregamento](../scriptcase/exemplos/diagnostico-carregamento.php.txt)
verifica `method_exists($this, 'criarConexaoOracle')` e chama a fábrica sem SQL.
`function_exists()` consulta funções globais e não verifica o método gerado pela
flag. As classes externas só precisam estar carregadas depois de chamar a fábrica.
Se o método existir, mas `BancoOracle` não for encontrado, o diagnóstico também
mostra a presença dos arquivos na pasta de execução, os autoloads incluídos e o
carregador do namespace. Use essas evidências antes de mudar a inclusão novamente.
Para inclusão PHP manual fora do modo de seleção do Scriptcase, use `require_once`
na interna; sua declaração não é protegida por um `if` externo à função.

Se a biblioteca cadastrada possuir `vendor/` mas a pasta de execução não a possuir,
a distribuição da externa ainda está incompleta. Habilitar a interna e distribuir
os arquivos externos são verificações distintas. A cópia manual para `_lib/` pode
servir a um diagnóstico pontual; a instalação só é homologada quando a geração e
a publicação preservam a externa completa, sem cópia manual por aplicação.
Confira primeiro a seleção da externa no projeto. Se ela já estiver selecionada,
um teste de atualização é reenviar o `autoload.php` original do pacote pela tela
de edição/upload da biblioteca externa e gerar novamente a aplicação. Verifique
se a árvore completa chegou ao destino; esse procedimento ainda depende de
confirmação no ambiente do mantenedor.

O uso de biblioteca pública e dos argumentos de inclusão segue a
[documentação de bibliotecas externas](https://www.scriptcase.net/documentation/en_us/v9/manual/07-tools/05-external-libraries/)
e a [referência de macros](https://www.scriptcase.net/docs/en_us/v9/manual/14-macros/02-macros/).
Os passos exatos de publicação devem ser conferidos na versão instalada.

## Atualizar e reverter

Antes de atualizar, preserve a pasta externa atualmente instalada, a biblioteca
interna, a versão e as evidências aprovadas em um diretório de backup fora do
caminho ativo. Guarde a entrega candidata inteira e seu manifesto.

Substitua a pasta completa pela candidata e atualize a interna correspondente;
evite misturar arquivos entre entregas. Gere e publique novamente as aplicações
piloto e execute os testes de regressão do roteiro. Se houver cache de PHP/OPcache,
use o procedimento da equipe para invalidá-lo antes de verificar a nova versão.

Para reverter, restaure a pasta externa e a interna preservadas, gere/publique as
aplicações conforme necessário e repita consulta, escrita e rollback nos pilotos.
Não sobrescreva nem descarte o backup até a aceitação da candidata. Registre qual
entrega foi ativada em cada ambiente e o resultado da reversão de teste.
