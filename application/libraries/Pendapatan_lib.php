<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pendapatan_lib - menghitung bagian toko dari tiap pesanan.
 * Simpan di: application/libraries/Pendapatan_lib.php
 *
 * SEMUA UANG MASUK KE SATU AKUN
 * Pembayaran Duitku diterima marketplace, bukan langsung ke rekening toko.
 * Jadi harus dicatat berapa hak tiap toko, lalu dicairkan terpisah.
 *
 * ONGKIR MILIK TOKO
 * Yang mengantar barang adalah penjual, dan dia yang membayar kurirnya.
 * Jadi ongkir diteruskan utuh - komisi hanya dihitung dari harga barang.
 * Memotong komisi dari ongkir sama saja memotong uang yang bukan
 * pendapatan penjual.
 */
class Pendapatan_lib {

    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->config('marketplace', TRUE);
    }

    /** Persentase komisi marketplace, dari config. */
    public function komisi_persen()
    {
        $p = $this->CI->config->item('komisi_persen', 'marketplace');

        // Dibatasi 0-50% supaya salah ketik di config tidak menghabiskan
        // pendapatan penjual tanpa ada yang menyadarinya.
        return max(0, min(50, (float) $p));
    }

    /**
     * Hitung dan simpan bagian toko untuk satu pesanan.
     *
     * Dipanggil sekali saat pembayaran diterima. Angkanya DISIMPAN, bukan
     * dihitung ulang tiap ditampilkan: persentase komisi bisa berubah tahun
     * depan, dan pesanan lama harus tetap memakai angka yang berlaku saat
     * pesanan itu dibuat.
     */
    public function catat(array $order)
    {
        $barang = (int) $order['subtotal'];
        $ongkir = (int) $order['shipping_fee'];

        $komisi = (int) round($barang * $this->komisi_persen() / 100);

        $this->CI->db->where('id', (int) $order['id'])->update('orders', array(
            'fee_platform' => $komisi,
            'net_store'    => max(0, $barang - $komisi + $ongkir),
        ));
    }

    /**
     * Pendapatan toko yang SIAP DICAIRKAN.
     *
     * Syaratnya pesanan sudah SELESAI, bukan sekadar lunas. Pesanan yang
     * masih dikirim bisa berakhir dengan refund, dan uang yang terlanjur
     * dicairkan jauh lebih sulit ditarik kembali daripada ditahan beberapa
     * hari lagi.
     */
    public function siap_cair($store_id)
    {
        $r = $this->CI->db
            ->select('COUNT(*) AS n, COALESCE(SUM(net_store), 0) AS jumlah', FALSE)
            ->where('store_id', (int) $store_id)
            ->where('payment_status', 'paid')
            ->where('order_status', 'delivered')
            ->where('payout_id IS NULL', NULL, FALSE)
            ->get('orders')->row_array();

        return array('jumlah' => (int) $r['jumlah'], 'pesanan' => (int) $r['n']);
    }

    /** Uang yang sudah masuk tapi belum boleh dicairkan (pesanan berjalan). */
    public function tertahan($store_id)
    {
        $r = $this->CI->db
            ->select('COUNT(*) AS n, COALESCE(SUM(net_store), 0) AS jumlah', FALSE)
            ->where('store_id', (int) $store_id)
            ->where('payment_status', 'paid')
            ->where_in('order_status', array('pending', 'confirmed', 'preparing', 'delivering'))
            ->where('payout_id IS NULL', NULL, FALSE)
            ->get('orders')->row_array();

        return array('jumlah' => (int) $r['jumlah'], 'pesanan' => (int) $r['n']);
    }

    /** Total yang sudah pernah dicairkan. */
    public function sudah_cair($store_id)
    {
        $r = $this->CI->db
            ->select('COALESCE(SUM(jumlah), 0) AS jumlah', FALSE)
            ->where('store_id', (int) $store_id)
            ->where('status', 'selesai')
            ->get('payouts')->row_array();

        return (int) $r['jumlah'];
    }

    /**
     * Buat pencairan untuk semua pesanan toko yang siap.
     *
     * Pesanan ditandai dengan payout_id dalam satu perintah bersyarat, dan
     * HANYA yang belum pernah ditandai. Dengan begitu dua admin yang menekan
     * tombol bersamaan tidak membuat dua pencairan untuk uang yang sama -
     * yang kedua akan mendapati tidak ada pesanan tersisa.
     *
     * @return array ['ok' => bool, 'pesan' => string, 'id' => int]
     */
    public function cairkan($store_id, $catatan = NULL)
    {
        $toko = $this->CI->db->where('id', (int) $store_id)->get('stores')->row_array();
        if ( ! $toko) {
            return array('ok' => FALSE, 'pesan' => 'Toko tidak ditemukan.');
        }

        $siap = $this->siap_cair($store_id);
        if ($siap['jumlah'] < 1) {
            return array('ok' => FALSE, 'pesan' => 'Belum ada pendapatan yang siap dicairkan.');
        }

        $this->CI->db->insert('payouts', array(
            'store_id'       => (int) $store_id,
            'jumlah'         => $siap['jumlah'],
            'jumlah_order'   => $siap['pesanan'],
            // Disalin, bukan dibaca ulang nanti - lihat catatan di migrasi.
            'bank_nama'      => $toko['bank_nama'],
            'bank_nomor'     => $toko['bank_nomor'],
            'bank_atas_nama' => $toko['bank_atas_nama'],
            'catatan'        => $catatan ? mb_substr($catatan, 0, 255) : NULL,
        ));

        $payout_id = (int) $this->CI->db->insert_id();

        $this->CI->db
            ->where('store_id', (int) $store_id)
            ->where('payment_status', 'paid')
            ->where('order_status', 'delivered')
            ->where('payout_id IS NULL', NULL, FALSE)
            ->update('orders', array('payout_id' => $payout_id));

        $terpakai = $this->CI->db->affected_rows();

        /* Kalau ternyata tidak ada pesanan yang tertandai - admin lain lebih
           dulu - pencairan kosong ini dibatalkan supaya tidak jadi catatan
           palsu di riwayat. */
        if ($terpakai < 1) {
            $this->CI->db->where('id', $payout_id)->delete('payouts');
            return array('ok' => FALSE, 'pesan' => 'Pendapatan ini baru saja dicairkan lewat proses lain.');
        }

        return array('ok' => TRUE, 'pesan' => 'Pencairan dibuat.', 'id' => $payout_id);
    }

    /** Tandai pencairan sudah ditransfer. */
    public function selesaikan($payout_id, $bukti = NULL)
    {
        $this->CI->db->where('id', (int) $payout_id)
                     ->where('status', 'diproses')
                     ->update('payouts', array(
                         'status'     => 'selesai',
                         'bukti'      => $bukti ? mb_substr($bukti, 0, 120) : NULL,
                         'selesai_at' => date('Y-m-d H:i:s'),
                     ));

        return $this->CI->db->affected_rows() > 0;
    }

    /**
     * Batalkan pencairan - pesanannya dilepas supaya bisa dicairkan lagi.
     * Dipakai kalau transfernya gagal (rekening salah, bank menolak).
     */
    public function gagalkan($payout_id, $catatan = NULL)
    {
        $p = $this->CI->db->where('id', (int) $payout_id)
                          ->where('status', 'diproses')
                          ->get('payouts')->row_array();
        if ( ! $p) {
            return FALSE;
        }

        $this->CI->db->where('payout_id', (int) $payout_id)
                     ->update('orders', array('payout_id' => NULL));

        $this->CI->db->where('id', (int) $payout_id)->update('payouts', array(
            'status'  => 'gagal',
            'catatan' => $catatan ? mb_substr($catatan, 0, 255) : NULL,
        ));

        return TRUE;
    }
}
