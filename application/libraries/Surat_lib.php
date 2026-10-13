<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Surat_lib - pengirim email.
 * Simpan di: application/libraries/Surat_lib.php
 *
 * Membungkus pustaka email CodeIgniter supaya yang memanggil tidak perlu tahu
 * soal SMTP, dan supaya ada satu tempat untuk memutuskan apa yang terjadi
 * kalau pengirimannya gagal.
 *
 * Dua hal yang dijamin di sini:
 *
 * 1. TANPA SMTP, EMAIL DICATAT KE LOG, BUKAN HILANG. Selama 'host' di
 *    config/surat.php masih kosong, isi emailnya ditulis ke
 *    application/logs/log-YYYY-MM-DD.php lengkap dengan tautannya. Jadi reset
 *    sandi bisa diuji di localhost tanpa server SMTP: tautannya disalin dari
 *    log. Alternatifnya - fitur ini tidak bisa dicoba sampai SMTP siap - akan
 *    membuatnya tidak pernah diuji sama sekali.
 *
 * 2. GAGAL KIRIM TIDAK PERNAH MEMATIKAN HALAMAN. SMTP sering gagal karena hal
 *    di luar kendali kita: kuota harian habis, jaringan hosting, sertifikat
 *    kedaluwarsa. Kalau itu sampai melempar galat ke pengunjung, pendaftaran
 *    yang sudah berhasil terlihat seperti gagal, dan orangnya mendaftar lagi.
 *    Di sini kegagalan dicatat lalu dikembalikan sebagai FALSE.
 */
class Surat_lib
{
    protected $CI;
    protected $cfg;

    public function __construct()
    {
        $this->CI = &get_instance();

        /* config/surat.php tidak ikut di repo, jadi di pemasangan baru berkas
           itu belum ada. Dimuat dengan $fail_gracefully = TRUE supaya
           ketiadaannya tidak mematikan seluruh aplikasi - yang terjadi hanya
           email masuk ke log. */
        $this->CI->config->load('surat', TRUE, TRUE);

        /* item($index, $section) - dua argumen, sama seperti cara config
           duitku dibaca di Payment.php. Dengan $use_sections = TRUE, isi
           berkasnya bersarang di bawah nama berkasnya, jadi item('surat')
           saja akan mengembalikan pembungkusnya, bukan datanya. */
        $this->cfg = (array) $this->CI->config->item('surat', 'surat');

        // Nilai bawaan kalau surat.php belum ada sama sekali.
        $this->cfg += array(
            'from_email' => 'noreply@localhost',
            'from_name'  => 'Sapa UMKM',
            'reply_to'   => '',
            'host'       => '',
            'port'       => 587,
            'user'       => '',
            'pass'       => '',
            'crypto'     => 'tls',
            'timeout'    => 8,
        );
    }

    /** Apakah SMTP sudah diatur? Dipakai juga oleh halaman yang ingin
     *  memberi tahu penjual bahwa emailnya belum benar-benar terkirim. */
    public function siap()
    {
        return trim((string) $this->cfg['host']) !== '';
    }

    /**
     * Kirim satu email HTML.
     *
     * @param  string $ke     alamat tujuan
     * @param  string $judul  subjek
     * @param  string $view   nama view di application/views/surat/
     * @param  array  $data   variabel untuk view-nya
     * @return bool           TRUE kalau benar-benar terkirim
     */
    public function kirim($ke, $judul, $view, array $data = array())
    {
        $isi = $this->CI->load->view('surat/' . $view, $data, TRUE);

        if (! $this->siap()) {
            return $this->ke_log($ke, $judul, $isi);
        }

        $this->CI->load->library('email');

        $this->CI->email->initialize(array(
            'protocol'    => 'smtp',
            'smtp_host'   => $this->cfg['host'],
            'smtp_port'   => (int) $this->cfg['port'],
            'smtp_user'   => $this->cfg['user'],
            'smtp_pass'   => $this->cfg['pass'],
            'smtp_crypto' => $this->cfg['crypto'] ?: NULL,
            'smtp_timeout'=> (int) $this->cfg['timeout'],
            'mailtype'    => 'html',
            'charset'     => 'utf-8',

            /* CRLF, bukan LF. Beberapa server SMTP - Gmail termasuk -
               menolak atau mengacak email yang barisnya hanya LF, karena
               RFC 5321 memang meminta CRLF. Gejalanya khas: emailnya
               "terkirim" tapi isinya jadi satu baris panjang. */
            'newline'     => "\r\n",
            'crlf'        => "\r\n",

            // Tanpa ini, baris panjang dipotong di tengah tag HTML.
            'wordwrap'    => FALSE,
        ));

        $this->CI->email->from($this->cfg['from_email'], $this->cfg['from_name']);
        if ($this->cfg['reply_to']) {
            $this->CI->email->reply_to($this->cfg['reply_to'], $this->cfg['from_name']);
        }
        $this->CI->email->to($ke);
        $this->CI->email->subject($judul);
        $this->CI->email->message($isi);

        /* Versi teks biasa untuk pembaca email yang tidak menampilkan HTML -
           dan, yang lebih sering penting, untuk penyaring spam: email yang
           HTML-nya tanpa pasangan teks lebih mudah dianggap spam. */
        $this->CI->email->set_alt_message($this->jadi_teks($isi));

        if ($this->CI->email->send(FALSE)) {
            return TRUE;
        }

        /* debugger() berisi jawaban server SMTP - itu yang menjelaskan KENAPA
           gagal (sandi salah, kuota habis, relay ditolak). Tanpa mencatatnya,
           yang tersisa hanya "gagal kirim" dan tidak ada yang bisa diperbaiki. */
        log_message('error', 'Surat_lib gagal kirim ke ' . $ke . ': '
            . strip_tags($this->CI->email->print_debugger(array('headers'))));

        return FALSE;
    }

