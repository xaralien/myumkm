<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Rincian_lib - menghitung rincian checkout: ongkir, voucher, subsidi,
 * pre-order, dan uang muka, per toko.
 *
 * Simpan di: application/libraries/Rincian_lib.php
 *
 * SATU-SATUNYA tempat angka checkout dihitung. Halaman checkout (lewat
 * AJAX checkout/hitung) dan penyimpanan pesanan (checkout/place) memanggil
 * fungsi yang sama - jadi angka yang dilihat pembeli PASTI sama dengan yang
 * ditagihkan. Dulu ongkir dihitung dua kali (JavaScript & PHP) dan harus
 * dijaga tetap selaras.
 *
 * Urutan potongan per toko:
 *   1. ongkir dasar toko (sudah termasuk gratis ongkir minimal belanja toko)
 *   2. voucher toko         (barang atau ongkir)      -> diskon_toko
 *   3. voucher platform     (dibagi proporsional)     -> diskon_platform
 *   4. subsidi ongkir otomatis dari Pengaturan        -> subsidi_ongkir
 */
class Rincian_lib
{
    protected $CI;

    /** Batas tanggal kirim pre-order: paling jauh 90 hari ke depan. */
    const PO_MAKS_HARI = 90;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library(array('cart_lib', 'voucher_lib', 'pengaturan_lib'));
        $this->CI->load->helper('money');
    }

    /**
     * @param array $per_toko  hasil Cart_lib::per_toko()
     * @param int   $district  kecamatan tujuan (0 = belum dipilih)
     * @param array $kode      kode voucher yang dimasukkan pembeli
     * @param array $pembeli   ['user_id' => ?, 'phone' => ?]
     * @param array $tanggal   [store_id => 'Y-m-d'] tanggal kirim pre-order
     */
    public function hitung(array $per_toko, $district, array $kode = array(), array $pembeli = array(), array $tanggal = array())
    {
        $uid   = isset($pembeli['user_id']) ? $pembeli['user_id'] : NULL;
        $phone = isset($pembeli['phone']) ? $pembeli['phone'] : NULL;

        $hasil = array(
            'ok'        => TRUE,
            'kelompok'  => array(),
            'subtotal'  => 0,
            'ongkir'    => 0,
            'potongan'  => 0,
            'total'     => 0,
            'bayar_sekarang' => 0,
            'voucher'   => array(),   // kode => ['ok','pesan','ket','potongan']
            'pesan'     => array(),
        );

        /* ---- 1. per toko: ongkir & pre-order ----------------------------- */
        foreach ($per_toko as $sid => $items) {
            $toko = $this->CI->cart_lib->toko($sid);
            if ( ! $toko) {
                continue;
            }

            $subtotal = 0;
            $po = array('ada' => FALSE, 'hari' => 0, 'dp' => 0, 'produk' => array());
            foreach ($items as $it) {
                $subtotal += (int) $it['line_total'];
                if ( ! empty($it['is_preorder'])) {
                    $po['ada']  = TRUE;
                    $po['hari'] = max($po['hari'], (int) $it['po_hari']);
                    $po['dp']   = max($po['dp'], (int) $it['po_dp_persen']);
                    $pid = (int) $it['product_id'];
                    if ( ! isset($po['produk'][$pid])) {
                        $po['produk'][$pid] = array('nama' => $it['product_name'], 'qty' => 0, 'kuota' => $it['po_kuota']);
                    }
                    $po['produk'][$pid]['qty'] += (int) $it['qty'];
                }
            }

            $k = array(
                'toko'          => $toko,
                'items'         => $items,
                'subtotal'      => $subtotal,
                'ongkir_ok'     => FALSE,
                'ongkir'        => 0,
                'ongkir_pesan'  => 'Pilih wilayah tujuan dulu.',
                'tingkat'       => NULL,
                'diskon_toko'   => 0,
                'voucher_toko'  => NULL,
                'diskon_platform' => 0,
                'voucher_platform' => NULL,
                'subsidi_ongkir' => 0,
                'total'         => 0,
                'preorder'      => $po,
                'tanggal_min'   => NULL,
                'tanggal_kirim' => NULL,
                'dp_persen'     => 0,
                'dp_jumlah'     => NULL,
                'galat'         => array(),
            );

            // Toko libur: pesanan biasa ditolak, pre-order tetap boleh
            // asalkan tanggal kirimnya setelah toko buka lagi.
            $libur = $this->libur($toko);
            if ($libur && ! $po['ada']) {
                $k['galat'][] = $toko['name'] . ' sedang libur sampai ' . tgl_id($libur)
                    . ($toko['libur_pesan'] ?? '' ? ' (' . $toko['libur_pesan'] . ')' : '') . '.';
            }

            if ($district) {
                $o = $this->CI->cart_lib->shipping_fee($district, $subtotal, $toko);
                $k['ongkir_ok']    = (bool) $o['ok'];
                $k['ongkir']       = (int) $o['ongkir'];
                $k['ongkir_pesan'] = $o['ok'] ? $this->ket_tingkat($o['tingkat'], $toko) : $o['pesan'];
                $k['tingkat']      = $o['tingkat'];
                if ( ! $o['ok']) {
                    $k['galat'][] = $o['pesan'];
                }
            }

            if ($po['ada']) {
                $min = date('Y-m-d', strtotime('+' . max(1, $po['hari']) . ' days'));
                if ($libur && $libur >= $min) {
                    $min = date('Y-m-d', strtotime($libur . ' +1 day'));
                }
                $k['tanggal_min'] = $min;
                $pilih = isset($tanggal[$sid]) ? (string) $tanggal[$sid] : '';
                $cek = $this->periksa_tanggal($pilih, $min, $po['produk']);
                if ($cek === TRUE) {
                    $k['tanggal_kirim'] = $pilih;
                } else {
                    $k['galat'][] = $cek;
                }
                $k['dp_persen'] = $po['dp'];
            }

            $hasil['kelompok'][$sid] = $k;
        }

        /* ---- 2. voucher ---------------------------------------------------- */
        $platform = NULL;
        foreach (array_unique(array_filter(array_map(array($this->CI->voucher_lib, 'rapikan_kode'), $kode))) as $kd) {
            $v = $this->CI->voucher_lib->cari($kd);
            if ( ! $v) {
                $hasil['voucher'][$kd] = array('ok' => FALSE, 'pesan' => 'Kode ' . $kd . ' tidak ditemukan.');
                continue;
            }

            if ($v['store_id'] === NULL) {
                if ($platform) {
                    $hasil['voucher'][$kd] = array('ok' => FALSE, 'pesan' => 'Hanya satu voucher platform per checkout.');
                    continue;
                }
                $platform = $v;
                continue;   // dihitung setelah voucher toko
            }

            $sid = (int) $v['store_id'];
            if ( ! isset($hasil['kelompok'][$sid])) {
                $hasil['voucher'][$kd] = array('ok' => FALSE, 'pesan' => 'Voucher ' . $kd . ' hanya untuk produk dari tokonya.');
                continue;
            }
            $k =& $hasil['kelompok'][$sid];
            if ($k['voucher_toko']) {
                $hasil['voucher'][$kd] = array('ok' => FALSE, 'pesan' => 'Hanya satu voucher per toko.');
                unset($k);
                continue;
            }
            $cek = $this->CI->voucher_lib->periksa($v, $k['subtotal'], $uid, $phone);
            if ( ! $cek['ok']) {
                $hasil['voucher'][$kd] = array('ok' => FALSE, 'pesan' => $cek['pesan']);
                unset($k);
                continue;
            }
            $pot = $this->CI->voucher_lib->potongan($v, $k['subtotal'], $k['ongkir']);
            $k['diskon_toko']  = $pot;
            $k['voucher_toko'] = $v;
            $hasil['voucher'][$kd] = array('ok' => TRUE, 'pesan' => '', 'ket' => $this->CI->voucher_lib->keterangan($v),
                                           'potongan' => $pot, 'toko' => $k['toko']['name']);
            unset($k);
        }

        if ($platform) {
            $this->terapkan_platform($hasil, $platform, $uid, $phone);
        }

        /* ---- 3. subsidi ongkir & total ------------------------------------ */
        $maks = (int) $this->CI->pengaturan_lib->get('subsidi_ongkir_maks');
        $min  = (int) $this->CI->pengaturan_lib->get('subsidi_ongkir_min');

        foreach ($hasil['kelompok'] as $sid => &$k) {
            $sisa_ongkir = $k['ongkir'] - $this->bagian_ongkir($k['voucher_toko'], $k['diskon_toko'])
                                        - $this->bagian_ongkir($k['voucher_platform'], $k['diskon_platform']);
            if ($maks > 0 && $k['ongkir_ok'] && $k['subtotal'] >= $min && $sisa_ongkir > 0) {
                $k['subsidi_ongkir'] = min($maks, $sisa_ongkir);
            }

            $k['total'] = max(0, $k['subtotal'] + $k['ongkir'] - $k['diskon_toko'] - $k['diskon_platform'] - $k['subsidi_ongkir']);

            if ($k['dp_persen'] > 0 && $k['total'] > 0) {
                $k['dp_jumlah'] = (int) ceil($k['total'] * $k['dp_persen'] / 100);
            }

            $hasil['subtotal'] += $k['subtotal'];
            $hasil['ongkir']   += $k['ongkir'];
            $hasil['potongan'] += $k['diskon_toko'] + $k['diskon_platform'] + $k['subsidi_ongkir'];
            $hasil['total']    += $k['total'];
            $hasil['bayar_sekarang'] += $k['dp_jumlah'] !== NULL ? $k['dp_jumlah'] : $k['total'];

            if ($k['galat']) {
                $hasil['ok'] = FALSE;
                $hasil['pesan'] = array_merge($hasil['pesan'], $k['galat']);
            }
        }
        unset($k);

        if ( ! $district) {
            $hasil['ok'] = FALSE;
        }

        return $hasil;
    }

    /**
     * Voucher platform: syarat minimal belanja dihitung dari SEMUA toko,
     * potongannya dibagi ke tiap toko sebanding subtotalnya - setiap pesanan
     * menyimpan bagiannya sendiri, supaya refund satu toko tetap benar.
     */
    protected function terapkan_platform(array &$hasil, array $v, $uid, $phone)
    {
        $layak = array();
        $sub = 0;
        $ong = 0;
        foreach ($hasil['kelompok'] as $sid => $k) {
            if ($k['ongkir_ok'] || ! $k['tingkat']) {
                $layak[] = $sid;
                $sub += $k['subtotal'] - ($v['jenis'] === 'ongkir' ? 0 : $this->bagian_barang($k['voucher_toko'], $k['diskon_toko']));
                $ong += $k['ongkir'] - $this->bagian_ongkir($k['voucher_toko'], $k['diskon_toko']);
            }
        }

        $cek = $this->CI->voucher_lib->periksa($v, $sub, $uid, $phone);
        if ( ! $cek['ok']) {
            $hasil['voucher'][$v['kode']] = array('ok' => FALSE, 'pesan' => $cek['pesan']);
            return;
        }

        $total_pot = $this->CI->voucher_lib->potongan($v, $sub, $ong);
        $sisa = $total_pot;

        foreach ($layak as $i => $sid) {
            $k =& $hasil['kelompok'][$sid];
            $dasar_k = $v['jenis'] === 'ongkir'
                ? $k['ongkir'] - $this->bagian_ongkir($k['voucher_toko'], $k['diskon_toko'])
                : $k['subtotal'] - $this->bagian_barang($k['voucher_toko'], $k['diskon_toko']);
            $dasar_semua = $v['jenis'] === 'ongkir' ? $ong : $sub;

            // Kelompok terakhir menerima sisanya, supaya pembulatan tidak
            // membuat jumlah bagian berbeda satu-dua rupiah dari totalnya.
            $bagian = ($i === count($layak) - 1)
                ? $sisa
                : ($dasar_semua > 0 ? (int) floor($total_pot * $dasar_k / $dasar_semua) : 0);
            $bagian = max(0, min($bagian, $dasar_k));

            $k['diskon_platform']  = $bagian;
            $k['voucher_platform'] = $v;
            $sisa -= $bagian;
            unset($k);
        }

        $hasil['voucher'][$v['kode']] = array('ok' => TRUE, 'pesan' => '',
            'ket' => $this->CI->voucher_lib->keterangan($v) . ' (voucher platform)',
            'potongan' => $total_pot - $sisa, 'toko' => NULL);
    }

    protected function bagian_ongkir($v, $pot)
    {
        return ($v && $v['jenis'] === 'ongkir') ? (int) $pot : 0;
    }

    protected function bagian_barang($v, $pot)
    {
        return ($v && $v['jenis'] !== 'ongkir') ? (int) $pot : 0;
    }

    /** Tanggal libur terakhir kalau toko sedang libur hari ini, atau NULL. */
    public function libur(array $toko)
    {
        if (empty($toko['libur_sampai'])) {
            return NULL;
        }
        $hari = date('Y-m-d');
        $mulai = ! empty($toko['libur_mulai']) ? $toko['libur_mulai'] : $hari;
        return ($mulai <= $hari && $toko['libur_sampai'] >= $hari) ? $toko['libur_sampai'] : NULL;
    }

    /**
     * Tanggal kirim pre-order sah? Juga memeriksa kuota harian per produk:
     * jumlah yang sudah dipesan untuk tanggal itu + pesanan ini.
     *
     * @return TRUE|string pesan galat
     */
    public function periksa_tanggal($tgl, $min, array $produk)
    {
        if ( ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl) || ! strtotime($tgl)) {
            return 'Pilih tanggal kirim untuk produk pre-order (paling cepat ' . tgl_id($min) . ').';
        }
        if ($tgl < $min) {
            return 'Tanggal kirim paling cepat ' . tgl_id($min) . ' - penjual butuh waktu membuatnya.';
        }
        if ($tgl > date('Y-m-d', strtotime('+' . self::PO_MAKS_HARI . ' days'))) {
            return 'Tanggal kirim terlalu jauh. Pilih dalam ' . self::PO_MAKS_HARI . ' hari ke depan.';
        }

        foreach ($produk as $pid => $p) {
            if ($p['kuota'] === NULL) {
                continue;
            }
            $terpesan = (int) $this->CI->db->select('COALESCE(SUM(oi.qty), 0) AS n', FALSE)
                ->from('order_items oi')
                ->join('orders o', 'o.id = oi.order_id')
                ->where('oi.product_id', (int) $pid)
                ->where('o.tanggal_kirim', $tgl)
                ->where('o.order_status !=', 'cancelled')
                ->get()->row()->n;

            if ($terpesan + (int) $p['qty'] > (int) $p['kuota']) {
                $sisa = max(0, (int) $p['kuota'] - $terpesan);
                return 'Kuota ' . $p['nama'] . ' untuk ' . tgl_id($tgl) . ' tinggal ' . $sisa
                    . '. Pilih tanggal lain atau kurangi jumlahnya.';
            }
        }
        return TRUE;
    }

    protected function ket_tingkat($tingkat, array $toko)
    {
        $ket = array(
            'kecamatan' => 'Satu kecamatan dengan toko.',
            'kota'      => 'Masih dalam ' . $toko['regency_name'] . '.',
            'provinsi'  => 'Luar ' . $toko['regency_name'] . ', masih dalam ' . $toko['province_name'] . '.',
            'luar'      => 'Luar ' . $toko['province_name'] . '.',
        );
        return isset($ket[$tingkat]) ? $ket[$tingkat] : '';
    }
}
