<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Order_model - simpan di application/models/Order_model.php
 */
class Order_model extends CI_Model
{

    /* ---------------------------------------------------------- membuat --- */

    /**
     * Simpan pesanan beserta itemnya dalam satu transaksi database.
     * Kalau salah satu query gagal, semuanya dibatalkan - jangan sampai
     * ada pesanan tanpa item, atau item tanpa pesanan.
     *
     * @return array|FALSE
     */
    public function create(array $order, array $items)
    {
        $this->db->trans_begin();

        $order['order_number'] = $this->generate_number();
        $order['access_token'] = bin2hex(random_bytes(20));

        $this->db->insert('orders', $order);
        $order_id = (int) $this->db->insert_id();

        foreach ($items as $it) {
            $this->db->insert('order_items', array(
                'order_id'     => $order_id,
                'product_id'   => $it['product_id'],
                'product_name' => $it['product_name'],
                'variant_name' => $it['variant_name'],
                'addons_json'  => $it['addons'] ? json_encode($it['addons']) : NULL,
                'unit_price'   => $it['unit_price'],
                'qty'          => $it['qty'],
                'line_total'   => $it['line_total'],
            ));
        }

        $this->db->insert('order_logs', array(
            'order_id' => $order_id,
            'status'   => 'pending',
            'note'     => 'Pesanan dibuat',
        ));

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            log_message('error', 'Gagal menyimpan pesanan: ' . $this->db->error()['message']);
            return FALSE;
        }

        $this->db->trans_commit();

