# Onartline Multisite Domain Mapping

Associe domínios personalizados a sites dentro de uma rede WordPress Multisite.

| | |
|---|---|
| **Requer WordPress** | 7.0 ou superior |
| **Requer PHP** | 8.3 ou superior |
| **Testado até** | 7.1 |
| **Licença** | GPLv2 ou posterior |

## Descrição

O Onartline Multisite Domain Mapping permite associar qualquer domínio a um site dentro da sua rede WordPress Multisite. É um plugin leve, fácil de configurar e adequado tanto para principiantes como para administradores experientes.

### Funcionalidades

- Associação de vários domínios a qualquer site da rede
- Definição de um domínio principal com redirecionamento automático
- Forçar HTTPS por domínio ou globalmente
- Suporte a redirecionamento 301 para domínios secundários
- Apresentação de informações de DNS aos administradores do site
- Gestão de domínios ao nível do site (opcional, controlada pelo Super Administrador)

### Requisitos

- PHP 8.3 ou superior
- WordPress 7.0 ou superior
- Instalação WordPress Multisite

## Instalação

### Importante – Leia antes de instalar

Este plugin é recomendado para **novas instalações de rede WordPress Multisite**.

A instalação do Onartline Multisite Domain Mapping numa **rede Multisite já existente e ativa não é recomendada** e é feita inteiramente por sua conta e risco. Pode interferir com configurações de domínio existentes, redirecionamentos ou outros plugins com funcionalidades semelhantes.

Se já gere uma rede Multisite e pretende utilizar este plugin, é fortemente recomendado configurar primeiro uma **nova instalação Multisite** e, em seguida, **migrar ou importar o conteúdo e os dados existentes** para essa nova instalação, em vez de adicionar este plugin à sua rede ativa atual.

### 1. Carregar o plugin

Carregue a pasta `onartline-multisite-domain-mapping` para `/wp-content/plugins/` ou instale-o diretamente através da administração de rede do WordPress em **Plugins → Adicionar novo**.

### 2. Ativar o plugin

Ative o plugin em **Administração de Rede → Plugins → Ativar para a rede**.

### 3. Configurar o sunrise.php

O Onartline Multisite Domain Mapping requer que o `sunrise.php` seja carregado antes da inicialização do WordPress.

**Instalação automática:**
Se `wp-content/` tiver permissões de escrita, o plugin copia automaticamente o `sunrise.php` durante a ativação. Será apresentada uma mensagem de sucesso na administração de rede.

**Instalação manual:**
Se a cópia automática falhar, copie o `sunrise.php` manualmente:

1. Copie o `sunrise.php` da pasta do plugin para `/wp-content/sunrise.php`
2. Adicione a seguinte linha ao seu `wp-config.php` – imediatamente antes de `require_once ABSPATH . 'wp-settings.php';`:

define( 'SUNRISE', true );

### 4. Configurar o wp-config.php

Certifique-se de que a seguinte linha está presente no seu `wp-config.php`:

define( 'SUNRISE', true );

### 5. ⚠️ Utilizadores Plesk – Desativar o "Domínio preferido"

Se o seu servidor utiliza Plesk, **tem de** desativar a definição "Domínio preferido" para cada domínio que pretende associar. Caso contrário, o Plesk irá intercetar o redirecionamento antes de o WordPress o poder processar, causando ciclos de redirecionamento ou associações incorretas.

1. Inicie sessão no Plesk
2. Aceda a **Sites Web e Domínios → o seu domínio → Definições de alojamento**
3. Defina **Domínio preferido** como **Nenhum**
4. Guarde as definições

### 6. Adicionar a sua primeira associação de domínio

1. Aceda a **Administração de Rede → Domain Mapping → Adicionar domínio**
2. Selecione o site de destino
3. Introduza o domínio (sem `http://` ou `https://`)
4. Opcionalmente, defina-o como Domínio Principal e ative o HTTPS
5. Guarde

### 7. Configurar o DNS

Aponte o seu domínio para o seu servidor configurando os seguintes registos DNS:

- **Registo A** – Nome: `@` – Valor: o endereço IPv4 do seu servidor
- **Registo AAAA** – Nome: `@` – Valor: o endereço IPv6 do seu servidor (opcional, se disponível)
- **Registo CNAME** – Nome: `www` – Valor: o seu domínio principal ou o CNAME do servidor

Os valores necessários são apresentados em **Administração de Rede → Domain Mapping → Definições**.

### 8. Desinstalação

Ao desativar e eliminar o Onartline Multisite Domain Mapping em **Administração de Rede → Plugins**, o plugin remove automaticamente:

