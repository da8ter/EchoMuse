# Stand und offene Punkte

## Stand des Repos (01.11.2026)

- Eigene Bibliothek seit 01.11.2026, Version 1.0, Build 1; vorher Teil von Sprachausgabe 0.2. Modul-GUIDs und Präfixe unverändert.

## Offen

- **Echter Dot:** Anmeldung, Ansage und LED-Ring laufen am echten Dot (01.11.2026). Offen sind die Sprachrunde an echter Hardware (seit der Puffer-Korrektur nicht erprobt), Hörprobe der Antwortqualität und Latenz. Jede Aktion am Gerät nur auf Zusage.
- **Absicherung des Gerätelinks:** kein TLS, kein mDNS. Die Freigabe ist an Token oder Adresse gebunden (`entscheidungen/echomuse-gateway.md`); ein Dot ohne Token hinter einem Relay/NAT ist damit nur schwach gebunden. Bewusst offen: Eine Freigabe ohne Warteeintrag (Skript, vorab) und Freigabelisten aus 1.0 binden die erste Anmeldung danach (wer zuerst kommt, wird gebunden). Token und TLS des EchoMuse-Controllers (`ca.pem`, `token` auf dem Dot) wären der nächste Schritt.
- **Mehrere gleichzeitige Sprachrunden** (heute eine, zweiter Dot bekommt `busy`).
- **Dazwischenreden während der Antwort:** erneutes Wecken desselben Dots bricht die Antwort ab; am echten Gerät nicht erprobt.

## Widersprüche und Risiken (nur gemeldet, nicht geändert)

- **`ConnectParent` in `IPSModuleStrict`:** Gateway, Gerät und Voice rufen `ConnectParent` in `Create`. Das Plattformwissen sagt, die Methode gebe es nur für `IPSModule`, Ersatz sei `GetCompatibleParents()` (ab 8.2). Beobachtet wurde unter 9.1, dass der Aufruf beim Anlegen über die Konsole wirkt.
- **Mindestversion:** `library.json` nennt Symcon 8.1; unter 8.x nicht geprüft.
- **Puffer des Gateways ohne Sperre:** `Conns` und `Plays` werden aus mehreren Einstiegspunkten gelesen und ganz zurückgeschrieben (`ReceiveData`, Timer `Keepalive`/`Pump`, `MessageSink`). Bei Voice führte genau dieses Muster zu verlorenen Blöcken (`entscheidungen/echomuse-voice.md`). Im Gateway ist kein Fehler beobachtet; Risiko, nicht belegt.

Stand: geprüft gegen den Code am 01.11.2026
