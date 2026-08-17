# Onartline Multisite Domain Mapping

Koppel aangepaste domeinen aan sites binnen een WordPress Multisite-netwerk.

| | |
|---|---|
| **Vereist WordPress** | 7.0 of hoger |
| **Vereist PHP** | 8.3 of hoger |
| **Getest tot** | 7.1 |
| **Licentie** | GPLv2 of later |

## Beschrijving

Onartline Multisite Domain Mapping maakt het mogelijk om elk gewenst domein te koppelen aan een site binnen uw WordPress Multisite-netwerk. De plugin is lichtgewicht, eenvoudig te configureren en geschikt voor zowel beginners als ervaren beheerders.

### Functies

- Koppeling van meerdere domeinen aan elke site binnen het netwerk
- Instellen van een primair domein met automatische omleiding
- Afdwingen van HTTPS per domein of netwerkbreed
- Ondersteuning voor 301-omleiding voor niet-primaire domeinen
- Weergave van DNS-informatie voor sitebeheerders
- Domeinbeheer op sitenniveau (optioneel, te bepalen door de Super Admin)

### Vereisten

- PHP 8.3 of hoger
- WordPress 7.0 of hoger
- WordPress Multisite-installatie

## Installatie

### Belangrijk – lees dit voor de installatie

Deze plugin wordt aanbevolen voor **nieuwe WordPress Multisite-netwerkinstallaties**.

Het installeren van Onartline Multisite Domain Mapping op een **reeds bestaand, actief Multisite-netwerk wordt niet aanbevolen** en gebeurt volledig op eigen risico. Dit kan bestaande domeinconfiguraties, omleidingen of andere plugins met vergelijkbare functionaliteit verstoren.

Als u al een Multisite-netwerk beheert en deze plugin wilt gebruiken, wordt sterk aangeraden om eerst een **nieuwe, schone Multisite-installatie** op te zetten en vervolgens uw bestaande inhoud en gegevens naar deze nieuwe installatie te **migreren of importeren**, in plaats van de plugin toe te voegen aan uw huidige actieve netwerk.

### 1. Plugin uploaden

Upload de map `onartline-multisite-domain-mapping` naar `/wp-content/plugins/` of installeer de plugin rechtstreeks via het WordPress-netwerkbeheer onder **Plugins → Nieuwe plugin**.

### 2. Plugin activeren

Activeer de plugin via **Netwerkbeheer → Plugins → Netwerkactiveren**.

### 3. sunrise.php instellen

Onartline Multisite Domain Mapping vereist dat `sunrise.php` wordt geladen voordat WordPress wordt geïnitialiseerd.

**Automatische installatie:**
Als `wp-content/` beschrijfbaar is, kopieert de plugin `sunrise.php` automatisch tijdens activering. U ontvangt een succesmelding in het netwerkbeheer.

**Handmatige installatie:**
Als het automatisch kopiëren mislukt, kopieert u `sunrise.php` handmatig:

1. Kopieer `sunrise.php` vanuit de pluginmap naar `/wp-content/sunrise.php`
2. Voeg de volgende regel toe aan uw `wp-config.php` – vlak vóór `require_once ABSPATH . 'wp-settings.php';`:

define( 'SUNRISE', true );

### 4. wp-config.php configureren

Zorg ervoor dat de volgende regel aanwezig is in uw `wp-config.php`:

define( 'SUNRISE', true );

### 5. ⚠️ Plesk-gebruikers – "Voorkeursdomein" uitschakelen

Als uw server Plesk gebruikt, **moet** u de instelling "Voorkeursdomein" uitschakelen voor elk domein dat u wilt koppelen. Anders onderschept Plesk de omleiding voordat WordPress deze kan verwerken, wat leidt tot omleidingslussen of onjuiste koppelingen.

1. Log in op Plesk
2. Ga naar **Websites & Domeinen → uw domein → Hostinginstellingen**
3. Stel **Voorkeursdomein** in op **Geen**
4. Sla de instellingen op

### 6. Uw eerste domeinkoppeling toevoegen

1. Ga naar **Netwerkbeheer → Domain Mapping → Domein toevoegen**
2. Selecteer de doelsite
3. Voer het domein in (zonder `http://` of `https://`)
4. Stel het optioneel in als Primair Domein en schakel HTTPS in
5. Sla op

### 7. DNS configureren

Wijs uw domein naar uw server door de volgende DNS-records in te stellen:

- **A-record** – Naam: `@` – Waarde: het IP-adres van uw server
- **CNAME-record** – Naam: `www` – Waarde: uw primaire domein of server-CNAME

