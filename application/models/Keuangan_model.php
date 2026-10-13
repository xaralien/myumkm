<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Keuangan_model - pembukuan & laporan penjualan toko.
 * Simpan di: application/models/Keuangan_model.php
 *
 * Dua sumber uang masuk:
 *   - marketplace : pesanan LUNAS yang tidak dibatalkan, sebesar bagian toko
 *                   (net_store: sudah dipotong komisi & voucher toko).
 *                   Tanggalnya = tanggal dibayar.
 *   - catatan     : baris tabel kas yang diketik penjual.
 */
class Keuangan_model extends CI_Model
{
    /** Kategori bawaan - cukup sedikit supaya mudah dipilih. */
    public static $kategori = array(
        'masuk'  => array('Penjualan langsung', 'Titipan / reseller', 'Pesanan katering', 'Modal tambahan', 'Lainnya'),
        'keluar' => array('Bahan baku', 'Kemasan', 'Transport & ongkir', 'Sewa tempat', 'Gaji / upah', 'Listrik, air, internet', 'Peralatan', 'Promosi', 'Lainnya'),
    );

    /** Kategori yang bukan pendapatan usaha (tidak masuk laba). */
    public static $bukan_usaha = array('Modal tambahan');

    /* ============================================================ kas === */

    public function simpan($store_id, array $in)
    {
        $jenis = $in['jenis'] === 'keluar' ? 'keluar' : 'masuk';
        $kat   = in_array($in['kategori'], self::$kategori[$jenis], TRUE) ? $in['kategori'] : 'Lainnya';
        $jml   = (int) preg_replace('/[^0-9]/', '', (string) $in['jumlah']);
        $tgl   = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $in['tanggal']) ? $in['tanggal'] : date('Y-m-d');

