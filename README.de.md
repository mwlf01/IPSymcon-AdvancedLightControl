# AdvancedLightControl für IP-Symcon

[![IP-Symcon Version](https://img.shields.io/badge/IP--Symcon-8.1+-blue.svg)](https://www.symcon.de)
[![Lizenz: EUPL-1.2](https://img.shields.io/badge/Lizenz-EUPL--1.2-blue.svg)](LICENSE)

Ein leistungsstarkes IP-Symcon-Modul zur zentralen Steuerung mehrerer Lichter mit automatischer Abschaltung, Präsenzerkennung, Helligkeitssteuerung und Kachel-Visualisierungs-Integration.

**[English Version](README.md)**

---

## Inhaltsverzeichnis

- [Funktionen](#funktionen)
- [Voraussetzungen](#voraussetzungen)
- [Installation](#installation)
- [Konfiguration](#konfiguration)
  - [Lampen](#lampen)
  - [Lichtschalter](#lichtschalter)
  - [Präsenzerkennung](#präsenzerkennung)
  - [Helligkeitssteuerung](#helligkeitssteuerung)
  - [Benachrichtigungen](#benachrichtigungen)
  - [Kachel-Visualisierungen](#kachel-visualisierungen)
- [Variablen](#variablen)
- [PHP-Funktionen](#php-funktionen)
- [Lizenz](#lizenz)

---

## Funktionen

- **Gruppen-Lichtsteuerung**: Beliebig viele Lampen registrieren und mit einem Hauptschalter steuern
- **Lichtschalter**: Physische Schalter mit drei Modi verbinden:
  - Taster (Umschalten bei Tastendruck)
  - Umschalten bei jeder Änderung
  - Nur Einschalten (Treppenhauslicht)
- **Präsenzerkennung**: 
  - Automatische Lichtsteuerung basierend auf Präsenzmeldern
  - Konfigurierbare Nachlaufzeit nach Präsenzende
  - Mehrere Präsenzmelder unterstützt (ODER-Logik)
  - Intelligente Unterscheidung zwischen manueller und präsenzgesteuerter Aktivierung
- **Helligkeitssteuerung**:
  - Licht nur einschalten, wenn Helligkeit unter Schwellwert
  - Unterstützt Integer- und Float-Helligkeitssensoren
  - Benutzer-einstellbarer Schwellwert über Visualisierung
- **Automatische Abschaltung**: 
  - Automatisches Ausschalten mit konfigurierbarer Zeit (1 Sekunde bis 48 Stunden)
  - Automatische Abschaltung hat Priorität vor Präsenzerkennung
- **Push-Benachrichtigungen**: 
  - Benachrichtigungen vor Abschaltung über Kachel-Visualisierung
  - Anpassbarer Lichtname und Standort in Benachrichtigungen
  - Unterstützung mehrerer Kachel-Visualisierungen
- **Benutzergesteuerte Funktionen**:
  - Alle Funktionen können vom Benutzer über Variablen in der Visualisierung aktiviert/deaktiviert werden
  - Alle Einstellungen zur Laufzeit änderbar ohne Konfigurationsänderungen

---

## Voraussetzungen

- IP-Symcon 8.1 oder höher
- Gültiges IP-Symcon-Abonnement für Push-Benachrichtigungen (optional)

---

## Installation

### Über den Module Store (Empfohlen)

1. IP-Symcon-Konsole öffnen
2. Navigieren zu **Module** > **Module Store**
3. Nach "AdvancedLightControl" oder "Erweiterte Lichtsteuerung" suchen
4. Auf **Installieren** klicken

### Manuelle Installation über Git

1. IP-Symcon-Konsole öffnen
2. Navigieren zu **Module** > **Module**
3. Auf **Hinzufügen** (Plus-Symbol) klicken
4. **Modul von URL hinzufügen** auswählen
5. Eingeben: `https://github.com/mwlf01/IPSymcon-AdvancedLightControl.git`
6. Auf **OK** klicken

### Manuelle Installation (Dateikopie)

1. Dieses Repository klonen oder herunterladen
2. Den Ordner in das IP-Symcon-Modulverzeichnis kopieren:
   - Windows: `C:\ProgramData\Symcon\modules\`
   - Linux: `/var/lib/symcon/modules/`
   - Docker: Volume-Mapping prüfen
3. Module in der IP-Symcon-Konsole neu laden

---

## Konfiguration

Nach der Installation eine neue Instanz erstellen:

1. Navigieren zu **Objekte** > **Objekt hinzufügen** > **Instanz**
2. Nach "AdvancedLightControl" oder "Erweiterte Lichtsteuerung" suchen
3. Auf **OK** klicken um die Instanz zu erstellen

### Lampen

Boolesche Variablen registrieren, die Ihre Lampen repräsentieren:

| Einstellung | Beschreibung |
|-------------|--------------|
| **Lampen-Variable** | Boolesche Variable auswählen, die eine Lampe steuert |
| **Name** | Optionaler Anzeigename zur Identifikation |

Sie können beliebig viele Lampen hinzufügen. Alle registrierten Lampen werden mit dem Hauptschalter gemeinsam geschaltet.

### Lichtschalter

Physische Schalter zur Lichtsteuerung verbinden:

| Einstellung | Beschreibung |
|-------------|--------------|
| **Schaltmodus** | Wie Schalter interpretiert werden (siehe unten) |
| **Schalter-Variable** | Boolesche Variable eines Schalters auswählen |
| **Name** | Optionaler Anzeigename zur Identifikation |

**Schaltmodi:**
- **Taster**: Erster Druck schaltet ein, zweiter Druck schaltet aus
- **Umschalten bei jeder Änderung**: Jede Zustandsänderung schaltet um
- **Nur Einschalten (Treppenhauslicht)**: Schaltet nur ein (nützlich mit Auto-Off)

**Hinweis:** Aktivieren/Deaktivieren der Lichtschalter über die Variable "Lichtschalter" in der Visualisierung.

### Präsenzerkennung

Präsenzmelder für automatische Lichtsteuerung konfigurieren:

| Einstellung | Beschreibung |
|-------------|--------------|
| **Präsenzmelder** | Liste der booleschen Präsenzmelder-Variablen |

**Verhalten:**
- Licht schaltet ein, wenn EIN Melder Präsenz meldet (ODER-Logik)
- Licht schaltet aus nach Nachlaufzeit, wenn ALLE Melder keine Präsenz melden
- Manuelles Einschalten wird separat verfolgt (Präsenz schaltet manuell aktiviertes Licht nicht aus)
- Nach Auto-Off muss Präsenz erst enden, bevor sie wieder einschalten kann

**Hinweis:** Aktivieren/Deaktivieren und Nachlaufzeit über Variablen in der Visualisierung einstellen.

### Helligkeitssteuerung

Helligkeitssensor konfigurieren:

| Einstellung | Beschreibung |
|-------------|--------------|
| **Helligkeitssensor-Variable** | Integer- oder Float-Variable mit Lux-Wert |

**Hinweis:** Aktivieren/Deaktivieren und Schwellwert über Variablen in der Visualisierung einstellen.

### Benachrichtigungen

Benachrichtigungseinstellungen für Auto-Off-Warnungen konfigurieren:

| Einstellung | Beschreibung |
|-------------|--------------|
| **Lichtname** | Name für Push-Benachrichtigungen (z.B. "Deckenlampe") |
| **Standort** | Standort für Push-Benachrichtigungen (z.B. "Wohnzimmer") |

### Kachel-Visualisierungen

Kachel-Visualisierungs-Instanzen für Push-Benachrichtigungen registrieren:

| Einstellung | Beschreibung |
|-------------|--------------|
| **Kachel-Visualisierung** | Kachel-Visualisierungs-Instanz auswählen |

Sie können mehrere Kachel-Visualisierungen registrieren. Alle registrierten Visualisierungen erhalten Push-Benachrichtigungen, wenn die Restzeit unter den konfigurierten Schwellwert fällt.

---

## Variablen

Das Modul erstellt folgende Variablen (alle immer verfügbar):

| Variable | Typ | Beschreibung |
|----------|-----|--------------|
| **Alle Lichter** | Boolean | Hauptschalter für alle Lampen |
| **Lichtschalter** | Boolean | Lichtschalter-Funktion aktivieren/deaktivieren |
| **Präsenzerkennung** | Boolean | Präsenzerkennung aktivieren/deaktivieren |
| **Präsenz-Nachlaufzeit** | Integer | Sekunden nach Präsenzende (Standard: 60) |
| **Helligkeitssteuerung** | Boolean | Helligkeitssteuerung aktivieren/deaktivieren |
| **Helligkeitsschwelle** | Integer | Lux-Schwellwert für Präsenzaktivierung (Standard: 100) |
| **Automatische Abschaltung** | Boolean | Auto-Off-Funktion aktivieren/deaktivieren |
| **Abschaltzeit** | Integer | Timeout in Sekunden (Standard: 300) |
| **Restzeit** | Integer | Countdown-Anzeige |
| **Timer verlängern** | Integer (Button) | Timer zurücksetzen/verlängern |
| **Benachrichtigungen** | Boolean | Push-Benachrichtigungen aktivieren/deaktivieren |
| **Benachrichtigung vor** | Integer | Sekunden vor Abschaltung für Benachrichtigung (Standard: 60) |

Alle Funktions-Umschalter sind standardmäßig deaktiviert. Benutzer können Funktionen aktivieren/deaktivieren und Einstellungen direkt über die Visualisierung ändern, ohne Zugriff auf die Instanzkonfiguration zu benötigen.

### Push-Benachrichtigungen

Bei Konfiguration mit Kachel-Visualisierungen und aktivierten Benachrichtigungen:

1. Licht schaltet ein (manuell, per Schalter oder durch Präsenz)
2. Auto-Off-Timer beginnt herunterzuzählen
3. Bei Erreichen des "Benachrichtigung vor"-Schwellwerts wird eine Push-Benachrichtigung gesendet
4. Benachrichtigung zeigt: **"Lichtname (Standort)"** - "Schaltet in X Sekunden ab. Tippen zum Verlängern."
5. Benutzer kann die Benachrichtigung antippen um den Timer zu verlängern
6. Ohne Verlängerung schaltet das Licht automatisch ab wenn der Timer Null erreicht

**Hinweis**: Automatische Abschaltung hat Priorität vor Präsenzerkennung. Nach Abschaltung muss Präsenz erst enden, bevor sie wieder einschalten kann.

---

## PHP-Funktionen

Das Modul stellt folgende öffentliche Funktionen für Skripte bereit:

### SwitchAll

Alle registrierten Lampen ein- oder ausschalten.

```php
ALC_SwitchAll(int $InstanceID, bool $State);
```

**Parameter:**
- `$InstanceID` - ID der AdvancedLightControl-Instanz
- `$State` - `true` zum Einschalten, `false` zum Ausschalten

**Beispiel:**
```php
// Alle Lampen einschalten
ALC_SwitchAll(12345, true);

// Alle Lampen ausschalten
ALC_SwitchAll(12345, false);
```

### ExtendTimer

Den Auto-Off-Timer auf den konfigurierten Timeout-Wert zurücksetzen/verlängern.

```php
ALC_ExtendTimer(int $InstanceID);
```

**Parameter:**
- `$InstanceID` - ID der AdvancedLightControl-Instanz

**Beispiel:**
```php
// Timer verlängern
ALC_ExtendTimer(12345);
```

### GetRemainingTime

Die aktuelle Restzeit bis zur automatischen Abschaltung in Sekunden abrufen.

```php
int ALC_GetRemainingTime(int $InstanceID);
```

**Parameter:**
- `$InstanceID` - ID der AdvancedLightControl-Instanz

**Rückgabe:** Restzeit in Sekunden (0 wenn Timer nicht läuft)

**Beispiel:**
```php
$remaining = ALC_GetRemainingTime(12345);
echo "Licht schaltet in $remaining Sekunden ab";
```

### SetAutoOffTime

Den Auto-Off-Timeout-Wert setzen.

```php
ALC_SetAutoOffTime(int $InstanceID, int $Seconds);
```

**Parameter:**
- `$InstanceID` - ID der AdvancedLightControl-Instanz
- `$Seconds` - Timeout in Sekunden (1-172800, also bis zu 48 Stunden)

**Beispiel:**
```php
// Auto-Off auf 10 Minuten setzen
ALC_SetAutoOffTime(12345, 600);
```

### SetAutoOffEnabled

Die Auto-Off-Funktion aktivieren oder deaktivieren.

```php
ALC_SetAutoOffEnabled(int $InstanceID, bool $Enabled);
```

**Parameter:**
- `$InstanceID` - ID der AdvancedLightControl-Instanz
- `$Enabled` - `true` zum Aktivieren, `false` zum Deaktivieren

**Beispiel:**
```php
// Auto-Off deaktivieren
ALC_SetAutoOffEnabled(12345, false);
```

---

## Changelog

### Version 2.2.0
- **Lizenzwechsel**: Das Projekt steht ab dieser Version unter der European Union Public Licence (EUPL), Version 1.2, anstelle der bisherigen MIT-Lizenz. Vorhandene Kopien der Versionen 2.1.0 und älter bleiben weiterhin unter der MIT-Lizenz; die neue Lizenz gilt ab Version 2.2.0.
- **Bugfix**: Wird eine Lampe extern eingeschaltet (z. B. direkt über ihre Variable oder über einen Hardware-Pfad, der das Modul umgeht), startet die automatische Abschaltung nun wie erwartet, und die Aktivierung wird als manuelle Einschaltung erfasst. Externe Deaktivierungen stoppen den Auto-Off-Timer entsprechend.
- **Bugfix**: Externe Zustandsänderungen (z. B. Lampe vor Ort ausschalten) werden nun unmittelbar mit dem Hauptschalter synchronisiert, sobald die vorherige Schaltaktion ihren Zielzustand erreicht hat. Zuvor konnte die 3-Sekunden-Karenzzeit nach einer Schaltaktion legitime externe Updates unterdrücken.
- **Bugfix**: Lichtschalter im Taster- und Toggle-Modus orientieren sich nun am tatsächlichen Zustand der Lampen statt am Hauptschalter. War eine Lampe extern eingeschaltet worden, während der Hauptschalter noch auf `false` stand, hat das nächste Schalterereignis das Licht zuvor nicht wie erwartet ausgeschaltet.
- **Bugfix**: `ALC_SetAutoOffTime` akzeptiert nun Werte bis 172800 Sekunden (48 Stunden), konsistent mit UI und Dokumentation
- **Bugfix**: Der Auto-Off-Timer startet jetzt sofort, wenn das Feature aktiviert wird und die Lampen bereits an sind
- **Bugfix**: Der Hauptschalter flackert nicht mehr, wenn Aktoren ihre Zustandsänderungen verzögert melden (3 Sekunden Karenzzeit nach jedem Schaltvorgang)
- **Bugfix**: Push-Benachrichtigungen werden nicht mehr sofort ausgelöst, wenn die Benachrichtigungsschwelle höher als die Auto-Off-Zeit konfiguriert ist
- **Bugfix**: Den Hauptschalter im aktuellen Zustand erneut zu betätigen ist nun ein No-Op und setzt nicht mehr unerwartet den Auto-Off-Timer zurück
- **Bugfix**: Der Taster-Modus richtet sich jetzt am tatsächlichen Hauptschalter-Zustand aus, um Desynchronisation nach externen Änderungen zu vermeiden
- **Bugfix**: Die Helligkeitsschwelle ist nun auf mindestens 1 Lux begrenzt, der mehrdeutige Fall „Schwelle = 0" entfällt
- **Verbesserung**: Lampen-Variablen ohne verfügbare Aktion werden jetzt im IP-Symcon-Log gemeldet, statt stillschweigend zu fehlen
- **Aufräumen**: Redundante `VM_UPDATE`-Konstante zugunsten der System-Konstante entfernt

### Version 2.1.0
- **Bugfix**: Konfigurationsänderungen (z.B. Hinzufügen einer Lampe) setzen die vom Benutzer gesetzten Variablenwerte nicht mehr zurück
- **Bugfix**: Aktive Timer und Betriebszustand (Auto-Off-Countdown, Taster-Status, etc.) bleiben bei Konfigurationsänderungen erhalten
- Standardwerte werden jetzt nur noch bei neu erstellten Variablen gesetzt, nicht mehr bei jedem Speichern der Konfiguration

### Version 2.0.0
- **Breaking Change**: Enable*-Checkboxen aus Instanzkonfiguration entfernt
- Alle Variablen werden jetzt immer erstellt (keine bedingte Erstellung mehr)
- Funktionen werden jetzt über Variablen in der Visualisierung aktiviert/deaktiviert
- Sichtbarkeitseinstellungen entfernt (alle Variablen immer sichtbar)
- Benutzerberechtigungen entfernt (alle Einstellungen benutzer-anpassbar)
- Übersichtlichere Variablennamen (Suffix "aktiviert" bei Umschaltern entfernt)
- "Benachrichtigungsschwelle" umbenannt zu "Benachrichtigung vor" für Klarheit

### Version 1.0.0
- Erstveröffentlichung
- Gruppen-Lichtsteuerung mit Hauptschalter
- Lichtschalter-Unterstützung mit drei Modi (Taster, Umschalten, Nur-Ein)
- Präsenzerkennung mit mehreren Meldern und Nachlaufzeit
- Helligkeitssteuerung mit konfigurierbarem Lux-Schwellwert
- Auto-Off-Timer mit konfigurierbarem Timeout (1 Sekunde bis 48 Stunden)
- Push-Benachrichtigungen über Kachel-Visualisierung mit anpassbarem Text
- Bidirektionale Synchronisation: Benutzeränderungen werden in Instanzkonfiguration gespeichert
- Flexible Sichtbarkeits- und Berechtigungssteuerung
- Vollständige deutsche Lokalisierung (Oberfläche, Variablen, Benachrichtigungen)

---

## Support

Bei Problemen, Funktionswünschen oder Beiträgen besuchen Sie bitte:
- [GitHub Repository](https://github.com/mwlf01/IPSymcon-AdvancedLightControl)
- [GitHub Issues](https://github.com/mwlf01/IPSymcon-AdvancedLightControl/issues)
- [Symcon Community](https://community.symcon.de/) – Benutzer: **mwlf**

---

## Lizenz

Dieses Projekt steht unter der **European Union Public Licence (EUPL) v. 1.2** — siehe die [LICENSE](LICENSE)-Datei für den vollständigen Lizenztext.

Die EUPL ist eine Copyleft-Lizenz: abgeleitete Werke, die weitergegeben werden, müssen ebenfalls unter der EUPL oder einer kompatiblen Lizenz veröffentlicht werden (z. B. GPL, AGPL, MPL, LGPL — die vollständige Kompatibilitätsliste steht im Anhang der EUPL). Frühere Releases bis Version 2.1.0 bleiben weiterhin unter der zuvor genutzten MIT-Lizenz verfügbar.

Die EUPL wird in 24 offiziellen Sprachfassungen veröffentlicht, die rechtlich alle gleichwertig sind. Die Lizenz kann in anderen Sprachen auf der [offiziellen EU-Seite](https://interoperable-europe.ec.europa.eu/collection/eupl/eupl-text-eupl-12) eingesehen werden.

---

## Autor

**mwlf01**

- GitHub: [@mwlf01](https://github.com/mwlf01)
- Symcon Community: [mwlf](https://community.symcon.de/)
