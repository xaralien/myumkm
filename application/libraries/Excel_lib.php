<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Excel_lib - baca & tulis berkas .xlsx sederhana, tanpa Composer.
 *
 * Simpan di: application/libraries/Excel_lib.php
 *
 * Penjual UMKM bekerja di Excel atau Google Sheets, bukan CSV. Berkas .xlsx
 * pada dasarnya ZIP berisi XML, jadi cukup ZipArchive + SimpleXML (keduanya
 * bawaan PHP) untuk:
 *   - membaca lembar pertama sebagai array baris          (baca)
 *   - membuat berkas dengan satu atau beberapa lembar      (tulis)
 *
 * Yang TIDAK didukung (dan memang tidak dibutuhkan di sini): rumus, gaya
 * sel, tanggal berformat. Angka dibaca sebagai teks lalu dibersihkan oleh
 * pemanggilnya.
 *
 * CSV juga diterima (baca_csv): pemisah koma atau titik koma dideteksi
 * sendiri, karena Excel berbahasa Indonesia menyimpan CSV dengan titik koma.
 */
class Excel_lib
{
    /** @return array|FALSE baris -> [kolom => teks] (indeks 0) */
    public function baca($path, $nama_asli = '')
    {
        $ext = strtolower(pathinfo($nama_asli ?: $path, PATHINFO_EXTENSION));
        if ($ext === 'csv' || $ext === 'txt') {
            return $this->baca_csv($path);
        }
        return $this->baca_xlsx($path);
    }

    public function baca_csv($path)
    {
        $isi = @file_get_contents($path);
        if ($isi === FALSE) {
            return FALSE;
        }
        $isi = preg_replace('/^\xEF\xBB\xBF/', '', $isi);   // BOM UTF-8
        if ( ! mb_check_encoding($isi, 'UTF-8')) {
            $isi = mb_convert_encoding($isi, 'UTF-8', 'Windows-1252');
        }
        $baris1 = strtok($isi, "\n");
        $pisah = substr_count($baris1, ';') > substr_count($baris1, ',') ? ';' : ',';

        $out = array();
        $fh = fopen('php://memory', 'r+');
        fwrite($fh, $isi);
        rewind($fh);
        while (($r = fgetcsv($fh, 0, $pisah, '"', '\\')) !== FALSE) {
            $out[] = array_map('trim', $r);
        }
        fclose($fh);
        return $out;
    }

    public function baca_xlsx($path)
    {
        if ( ! class_exists('ZipArchive')) {
            return FALSE;
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== TRUE) {
            return FALSE;
        }

        $teks = array();
        $ss = $zip->getFromName('xl/sharedStrings.xml');
        if ($ss !== FALSE) {
            $x = @simplexml_load_string($ss);
            if ($x) {
                foreach ($x->si as $si) {
                    // Teks berformat (rich text) terpecah di beberapa <r><t>.
                    $s = isset($si->t) ? (string) $si->t : '';
                    foreach ($si->r as $r) {
                        $s .= (string) $r->t;
                    }
                    $teks[] = $s;
                }
            }
        }

        $sheet = $this->lembar_pertama($zip);
        $zip->close();
        if ($sheet === FALSE) {
            return FALSE;
        }

        $x = @simplexml_load_string($sheet);
        if ( ! $x || ! isset($x->sheetData)) {
            return FALSE;
        }

        $out = array();
        foreach ($x->sheetData->row as $row) {
            $baris = array();
            foreach ($row->c as $c) {
                $kol = $this->indeks_kolom((string) $c['r']);
                $t = (string) $c['t'];
                if ($t === 's') {
                    $v = isset($teks[(int) $c->v]) ? $teks[(int) $c->v] : '';
                } elseif ($t === 'inlineStr') {
                    $v = (string) $c->is->t;
                } else {
                    $v = (string) $c->v;
                    // 15000.0 -> 15000
                    if (is_numeric($v) && floor((float) $v) == (float) $v && strpos($v, 'E') === FALSE) {
                        $v = (string) (int) round((float) $v);
                    }
                }
                $baris[$kol] = trim($v);
            }
            if ($baris) {
                $maks = max(array_keys($baris));
                $penuh = array();
                for ($i = 0; $i <= $maks; $i++) {
                    $penuh[] = isset($baris[$i]) ? $baris[$i] : '';
                }
                $out[] = $penuh;
            }
        }
        return $out;
    }