        if ($jml <= 0) {
            return array('ok' => FALSE, 'pesan' => 'Jumlah uang harus diisi.');
        }
        if ($tgl > date('Y-m-d')) {
            return array('ok' => FALSE, 'pesan' => 'Tanggal tidak boleh di masa depan.');
        }
        $this->db->insert('kas', array(
            'store_id'   => (int) $store_id,
            'tanggal'    => $tgl,
            'jenis'      => $jenis,
            'kategori'   => $kat,
            'jumlah'     => $jml,
            'keterangan' => mb_substr(trim((string) $in['keterangan']), 0, 200) ?: NULL,
        ));
        return array('ok' => TRUE, 'pesan' => ($jenis === 'masuk' ? 'Uang masuk ' : 'Uang keluar ') . rupiah($jml) . ' dicatat.');
    }

    public function hapus($store_id, $id)
    {
        $this->db->where('id', (int) $id)->where('store_id', (int) $store_id)->delete('kas');
        return $this->db->affected_rows() > 0;
    }

    /**
     * Semua transaksi periode ini, terbaru dulu: catatan kas + pesanan
     * marketplace (yang terakhir tidak bisa dihapus dari sini).
     */
    public function arus($store_id, $dari, $sampai)
    {
        $kas = $this->db->select("id, tanggal, jenis, kategori, jumlah, keterangan, 'catatan' AS sumber, NULL AS order_number", FALSE)
            ->where('store_id', (int) $store_id)->where('tanggal >=', $dari)->where('tanggal <=', $sampai)
            ->get('kas')->result_array();

        $mp = $this->db->select("id, DATE(paid_at) AS tanggal, 'masuk' AS jenis, 'Penjualan marketplace' AS kategori,
                                 net_store AS jumlah, CONCAT('Pesanan ', order_number) AS keterangan,
                                 'marketplace' AS sumber, order_number", FALSE)
            ->where('store_id', (int) $store_id)
            ->where('payment_status', 'paid')->where('order_status !=', 'cancelled')
            ->where('paid_at >=', $dari . ' 00:00:00')->where('paid_at <=', $sampai . ' 23:59:59')
            ->get('orders')->result_array();

        $semua = array_merge($kas, $mp);
        usort($semua, function ($a, $b) {
            return strcmp($b['tanggal'], $a['tanggal']) ?: ($b['id'] - $a['id']);
        });
        return $semua;
    }

    /** Ringkasan periode: masuk (marketplace & catatan), keluar, laba. */
    public function ringkasan($store_id, $dari, $sampai)
    {
        $mp = (int) $this->db->select('COALESCE(SUM(net_store), 0) AS n', FALSE)
            ->where('store_id', (int) $store_id)
            ->where('payment_status', 'paid')->where('order_status !=', 'cancelled')
            ->where('paid_at >=', $dari . ' 00:00:00')->where('paid_at <=', $sampai . ' 23:59:59')
            ->get('orders')->row()->n;

        $baris = $this->db->select('jenis, kategori, SUM(jumlah) AS n', FALSE)
            ->where('store_id', (int) $store_id)->where('tanggal >=', $dari)->where('tanggal <=', $sampai)
            ->group_by(array('jenis', 'kategori'))->get('kas')->result_array();

        $masuk = 0; $modal = 0; $keluar = 0; $per_kat = array('masuk' => array(), 'keluar' => array());
        foreach ($baris as $b) {
            $per_kat[$b['jenis']][$b['kategori']] = (int) $b['n'];
            if ($b['jenis'] === 'keluar') {
                $keluar += (int) $b['n'];
            } elseif (in_array($b['kategori'], self::$bukan_usaha, TRUE)) {
                $modal += (int) $b['n'];
            } else {
                $masuk += (int) $b['n'];
            }
        }
        arsort($per_kat['keluar']);

        return array(
            'marketplace' => $mp,
            'catatan'     => $masuk,
            'modal'       => $modal,
            'pendapatan'  => $mp + $masuk,
            'pengeluaran' => $keluar,
            'laba'        => $mp + $masuk - $keluar,
            'per_kategori' => $per_kat,
        );
    }

    /** Ringkasan per bulan, $n bulan terakhir (termasuk bulan ini). */
    public function per_bulan($store_id, $n = 6)
    {
        $out = array();
        for ($i = $n - 1; $i >= 0; $i--) {
            $awal  = date('Y-m-01', strtotime("first day of -$i month"));
            $akhir = date('Y-m-t', strtotime($awal));
            $r = $this->ringkasan($store_id, $awal, $akhir);
            $r['bulan'] = $awal;
            $r['pesanan'] = (int) $this->db->where('store_id', (int) $store_id)
                ->where('payment_status', 'paid')->where('order_status !=', 'cancelled')
                ->where('paid_at >=', $awal . ' 00:00:00')->where('paid_at <=', $akhir . ' 23:59:59')
                ->count_all_results('orders');
            $out[] = $r;
        }
        return $out;
    }

    /* ===================================================== penjualan === */

    /** Syarat dasar pesanan yang dihitung sebagai penjualan. */
    protected function lunas($store_id, $dari, $sampai, $alias = 'o')
    {
        $this->db->where($alias . '.store_id', (int) $store_id)
            ->where($alias . '.payment_status', 'paid')
            ->where($alias . '.order_status !=', 'cancelled')
            ->where($alias . '.paid_at >=', $dari . ' 00:00:00')
            ->where($alias . '.paid_at <=', $sampai . ' 23:59:59');
    }

    public function penjualan($store_id, $dari, $sampai)
    {
        $this->lunas($store_id, $dari, $sampai);
        $total = $this->db->select('COUNT(*) AS pesanan, COALESCE(SUM(o.subtotal), 0) AS omzet,
                                    COALESCE(SUM(o.net_store), 0) AS bersih, COUNT(DISTINCT o.customer_phone) AS pembeli', FALSE)
            ->get('orders o')->row_array();

        // Omzet per hari, termasuk hari tanpa penjualan (diisi 0).
        $this->lunas($store_id, $dari, $sampai);
        $harian = array();
        foreach ($this->db->select('DATE(o.paid_at) AS tgl, SUM(o.subtotal) AS omzet, COUNT(*) AS pesanan', FALSE)
                     ->group_by('DATE(o.paid_at)')->get('orders o')->result_array() as $h) {
            $harian[$h['tgl']] = $h;
        }
        $seri = array();
        for ($t = strtotime($dari); $t <= strtotime($sampai); $t += 86400) {
            $d = date('Y-m-d', $t);
            $seri[] = array('tgl' => $d, 'omzet' => isset($harian[$d]) ? (int) $harian[$d]['omzet'] : 0,
                            'pesanan' => isset($harian[$d]) ? (int) $harian[$d]['pesanan'] : 0);
        }

        $this->lunas($store_id, $dari, $sampai);
        $produk = $this->db->select('oi.product_name, SUM(oi.qty) AS qty, SUM(oi.line_total) AS omzet', FALSE)
            ->from('order_items oi')->join('orders o', 'o.id = oi.order_id')
            ->group_by('oi.product_name')->order_by('qty', 'DESC')->limit(10)->get()->result_array();

        $this->lunas($store_id, $dari, $sampai);
        $jam = array_fill(0, 24, 0);
        foreach ($this->db->select('HOUR(o.created_at) AS j, COUNT(*) AS n', FALSE)
                     ->group_by('HOUR(o.created_at)')->get('orders o')->result_array() as $r) {
            $jam[(int) $r['j']] = (int) $r['n'];
        }

        $this->lunas($store_id, $dari, $sampai);
        $kota = $this->db->select('o.recipient_city AS kota, COUNT(*) AS n', FALSE)
            ->group_by('o.recipient_city')->order_by('n', 'DESC')->limit(5)->get('orders o')->result_array();

        // Pembeli yang memesan lebih dari sekali di periode ini: tanda
        // pelanggan setia, angka yang ditanyakan pendamping & bank.
        $this->lunas($store_id, $dari, $sampai);
        $ulang = count($this->db->select('o.customer_phone')
            ->group_by('o.customer_phone')->having('COUNT(*) >', 1)
            ->get('orders o')->result_array());
        $total['pembeli_ulang'] = $ulang;

        return array(
            'total'  => $total,
            'harian' => $seri,
            'produk' => $produk,
            'jam'    => $jam,
            'kota'   => $kota,
        );
    }
}
