<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Analitik_model - angka platform untuk admin & mitra (dinas/pemda).
 * Simpan di: application/models/Analitik_model.php
 *
 * GMV = nilai transaksi lunas yang tidak dibatalkan (total yang dibayar
 * pembeli, termasuk ongkir). Tanggalnya = tanggal dibayar.
 */
class Analitik_model extends CI_Model
{
    protected function lunas($dari, $sampai, $a = 'o')
    {
        $this->db->where($a . '.payment_status', 'paid')->where($a . '.order_status !=', 'cancelled')
            ->where($a . '.paid_at >=', $dari . ' 00:00:00')->where($a . '.paid_at <=', $sampai . ' 23:59:59');
    }

    public function ringkasan($dari, $sampai)
    {
        $this->lunas($dari, $sampai);
        $r = $this->db->select('COUNT(*) AS pesanan, COALESCE(SUM(o.total), 0) AS gmv, COALESCE(SUM(o.subtotal), 0) AS barang,
                COALESCE(SUM(o.fee_platform), 0) AS komisi,
                COALESCE(SUM(' . ($this->db->field_exists('diskon_platform', 'orders') ? 'o.diskon_platform + o.subsidi_ongkir' : '0') . '), 0) AS biaya_promo,
                COUNT(DISTINCT o.store_id) AS toko_jual, COUNT(DISTINCT o.customer_phone) AS pembeli', FALSE)
            ->get('orders o')->row_array();

        $r['toko_baru'] = $this->db->where('created_at >=', $dari . ' 00:00:00')->where('created_at <=', $sampai . ' 23:59:59')
            ->count_all_results('stores');
        $r['akun_baru'] = $this->db->where('created_at >=', $dari . ' 00:00:00')->where('created_at <=', $sampai . ' 23:59:59')
            ->count_all_results('users');
        $r['toko_aktif'] = $this->db->where('is_active', 1)->count_all_results('stores');
        $r['aov'] = $r['pesanan'] ? (int) round($r['gmv'] / $r['pesanan']) : 0;
        $r['bersih_platform'] = (int) $r['komisi'] - (int) $r['biaya_promo'];
        return $r;
    }

    public function harian($dari, $sampai)
    {
        $this->lunas($dari, $sampai);
        $ada = array();
        foreach ($this->db->select('DATE(o.paid_at) AS tgl, SUM(o.total) AS gmv, COUNT(*) AS n', FALSE)
                     ->group_by('DATE(o.paid_at)')->get('orders o')->result_array() as $h) {
            $ada[$h['tgl']] = $h;
        }
        $out = array();
        for ($t = strtotime($dari); $t <= strtotime($sampai); $t += 86400) {
            $d = date('Y-m-d', $t);
            $out[] = array('tgl' => $d, 'gmv' => (int) ($ada[$d]['gmv'] ?? 0), 'n' => (int) ($ada[$d]['n'] ?? 0));
        }
        return $out;
    }

    public function per_kategori($dari, $sampai)
    {
        $this->lunas($dari, $sampai);
        return $this->db->select('COALESCE(c.name, \'Tanpa kategori\') AS nama, SUM(oi.line_total) AS nilai, SUM(oi.qty) AS qty', FALSE)
            ->from('order_items oi')->join('orders o', 'o.id = oi.order_id')
            ->join('products p', 'p.id = oi.product_id', 'left')->join('categories c', 'c.id = p.category_id', 'left')
            ->group_by('c.id')->order_by('nilai', 'DESC')->get()->result_array();
    }

    public function per_wilayah($dari, $sampai)
    {
        $this->lunas($dari, $sampai);
        return $this->db->select('r.name AS nama, SUM(o.subtotal) AS nilai, COUNT(*) AS n, COUNT(DISTINCT o.store_id) AS toko', FALSE)
            ->from('orders o')->join('stores s', 's.id = o.store_id')->join('regencies r', 'r.id = s.regency_id', 'left')
            ->group_by('s.regency_id')->order_by('nilai', 'DESC')->limit(15)->get()->result_array();
    }

    public function toko_teratas($dari, $sampai, $n = 10)
    {
        $this->lunas($dari, $sampai);
        return $this->db->select('s.name, s.slug, r.name AS kota, SUM(o.subtotal) AS nilai, COUNT(*) AS n', FALSE)
            ->from('orders o')->join('stores s', 's.id = o.store_id')->join('regencies r', 'r.id = s.regency_id', 'left')
            ->group_by('o.store_id')->order_by('nilai', 'DESC')->limit((int) $n)->get()->result_array();
    }

    /**
     * Sebaran UMKM per kecamatan: jumlah toko, toko yang menjual 30 hari
     * terakhir, dan omzetnya. Dipakai peta sebaran & tabel pembinaan.
     */
    public function sebaran()
    {
        $batas = date('Y-m-d H:i:s', strtotime('-30 days'));
        return $this->db->select("r.name AS kota, d.name AS kecamatan, s.district_id,
                COUNT(s.id) AS toko, SUM(s.is_active) AS aktif,
                SUM(EXISTS (SELECT 1 FROM orders o WHERE o.store_id = s.id AND o.payment_status = 'paid' AND o.paid_at >= '{$batas}')) AS menjual,
                COALESCE(SUM((SELECT SUM(o2.subtotal) FROM orders o2 WHERE o2.store_id = s.id AND o2.payment_status = 'paid'
                                AND o2.order_status != 'cancelled' AND o2.paid_at >= '{$batas}')), 0) AS omzet", FALSE)
            ->from('stores s')->join('districts d', 'd.id = s.district_id', 'left')->join('regencies r', 'r.id = s.regency_id', 'left')
            ->group_by('s.district_id')->order_by('toko', 'DESC')->get()->result_array();
    }

    /** Titik toko untuk peta. */
    public function titik_toko()
    {
        return $this->db->select('s.id, s.name, s.slug, s.latitude AS lat, s.longitude AS lng, s.is_active, s.pendamping_id,
                d.name AS kecamatan, r.name AS kota', FALSE)
            ->from('stores s')->join('districts d', 'd.id = s.district_id', 'left')->join('regencies r', 'r.id = s.regency_id', 'left')
            ->where('s.latitude IS NOT NULL', NULL, FALSE)->where('s.longitude IS NOT NULL', NULL, FALSE)
            ->get()->result_array();
    }
}
