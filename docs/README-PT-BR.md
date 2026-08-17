# Onartline Multisite Domain Mapping

Associe domínios personalizados a sites dentro de uma rede WordPress Multisite.

| | |
|---|---|
| **Requer WordPress** | 7.0 ou superior |
| **Requer PHP** | 8.3 ou superior |
| **Testado até** | 7.1 |
| **Licença** | GPLv2 ou posterior |

## Descrição

Onartline Multisite Domain Mapping permite associar qualquer domínio a um site dentro da sua rede WordPress Multisite. É um plugin leve, fácil de configurar e adequado tanto para iniciantes quanto para administradores experientes.

### Recursos

- Associação de múltiplos domínios a qualquer site da rede
- Definição de um domínio principal com redirecionamento automático
- Forçar HTTPS por domínio ou globalmente
- Suporte a redirecionamento 301 para domínios secundários
- Exibição de informações de DNS para administradores do site
- Gerenciamento de domínios em nível de site (opcional, controlado pelo Super Administrador)

### Requisitos

- PHP 8.3 ou superior
- WordPress 7.0 ou superior
- Instalação WordPress Multisite

## Instalação

### Importante – Leia antes de instalar

Este plugin é recomendado para **novas instalações de rede WordPress Multisite**.

A instalação do Onartline Multisite Domain Mapping em uma **rede Multisite já existente e ativa não é recomendada** e é feita totalmente por sua conta e risco. Isso pode interferir em configurações de domínio existentes, redirecionamentos ou outros plugins com funcionalidades semelhantes.

Se você já gerencia uma rede Multisite e deseja usar este plugin, é altamente recomendável configurar primeiro uma **nova instalação Multisite limpa** e, em seguida, **migrar ou importar seu conteúdo e dados existentes** para essa nova instalação, em vez de adicionar este plugin à sua rede ativa atual.

### 1. Enviar o plugin

Envie a pasta `onartline-multisite-domain-mapping` para `/wp-content/plugins/` ou instale diretamente pela administração de rede do WordPress em **Plugins → Adicionar novo**.

### 2. Ativar o plugin

Ative o plugin em **Administração de Rede → Plugins → Ativar para a rede**.

### 3. Configurar o sunrise.php

O Onartline Multisite Domain Mapping exige que o `sunrise.php` seja carregado antes da inicialização do WordPress.

**Instalação automática:**
Se `wp-content/` tiver permissão de escrita, o plugin copia o `sunrise.php` automaticamente durante a ativação. Uma mensagem de sucesso será exibida na administração de rede.

**Instalação manual:**
Se a cópia automática falhar, copie o `sunrise.php` manualmente:

1. Copie o `sunrise.php` da pasta do plugin para `/wp-content/sunrise.php`
2. Adicione a seguinte linha ao seu `wp-config.php` – logo antes de `require_once ABSPATH . 'wp-settings.php';`:

define( 'SUNRISE', true );

### 4. Configurar o wp-config.php

Certifique-se de que a seguinte linha esteja presente no seu `wp-config.php`:

define( 'SUNRISE', true );

### 5. ⚠️ Usuários do Plesk – Desativar o "Domínio preferencial"

Se o seu servidor usa Plesk, você **deve** desativar a configuração "Domínio preferencial" para cada domínio que deseja associar. Caso contrário, o Plesk interceptará o redirecionamento antes que o WordPress possa processá-lo, causando loops de redirecionamento ou associações incorretas.

1. Faça login no Plesk
2. Vá para **Sites e Domínios → seu domínio → Configurações de hospedagem**
3. Defina **Domínio preferencial** como **Nenhum**
4. Salve as configurações

### 6. Adicionar sua primeira associação de domínio

1. Vá para **Administração de Rede → Domain Mapping → Adicionar domínio**
2. Selecione o site de destino
3. Insira o domínio (sem `http://` ou `https://`)
4. Opcionalmente, defina-o como Domínio Principal e ative o HTTPS
5. Salve

### 7. Configurar o DNS

Aponte seu domínio para o seu servidor configurando os seguintes registros DNS:

- **Registro A** – Nome: `@` – Valor: o endereço IP do seu servidor
- **Registro CNAME** – Nome: `www` – Valor: seu domínio principal ou o CNAME do servidor

