# Onartline Multisite Domain Mapping

Associa domini personalizzati ai siti all'interno di una rete WordPress Multisito.

| | |
|---|---|
| **Richiede WordPress** | 7.0 o superiore |
| **Richiede PHP** | 8.3 o superiore |
| **Testato fino a** | 7.1 |
| **Licenza** | GPLv2 o successiva |

## Descrizione

Onartline Multisite Domain Mapping consente di associare qualsiasi dominio a un sito all'interno della tua rete WordPress Multisito. È leggero, facile da configurare e adatto sia ai principianti che agli amministratori esperti.

### Funzionalità

- Associazione di più domini a qualsiasi sito della rete
- Impostazione di un dominio principale con reindirizzamento automatico
- Forzatura di HTTPS per dominio o globalmente
- Supporto per il reindirizzamento 301 per i domini secondari
- Visualizzazione delle informazioni DNS per gli amministratori del sito
- Gestione dei domini a livello di sito (opzionale, controllata dal Super Amministratore)

### Requisiti

- PHP 8.3 o superiore
- WordPress 7.0 o superiore
- Installazione WordPress Multisito

## Installazione

### Importante – Leggere prima dell'installazione

Questo plugin è consigliato per **nuove installazioni di rete WordPress Multisito**.

L'installazione di Onartline Multisite Domain Mapping su una **rete Multisito già esistente e attiva non è consigliata** ed è effettuata interamente a proprio rischio. Potrebbe interferire con configurazioni di dominio esistenti, reindirizzamenti o altri plugin con funzionalità simili.

Se già gestisci una rete Multisito e desideri utilizzare questo plugin, è fortemente consigliato configurare prima una **nuova installazione Multisito**, per poi **migrare o importare i contenuti e i dati esistenti** in quella nuova installazione, anziché aggiungere questo plugin alla rete attuale già attiva.

### 1. Caricare il plugin

Carica la cartella `onartline-multisite-domain-mapping` in `/wp-content/plugins/` oppure installalo direttamente dall'amministrazione di rete di WordPress in **Plugin → Aggiungi nuovo**.

### 2. Attivare il plugin

Attiva il plugin da **Amministrazione di rete → Plugin → Attiva per la rete**.

### 3. Configurare sunrise.php

Onartline Multisite Domain Mapping richiede che `sunrise.php` venga caricato prima dell'inizializzazione di WordPress.

**Installazione automatica:**
Se `wp-content/` è scrivibile, il plugin copia automaticamente `sunrise.php` durante l'attivazione. Verrà visualizzato un messaggio di successo nell'amministrazione di rete.

**Installazione manuale:**
Se la copia automatica fallisce, copia `sunrise.php` manualmente:

1. Copia `sunrise.php` dalla cartella del plugin in `/wp-content/sunrise.php`
2. Aggiungi la seguente riga al tuo `wp-config.php` – subito prima di `require_once ABSPATH . 'wp-settings.php';`:

define( 'SUNRISE', true );

### 4. Configurare wp-config.php

Assicurati che la seguente riga sia presente nel tuo `wp-config.php`:

define( 'SUNRISE', true );

### 5. ⚠️ Utenti Plesk – Disattivare il "Dominio preferito"

Se il tuo server utilizza Plesk, **devi** disattivare l'impostazione "Dominio preferito" per ogni dominio che desideri associare. In caso contrario, Plesk intercetterà il reindirizzamento prima che WordPress possa gestirlo, causando loop di reindirizzamento o associazioni non funzionanti.

1. Accedi a Plesk
2. Vai su **Siti web e domini → il tuo dominio → Impostazioni hosting**
3. Imposta **Dominio preferito** su **Nessuno**
4. Salva le impostazioni

### 6. Aggiungere la prima associazione di dominio

1. Vai su **Amministrazione di rete → Domain Mapping → Aggiungi dominio**
2. Seleziona il sito di destinazione
3. Inserisci il dominio (senza `http://` o `https://`)
4. Facoltativamente impostalo come Dominio Principale e attiva HTTPS
5. Salva

### 7. Configurare il DNS

Punta il tuo dominio al tuo server impostando i seguenti record DNS:

- **Record A** – Nome: `@` – Valore: l'indirizzo IP del tuo server
- **Record CNAME** – Nome: `www` – Valore: il tuo dominio principale o il CNAME del server

