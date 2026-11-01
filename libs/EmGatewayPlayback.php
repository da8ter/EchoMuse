<?php

declare(strict_types=1);

/**
 * Wiedergabe auf dem Lautsprecher des Dots: Ton wird in Perioden zu 42,7 ms als 0x02-Rahmen
 * geschickt, höchstens EmPcm::LEAD_SECONDS der Echtzeit voraus (sonst blockiert das Gerät seinen
 * Lesevorgang und beantwortet keine Pings mehr), am Ende folgt 0x03. Ein Timer füllt nach.
 */
trait EmGatewayPlayback
{
    // ------------------------------------------------------------------ Wiedergabe

    /** Hängt Ton an die Wiedergabe des Geräts an; den Rest übernimmt der Pump-Timer. */
    private function queuePcm(string $id, string $pcm, bool $open = false): string
    {
        if ($this->dataConnection($id) === null) {
            return 'device has no audio connection';
        }
        /* Der Ton liegt in einer Datei, nicht im Puffer: Symcon kappt Instanz-Puffer
           bei 512 kB (Warnung ab 256 kB), und schon 7 s Ansage sind als Base64
           rund 900 kB — der Puffer wurde abgeschnitten, das JSON unlesbar, und es
           kam nichts an (gemessen 01.11.2026 am echten Dot). */
        $play = $this->plays();
        if (isset($play[$id])) {
            $rest = $this->readPcm($id);
            $this->writePcm($id, substr($rest, (int)$play[$id]['pos']) . $pcm);
            $play[$id]['sentBase'] = (float)$play[$id]['sent'];
            $play[$id]['pos'] = 0;
            $play[$id]['open'] = $open || !empty($play[$id]['open']);
        } else {
            $this->writePcm($id, $pcm);
            $play[$id] = ['pos' => 0, 'start' => EmClock::now(), 'sent' => 0.0, 'sentBase' => 0.0, 'open' => $open];
        }
        $this->savePlays($play);
        $this->pumpDevices();
        $this->SetTimerInterval('Pump', 250);
        return '';
    }

    public function Pump(): void
    {
        $this->pumpDevices();
        if ($this->plays() === []) {
            $this->SetTimerInterval('Pump', 0);
        }
    }

    private function pumpDevices(): void
    {
        $play = $this->plays();
        $ringAus = [];
        foreach ($play as $id => $p) {
            $conn = $this->dataConnection($id);
            if ($conn === null) {
                unset($play[$id]);
                $this->dropPcm((string)$id);
                $this->LogMessage(sprintf('%s: %s', $this->Translate('Audio connection lost during playback'), $id), KL_WARNING);
                continue;
            }
            $pcm = $this->readPcm((string)$id);
            $len = strlen($pcm);
            $pos = (int)$p['pos'];
            $sent = (float)$p['sent'];
            $elapsed = EmClock::now() - (float)$p['start'];
            if ($sent < $elapsed) { // Unterlauf (die Antwort kam langsamer als sie gespielt wird): Zählung neu ansetzen, sonst entfällt die Bremse
                $p['start'] = EmClock::now() - $sent;
                $play[$id]['start'] = $p['start'];
                $elapsed = $sent;
            }
            $out = '';
            while ($pos < $len && $sent - $elapsed < EmPcm::LEAD_SECONDS) {
                $chunk = substr($pcm, $pos, EmPcm::PERIOD_BYTES);
                if (strlen($chunk) < EmPcm::PERIOD_BYTES) {
                    $chunk = str_pad($chunk, EmPcm::PERIOD_BYTES, "\0");
                }
                $out .= EmWebSocket::binary(EmProtocol::speakerFrame($chunk));
                $pos += EmPcm::PERIOD_BYTES;
                $sent += EmPcm::PERIOD_SECONDS;
            }
            if ($pos >= $len && !empty($p['open'])) {
                $play[$id]['pos'] = $pos; // die Antwort wird noch erzeugt: kein Ende, weiter warten
                $play[$id]['sent'] = $sent;
            } elseif ($pos >= $len) {
                $out .= EmWebSocket::binary(EmProtocol::speakerEnd());
                if (!empty($p['ringOff'])) {
                    $ringAus[] = (string)$id;
                }
                unset($play[$id]);
                $this->dropPcm((string)$id);
            } else {
                $play[$id]['pos'] = $pos;
                $play[$id]['sent'] = $sent;
            }
            if ($out !== '') {
                $this->sendRaw($conn['ip'], $conn['port'], $out);
            }
        }
        $this->savePlays($play);
        foreach ($ringAus as $id) {
            $this->voiceRing($id, 'off');
        }
    }

    /**
     * Die Tondatei einer Wiedergabe (je Instanz und Gerät) — in einem eigenen
     * Ordner des Kernels mit Rechten 0700, nicht im gemeinsamen Temp-Ordner:
     * dort wäre der vorhersagbare Name per Symlink umlenkbar und die Datei für
     * andere Konten lesbar (Sicherheitsprüfung 01.11.2026).
     */
    private function pcmFile(string $id): string
    {
        $dir = rtrim(IPS_GetKernelDir(), '/') . '/echomuse-pcm';
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        @chmod($dir, 0700);
        return $dir . '/' . $this->InstanceID . '-' . md5($id) . '.pcm';
    }

    private function readPcm(string $id): string
    {
        $file = $this->pcmFile($id);
        if (is_link($file)) {
            return '';
        }
        $data = @file_get_contents($file);
        return is_string($data) ? $data : '';
    }

    private function writePcm(string $id, string $pcm): void
    {
        $file = $this->pcmFile($id);
        if (is_link($file)) {
            @unlink($file);
        }
        $alt = umask(0077);
        try {
            file_put_contents($file, $pcm, LOCK_EX);
        } finally {
            umask($alt);
        }
        @chmod($file, 0600);
    }

    private function dropPcm(string $id): void
    {
        @unlink($this->pcmFile($id));
    }

    /** Die Antwort ist vollständig: nach dem restlichen Ton folgt das Ende. */
    private function endStream(string $id): void
    {
        $play = $this->plays();
        if (isset($play[$id])) {
            $play[$id]['open'] = false;
            $this->savePlays($play);
            $this->pumpDevices();
        }
    }

    /** Wiedergabe sofort abbrechen (Barge-in oder neue Sprachrunde). */
    private function flushPlayback(string $id): void
    {
        $play = $this->plays();
        if (isset($play[$id])) {
            unset($play[$id]);
            $this->dropPcm($id);
            $this->savePlays($play);
            $this->sendControl($id, EmProtocol::speakerFlush());
        }
    }
}