- Os ficheiros do plugin
- O ficheiro `sunrise.php` de `/wp-content/`
- As tabelas da base de dados (apenas se "Eliminar dados na desinstalação" estava ativado nas definições do plugin)

**Importante – é necessário um passo manual**

O plugin **não consegue remover automaticamente** a seguinte linha do seu `wp-config.php`:

define( 'SUNRISE', true );

Esta linha foi adicionada manualmente durante a instalação e também deve ser **removida manualmente** após a desinstalação do plugin. Se esta linha permanecer no `wp-config.php` depois de o `sunrise.php` ser eliminado, o WordPress tentará carregar um ficheiro que já não existe, causando avisos como:

Warning: include_once(.../wp-content/sunrise.php): Failed to open stream: No such file or directory

e possivelmente erros de "headers already sent" na página de início de sessão ou noutras partes do site.

**Para resolver:** Abra o seu `wp-config.php` e elimine (ou comente) a linha `define( 'SUNRISE', true );`, depois guarde o ficheiro.

## Capturas de ecrã

1. Adicionar domínio – formulário para criar novas associações de domínio
2. Vista geral do Domain Mapping – gestão de todos os domínios associados
3. Definições do Domain Mapping – HTTPS, redirecionamentos e informações de DNS

## Changelog

### 1.0.1
- Correção: Corrigida a invalidação de cache após guardar um novo domínio nas definições do site, para que os domínios recém-adicionados sejam resolvidos corretamente de imediato.
- Correção: Resolvido um erro de análise (parse error) causado por métodos duplicados definidos fora da classe omdm_Login_Handler.
- Correção: O mapeamento de domínios agora exclui corretamente os pedidos de início de sessão, AJAX e REST.
- Correção: Corrigidos os valores predefinidos em set_default_options().
- Novo: Adicionado suporte a IPv6 no campo de informações de DNS na página de definições.
- Novo: Introduzida uma validação de formato consistente para entradas IPv4, IPv6 e CNAME.

### 1.0.0
- Lançamento inicial

## Perguntas frequentes

**Posso instalar este plugin numa rede Multisite já existente e ativa?**
Isto não é recomendado e é feito inteiramente por sua conta e risco. O Onartline Multisite Domain Mapping foi concebido para novas instalações Multisite. Se já gere uma rede Multisite ativa, é fortemente recomendado configurar primeiro uma nova instalação e migrar o seu conteúdo existente para lá, em vez de adicionar este plugin à sua rede atual. Consulte a nota no início da secção **Instalação** para mais detalhes sobre a abordagem recomendada.

**O domínio está em ciclo de redirecionamento – o que devo fazer?**
Verifique se "Domínio preferido" está configurado no Plesk. Defina-o como "Nenhum". Verifique também se `define( 'SUNRISE', true );` está presente no `wp-config.php`.

Se estiver a utilizar a funcionalidade de redirecionamento 301 do plugin, verifique as definições de alojamento para esse domínio específico (por exemplo, no Plesk, cPanel ou outros painéis de alojamento) e desative quaisquer regras de redirecionamento existentes, se necessário.

Se já existirem redirecionamentos 301 configurados ao nível do alojamento para esse domínio e pretender mantê-los, desative em vez disso a opção de redirecionamento 301 nas definições do plugin – caso contrário, ocorrerá um ciclo de redirecionamento.

**O sunrise.php não foi copiado automaticamente – o que faço agora?**
Copie o `sunrise.php` manualmente da pasta do plugin para `/wp-content/sunrise.php` e adicione `define( 'SUNRISE', true );` ao seu `wp-config.php`.

**O plugin não funciona no meu site – porquê?**
O Onartline Multisite Domain Mapping requer uma instalação WordPress Multisite e PHP 8.3+. Instalações de site único não são suportadas.

**Os administradores do site podem gerir os seus próprios domínios?**
Sim – o Super Administrador pode ativar isto em **Administração de Rede → Domain Mapping → Definições → Domain Mapping para administradores do site**.

**O plugin suporta atualizações automáticas?**
Sim – assim que publicado no repositório de plugins do WordPress, as atualizações automáticas são totalmente suportadas.

**Desinstalei o plugin, mas agora vejo erros relacionados com sunrise.php ou "headers already sent" – o que aconteceu?**
Isto acontece se a linha `define( 'SUNRISE', true );` não foi removida do `wp-config.php` após a desinstalação do plugin. Como o `sunrise.php` já não existe após a desinstalação, o WordPress falha ao tentar carregá-lo. Basta remover esta linha do `wp-config.php` para resolver o problema.