# Onartline Multisite Domain Mapping

Associez des domaines personnalisés à des sites au sein d'un réseau WordPress Multisite.

| | |
|---|---|
| **Nécessite WordPress** | 7.0 ou supérieur |
| **Nécessite PHP** | 8.3 ou supérieur |
| **Testé jusqu'à** | 7.1 |
| **Licence** | GPLv2 ou ultérieure |

## Description

Onartline Multisite Domain Mapping permet d'associer n'importe quel domaine à un site au sein de votre réseau WordPress Multisite. Léger et facile à configurer, il s'adresse aussi bien aux débutants qu'aux administrateurs expérimentés.

### Fonctionnalités

- Association de plusieurs domaines à n'importe quel site du réseau
- Définition d'un domaine principal avec redirection automatique
- Application forcée de HTTPS par domaine ou globalement
- Prise en charge de la redirection 301 pour les domaines secondaires
- Affichage des informations DNS pour les administrateurs de site
- Gestion des domaines au niveau du site (facultatif, contrôlé par le Super Administrateur)

### Prérequis

- PHP 8.3 ou supérieur
- WordPress 7.0 ou supérieur
- Installation WordPress Multisite

## Installation

### Important – À lire avant l'installation

Ce plugin est recommandé pour les **nouvelles installations de réseau WordPress Multisite**.

L'installation de Onartline Multisite Domain Mapping sur un **réseau Multisite déjà existant et actif n'est pas recommandée** et se fait entièrement à vos propres risques. Elle peut interférer avec des configurations de domaine existantes, des redirections ou d'autres plugins ayant des fonctionnalités similaires.

Si vous gérez déjà un réseau Multisite et souhaitez utiliser ce plugin, il est fortement recommandé de mettre en place au préalable une **nouvelle installation Multisite**, puis de **migrer ou importer votre contenu et vos données existants** vers cette nouvelle installation, plutôt que d'ajouter ce plugin à votre réseau actif actuel.

### 1. Téléverser le plugin

Téléversez le dossier `onartline-multisite-domain-mapping` dans `/wp-content/plugins/` ou installez-le directement depuis l'administration réseau de WordPress sous **Extensions → Ajouter**.

### 2. Activer le plugin

Activez le plugin depuis **Administration du réseau → Extensions → Activer pour le réseau**.

### 3. Configurer sunrise.php

Onartline Multisite Domain Mapping nécessite que `sunrise.php` soit chargé avant l'initialisation de WordPress.

**Installation automatique :**
Si `wp-content/` est accessible en écriture, le plugin copie automatiquement `sunrise.php` lors de l'activation. Un message de succès s'affiche dans l'administration réseau.

**Installation manuelle :**
Si la copie automatique échoue, copiez `sunrise.php` manuellement :

1. Copiez `sunrise.php` depuis le dossier du plugin vers `/wp-content/sunrise.php`
2. Ajoutez la ligne suivante à votre `wp-config.php` – juste avant `require_once ABSPATH . 'wp-settings.php';` :

define( 'SUNRISE', true );

### 4. Configurer wp-config.php

Assurez-vous que la ligne suivante est présente dans votre `wp-config.php` :

define( 'SUNRISE', true );

### 5. ⚠️ Utilisateurs Plesk – Désactiver le « Domaine préféré »

Si votre serveur utilise Plesk, vous **devez** désactiver le paramètre « Domaine préféré » pour chaque domaine que vous souhaitez associer. Sinon, Plesk interceptera la redirection avant que WordPress ne puisse la traiter, ce qui provoquera des boucles de redirection ou des associations incorrectes.

1. Connectez-vous à Plesk
2. Accédez à **Sites Web & Domaines → votre domaine → Paramètres d'hébergement**
3. Réglez **Domaine préféré** sur **Aucun**
4. Enregistrez les paramètres

### 6. Ajouter votre première association de domaine

1. Accédez à **Administration du réseau → Domain Mapping → Ajouter un domaine**
2. Sélectionnez le site cible
3. Saisissez le domaine (sans `http://` ni `https://`)
4. Définissez-le éventuellement comme domaine principal et activez HTTPS
5. Enregistrez

### 7. Configurer le DNS

Faites pointer votre domaine vers votre serveur en configurant les enregistrements DNS suivants :

- **Enregistrement A** – Nom : `@` – Valeur : l'adresse IP de votre serveur
- **Enregistrement CNAME** – Nom : `www` – Valeur : votre domaine principal ou le CNAME du serveur

