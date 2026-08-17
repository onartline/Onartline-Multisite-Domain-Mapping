# Onartline Multisite Domain Mapping

Verknüpft benutzerdefinierte Domains mit Websites in einem WordPress-Multisite-Netzwerk.

| | |
|---|---|
| **Benötigt WordPress** | 7.0 oder höher |
| **Benötigt PHP** | 8.3 oder höher |
| **Getestet bis** | 7.1 |
| **Lizenz** | GPLv2 oder später |

## Beschreibung

Onartline Multisite Domain Mapping ermöglicht es, jede beliebige Domain mit einer Website in Ihrem WordPress-Multisite-Netzwerk zu verknüpfen. Das Plugin ist schlank, einfach zu konfigurieren und sowohl für Einsteiger als auch für erfahrene Administratoren geeignet.

### Funktionen

- Verknüpfung mehrerer Domains mit einer beliebigen Website im Netzwerk
- Festlegung einer Primärdomain mit automatischer Weiterleitung
- Erzwingung von HTTPS pro Domain oder global
- Unterstützung von 301-Weiterleitungen für nicht-primäre Domains
- Anzeige von DNS-Informationen für Website-Administratoren
- Domain-Verwaltung auf Website-Ebene (optional, vom Super Admin steuerbar)

### Voraussetzungen

- PHP 8.3 oder höher
- WordPress 7.0 oder höher
- WordPress-Multisite-Installation

## Installation

### Wichtig – bitte vor der Installation lesen

Dieses Plugin wird für **neue WordPress-Multisite-Netzwerkinstallationen** empfohlen.

Die Installation von Onartline Multisite Domain Mapping auf einem **bereits bestehenden, aktiven Multisite-Netzwerk wird nicht empfohlen** und erfolgt vollständig auf eigenes Risiko. Sie kann bestehende Domain-Konfigurationen, Weiterleitungen oder andere Plugins mit ähnlicher Funktionalität stören.

Falls Sie bereits ein Multisite-Netzwerk betreiben und dieses Plugin nutzen möchten, wird dringend empfohlen, zunächst eine **neue, frische Multisite-Installation** einzurichten und anschließend Ihre bestehenden Inhalte und Daten in diese neue Installation zu **migrieren oder importieren**, anstatt das Plugin zu Ihrem aktuellen, aktiven Netzwerk hinzuzufügen.

### 1. Plugin hochladen

Laden Sie den Ordner `onartline-multisite-domain-mapping` in `/wp-content/plugins/` hoch oder installieren Sie das Plugin direkt über den WordPress-Netzwerk-Administrator unter **Plugins → Installieren**.

### 2. Plugin aktivieren

Aktivieren Sie das Plugin über **Netzwerkverwaltung → Plugins → Netzwerkaktivierung**.

### 3. sunrise.php einrichten

Onartline Multisite Domain Mapping erfordert, dass `sunrise.php` geladen wird, bevor WordPress initialisiert wird.

**Automatische Installation:**
Wenn `wp-content/` beschreibbar ist, kopiert das Plugin `sunrise.php` bei der Aktivierung automatisch. Sie erhalten eine Erfolgsmeldung in der Netzwerkverwaltung.

**Manuelle Installation:**
Falls das automatische Kopieren fehlschlägt, kopieren Sie `sunrise.php` manuell:

1. Kopieren Sie `sunrise.php` aus dem Plugin-Ordner nach `/wp-content/sunrise.php`
2. Fügen Sie folgende Zeile in Ihre `wp-config.php` ein – direkt vor `require_once ABSPATH . 'wp-settings.php';`:

define( 'SUNRISE', true );

### 4. wp-config.php konfigurieren

Stellen Sie sicher, dass folgende Zeile in Ihrer `wp-config.php` vorhanden ist:

define( 'SUNRISE', true );

### 5. ⚠️ Plesk-Nutzer – „Bevorzugte Domain“ deaktivieren

Falls Ihr Server unter Plesk läuft, **müssen** Sie die Einstellung „Bevorzugte Domain“ für jede zu verknüpfende Domain deaktivieren. Andernfalls fängt Plesk die Weiterleitung ab, bevor WordPress sie verarbeiten kann, was zu Weiterleitungsschleifen oder fehlerhaften Verknüpfungen führt.

1. Bei Plesk anmelden
2. Zu **Websites & Domains → Ihre Domain → Hosting-Einstellungen** gehen
3. **Bevorzugte Domain** auf **Keine** setzen
4. Einstellungen speichern

### 6. Erste Domain-Verknüpfung hinzufügen

1. Zu **Netzwerkverwaltung → Domain Mapping → Domain hinzufügen** gehen
2. Zielseite auswählen
3. Domain eingeben (ohne `http://` oder `https://`)
4. Optional als Primärdomain festlegen und HTTPS aktivieren
5. Speichern

### 7. DNS konfigurieren

Richten Sie Ihre Domain auf Ihren Server aus, indem Sie folgende DNS-Einträge setzen:

- **A-Eintrag** – Name: `@` – Wert: Ihre Server-IP-Adresse
- **CNAME-Eintrag** – Name: `www` – Wert: Ihre Primärdomain oder Server-CNAME

