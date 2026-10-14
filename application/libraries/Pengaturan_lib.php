<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Pengaturan_lib - pengaturan sistem yang bisa diubah admin dari panel.
 *
 * Simpan di: application/libraries/Pengaturan_lib.php
 * Pakai    : $this->load->library('pengaturan_lib');
 *            $this->pengaturan_lib->get('komisi_persen');
 *
 * Nilai dibaca dari tabel `settings`. Kalau kuncinya belum pernah disimpan,
 * jatuh ke config/marketplace.php, lalu ke nilai bawaan di daftar() - jadi
 * situs tetap berjalan walau tabelnya kosong atau migrasi 26 belum
 * dijalankan.
 */
class Pengaturan_lib
{
    protected $CI;

    /** Cache per permintaan - satu query untuk semua kunci. */
    protected $_nilai = NULL;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->config->load('marketplace', TRUE, TRUE);
    }

    /**
     * Semua kunci yang dikenali, beserta label & jenis kolomnya di formulir.
     * Kunci yang tidak ada di sini DITOLAK saat disimpan - formulir tidak
     * bisa dipakai menulis kunci sembarang.
     *
     * rahasia = TRUE: nilainya tidak pernah dicetak balik ke formulir.
     */
    public function daftar()
    {
        return array(
            'Umum' => array(
                'nama_brand'   => array('label' => 'Nama marketplace', 'jenis' => 'teks', 'bawaan' => 'Sapa UMKM'),
                'slogan'       => array('label' => 'Slogan', 'jenis' => 'teks', 'bawaan' => 'Belanja langsung dari pelaku UMKM di kotamu'),
                'wa_admin'     => array('label' => 'WhatsApp admin (628...)', 'jenis' => 'teks', 'bawaan' => ''),
                'email_admin'  => array('label' => 'Email admin', 'jenis' => 'teks', 'bawaan' => ''),
            ),
            'Keuangan' => array(
                'komisi_persen' => array('label' => 'Komisi platform (%)', 'jenis' => 'angka', 'bawaan' => 5,
                                         'catatan' => 'Dari harga barang, bukan ongkir. Pesanan lama tidak berubah.'),
                'tahan_hari'    => array('label' => 'Tahan dana setelah selesai (hari)', 'jenis' => 'angka', 'bawaan' => 0),
            ),
            'WhatsApp' => array(
                /* Ketiganya tidak tampil di formulir umum ('tersembunyi'):
                   WhatsApp diatur lewat geseran Mati/Bind/FastWA/Kirimi di
                   Pengaturan (admin/Pengaturan::server_wa), yang menulis
                   wa_aktif di sini dan utility.server_wa untuk Api_Whatsapp.
                   wa_url & wa_token = gerbang cadangan format Fonnte, hanya
                   dipakai bila Api_Whatsapp.php tidak ada. */
                'wa_aktif'  => array('label' => 'Kirim notifikasi WhatsApp', 'jenis' => 'ya_tidak', 'tersembunyi' => TRUE,
                                     'bawaan' => is_file(APPPATH . 'libraries/Api_Whatsapp.php') ? '1' : '0'),
                'wa_url'    => array('label' => 'Alamat API gerbang WA', 'jenis' => 'teks', 'tersembunyi' => TRUE,
                                     'bawaan' => 'https://api.fonnte.com/send'),
                'wa_token'  => array('label' => 'Token gerbang WA', 'jenis' => 'teks', 'bawaan' => '', 'rahasia' => TRUE, 'tersembunyi' => TRUE),
            ),
            'Email' => array(
                'email_aktif'   => array('label' => 'Kirim notifikasi email', 'jenis' => 'ya_tidak', 'bawaan' => '0'),
                'email_dari'    => array('label' => 'Alamat pengirim', 'jenis' => 'teks', 'bawaan' => ''),
                'smtp_host'     => array('label' => 'SMTP host', 'jenis' => 'teks', 'bawaan' => '',
                                         'catatan' => 'Kosongkan untuk memakai SMTP di config/surat.php (yang juga dipakai email reset sandi), atau mail() server kalau itu pun kosong.'),
                'smtp_port'     => array('label' => 'SMTP port', 'jenis' => 'angka', 'bawaan' => 587),
                'smtp_user'     => array('label' => 'SMTP user', 'jenis' => 'teks', 'bawaan' => ''),
                'smtp_pass'     => array('label' => 'SMTP password', 'jenis' => 'teks', 'bawaan' => '', 'rahasia' => TRUE),
            ),
            'Peta' => array(
                'google_maps_key' => array('label' => 'Kunci API Google Maps (opsional)', 'jenis' => 'teks', 'bawaan' => '',
                                           'catatan' => 'Cadangan terakhir untuk membaca kecamatan dari GPS, setelah OpenStreetMap dan BigDataCloud. '
                                                      . 'Aktifkan Geocoding API dan BATASI kunci ke domain situs ini - kunci terlihat di halaman.'),
            ),
            'Ekspedisi' => array(
                'ongkir_api_aktif' => array('label' => 'Cek tarif & resi lewat agregator', 'jenis' => 'ya_tidak', 'bawaan' => '0'),
                'ongkir_api_url'   => array('label' => 'Alamat API agregator', 'jenis' => 'teks', 'bawaan' => 'https://api.biteship.com'),
                'ongkir_api_kunci' => array('label' => 'Kunci API agregator', 'jenis' => 'teks', 'bawaan' => '', 'rahasia' => TRUE),
            ),
            'Promo' => array(
                'subsidi_ongkir_maks' => array('label' => 'Subsidi ongkir platform per pesanan (Rp)', 'jenis' => 'angka', 'bawaan' => 0,
                                               'catatan' => '0 = tidak ada subsidi. Ditanggung platform, tidak memotong bagian toko.'),
                'subsidi_ongkir_min'  => array('label' => 'Subsidi berlaku mulai belanja (Rp)', 'jenis' => 'angka', 'bawaan' => 0),
            ),
            'Stok' => array(
                'stok_menipis' => array('label' => 'Ambang stok menipis', 'jenis' => 'angka', 'bawaan' => 5,
                                        'catatan' => 'Penjual diberi tahu saat stok produk sampai di angka ini.'),
            ),
        );
    }

    /** Semua definisi dalam satu array datar: kunci => definisi. */
    public function definisi()
    {
        $datar = array();
        foreach ($this->daftar() as $kelompok) {
            $datar += $kelompok;
        }
        return $datar;
    }

    public function get($kunci, $bawaan = NULL)
    {
        $semua = $this->semua();
        if (array_key_exists($kunci, $semua) && $semua[$kunci] !== NULL && $semua[$kunci] !== '') {
            return $semua[$kunci];
        }

        // Nilai lama di config/marketplace.php & config.php tetap dihormati.
        $cfg = $this->CI->config->item($kunci, 'marketplace');
        if ($cfg === NULL) {
            $cfg = $this->CI->config->item($kunci);
        }
        if ($cfg !== NULL && $cfg !== FALSE && $cfg !== '') {
            return $cfg;
        }

        if ($bawaan !== NULL) {
            return $bawaan;
        }
        $def = $this->definisi();
        return isset($def[$kunci]) ? $def[$kunci]['bawaan'] : NULL;
    }

    public function aktif($kunci)
    {
        return (string) $this->get($kunci) === '1';
    }

    /** Semua nilai tersimpan. Tabel yang belum ada = kosong, bukan galat. */
    public function semua()
    {
        if ($this->_nilai !== NULL) {
            return $this->_nilai;
        }
        $this->_nilai = array();

        if ( ! $this->CI->db->table_exists('settings')) {
            return $this->_nilai;
        }
        foreach ($this->CI->db->get('settings')->result_array() as $r) {
            $this->_nilai[$r['kunci']] = $r['nilai'];
        }
        return $this->_nilai;
    }

    /**
     * Simpan beberapa kunci sekaligus. Kunci di luar daftar() diabaikan.
     * Kolom rahasia yang dikirim kosong TIDAK menimpa nilai lama - formulir
     * memang tidak pernah mengisinya kembali.
     *
     * @return array kunci yang benar-benar berubah
     */
    public function simpan(array $data)
    {
        $def    = $this->definisi();
        $lama   = $this->semua();
        $ubah   = array();

        foreach ($data as $k => $v) {
            if ( ! isset($def[$k])) {
                continue;
            }
            $v = is_string($v) ? trim($v) : $v;

            if ( ! empty($def[$k]['rahasia']) && ($v === '' || $v === NULL)) {
                continue;
            }
            if ($def[$k]['jenis'] === 'angka') {
                $v = (string) max(0, (float) str_replace(',', '.', preg_replace('/[^0-9,.]/', '', (string) $v)));
            }
            if ($def[$k]['jenis'] === 'ya_tidak') {
                $v = $v === '1' ? '1' : '0';
            }

            if (isset($lama[$k]) && (string) $lama[$k] === (string) $v) {
                continue;
            }

            $this->CI->db->replace('settings', array('kunci' => $k, 'nilai' => $v));
            $ubah[] = $k;
        }

        $this->_nilai = NULL;
        return $ubah;
    }
}
