# Stand und offene Punkte

## Stand des Repos (01.11.2026)

- Eigene Bibliothek seit 01.11.2026, Version 1.0, Build 1; vorher Teil von Sprachausgabe 0.2. Modul-GUIDs und Präfixe unverändert.

## Offen

- **Echter Dot:** Anmeldung, Ansage und LED-Ring laufen am echten Dot (01.11.2026). Offen sind die Sprachrunde an echter Hardware (seit der Puffer-Korrektur nicht erprobt), Hörprobe der Antwortqualität und Latenz. Jede Aktion am Gerät nur auf Zusage.
- **Absicherung des Gerätelinks:** kein TLS, kein Token, kein mDNS. Die Gerätekennung ist die einzige Identität.
- **Sicherheitsprüfung 01.11.2026 (unveränderter Bestand, offen):**
  - Gerätelink: Freigabe hängt nur an der Gerätekennung; das vom Dot gesendete `X-EM-Token` beim Freigeben binden oder die erste IP festhalten.
  - `EmWebSocket::MAX_MESSAGE` (2 MB) liegt über der 512-kB-Grenze des Instanz-Puffers `Conns`; Grenze je Verbindung kleiner wählen und die Zahl der Verbindungen deckeln.
  - `EmSpool` schreibt Mikrofon-Ton nach `media/echomuse/` (0755) — wie `pcmFile` in einen eigenen 0700-Ordner mit 0600-Dateien ohne Symlinks legen.
- **Mehrere gleichzeitige Sprachrunden** (heute eine, zweiter Dot bekommt `busy`).
- **Dazwischenreden während der Antwort:** erneutes Wecken desselben Dots bricht die Antwort ab; am echten Gerät nicht erprobt.

## Widersprüche und Risiken (nur gemeldet, nicht geändert)

- **`ConnectParent` in `IPSModuleStrict`:** Gateway, Gerät und Voice rufen `ConnectParent` in `Create`. Das Plattformwissen sagt, die Methode gebe es nur für `IPSModule`, Ersatz sei `GetCompatibleParents()` (ab 8.2). Beobachtet wurde unter 9.1, dass der Aufruf beim Anlegen über die Konsole wirkt.
- **Mindestversion:** `library.json` nennt Symcon 8.1; unter 8.x nicht geprüft.
- **Puffer des Gateways ohne Sperre:** `Conns` und `Plays` werden aus mehreren Einstiegspunkten gelesen und ganz zurückgeschrieben (`ReceiveData`, Timer `Keepalive`/`Pump`, `MessageSink`). Bei Voice führte genau dieses Muster zu verlorenen Blöcken (`entscheidungen/echomuse-voice.md`). Im Gateway ist kein Fehler beobachtet; Risiko, nicht belegt.

Stand: geprüft gegen den Code am 01.11.2026
