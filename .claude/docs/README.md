# EchoMuse — Projektdoku

Versioniertes Projektwissen: **warum** etwas so gebaut ist, was am laufenden System gemessen wurde und wie man es prüft. Bedienung steht im README der Bibliothek.

Jede Datei endet mit „Stand: geprüft gegen den Code am …“. Ändert ein Commit eine hier beschriebene Entscheidung, wird die Datei im selben Commit nachgezogen.

## Entscheidungen (`entscheidungen/`)

- [echomuse-gateway](entscheidungen/echomuse-gateway.md) – Symcon als Controller für Echo Dots: Server Socket, eigener WebSocket, Gerätelink, LED-Ring
- [echomuse-voice](entscheidungen/echomuse-voice.md) – Sprachrunde über SymDo und OpenAI Realtime, Entkopplung der Instanzen

## Testen (`testen/`)

- [pruefstaende](testen/pruefstaende.md) – die Prüfstände und Live-Proben mit Attrappen-Dot und Attrappen-Realtime

## Symcon-Plattform

Allgemeines, gemessenes Symcon-Verhalten steht in der SymDo-Bibliothek (lokal `../../List/.claude/docs/plattform/`). Für diese Bibliothek wichtig: `module-strict-und-php.md` (HEX-Datenfluss), `instanzen-und-nebenlaeufigkeit.md` (Verbinden braucht beide GUID-Richtungen, Abarbeitung je Instanz), `timer.md`.

## Stand

[stand.md](stand.md) – offene Punkte und bekannte Widersprüche.

Stand: geprüft gegen den Code am 01.11.2026
