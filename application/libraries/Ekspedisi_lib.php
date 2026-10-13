<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Ekspedisi_lib - daftar kurir, lacak resi, dan cek tarif ongkir.
 *
 * Simpan di: application/libraries/Ekspedisi_lib.php
 *
 * Dua mode:
 *   1. Tanpa kunci API (bawaan) - penjual memilih kurir dari daftar, pembeli
 *      mendapat tombol "Salin resi" + tautan ke halaman lacak resmi kurir.
 *   2. Dengan agregator (Admin > Pengaturan > Ekspedisi) - riwayat perjalanan
 *      paket tampil langsung di halaman pesanan, dan penjual bisa
 *      membandingkan tarif beberapa kurir sekaligus.
 *
 * Format API mengikuti Biteship (api.biteship.com):
 *   GET  /v1/trackings/{resi}/couriers/{kode}
 *   POST /v1/rates/couriers
 * Agregator lain dengan format sama cukup diganti alamatnya di pengaturan.
 *
 * Hasil lacak disimpan di cache berkas 30 menit supaya membuka halaman
 * pesanan berkali-kali tidak menghabiskan kuota API.
 */
class Ekspedisi_lib
{
    protected $CI;

    /** kode => [nama, halaman lacak resmi]. Kode = kode kurir di agregator. */
    protected $kurir = array(
        'jne'       => array('JNE',              'https://www.jne.co.id/tracking-package'),
        'jnt'       => array('J&T Express',      'https://jet.co.id/track'),
        'sicepat'   => array('SiCepat',          'https://www.sicepat.com/checkAwb'),
        'pos'       => array('Pos Indonesia',    'https://www.posindonesia.co.id/id/tracking'),
        'anteraja'  => array('AnterAja',         'https://anteraja.id/tracking'),
        'ninja'     => array('Ninja Xpress',     'https://www.ninjaxpress.co/id-id/tracking'),
        'lion'      => array('Lion Parcel',      'https://lionparcel.com/track/stt'),
        'idexpress' => array('ID Express',       'https://idexpress.com/lacak-paket/'),
        'sap'       => array('SAP Express',      'https://www.sap-express.id/layanan/tracking'),
        'tiki'      => array('TIKI',             'https://www.tiki.id/id/track'),
        'wahana'    => array('Wahana',           'https://www.wahana.com/'),
        'gojek'     => array('GoSend',           NULL),
        'grab'      => array('GrabExpress',      NULL),
        'sendiri'   => array('Diantar sendiri',  NULL),
    );

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('pengaturan_lib');
    }

    /** @return array kode => nama, untuk <datalist>/<select>. */
    public function daftar()
    {
        $out = array();
        foreach ($this->kurir as $k => $x) {
            $out[$k] = $x[0];
        }
        return $out;
    }

    /**
     * Teks bebas yang diketik penjual ("jnt", "J&T", "jne reg") -> kode.
     * @return string|NULL
     */
    public function kode($teks)
    {
        $t = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $teks));
        if ($t === '') {
            return NULL;
        }
        $alias = array(
            'jt' => 'jnt', 'jet' => 'jnt', 'jntexpress' => 'jnt', 'jtexpress' => 'jnt',
            'posindonesia' => 'pos', 'ninjaxpress' => 'ninja', 'lionparcel' => 'lion',
            'gosend' => 'gojek', 'grabexpress' => 'grab', 'sapexpress' => 'sap',
            'diantarsendiri' => 'sendiri', 'antarsendiri' => 'sendiri', 'kurirtoko' => 'sendiri',
        );
        foreach ($alias as $a => $k) {
            if (strpos($t, $a) === 0) {
                return $k;
            }
        }
        foreach ($this->kurir as $k => $x) {
            $n = strtolower(preg_replace('/[^a-z0-9]/i', '', $x[0]));
            if (strpos($t, $k) === 0 || strpos($t, $n) === 0) {
                return $k;
            }
        }
        return NULL;
    }

    public function api_aktif()
    {
        $p = $this->CI->pengaturan_lib;
        return $p->get('ongkir_api_aktif') === '1' && $p->get('ongkir_api_kunci') !== ''
            && function_exists('curl_init');
    }

    /**
     * Lacak paket.
     * @return array|NULL NULL kalau pesanan tidak punya resi.
     *   ['kurir', 'resi', 'tautan' (halaman resmi|NULL), 'status' (teks|NULL),
     *    'riwayat' => [['waktu', 'ket'], ...] terbaru dulu, 'sumber' => 'api'|'manual']
     */
    public function lacak($kurir_teks, $resi)
    {
        $resi = trim((string) $resi);
        if ($resi === '') {
            return NULL;
        }
        $kode = $this->kode($kurir_teks);
        $out = array(
            'kurir'   => $kode ? $this->kurir[$kode][0] : (string) $kurir_teks,
            'resi'    => $resi,
            'tautan'  => $kode ? $this->kurir[$kode][1] : NULL,
            'status'  => NULL,
            'riwayat' => array(),
            'sumber'  => 'manual',
        );
        if ( ! $kode || ! $this->kurir[$kode][1] || ! $this->api_aktif()) {
            return $out;
        }

        $this->CI->load->driver('cache', array('adapter' => 'file'));
        $kunci_cache = 'lacak_' . $kode . '_' . preg_replace('/[^A-Za-z0-9]/', '', $resi);
        $hasil = $this->CI->cache->get($kunci_cache);
        if ($hasil === FALSE) {
            $hasil = $this->minta('GET', '/v1/trackings/' . rawurlencode($resi) . '/couriers/' . $kode);
            // Gagal pun disimpan sebentar supaya API yang sedang down tidak dipukul terus.
            $this->CI->cache->save($kunci_cache, $hasil ?: array(), $hasil ? 1800 : 300);
        }
        if (empty($hasil['history']) || ! is_array($hasil['history'])) {
            return $out;
        }

        $riwayat = array();
        foreach ($hasil['history'] as $h) {
            $riwayat[] = array(
                'waktu' => isset($h['updated_at']) ? date('Y-m-d H:i', strtotime($h['updated_at'])) : '',
                'ket'   => isset($h['note']) ? (string) $h['note'] : (isset($h['status']) ? (string) $h['status'] : ''),
            );
        }
        usort($riwayat, function ($a, $b) { return strcmp($b['waktu'], $a['waktu']); });

        $out['riwayat'] = $riwayat;
        $out['status']  = isset($hasil['status']) ? $this->nama_status($hasil['status']) : NULL;
        $out['sumber']  = 'api';
        return $out;
    }

    /**
     * Bandingkan tarif kurir.
     * @return array|string daftar tarif (termurah dulu) atau pesan galat.
     *   [['kurir', 'layanan', 'harga', 'estimasi'], ...]
     */
    public function tarif($asal, $tujuan, $berat_gram, $nilai = 0)
    {
        if ( ! $this->api_aktif()) {
            return 'Cek tarif otomatis belum diaktifkan admin.';
        }
        $kurir = array();
        foreach ($this->kurir as $k => $x) {
            if ($x[1]) {
                $kurir[] = $k;
            }
        }
        $hasil = $this->minta('POST', '/v1/rates/couriers', array(
            'origin_postal_code'      => (int) $asal,
            'destination_postal_code' => (int) $tujuan,
            'couriers'                => implode(',', $kurir),
            'items'                   => array(array(
                'name' => 'Paket', 'value' => max(1000, (int) $nilai),
                'weight' => max(1, (int) $berat_gram), 'quantity' => 1,
            )),
        ));
        if ( ! $hasil || empty($hasil['pricing'])) {
            return 'Tarif tidak ditemukan. Periksa kode pos asal dan tujuan.';
        }
        $out = array();
        foreach ($hasil['pricing'] as $r) {
            $out[] = array(
                'kurir'    => isset($r['courier_name']) ? $r['courier_name'] : '',
                'layanan'  => isset($r['courier_service_name']) ? $r['courier_service_name'] : '',
                'harga'    => isset($r['price']) ? (int) $r['price'] : 0,
                'estimasi' => isset($r['duration'])
                    ? str_ireplace(array('days', 'day', 'hours', 'hour'), array('hari', 'hari', 'jam', 'jam'), $r['duration'])
                    : (isset($r['shipment_duration_range']) ? $r['shipment_duration_range'] . ' hari' : ''),
            );
        }
        usort($out, function ($a, $b) { return $a['harga'] - $b['harga']; });
        return $out;
    }

    /* ------------------------------------------------------------------ */

    protected function nama_status($s)
    {
        $peta = array(
            'confirmed' => 'Pesanan dikonfirmasi', 'allocated' => 'Kurir ditugaskan', 'picking_up' => 'Kurir menuju penjual',
            'picked' => 'Paket diambil kurir', 'dropping_off' => 'Dalam perjalanan', 'on_hold' => 'Tertahan',
            'delivered' => 'Terkirim', 'returned' => 'Dikembalikan', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan',
            'courier_not_found' => 'Kurir tidak ditemukan', 'disposed' => 'Dimusnahkan',
        );
        $s = strtolower((string) $s);
        return isset($peta[$s]) ? $peta[$s] : ucfirst(str_replace('_', ' ', $s));
    }

    /** @return array|NULL JSON hasil, NULL kalau gagal. */
    protected function minta($metode, $jalur, $isi = NULL)
    {
        $p = $this->CI->pengaturan_lib;
        $ch = curl_init(rtrim($p->get('ongkir_api_url'), '/') . $jalur);
        $opsi = array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_HTTPHEADER     => array('Authorization: ' . $p->get('ongkir_api_kunci'), 'Content-Type: application/json'),
        );
        if ($metode === 'POST') {
            $opsi[CURLOPT_POST] = TRUE;
            $opsi[CURLOPT_POSTFIELDS] = json_encode($isi);
        }
        curl_setopt_array($ch, $opsi);
        $balas = curl_exec($ch);
        $kode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($balas === FALSE || $kode < 200 || $kode >= 300) {
            log_message('error', 'Ekspedisi ' . $jalur . ' gagal: HTTP ' . $kode);
            return NULL;
        }
        $j = json_decode($balas, TRUE);
        return is_array($j) ? $j : NULL;
    }
}