De benodigde waarden worden weergegeven in **Netwerkbeheer → Domain Mapping → Instellingen**.

### 8. Verwijderen

Wanneer u Onartline Multisite Domain Mapping deactiveert en verwijdert via **Netwerkbeheer → Plugins**, verwijdert de plugin automatisch:

- De pluginbestanden
- Het bestand `sunrise.php` uit `/wp-content/`
- De databasetabellen (alleen als "Gegevens verwijderen bij verwijdering" was ingeschakeld in de plugininstellingen)

**Belangrijk – handmatige stap vereist**

De plugin **kan de volgende regel niet automatisch verwijderen** uit uw `wp-config.php`:

define( 'SUNRISE', true );

Deze regel is tijdens de installatie handmatig toegevoegd en moet na het verwijderen van de plugin ook **handmatig worden verwijderd**. Als deze regel in `wp-config.php` blijft staan nadat `sunrise.php` is verwijderd, probeert WordPress een bestand te laden dat niet meer bestaat, wat leidt tot waarschuwingen zoals:

Warning: include_once(.../wp-content/sunrise.php): Failed to open stream: No such file or directory

en mogelijk "headers already sent"-fouten op de inlogpagina of elders.

**Oplossing:** Open uw `wp-config.php` en verwijder (of commentarieer) de regel `define( 'SUNRISE', true );`, sla het bestand vervolgens op.

## Schermafbeeldingen

1. Domein toevoegen – formulier voor het aanmaken van nieuwe domeinkoppelingen
2. Domain Mapping-overzicht – beheer van alle gekoppelde domeinen
3. Domain Mapping-instellingen – HTTPS, omleidingen en DNS-informatie

## Changelog

### 1.0.0
- Eerste release

## Veelgestelde vragen

**Kan ik deze plugin installeren op een reeds bestaand, actief Multisite-netwerk?**
Dit wordt niet aanbevolen en gebeurt volledig op eigen risico. Onartline Multisite Domain Mapping is ontworpen voor nieuwe Multisite-installaties. Als u al een actief Multisite-netwerk beheert, wordt sterk aangeraden om eerst een nieuwe installatie op te zetten en uw bestaande inhoud daarnaar te migreren, in plaats van de plugin toe te voegen aan uw huidige netwerk. Zie de opmerking aan het begin van de sectie **Installatie** voor meer details over de aanbevolen aanpak.

**Het domein leidt in een lus om – wat moet ik doen?**
Controleer of "Voorkeursdomein" is ingesteld in Plesk. Stel dit in op "Geen". Controleer ook of `define( 'SUNRISE', true );` aanwezig is in `wp-config.php`.

Als u de 301-omleidingsfunctie van de plugin gebruikt, controleer dan de hostinginstellingen voor dat specifieke domein (bijvoorbeeld in Plesk, cPanel of andere hostingpanelen) en schakel indien nodig bestaande omleidingsregels uit.

Als er op hostingniveau al 301-omleidingen zijn ingesteld voor dat domein en u deze wilt behouden, schakel dan in plaats daarvan de 301-omleidingsoptie in de plugininstellingen uit – anders ontstaat er een omleidingslus.

**sunrise.php is niet automatisch gekopieerd – wat nu?**
Kopieer `sunrise.php` handmatig vanuit de pluginmap naar `/wp-content/sunrise.php` en voeg `define( 'SUNRISE', true );` toe aan uw `wp-config.php`.

**De plugin werkt niet op mijn website – waarom?**
Onartline Multisite Domain Mapping vereist een WordPress Multisite-installatie en PHP 8.3+. Installaties met één site worden niet ondersteund.

**Kunnen sitebeheerders hun eigen domeinen beheren?**
Ja – de Super Admin kan dit inschakelen via **Netwerkbeheer → Domain Mapping → Instellingen → Domain Mapping voor sitebeheerders**.

**Ondersteunt de plugin automatische updates?**
Ja – zodra de plugin is gepubliceerd in de WordPress-pluginmap, worden automatische updates volledig ondersteund.

**Ik heb de plugin verwijderd, maar zie nu fouten met betrekking tot sunrise.php of "headers already sent" – wat is er gebeurd?**
Dit gebeurt als de regel `define( 'SUNRISE', true );` niet is verwijderd uit `wp-config.php` na het verwijderen van de plugin. Omdat `sunrise.php` niet meer bestaat na verwijdering, mislukt WordPress bij het proberen te laden ervan. Verwijder simpelweg deze regel uit `wp-config.php` om het probleem op te lossen.