Os valores necessários são exibidos em **Administração de Rede → Domain Mapping → Configurações**.

### 8. Desinstalação

Ao desativar e excluir o Onartline Multisite Domain Mapping em **Administração de Rede → Plugins**, o plugin remove automaticamente:

- Os arquivos do plugin
- O arquivo `sunrise.php` de `/wp-content/`
- As tabelas do banco de dados (somente se "Excluir dados na desinstalação" estava ativado nas configurações do plugin)

**Importante – etapa manual necessária**

O plugin **não consegue remover automaticamente** a seguinte linha do seu `wp-config.php`:

define( 'SUNRISE', true );

Essa linha foi adicionada manualmente durante a instalação e também deve ser **removida manualmente** após a desinstalação do plugin. Se essa linha permanecer no `wp-config.php` depois que o `sunrise.php` for excluído, o WordPress tentará carregar um arquivo que não existe mais, causando avisos como:

Warning: include_once(.../wp-content/sunrise.php): Failed to open stream: No such file or directory

e possivelmente erros de "headers already sent" na página de login ou em outras partes do site.

**Para resolver:** Abra o seu `wp-config.php` e exclua (ou comente) a linha `define( 'SUNRISE', true );`, depois salve o arquivo.

## Capturas de tela

1. Adicionar domínio – formulário para criar novas associações de domínio
2. Visão geral do Domain Mapping – gerenciamento de todos os domínios associados
3. Configurações do Domain Mapping – HTTPS, redirecionamentos e informações de DNS

## Changelog

### 1.0.0
- Lançamento inicial

## Perguntas frequentes

**Posso instalar este plugin em uma rede Multisite já existente e ativa?**
Isso não é recomendado e é feito totalmente por sua conta e risco. O Onartline Multisite Domain Mapping foi projetado para novas instalações Multisite. Se você já gerencia uma rede Multisite ativa, é altamente recomendável configurar primeiro uma nova instalação e migrar seu conteúdo existente para ela, em vez de adicionar este plugin à sua rede atual. Consulte a observação no início da seção **Instalação** para mais detalhes sobre a abordagem recomendada.

**O domínio está em loop de redirecionamento – o que devo fazer?**
Verifique se "Domínio preferencial" está configurado no Plesk. Defina-o como "Nenhum". Verifique também se `define( 'SUNRISE', true );` está presente no `wp-config.php`.

Se você estiver usando o recurso de redirecionamento 301 do plugin, verifique as configurações de hospedagem para esse domínio específico (por exemplo, no Plesk, cPanel ou outros painéis de hospedagem) e desative quaisquer regras de redirecionamento existentes, se necessário.

Se já existirem redirecionamentos 301 configurados no nível da hospedagem para esse domínio e você quiser mantê-los, desative a opção de redirecionamento 301 nas configurações do plugin – caso contrário, ocorrerá um loop de redirecionamento.

**O sunrise.php não foi copiado automaticamente – o que devo fazer agora?**
Copie o `sunrise.php` manualmente da pasta do plugin para `/wp-content/sunrise.php` e adicione `define( 'SUNRISE', true );` ao seu `wp-config.php`.

**O plugin não funciona no meu site – por quê?**
O Onartline Multisite Domain Mapping requer uma instalação WordPress Multisite e PHP 8.3+. Instalações de site único não são suportadas.

**Os administradores do site podem gerenciar seus próprios domínios?**
Sim – o Super Administrador pode ativar isso em **Administração de Rede → Domain Mapping → Configurações → Domain Mapping para administradores do site**.

**O plugin suporta atualizações automáticas?**
Sim – uma vez publicado no repositório de plugins do WordPress, as atualizações automáticas são totalmente suportadas.

**Desinstalei o plugin, mas agora vejo erros relacionados ao sunrise.php ou "headers already sent" – o que aconteceu?**
Isso acontece se a linha `define( 'SUNRISE', true );` não foi removida do `wp-config.php` após a desinstalação do plugin. Como o `sunrise.php` não existe mais após a desinstalação, o WordPress falha ao tentar carregá-lo. Basta remover essa linha do `wp-config.php` para resolver o problema.