I valori necessari vengono mostrati in **Amministrazione di rete → Domain Mapping → Impostazioni**.

### 8. Disinstallazione

Quando disattivi ed elimini Onartline Multisite Domain Mapping da **Amministrazione di rete → Plugin**, il plugin rimuove automaticamente:

- I file del plugin
- Il file `sunrise.php` da `/wp-content/`
- Le tabelle del database (solo se "Elimina i dati alla disinstallazione" era stato attivato nelle impostazioni del plugin)

**Importante – è richiesto un passaggio manuale**

Il plugin **non può rimuovere automaticamente** la seguente riga dal tuo `wp-config.php`:

define( 'SUNRISE', true );

Questa riga è stata aggiunta manualmente durante l'installazione e deve essere **rimossa manualmente** anche dopo la disinstallazione del plugin. Se questa riga rimane nel `wp-config.php` dopo l'eliminazione di `sunrise.php`, WordPress tenterà di caricare un file che non esiste più, causando avvisi come il seguente:

Warning: include_once(.../wp-content/sunrise.php): Failed to open stream: No such file or directory

e possibilmente errori "headers already sent" nella pagina di login o altrove.

**Per risolvere:** Apri il tuo `wp-config.php` ed elimina (o commenta) la riga `define( 'SUNRISE', true );`, quindi salva il file.

## Screenshot

1. Aggiungi dominio – modulo per la creazione di nuove associazioni di dominio
2. Panoramica Domain Mapping – gestione di tutti i domini associati
3. Impostazioni Domain Mapping – HTTPS, reindirizzamenti e informazioni DNS

## Changelog

### 1.0.0
- Rilascio iniziale

## Domande frequenti

**Posso installare questo plugin su una rete Multisito già esistente e attiva?**
Questo non è consigliato ed è effettuato interamente a proprio rischio. Onartline Multisite Domain Mapping è progettato per nuove installazioni Multisito. Se già gestisci una rete Multisito attiva, è fortemente consigliato configurare prima una nuova installazione e migrare lì i contenuti esistenti, anziché aggiungere questo plugin alla rete attuale. Consulta la nota all'inizio della sezione **Installazione** per maggiori dettagli sull'approccio consigliato.

**Il dominio reindirizza in loop – cosa devo fare?**
Verifica se in Plesk è impostato il "Dominio preferito". Impostalo su "Nessuno". Verifica inoltre che `define( 'SUNRISE', true );` sia presente in `wp-config.php`.

Se utilizzi la funzione di reindirizzamento 301 del plugin, controlla le impostazioni di hosting per quel dominio specifico (ad esempio in Plesk, cPanel o altri pannelli di hosting) e disattiva eventuali regole di reindirizzamento esistenti, se necessario.

Se a livello di hosting sono già configurati reindirizzamenti 301 per quel dominio e desideri mantenerli, disattiva invece l'opzione di reindirizzamento 301 nelle impostazioni del plugin – altrimenti si verificherà un loop di reindirizzamento.

**sunrise.php non è stato copiato automaticamente – cosa devo fare?**
Copia `sunrise.php` manualmente dalla cartella del plugin in `/wp-content/sunrise.php` e aggiungi `define( 'SUNRISE', true );` al tuo `wp-config.php`.

**Il plugin non funziona sul mio sito – perché?**
Onartline Multisite Domain Mapping richiede un'installazione WordPress Multisito e PHP 8.3+. Le installazioni a sito singolo non sono supportate.

**Gli amministratori del sito possono gestire i propri domini?**
Sì – il Super Amministratore può attivare questa opzione in **Amministrazione di rete → Domain Mapping → Impostazioni → Domain Mapping per amministratori del sito**.

**Il plugin supporta gli aggiornamenti automatici?**
Sì – una volta pubblicato nel repository dei plugin di WordPress, gli aggiornamenti automatici sono pienamente supportati.

**Ho disinstallato il plugin, ma ora vedo errori relativi a sunrise.php o "headers already sent" – cosa è successo?**
Questo accade se la riga `define( 'SUNRISE', true );` non è stata rimossa dal `wp-config.php` dopo la disinstallazione del plugin. Poiché `sunrise.php` non esiste più dopo la disinstallazione, WordPress fallisce quando tenta di caricarlo. Rimuovi semplicemente questa riga da `wp-config.php` per risolvere il problema.