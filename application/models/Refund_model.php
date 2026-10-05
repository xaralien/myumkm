<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Refund_model - permintaan pengembalian dana.
 * Simpan di: application/models/Refund_model.php
 *
 * Duitku tidak menyediakan API pengembalian dana, jadi transfernya manual.
 * Yang dikerjakan sistem adalah MENCATAT: siapa meminta, siapa menyetujui,
 * berapa, dan kapan uangnya dikirim. Tanpa catatan ini, sengketa "sudah
 * ditransfer atau belum" tidak punya bukti selain ingatan masing-masing.
 *
 * Alur: diminta -> disetujui -> selesai
 *                \-> ditolak
 */
class Refund_model extends CI_Model {

    /** Permintaan yang masih berjalan untuk satu pesanan, kalau ada. */
    public function aktif($order_id)
    {
        return $this->db
            ->where('order_id', (int) $order_id)
            ->where_in('status', array('diminta', 'disetujui'))
            ->order_by('id', 'DESC')
            ->get('refunds')->row_array();
    }

    public function terakhir($order_id)
    {
        return $this->db->where('order_id', (int) $order_id)
                        ->order_by('id', 'DESC')
                        ->get('refunds')->row_array();
    }

    /**
     * Pembeli mengajukan refund.
     *
     * @return array ['ok' => bool, 'pesan' => string]
     */
    public function ajukan(array $order, $alasan, $user_id = NULL)
    {
        $alasan = trim((string) $alasan);

        if (mb_strlen($alasan) < 10) {
            return array('ok' => FALSE, 'pesan' => 'Tuliskan alasannya minimal 10 karakter supaya penjual bisa menilai.');
        }

        if ($order['payment_status'] !== 'paid') {
            return array('ok' => FALSE, 'pesan' => 'Pesanan ini belum dibayar, jadi tidak ada dana yang perlu dikembalikan.');
        }

        /* Satu permintaan hidup per pesanan. Tanpa ini, pembeli yang menekan
           kirim dua kali membuat penjual melihat dua permintaan untuk hal
           yang sama dan bingung mana yang harus dijawab. */
        if ($this->aktif($order['id'])) {
            return array('ok' => FALSE, 'pesan' => 'Permintaan refund untuk pesanan ini sedang diproses.');
        }

        $this->db->insert('refunds', array(
            'order_id' => (int) $order['id'],
            'store_id' => (int) $order['store_id'],
            'user_id'  => $user_id ? (int) $user_id : NULL,
            'jumlah'   => (int) $order['total'],
            'alasan'   => mb_substr($alasan, 0, 500),
        ));

        $this->pesan_sistem($order, 'Pembeli mengajukan pengembalian dana. Alasan: ' . $alasan);

        return array('ok' => TRUE, 'pesan' => 'Permintaan terkirim. Penjual akan menanggapinya.');
    }

    /**
     * Penjual menyetujui atau menolak.
     *
     * Menyetujui TIDAK langsung mengirim uang - uangnya ada di akun
     * marketplace, jadi admin yang mentransfer. Yang terjadi di sini:
     * pesanan dibatalkan, stok dikembalikan, dan pendapatan toko untuk
     * pesanan itu dibatalkan juga.
     */
    public function putuskan($refund_id, $store_id, $setuju, $catatan = NULL)
    {
        $r = $this->db->where('id', (int) $refund_id)
                      ->where('store_id', (int) $store_id)   // hanya tokonya sendiri
                      ->where('status', 'diminta')
                      ->get('refunds')->row_array();

        if ( ! $r) {
            return array('ok' => FALSE, 'pesan' => 'Permintaan tidak ditemukan atau sudah diputuskan.');
        }

        $this->db->where('id', $r['id'])->where('status', 'diminta')->update('refunds', array(
            'status'          => $setuju ? 'disetujui' : 'ditolak',
            'catatan_penjual' => $catatan ? mb_substr($catatan, 0, 500) : NULL,
            'diputus_at'      => date('Y-m-d H:i:s'),
        ));

        if ($this->db->affected_rows() < 1) {
            return array('ok' => FALSE, 'pesan' => 'Permintaan sudah diputuskan lewat proses lain.');
        }

        $order = $this->db->where('id', $r['order_id'])->get('orders')->row_array();

        if ($setuju) {
            $this->load->model('order_model');
            $this->order_model->batalkan($order, 'seller', 'Pengembalian dana disetujui');

            /* Pendapatan toko untuk pesanan ini dinolkan - barangnya tidak
               jadi terjual. Kalau dibiarkan, uangnya ikut tercairkan padahal
               sedang dikembalikan ke pembeli. */
            $this->db->where('id', $order['id'])->update('orders', array(
                'fee_platform' => 0,
                'net_store'    => 0,
            ));

            $this->pesan_sistem($order,
                'Penjual menyetujui pengembalian dana. Dana akan ditransfer admin dalam beberapa hari kerja.'
                . ($catatan ? ' Catatan: ' . $catatan : ''));
        } else {
            $this->pesan_sistem($order,
                'Penjual menolak pengembalian dana.' . ($catatan ? ' Alasan: ' . $catatan : ''));
        }

        return array('ok' => TRUE, 'pesan' => $setuju ? 'Pengembalian disetujui.' : 'Permintaan ditolak.');
    }

    /** Admin menandai dana sudah ditransfer. */
    public function selesaikan($refund_id, $bukti = NULL)
    {
        $r = $this->db->where('id', (int) $refund_id)
                      ->where('status', 'disetujui')
                      ->get('refunds')->row_array();
        if ( ! $r) {
            return FALSE;
        }

        $this->db->where('id', $r['id'])->where('status', 'disetujui')->update('refunds', array(
            'status'         => 'selesai',
            'bukti_transfer' => $bukti ? mb_substr($bukti, 0, 120) : NULL,
            'selesai_at'     => date('Y-m-d H:i:s'),
        ));

        if ($this->db->affected_rows() < 1) {
            return FALSE;
        }

        $order = $this->db->where('id', $r['order_id'])->get('orders')->row_array();
        $this->load->helper('money');

        $this->pesan_sistem($order,
            'Dana ' . rupiah($r['jumlah']) . ' sudah dikembalikan.'
            . ($bukti ? ' Bukti transfer: ' . $bukti : ''));

        return TRUE;
    }

    /* ------------------------------------------------------------------ */

    public function daftar_toko($store_id, $status = NULL, $limit = 50)
    {
        $this->db->select('r.*, o.order_number, o.customer_name', FALSE)
                 ->from('refunds r')
                 ->join('orders o', 'o.id = r.order_id')
                 ->where('r.store_id', (int) $store_id);

        if ($status) {
            $this->db->where('r.status', $status);
        }

        return $this->db->order_by('r.created_at', 'DESC')->limit((int) $limit)->get()->result_array();
    }

    public function daftar_admin($status = NULL, $limit = 100)
    {
        $this->db->select('r.*, o.order_number, o.customer_name, o.customer_phone, s.name AS toko', FALSE)
                 ->from('refunds r')
                 ->join('orders o', 'o.id = r.order_id')
                 ->join('stores s', 's.id = r.store_id', 'left');

        if ($status) {
            $this->db->where('r.status', $status);
        }

        return $this->db->order_by('r.created_at', 'DESC')->limit((int) $limit)->get()->result_array();
    }

    protected function pesan_sistem(array $order, $isi)
    {
        $this->load->model('chat_model');

        $conv = $this->chat_model->conv($order['store_id'], $order['user_id'], $order['id']);
        if ($conv) {
            $this->chat_model->sistem_conv($conv, $isi);
        }
    }
}