Les valeurs requises sont affichées dans **Administration du réseau → Domain Mapping → Paramètres**.

### 8. Désinstallation

Lorsque vous désactivez et supprimez Onartline Multisite Domain Mapping depuis **Administration du réseau → Extensions**, le plugin supprime automatiquement :

- Les fichiers du plugin
- Le fichier `sunrise.php` de `/wp-content/`
- Les tables de la base de données (uniquement si « Supprimer les données lors de la désinstallation » était activé dans les paramètres du plugin)

**Important – étape manuelle requise**

Le plugin **ne peut pas supprimer automatiquement** la ligne suivante de votre `wp-config.php` :

define( 'SUNRISE', true );

Cette ligne a été ajoutée manuellement lors de l'installation et doit également être **supprimée manuellement** après la désinstallation du plugin. Si cette ligne reste dans `wp-config.php` après la suppression de `sunrise.php`, WordPress tentera de charger un fichier qui n'existe plus, ce qui entraînera des avertissements tels que :

Warning: include_once(.../wp-content/sunrise.php): Failed to open stream: No such file or directory

et éventuellement des erreurs « headers already sent » sur la page de connexion ou ailleurs.

**Pour résoudre ce problème :** Ouvrez votre `wp-config.php`, supprimez (ou commentez) la ligne `define( 'SUNRISE', true );`, puis enregistrez le fichier.

## Captures d'écran

1. Ajouter un domaine – formulaire de création de nouvelles associations de domaine
2. Vue d'ensemble du Domain Mapping – gestion de tous les domaines associés
3. Paramètres du Domain Mapping – HTTPS, redirections et informations DNS

## Journal des modifications

### 1.0.0
- Version initiale

## Foire aux questions

**Puis-je installer ce plugin sur un réseau Multisite déjà existant et actif ?**
Cela n'est pas recommandé et se fait entièrement à vos propres risques. Onartline Multisite Domain Mapping est conçu pour les nouvelles installations Multisite. Si vous gérez déjà un réseau Multisite actif, il est fortement recommandé de mettre en place au préalable une nouvelle installation et d'y migrer votre contenu existant, plutôt que d'ajouter ce plugin à votre réseau actuel. Consultez la note en début de section **Installation** pour plus de détails sur l'approche recommandée.

**Le domaine redirige en boucle – que dois-je faire ?**
Vérifiez si « Domaine préféré » est configuré dans Plesk. Réglez-le sur « Aucun ». Vérifiez également que `define( 'SUNRISE', true );` est présent dans `wp-config.php`.

Si vous utilisez la fonctionnalité de redirection 301 du plugin, vérifiez les paramètres d'hébergement pour le domaine concerné (par exemple dans Plesk, cPanel ou d'autres panneaux d'hébergement) et désactivez si nécessaire les règles de redirection existantes.

Si des redirections 301 sont déjà configurées au niveau de l'hébergement pour ce domaine et que vous souhaitez les conserver, désactivez plutôt l'option de redirection 301 dans les paramètres du plugin – sinon une boucle de redirection se produira.

**sunrise.php n'a pas été copié automatiquement – que faire maintenant ?**
Copiez `sunrise.php` manuellement depuis le dossier du plugin vers `/wp-content/sunrise.php` et ajoutez `define( 'SUNRISE', true );` à votre `wp-config.php`.

**Le plugin ne fonctionne pas sur mon site – pourquoi ?**
Onartline Multisite Domain Mapping nécessite une installation WordPress Multisite et PHP 8.3+. Les installations à site unique ne sont pas prises en charge.

**Les administrateurs de site peuvent-ils gérer leurs propres domaines ?**
Oui – le Super Administrateur peut activer cette fonctionnalité sous **Administration du réseau → Domain Mapping → Paramètres → Domain Mapping pour administrateurs de site**.

**Le plugin prend-il en charge les mises à jour automatiques ?**
Oui – une fois publié sur le répertoire des extensions WordPress, les mises à jour automatiques sont entièrement prises en charge.

**J'ai désinstallé le plugin, mais je vois maintenant des erreurs concernant sunrise.php ou « headers already sent » – que s'est-il passé ?**
Cela se produit si la ligne `define( 'SUNRISE', true );` n'a pas été supprimée de `wp-config.php` après la désinstallation du plugin. Comme `sunrise.php` n'existe plus après la désinstallation, WordPress échoue lorsqu'il tente de le charger. Il suffit de supprimer cette ligne de `wp-config.php` pour résoudre le problème.