    /**
     * Buat .xlsx.
     * @param array $lembar ['Nama lembar' => [[sel, sel], [sel, sel]], ...]
     * @return string|FALSE isi berkas
     */
    public function tulis(array $lembar)
    {
        if ( ! class_exists('ZipArchive')) {
            return FALSE;
        }
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== TRUE) {
            return FALSE;
        }

        $nama = array_keys($lembar);
        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $wb_sheets = '';
        $wb_rels = '';
        foreach ($nama as $i => $n) {
            $no = $i + 1;
            $ct .= '<Override PartName="/xl/worksheets/sheet' . $no . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $wb_sheets .= '<sheet name="' . $this->esc(mb_substr($n, 0, 31)) . '" sheetId="' . $no . '" r:id="rId' . $no . '"/>';
            $wb_rels .= '<Relationship Id="rId' . $no . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $no . '.xml"/>';
            $zip->addFromString('xl/worksheets/sheet' . $no . '.xml', $this->xml_lembar($lembar[$n]));
        }
        $ct .= '</Types>';
        $wb_rels .= '<Relationship Id="rId' . (count($nama) + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        $zip->addFromString('[Content_Types].xml', $ct);
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $wb_sheets . '</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $wb_rels . '</Relationships>');
        // Gaya 1 = tebal, dipakai untuk baris judul.
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Arial"/></font><font><b/><sz val="11"/><name val="Arial"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border/></borders>'
            . '<cellStyleXfs count="1"><xf/></cellStyleXfs>'
            . '<cellXfs count="2"><xf fontId="0"/><xf fontId="1" applyFont="1"/></cellXfs>'
            . '</styleSheet>');
        $zip->close();

        $isi = file_get_contents($tmp);
        @unlink($tmp);
        return $isi;
    }

    /** Kirim .xlsx ke browser sebagai unduhan. */
    public function unduh($nama_berkas, array $lembar)
    {
        $isi = $this->tulis($lembar);
        if ($isi === FALSE) {
            show_error('Ekstensi zip PHP tidak aktif - berkas Excel tidak bisa dibuat.', 500);
        }
        $CI = &get_instance();
        $CI->output
            ->set_content_type('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->set_header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $nama_berkas) . '"')
            ->set_header('Cache-Control: no-store')
            ->set_output($isi);
    }

    /* ------------------------------------------------------------------ */

    protected function xml_lembar(array $baris)
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
           . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        foreach (array_values($baris) as $i => $sel) {
            $r = $i + 1;
            $x .= '<row r="' . $r . '">';
            foreach (array_values($sel) as $j => $v) {
                $ref = $this->huruf_kolom($j) . $r;
                $gaya = $i === 0 ? ' s="1"' : '';
                if (is_int($v) || is_float($v)) {
                    $x .= '<c r="' . $ref . '"' . $gaya . '><v>' . $v . '</v></c>';
                } else {
                    $x .= '<c r="' . $ref . '" t="inlineStr"' . $gaya . '><is><t xml:space="preserve">'
                        . $this->esc((string) $v) . '</t></is></c>';
                }
            }
            $x .= '</row>';
        }
        return $x . '</sheetData></worksheet>';
    }

    protected function lembar_pertama(ZipArchive $zip)
    {
        $s = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($s !== FALSE) {
            return $s;
        }
        // Beberapa aplikasi menamai lembar berbeda - ambil worksheet pertama.
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $n = $zip->getNameIndex($i);
            if (strpos($n, 'xl/worksheets/') === 0 && substr($n, -4) === '.xml') {
                return $zip->getFromIndex($i);
            }
        }
        return FALSE;
    }

    protected function indeks_kolom($ref)
    {
        $huruf = preg_replace('/[^A-Z]/', '', strtoupper($ref));
        $n = 0;
        for ($i = 0; $i < strlen($huruf); $i++) {
            $n = $n * 26 + (ord($huruf[$i]) - 64);
        }
        return max(0, $n - 1);
    }

    protected function huruf_kolom($i)
    {
        $s = '';
        $i++;
        while ($i > 0) {
            $m = ($i - 1) % 26;
            $s = chr(65 + $m) . $s;
            $i = (int) (($i - $m) / 26);
        }
        return $s;
    }

    protected function esc($s)
    {
        // Karakter kendali dibuang: XML menolaknya dan Excel menganggap berkasnya rusak.
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s);
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
