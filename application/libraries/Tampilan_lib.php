<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tampilan_lib - memilih antara tampilan mobile dan desktop.
 * Simpan di: application/libraries/Tampilan_lib.php
 *
 * Urutannya:
 *   1. ?tampilan=mobile / ?tampilan=desktop  -> pilihan pengguna, disimpan
 *   2. cookie pilihan sebelumnya
 *   3. deteksi dari user agent
 *
 * Pilihan manual selalu menang. Deteksi perangkat TIDAK PERNAH tepat 100% -
 * tablet, browser dengan "mode desktop", dan perangkat baru sering salah
 * dikenali. Tanpa jalan keluar manual, pengguna yang salah dikenali
 * terjebak di tampilan yang tidak cocok.
 */
class Tampilan_lib {

    /* Dua cookie, sengaja dipisah:
         tampilan       -> pilihan MANUAL pengguna, selalu menang
         tampilan_lebar -> hasil pengukuran lebar layar oleh JavaScript
       Kalau disatukan, koreksi otomatis akan menimpa pilihan manual dan
       tombol "Buka tampilan desktop" jadi tidak berguna. */
    const COOKIE = 'tampilan';
    const COOKIE_LEBAR = 'tampilan_lebar';

    protected $CI;
    protected $mode;

    /* Ditandai saat pengguna baru saja menekan "Ikuti ukuran layar".
       Cookie yang baru dihapus MASIH terbaca di permintaan yang sama -
       browser baru membuangnya di permintaan berikutnya - jadi tanpa
       penanda ini, halaman balasannya masih mengaku terkunci. */
    protected $lepas_manual = FALSE;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->helper(array('cookie', 'url'));
    }

    public function mode()
    {
        if ($this->mode !== NULL) {
            return $this->mode;
        }

        $minta = $this->CI->input->get('tampilan');

        /* 'auto' mengembalikan ke deteksi otomatis: cookie pilihan manual
           dihapus, dan lebar layar kembali jadi penentu. */
        if ($minta === 'auto') {
            delete_cookie(self::COOKIE);

            /* Cookie yang baru dihapus MASIH terbaca di permintaan yang sama -
               browser baru membuangnya di permintaan berikutnya. Tanpa
               penanda ini, halaman balasannya masih mengaku terkunci dan
               tampilan.js ikut berhenti bekerja. */
            $this->lepas_manual = TRUE;
            unset($_COOKIE[config_item('cookie_prefix') . self::COOKIE], $_COOKIE[self::COOKIE]);

            // Lebar layar yang sudah terukur tetap dipakai, bukan menebak ulang.
            return $this->mode = $this->dari_lebar();
        }

        if ($minta === 'mobile' || $minta === 'desktop') {
            // Setahun: pilihan tampilan bukan sesuatu yang orang ingin
            // tentukan ulang tiap kali membuka situs.
            set_cookie(self::COOKIE, $minta, 31536000);

            /* $_COOKIE ikut diisi untuk permintaan INI juga. set_cookie()
               hanya mengirim header, jadi tanpa baris ini manual() masih
               menjawab FALSE di halaman yang sama - dan tampilan.js akan
               menganggapnya pilihan otomatis lalu memantulkannya balik
               sesuai lebar layar. Sakelarnya seolah tidak berfungsi. */
            $_COOKIE[config_item('cookie_prefix') . self::COOKIE] = $minta;

            return $this->mode = $minta;
        }

        $cookie = $this->CI->input->cookie(self::COOKIE, TRUE);
        if ($cookie === 'mobile' || $cookie === 'desktop') {
            return $this->mode = $cookie;
        }

        return $this->mode = $this->dari_lebar();
    }

    /** Tanpa pilihan manual: ikut lebar layar, lalu user agent. */
    protected function dari_lebar()
    {
        $lebar = $this->CI->input->cookie(self::COOKIE_LEBAR, TRUE);
        if ($lebar === 'mobile' || $lebar === 'desktop') {
            return $lebar;
        }
        return $this->deteksi();
    }

    /** Sedang mengikuti perangkat, bukan pilihan yang dikunci pengguna. */
    public function otomatis()
    {
        return ! $this->manual();
    }

    public function is_mobile()
    {
        return $this->mode() === 'mobile';
    }

    /** Apakah pilihan tampilan berasal dari pengguna sendiri? */
    public function manual()
    {
        if ($this->lepas_manual) {
            return FALSE;
        }
        $c = $this->CI->input->cookie(self::COOKIE, TRUE);
        return ($c === 'mobile' || $c === 'desktop');
    }

    protected function deteksi()
    {
        /* Client Hint dari browser modern. Ini JAWABAN BROWSER, bukan tebakan
           dari teks user agent - jadi dipercaya lebih dulu. Browser lama
           tidak mengirimnya, dan di situ baru user agent dipakai. */
        $ch = $this->CI->input->server('HTTP_SEC_CH_UA_MOBILE');
        if ($ch === '?1') {
            return 'mobile';
        }
        if ($ch === '?0') {
            return 'desktop';
        }

        $this->CI->load->library('user_agent');

        /* Tablet dianggap desktop: layarnya cukup lebar untuk tata letak dua
           kolom, dan tampilan mobile akan terlihat kosong melompong di sana. */
        if ($this->CI->agent->is_mobile() && ! $this->CI->agent->is_mobile('tablet')) {
            return 'mobile';
        }
        return 'desktop';
    }

    /**
     * Apakah perangkatnya SEBENARNYA HP, terlepas dari tampilan yang sedang
     * dipakai? Dipakai kerangka desktop untuk memutuskan apakah tautan
     * "Buka tampilan HP" perlu ditampilkan.
     */
    public function perangkat_hp()
    {
        if ($this->CI->input->cookie(self::COOKIE_LEBAR, TRUE) === 'mobile') {
            return TRUE;
        }
        return $this->deteksi() === 'mobile';
    }

    /**
     * Alamat halaman ini dengan tampilan tertentu.
     *
     * @param string $mode 'mobile', 'desktop', atau 'auto'
     */
    public function url_mode($mode)
    {
        $q = $this->CI->input->server('QUERY_STRING');
        parse_str((string) $q, $par);
        $par['tampilan'] = $mode;

        return site_url(uri_string()) . '?' . http_build_query($par);
    }

    /** Alamat halaman ini dengan tampilan yang berlawanan. */
    public function url_tukar()
    {
        return $this->url_mode($this->is_mobile() ? 'desktop' : 'mobile');
    }
}
