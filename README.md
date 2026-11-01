# EchoMuse (Symcon)

Symcon als Controller für Echo Dots der 2. Generation mit der freien Firmware [EchoMuse](https://github.com/wilbowes/EchoMuse): Ansagen, Signaltöne, LED-Ring und Sprachgespräche, ganz ohne Home Assistant.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Enthaltene Module](#3-enthaltene-module)
4. [Einrichten](#4-einrichten)
5. [PHP-Befehle](#5-php-befehle)
6. [Versionshistorie](#6-versionshistorie)

## 1. Funktionsumfang

- Dots wählen sich beim **EchoMuse Gateway** ein; neue Geräte gibt man im Gateway frei.
- Je Dot ein **EchoMuse Gerät** mit Online-Status, Lautstärke, Stumm, Taste, Firmware und letztem Kontakt.
- WAV-Dateien aus dem Symcon-Medienordner abspielen, Signaltöne, eingebaute Klänge, Einstellungen des Dots.
- **LED-Ring:** eigener Ring (Aus, Leuchten, Pulsieren, Kreisen, Regenbogen, Farbe wählbar) und kurze Hinweise aus Skripten. Während einer Sprachrunde zeigt der Ring Zuhören, Nachdenken und Antworten.
- **Sprachgespräche** (EchoMuse Voice): Wakeword auf dem Dot, Sprache gestreamt zur Realtime-Schnittstelle von OpenAI, Anweisungen und Werkzeuge über [SymDo](https://github.com/da8ter/SymDo-Family-Organizer).
- Die Bibliothek **Sprachausgabe** nutzt die Geräte als Ausgabe „EchoMuse-Dot (KI-Stimme)“, wenn beide installiert sind.

## 2. Voraussetzungen

- Symcon ab Version 8.1
- Echo Dot der 2. Generation mit EchoMuse-Firmware (emOS) ab 2.16.0
- Für Sprachgespräche: SymDo mit einem Realtime-Modell (`gpt-realtime-mini` oder `gpt-realtime`)

## 3. Enthaltene Module

| Modul | Präfix | Aufgabe |
|---|---|---|
| EchoMuse Gateway | `EMGW` | Splitter auf einem Server Socket (Port 8767): Gerätelink, Freigabe, Wiedergabe, Vermittlung der Sprachrunde |
| EchoMuse Gerät | `EMGD` | ein Dot mit Variablen, Ansagen und LED-Ring |
| EchoMuse Voice | `EMVS` | Sprachrunde Dot ↔ OpenAI Realtime, Sitzung und Werkzeuge über SymDo |

## 4. Einrichten

1. **EchoMuse Gateway** anlegen; es legt einen Server Socket auf Port 8767 an.
2. Auf dem Dot die Controller-Adresse eintragen: `/data/local/etc/echomuse/controller.json` mit `{"endpoints":[{"host":"<Symcon-Adresse>","port":8767}]}`. mDNS gibt es noch nicht. Liegt dort eine `ca.pem`, wählt der Dot nur verschlüsselt; das Gateway spricht noch kein TLS.
3. Der Dot meldet sich an und erscheint im Gateway unter den wartenden Geräten. Freigeben legt die Geräte-Instanz an.

### Sprachgespräche (EchoMuse Voice)

Der Dot erkennt sein Wakeword selbst; bis dahin verlässt kein Ton das Gerät. Danach läuft die Sprache gestreamt zu OpenAI und die Antwort kommt schon beim Erzeugen zurück. Der OpenAI-Schlüssel bleibt in SymDo.

1. **EchoMuse Voice** anlegen (es legt einen Client Socket an). SymDo-Adresse (`http://<symcon>:3777/hook/lists/app`), SymDo-Zugangstoken und Nutzerkennung eintragen, das Gateway wählen.
2. Im **Gateway** das Voice-Modul wählen. Das Gateway schaltet die Wakeword-Erkennung auf dem Dot und den Signalton ein.
3. In SymDo ein Realtime-Modell einstellen, nicht GPT-Live (das läuft über WebRTC).

Es läuft eine Sitzung gleichzeitig; ein zweiter Dot bekommt „busy“.

## 5. PHP-Befehle

```php
EMGD_SpeakFile(int $GeraetID, string $Datei): string          // WAV aus dem Medienordner abspielen
EMGD_Beep(int $GeraetID, int $Sekunden): string               // Testton
EMGD_PlayCue(int $GeraetID, string $Klang): string            // eingebauter Klang des Dots
EMGD_SendConfig(int $GeraetID, string $Json): string          // Einstellungen des Dots, z. B. {"bassGuardDb":-12}
EMGD_SetRing(int $GeraetID, string $Muster, int $Farbe, int $Sekunden): string
EMGW_ApproveDevice(int $GatewayID, string $Geraetekennung): string
```

Die Rückgabe ist leer, wenn der Befehl angenommen wurde, sonst der Grund. `EMGD_SetRing`: Muster `off`, `solid`, `pulse`, `spin` oder `rotate` (Farbkreis), Farbe als `0xRRGGBB`, `$Sekunden` 0 = bis zum nächsten Befehl. Den dauerhaften Ring stellt man über die Variablen „Ring“ und „Ringfarbe“ ein.

## 6. Versionshistorie

- **1.0**: eigene Bibliothek, vorher Teil von Sprachausgabe 0.2. Modul-GUIDs und Präfixe sind gleich geblieben, bestehende Instanzen laufen weiter. Neu: LED-Ring; Ansagen über eine Datei statt des Instanz-Puffers (längere Ansagen kamen vorher nicht an).