        $order['id'] = $order_id;
        return $order;
    }

    /**
     * Nomor pesanan yang enak dibaca dan disebutkan lewat telepon.
     * Format: BNG-20260803-K7F3Q
     * Huruf yang mudah tertukar (I, O, 0, 1) sengaja tidak dipakai.
     */
    protected function generate_number()
    {
        $abjad = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        for ($coba = 0; $coba < 8; $coba++) {
            $acak = '';
            for ($i = 0; $i < 5; $i++) {
                $acak .= $abjad[random_int(0, strlen($abjad) - 1)];
            }
            $nomor = 'BNG-' . date('Ymd') . '-' . $acak;

            if (! $this->db->where('order_number', $nomor)->count_all_results('orders')) {
                return $nomor;
            }
        }
        // Sangat kecil kemungkinannya sampai sini; pakai uniqid sebagai cadangan.
        return 'BNG-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }

    /* --------------------------------------------------------- membaca --- */

    public function get_by_number($order_number)
    {
        return $this->db->where('order_number', $order_number)
            ->get('orders')->row_array();
    }

    public function items($order_id)
    {
        $rows = $this->db->where('order_id', (int) $order_id)
            ->get('order_items')->result_array();
        foreach ($rows as &$r) {
            $r['addons'] = $r['addons_json'] ? json_decode($r['addons_json'], TRUE) : array();
        }
        return $rows;
    }

    public function logs($order_id)
    {
        return $this->db->where('order_id', (int) $order_id)
            ->order_by('created_at', 'ASC')
            ->get('order_logs')->result_array();
    }

    /**
     * Pencarian untuk halaman lacak. Guest tidak punya akun, jadi kuncinya
     * nomor pesanan + nomor HP - dua hal yang hanya diketahui pemesan.
     */
    public function find_for_guest($order_number, $phone)
    {
        return $this->db->where('order_number', $order_number)
            ->where('customer_phone', $phone)
            ->get('orders')->row_array();
    }

    public function find_by_token($order_number, $token)
    {
        $row = $this->db->where('order_number', $order_number)->get('orders')->row_array();
        if (! $row) {
            return NULL;
        }
        return hash_equals($row['access_token'], (string) $token) ? $row : NULL;
    }

    /* ------------------------------------------------------- pembayaran --- */

    public function set_duitku_info($order_id, array $info)
    {
        $this->db->where('id', (int) $order_id)->update('orders', array(
            'duitku_reference'   => isset($info['reference'])   ? $info['reference']   : NULL,
            'duitku_payment_url' => isset($info['payment_url']) ? $info['payment_url'] : NULL,
            'duitku_va_number'   => isset($info['va_number'])   ? $info['va_number']   : NULL,
        ));
    }

    /**
     * Tandai lunas. Aman dipanggil berkali-kali: Duitku bisa mengirim
     * callback yang sama lebih dari sekali kalau balasan pertama tidak
     * sampai. Tanpa penjagaan ini, log status jadi ganda.
     *
     * @return bool TRUE kalau status benar-benar berubah pada panggilan ini
     */
    public function mark_paid($order_number, $reference = NULL, $va = NULL)
    {
        $order = $this->get_by_number($order_number);
        if (! $order) {
            return FALSE;
        }
        if ($order['payment_status'] === 'paid') {
            return FALSE;   // sudah pernah diproses, tidak apa-apa
        }

        $this->db->where('id', $order['id'])
            ->where('payment_status !=', 'paid')   // pengaman balapan
            ->update('orders', array(
                'payment_status'   => 'paid',
                'order_status'     => 'confirmed',
                'paid_at'          => date('Y-m-d H:i:s'),
                'duitku_reference' => $reference ?: $order['duitku_reference'],
                'duitku_va_number' => $va ?: $order['duitku_va_number'],
            ));

        if ($this->db->affected_rows() < 1) {
            return FALSE;
        }

        $this->db->insert('order_logs', array(
            'order_id' => $order['id'],
            'status'   => 'confirmed',
            'note'     => 'Pembayaran diterima',
        ));
        return TRUE;
    }

    public function mark_failed($order_number, $status = 'failed', $note = NULL)
    {
        $order = $this->get_by_number($order_number);
 
        if ( ! $order || $order['payment_status'] === 'paid') {
            return FALSE;
        }
 
        $data = array('payment_status' => $status);
 
        /* 'expired' bersifat FINAL - batas waktu bayar sudah lewat dan
           invoice-nya tidak bisa dipakai lagi, jadi pesanannya ikut
           dibatalkan.
 
           'failed' TIDAK membatalkan pesanan: itu berarti pembuatan
           tagihan yang gagal, bukan pembayaran yang ditolak. Pesanannya
           masih utuh dan pembeli boleh mencoba lagi dari halaman lacak. */
        if ($status === 'expired') {
            $data['order_status'] = 'cancelled';
        }
 
        $this->db->where('id', $order['id'])
                 ->where('payment_status !=', 'paid')   // pengaman balapan
                 ->update('orders', $data);
 
        if ($this->db->affected_rows() < 1) {
            return FALSE;
        }
 
        $this->db->insert('order_logs', array(
            'order_id' => $order['id'],
            'status'   => ($status === 'expired') ? 'cancelled' : $status,
            'note'     => $note ?: 'Pembayaran tidak selesai',
        ));
        return TRUE;
    }

    /**
     * Pesanan Duitku yang belum lunas dan masih layak diperiksa.
     * Dipakai penyapu berkala.
     */
    public function belum_lunas($jam = 48, $limit = 50)
    {
        return $this->db
            ->where('payment_method', 'duitku')
            ->where('payment_status', 'unpaid')
            ->where('duitku_reference IS NOT NULL', NULL, FALSE)
            ->where('created_at >=', date('Y-m-d H:i:s', time() - ((int) $jam * 3600)))
            ->order_by('created_at', 'ASC')
            ->limit((int) $limit)
            ->get('orders')->result_array();
    }

    /**
     * Pesanan yang sudah lewat batas waktu bayar tapi masih 'unpaid'.
     * Tanpa ini, pesanan tak terbayar menumpuk selamanya dan penjual
     * tidak bisa membedakan mana yang benar-benar masih ditunggu.
     */
    public function tandai_kedaluwarsa($jam = 24, $jam_failed = NULL)
    {
        if ($jam_failed === NULL) {
            $jam_failed = $jam * 2;
        }
 
        $batas        = date('Y-m-d H:i:s', time() - ((int) $jam * 3600));
        $batas_failed = date('Y-m-d H:i:s', time() - ((int) $jam_failed * 3600));
 
        /* Dua syarat waktu yang berbeda, jadi tidak bisa satu WHERE polos.
           group_start() memastikan OR-nya tidak bocor keluar dan ikut
           membatalkan pesanan yang sudah lunas. */
        $rows = $this->db->select('id, payment_status')
            ->where('payment_method', 'duitku')
            ->where('order_status !=', 'cancelled')
            ->group_start()
                ->group_start()
                    ->where('payment_status', 'unpaid')
                    ->where('created_at <', $batas)
                ->group_end()
                ->or_group_start()
                    ->where('payment_status', 'failed')
                    ->where('created_at <', $batas_failed)
                ->group_end()
            ->group_end()
            ->get('orders')->result_array();
 
        if ( ! $rows) {
            return 0;
        }
 
        $ids = array_column($rows, 'id');
 
        $this->db->where_in('id', $ids)->update('orders', array(
            'payment_status' => 'expired',
            'order_status'   => 'cancelled',
        ));
 
        foreach ($rows as $r) {
            $this->db->insert('order_logs', array(
                'order_id' => $r['id'],
                'status'   => 'cancelled',
                'note'     => $r['payment_status'] === 'failed'
                    ? 'Tagihan gagal dibuat dan tidak dilanjutkan'
                    : 'Batas waktu pembayaran habis',
            ));
        }
        return count($ids);
    }
 
    /**
     * Pesanan yang statusnya SUDAH final di sisi pembayaran, tapi
     * order_status-nya masih menggantung di alur.
     *
     * Ini membersihkan sisa dari bug lama: mark_failed() dulu hanya
     * mengubah payment_status, sehingga pesanan mati tetap berstatus
     * 'pending'. Aman dijalankan berkali-kali.
     *
     * @return int jumlah yang dirapikan
     */
    public function bersihkan_menggantung()
    {
        $rows = $this->db->select('id')
            ->where_in('payment_status', array('expired', 'failed'))
            ->where('order_status', 'pending')
            ->get('orders')->result_array();
 
        if ( ! $rows) {
            return 0;
        }
 
        $ids = array_column($rows, 'id');
 
        $this->db->where_in('id', $ids)->update('orders', array(
            'order_status' => 'cancelled',
        ));
 
        foreach ($ids as $id) {
            $this->db->insert('order_logs', array(
                'order_id' => $id,
                'status'   => 'cancelled',
                'note'     => 'Pembayaran tidak selesai',
            ));
        }
        return count($ids);
    }
    
    public function log_callback($order_number, $payload, $valid, $note = NULL)
    {
        $this->db->insert('payment_callbacks', array(
            'order_number' => $order_number,
            'raw_payload'  => is_string($payload) ? $payload : json_encode($payload),
            'is_valid'     => $valid ? 1 : 0,
            'note'         => $note,
        ));
    }

    
}
