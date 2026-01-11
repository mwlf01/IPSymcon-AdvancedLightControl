# AdvancedLightControl für IP-Symcon

[![IP-Symcon Version](https://img.shields.io/badge/IP--Symcon-7.0+-blue.svg)](https://www.symcon.de)
[![Lizenz](https://img.shields.io/badge/Lizenz-MIT-green.svg)](LICENSE)

Ein leistungsstarkes IP-Symcon-Modul zur zentralen Steuerung mehrerer Lichter mit automatischer Abschaltung, Präsenzerkennung, Helligkeitssteuerung und Kachel-Visualisierungs-Integration.

**[English version](README.md)**

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
  - [Automatische Abschaltung](#automatische-abschaltung)
  - [Kachel-Visualisierungen](#kachel-visualisierungen)
  - [Sichtbarkeit in Visualisierung](#sichtbarkeit-in-visualisierung)
  - [Benutzerberechtigungen](#benutzerberechtigungen)
- [Visualisierung](#visualisierung)
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
- **Flexible Sichtbarkeit & Berechtigungen**:
  - Einzelne Steuerelemente in der Visualisierung ein-/ausblenden
  - Pro-Funktion Benutzerberechtigungen
  - Bedingte Variablen (nur erstellt wenn Funktion aktiviert)

---

## Voraussetzungen

- IP-Symcon 7.0 oder höher
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
| **Lichtschalter aktivieren** | Lichtschalter-Funktion ein-/ausschalten |
| **Schaltmodus** | Wie Schalter interpretiert werden (siehe unten) |
| **Schalter-Variable** | Boolesche Variable eines Schalters auswählen |
| **Name** | Optionaler Anzeigename zur Identifikation |

**Schaltmodi:**
- **Taster**: Erster Druck schaltet ein, zweiter Druck schaltet aus
- **Umschalten bei jeder Änderung**: Jede Zustandsänderung schaltet um
- **Nur Einschalten (Treppenhauslicht)**: Schaltet nur ein (nützlich mit Auto-Off)

### Präsenzerkennung

Automatische Lichtsteuerung basierend auf Präsenz:

| Einstellung | Beschreibung | Standard |
|-------------|--------------|----------|
| **Präsenzerkennung aktivieren** | Präsenzbasierte Steuerung ein-/ausschalten | Aus |
| **Präsenzmelder** | Liste der booleschen Präsenzmelder-Variablen | - |
| **Nachlaufzeit (s)** | Sekunden nach Präsenzende bis zum Ausschalten | 60 |

**Verhalten:**
- Licht schaltet ein, wenn EIN Melder Präsenz meldet (ODER-Logik)
- Licht schaltet aus nach Nachlaufzeit, wenn ALLE Melder keine Präsenz melden
- Manuelles Einschalten wird separat verfolgt (Präsenz schaltet manuell aktiviertes Licht nicht aus)
- Nach Auto-Off muss Präsenz erst enden, bevor sie wieder einschalten kann

### Helligkeitssteuerung

Licht nur einschalten, wenn es dunkel genug ist:

| Einstellung | Beschreibung | Standard |
|-------------|--------------|----------|
| **Helligkeitssteuerung aktivieren** | Helligkeitsbasierte Steuerung ein-/ausschalten | Aus |
| **Helligkeitssensor-Variable** | Integer- oder Float-Variable mit Lux-Wert | - |
| **Helligkeitsschwelle (Lux)** | Licht schaltet nur unter diesem Wert ein | 100 |

### Automatische Abschaltung

Automatische Abschaltung konfigurieren:

| Einstellung | Beschreibung | Standard |
|-------------|--------------|----------|
| **Automatische Abschaltung aktivieren** | Gesamte Auto-Off-Funktionalität ein-/ausschalten | Aus |
| **Abschaltzeit (s)** | Zeit in Sekunden bis zur automatischen Abschaltung (1-172800) | 300 |
| **Push-Benachrichtigungen aktivieren** | Benachrichtigungen vor Abschaltung senden | Aus |
| **Benachrichtigungsschwelle (s)** | Sekunden vor Abschaltung für Benachrichtigung | 60 |
| **Lichtname** | Name für Push-Benachrichtigungen (z.B. "Deckenlampe") | - |
| **Standort** | Standort für Push-Benachrichtigungen (z.B. "Wohnzimmer") | - |

**Hinweis**: Automatische Abschaltung hat Priorität vor Präsenzerkennung. Nach Abschaltung muss Präsenz erst enden, bevor sie wieder einschalten kann.

### Kachel-Visualisierungen

Kachel-Visualisierungs-Instanzen für Push-Benachrichtigungen registrieren:

| Einstellung | Beschreibung |
|-------------|--------------|
| **Kachel-Visualisierung** | Kachel-Visualisierungs-Instanz auswählen |

Sie können mehrere Kachel-Visualisierungen registrieren. Alle registrierten Visualisierungen erhalten Push-Benachrichtigungen, wenn die Restzeit unter den konfigurierten Schwellwert fällt.

### Sichtbarkeit in Visualisierung

Steuern, welche Elemente in der Visualisierung sichtbar sind:

| Einstellung | Beschreibung |
|-------------|--------------|
| **Hauptschalter anzeigen** | Haupt-Ein/Aus-Schalter anzeigen |
| **Lichtschalter-Umschalter anzeigen** | Schalter-Aktivierung anzeigen |
| **Präsenz-Umschalter anzeigen** | Präsenzerkennung-Umschalter anzeigen |
| **Präsenz-Nachlaufzeit anzeigen** | Nachlaufzeit-Einstellung anzeigen |
| **Helligkeits-Umschalter anzeigen** | Helligkeitssteuerung-Umschalter anzeigen |
| **Helligkeitsschwelle anzeigen** | Helligkeitsschwelle-Einstellung anzeigen |
| **Abschaltungs-Umschalter anzeigen** | Auto-Off-Umschalter anzeigen |
| **Abschaltzeit anzeigen** | Timeout-Konfiguration anzeigen |
| **Restzeit anzeigen** | Countdown-Timer anzeigen |
| **Timer-Verlängerung anzeigen** | Timer-Reset-Button anzeigen |
| **Benachrichtigungs-Umschalter anzeigen** | Benachrichtigungs-Umschalter anzeigen |
| **Benachrichtigungsschwelle anzeigen** | Benachrichtigungsschwelle-Einstellung anzeigen |

### Benutzerberechtigungen

Steuern, was Benutzer über die Visualisierung ändern können:

| Einstellung | Beschreibung |
|-------------|--------------|
| **Lichtschalter umschalten erlauben** | Benutzer können Lichtschalter aktivieren/deaktivieren |
| **Präsenz umschalten erlauben** | Benutzer können Präsenzerkennung aktivieren/deaktivieren |
| **Helligkeit umschalten erlauben** | Benutzer können Helligkeitssteuerung aktivieren/deaktivieren |
| **Helligkeitsschwelle ändern erlauben** | Benutzer können Helligkeitsschwelle anpassen |
| **Abschaltung umschalten erlauben** | Benutzer können Auto-Off ein-/ausschalten |
| **Abschaltzeit ändern erlauben** | Benutzer können Timeout anpassen |
| **Timer verlängern erlauben** | Benutzer können Timer zurücksetzen/verlängern |
| **Benachrichtigungen umschalten erlauben** | Benutzer können Benachrichtigungen ein-/ausschalten |
| **Benachrichtigungsschwelle ändern erlauben** | Benutzer können Benachrichtigungsschwelle anpassen |

Wenn eine Berechtigung deaktiviert ist, ist das Steuerelement sichtbar aber schreibgeschützt.

**Hinweis:** Benutzeränderungen über die Visualisierung werden automatisch in die Instanzkonfiguration zurückgeschrieben. Das bedeutet, Änderungen bleiben erhalten und gehen nicht verloren, wenn der Administrator andere Konfigurationsänderungen vornimmt.

---

## Visualisierung

Das Modul erstellt folgende Variablen (bedingt basierend auf aktivierten Funktionen):

| Variable | Typ | Beschreibung |
|----------|-----|--------------|
| **Alle Lichter** | Boolean | Hauptschalter für alle Lampen |
| **Lichtschalter aktiviert** | Boolean | Umschalter für Lichtschalter-Funktion |
| **Präsenz aktiviert** | Boolean | Umschalter für Präsenzerkennung |
| **Präsenz-Nachlaufzeit** | Integer | Sekunden nach Präsenzende |
| **Helligkeit aktiviert** | Boolean | Umschalter für Helligkeitssteuerung |
| **Helligkeitsschwelle** | Integer | Lux-Schwellwert für Präsenzaktivierung |
| **Abschaltung aktiviert** | Boolean | Umschalter für Auto-Off-Funktion |
| **Abschaltzeit** | Integer | Timeout in Sekunden |
| **Restzeit** | Integer | Countdown-Anzeige |
| **Timer verlängern** | Integer (Button) | Timer zurücksetzen/verlängern |
| **Benachrichtigungen aktiviert** | Boolean | Umschalter für Push-Benachrichtigungen |
| **Benachrichtigungsschwelle** | Integer | Sekunden vor Abschaltung für Benachrichtigung |

### Push-Benachrichtigungen

Bei Konfiguration mit Kachel-Visualisierungen und aktivierten Push-Benachrichtigungen:

1. Licht schaltet ein (manuell, per Schalter oder durch Präsenz)
2. Auto-Off-Timer beginnt herunterzuzählen
3. Bei Erreichen der Benachrichtigungsschwelle wird eine Push-Benachrichtigung gesendet
4. Benachrichtigung zeigt: **"Lichtname (Standort)"** - "Schaltet in X Sekunden ab. Tippen zum Verlängern."
5. Benutzer kann die Benachrichtigung antippen um den Timer zu verlängern
6. Ohne Verlängerung schaltet das Licht automatisch ab wenn der Timer Null erreicht

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
- `$Seconds` - Timeout in Sekunden (1-86400)

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

### Version 1.0.0 (2026-01-11)
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

---

## Lizenz

Dieses Projekt ist unter der MIT-Lizenz lizenziert - siehe [LICENSE](LICENSE) Datei für Details.

Die MIT-Lizenz erlaubt die freie Nutzung, Modifikation und Weitergabe der Software, sowohl für private als auch kommerzielle Zwecke, unter der Bedingung, dass der Urheberrechtshinweis und die Lizenz beibehalten werden.

---

## Autor

**mwlf01**

- GitHub: [@mwlf01](https://github.com/mwlf01)