    /* ------------------------------------------------------------------ */

    /**
     * Cadangan saat SMTP belum diatur: tulis emailnya ke log.
     *
     * Dicatat sebagai 'error', bukan 'info' atau 'debug'. Bukan karena ini
     * galat, tapi karena threshold log bawaan CodeIgniter sering hanya
     * menyalakan level 1 (error) - dan email yang dicatat di level yang
     * dimatikan sama saja dengan email yang hilang.
     */
    protected function ke_log($ke, $judul, $isi)
    {
        // Tautan dipisahkan supaya tidak perlu mengurai HTML di log.
        preg_match_all('#https?://[^"\'<\s]+#i', $isi, $m);
        $tautan = ! empty($m[0]) ? implode("\n  ", array_unique($m[0])) : '(tidak ada)';

        log_message('error',
            "SURAT TIDAK DIKIRIM - SMTP belum diatur di config/surat.php.\n"
            . "  Kepada : {$ke}\n"
            . "  Judul  : {$judul}\n"
            . "  Tautan :\n  {$tautan}\n"
            . "  Salin tautan di atas ke browser untuk melanjutkan."
        );

        /* FALSE, bukan TRUE. Yang memanggil memakai nilai ini untuk memberi
           tahu pengguna apakah emailnya benar-benar berangkat; mengaku
           berhasil di sini berarti pengguna menunggu email yang tidak akan
           pernah datang. */
        return FALSE;
    }

    /** HTML jadi teks yang masih enak dibaca. */
    protected function jadi_teks($html)
    {
        /* <head> dibuang lebih dulu, berikut blok pratinjau yang
           display:none. Keduanya tidak terlihat di versi HTML, tapi
           strip_tags() tidak tahu soal CSS - jadi tanpa langkah ini judul
           dan teks pratinjau muncul di atas versi teks, mengulang apa yang
           sudah ada di subjek. */
        $t = preg_replace('#<head\b.*?</head>#is', '', $html);
        $t = preg_replace('#<(script|style)\b.*?</\1>#is', '', $t);
        $t = preg_replace('#<div[^>]*display:\s*none.*?</div>#is', '', $t);

        $t = preg_replace('#<(br|/p|/div|/h[1-6]|/tr)[^>]*>#i', "\n", $t);
        $t = html_entity_decode(strip_tags($t), ENT_QUOTES, 'UTF-8');

        // Spasi ganda jadi satu. Dilakukan sebelum merapikan baris, karena
        // tabel tata letak meninggalkan banyak indentasi.
        $t = preg_replace('/[ \t]+/', ' ', $t);

        /* Baris yang isinya cuma spasi dikosongkan DULU.

           Tanpa ini, perapian baris kosong di bawah tidak kena apa-apa:
           barisnya bukan "\n\n\n" melainkan "\n \n \n", dan pola \n{3,}
           tidak cocok dengan itu. Gejalanya khas - versi teks penuh baris
           kosong berspasi, dan tabel tata letak email memang menghasilkan
           puluhan di antaranya. */
        $t = preg_replace('/\n[ \t]+/', "\n", $t);

        // Dua baris kosong atau lebih jadi satu.
        $t = preg_replace("/\n{3,}/", "\n\n", $t);

        return trim($t);
    }
}