Die benötigten Werte werden unter **Netzwerkverwaltung → Domain Mapping → Einstellungen** angezeigt.

### 8. Deinstallation

Wenn Sie Onartline Multisite Domain Mapping über **Netzwerkverwaltung → Plugins** deaktivieren und löschen, entfernt das Plugin automatisch:

- Die Plugin-Dateien
- Die Datei `sunrise.php` aus `/wp-content/`
- Die Datenbanktabellen (nur wenn „Daten bei Deinstallation löschen“ in den Plugin-Einstellungen aktiviert war)

**Wichtig – manueller Schritt erforderlich**

Das Plugin **kann folgende Zeile nicht automatisch** aus Ihrer `wp-config.php` entfernen:

define( 'SUNRISE', true );

Diese Zeile wurde während der Installation manuell hinzugefügt und muss nach der Deinstallation des Plugins ebenfalls **manuell entfernt werden**. Falls diese Zeile nach dem Löschen von `sunrise.php` in der `wp-config.php` verbleibt, versucht WordPress, eine nicht mehr vorhandene Datei zu laden, was zu Warnungen wie folgender führt:

Warning: include_once(.../wp-content/sunrise.php): Failed to open stream: No such file or directory

und möglicherweise zu „headers already sent“-Fehlern auf der Login-Seite oder an anderer Stelle.

**Lösung:** Öffnen Sie Ihre `wp-config.php` und entfernen (oder kommentieren) Sie die Zeile `define( 'SUNRISE', true );`, speichern Sie anschließend die Datei.

## Screenshots

1. Domain hinzufügen – Formular zum Erstellen neuer Domain-Verknüpfungen
2. Domain-Mapping-Übersicht – Verwaltung aller verknüpften Domains
3. Domain-Mapping-Einstellungen – HTTPS, Weiterleitungen und DNS-Informationen

## Changelog

### 1.0.0
- Erstveröffentlichung

## Häufig gestellte Fragen

**Kann ich dieses Plugin auf einem bestehenden, aktiven Multisite-Netzwerk installieren?**
Dies wird nicht empfohlen und erfolgt vollständig auf eigenes Risiko. WS Domain Mapping wurde für neue Multisite-Installationen entwickelt. Falls Sie bereits ein aktives Multisite-Netzwerk betreiben, wird dringend empfohlen, zunächst eine frische Installation einzurichten und Ihre bestehenden Inhalte dorthin zu migrieren, anstatt das Plugin zu Ihrem aktuellen Netzwerk hinzuzufügen. Weitere Details und unsere empfohlene Vorgehensweise finden Sie am Anfang des Abschnitts **Installation**.

**Die Domain leitet in einer Schleife weiter – was soll ich tun?**
Prüfen Sie, ob bei Plesk „Bevorzugte Domain“ eingestellt ist. Setzen Sie diese auf „Keine“. Überprüfen Sie außerdem, ob `define( 'SUNRISE', true );` in der `wp-config.php` vorhanden ist.

Falls Sie die 301-Weiterleitungsfunktion des Plugins verwenden, prüfen Sie die Hosting-Einstellungen für die betreffende Domain (z. B. in Plesk, cPanel oder anderen Hosting-Panels) und deaktivieren Sie dort gegebenenfalls bestehende Weiterleitungsregeln.

Falls auf Hosting-Ebene bereits 301-Weiterleitungen für diese Domain konfiguriert sind und Sie diese beibehalten möchten, deaktivieren Sie stattdessen die 301-Weiterleitungsoption in den Plugin-Einstellungen – andernfalls entsteht eine Weiterleitungsschleife.

**sunrise.php wurde nicht automatisch kopiert – was nun?**
Kopieren Sie `sunrise.php` manuell aus dem Plugin-Ordner nach `/wp-content/sunrise.php` und fügen Sie `define( 'SUNRISE', true );` in Ihre `wp-config.php` ein.

**Das Plugin funktioniert nicht auf meiner Website – warum?**
Onartline Multisite Domain Mapping erfordert eine WordPress-Multisite-Installation und PHP 8.3+. Einzelinstallationen werden nicht unterstützt.

**Können Website-Administratoren ihre eigenen Domains verwalten?**
Ja – der Super Admin kann dies unter **Netzwerkverwaltung → Domain Mapping → Einstellungen → Website-Admin-Domain-Mapping** aktivieren.

**Unterstützt das Plugin automatische Updates?**
Ja – sobald das Plugin im WordPress-Plugin-Verzeichnis veröffentlicht ist, werden automatische Updates vollständig unterstützt.

**Ich habe das Plugin deinstalliert, sehe aber jetzt Fehler zu sunrise.php oder „headers already sent“ – was ist passiert?**
Dies geschieht, wenn die Zeile `define( 'SUNRISE', true );` nach der Deinstallation des Plugins nicht aus der `wp-config.php` entfernt wurde. Da `sunrise.php` nach der Deinstallation nicht mehr existiert, schlägt WordPress beim Versuch fehl, die Datei zu laden. Entfernen Sie einfach diese Zeile aus der `wp-config.php`, um das Problem zu beheben.