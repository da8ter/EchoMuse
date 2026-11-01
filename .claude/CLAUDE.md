# EchoMuse (Bibliothek für Symcon)

Symcon als Controller für Echo Dots der 2. Generation mit der Firmware EchoMuse: Gerätelink, Ansagen, LED-Ring, Sprachgespräche über SymDo. Öffentliches Repo `da8ter/EchoMuse`, Zweig `main`. Bis 01.11.2026 Teil der Bibliothek Sprachausgabe (dort steht die ältere Git-Historie).

Projektwissen (Entscheidungen, Protokoll-Eigenheiten, Test-Rezepte): **`.claude/docs/README.md`**. Offenes: `.claude/docs/stand.md`. Betriebsdaten dieses Rechners stehen in `CLAUDE.local.md` (nicht eingecheckt).

## Aufbau

- **`EchoMuseGateway/`** (EMGW, Splitter auf einem Server Socket): Gerätelink der EchoMuse-Firmware, Freigabe, Wiedergabe, Sprachrunden-Vermittlung (Traits `libs/EmGateway*.php`).
- **`EchoMuseGeraet/`** (EMGD): ein Dot mit Variablen, LED-Ring und `SpeakFile`, `Beep`, `PlayCue`, `SendConfig`, `SetRing`.
- **`EchoMuseVoice/`** (EMVS, auf einem Client Socket): Sprachrunde Dot ↔ OpenAI Realtime, Sitzung und Werkzeuge über SymDo.
- **`libs/`**: reine Bausteine ohne Symcon (`EmWebSocket`, `EmProtocol`, `EmPcm`, `EmRealtime`, `EmSpool`, `EmWsClient`, `EmClock`, `SymDoVoiceClient`) plus die Gateway-Traits.
- Modul-GUIDs und Präfixe NICHT ändern: bestehende Instanzen aus der Sprachausgabe-Zeit hängen daran.
- Andere Bibliotheken (Sprachausgabe, SymDo) rufen nur öffentliche Funktionen (`EMGD_*`) und prüfen vorher `function_exists`. Keine Klassen über Bibliotheksgrenzen teilen.
- Alle Module: `IPSModuleStrict`, Darstellungen statt Variablenprofilen.

## Prüfen

```bash
php tests/echomuse_lib_test.php   # reine Bausteine, ohne Symcon
php tests/echomuse_test.php       # Gateway, Gerät, Voice mit Attrappen-Dot
php -l <Datei>
```

`echomuse_test.php` braucht den Prüfstand-Kernel aus dem Nachbarordner `../LGThinQ/tests/bootstrap.php`. Live-Proben: `.claude/docs/testen/pruefstaende.md`.

## Regeln

- **Commits:** ein Thema je Commit, deutsche Botschaft, **ohne** Co-Authored-By-Zeile. Prüfstände vorher laufen lassen.
- **Nie** `git checkout`/`git restore` auf Dateien: die Arbeitskopie kann nicht committete Arbeit enthalten.
- **Push und Release nur auf Zuruf.** Release: `build` und `date` in `library.json` hochsetzen, Versionshistorie im README nachziehen.
- **Doku nachziehen:** Ändert ein Commit eine Entscheidung aus `docs/`, wird die Datei im selben Commit angepasst und ihr „Stand“-Datum erneuert.
- **Öffentliches Repo:** keine IP-Adressen, Ports lokaler Systeme, Instanz-IDs, Token, Gerätekennungen echter Dots, Pfade unter `/Users/` – weder im Code noch in Tests oder Doku. Fixtures mit Platzhaltern.
- **Echte Geräte:** jede Aktion an einem echten Dot oder eine hörbare Probe nur auf Zusage des Nutzers.
- **Symcon-Plattformwissen** steht geprüft in der SymDo-Bibliothek: `../List/.claude/docs/plattform/` – dort nachlesen, nicht hierher kopieren.
