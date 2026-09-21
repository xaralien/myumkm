<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Chat_model - percakapan & persetujuan foto pesanan.
 * Simpan di: application/models/Chat_model.php
 */
class Chat_model extends CI_Model
{

    /* ------------------------------------------------------- percakapan --- */

    public function pesan($order_id, $sejak_id = 0)
    {
        return $this->db->where('order_id', (int) $order_id)
            ->where('id >', (int) $sejak_id)
            ->order_by('id', 'ASC')
            ->get('order_messages')->result_array();
    }

    public function kirim(
        $order_id,
        $pengirim,
        $tipe,
        $isi = NULL,
        $image = NULL,
        $user_id = NULL
    ) {
        $this->db->insert('order_messages', array(
            'order_id' => (int) $order_id,
            'pengirim' => $pengirim,
            'user_id'  => $user_id ? (int) $user_id : NULL,
            'tipe'     => $tipe,
            'isi'      => $isi,
            'image'    => $image,

            // Pengirim otomatis dianggap sudah membaca pesannya sendiri.
            'dibaca_customer' => ($pengirim === 'customer') ? 1 : 0,
            'dibaca_seller'   => ($pengirim === 'seller')   ? 1 : 0,
        ));
        return (int) $this->db->insert_id();
    }

    /** Catatan otomatis - muncul di aliran percakapan yang sama. */
    public function sistem($order_id, $isi)
    {
        return $this->kirim($order_id, 'sistem', 'sistem', $isi);
    }

    public function tandai_dibaca($order_id, $sisi)
    {
        $kolom = ($sisi === 'customer') ? 'dibaca_customer' : 'dibaca_seller';

        $this->db->where('order_id', (int) $order_id)
            ->where($kolom, 0)
            ->update('order_messages', array($kolom => 1));
    }

    public function belum_dibaca($order_id, $sisi)
    {
        $kolom = ($sisi === 'customer') ? 'dibaca_customer' : 'dibaca_seller';

        return (int) $this->db->where('order_id', (int) $order_id)
            ->where($kolom, 0)
            ->count_all_results('order_messages');
    }

    /** Jumlah pesan belum dibaca per pesanan, untuk lencana di daftar. */
    public function belum_dibaca_toko($store_id)
    {
        $rows = $this->db
            ->select('m.order_id, COUNT(*) AS jml', FALSE)
            ->from('order_messages m')
            ->join('orders o', 'o.id = m.order_id')
            ->where('o.store_id', (int) $store_id)
            ->where('m.dibaca_seller', 0)
            ->group_by('m.order_id')
            ->get()->result_array();

        $out = array();
        foreach ($rows as $r) {
            $out[(int) $r['order_id']] = (int) $r['jml'];
        }
        return $out;
    }

    /* -------------------------------------------------------------- ACC --- */

    /**
     * Hitung tenggat ACC.
     *
     * Diambil yang PALING AWAL antara:
     *   a. sekarang + waktu tunggu toko
     *   b. jadwal kirim - jeda persiapan
     *
     * Batas (b) itu yang penting: tanpa itu, foto yang dikirim mepet jadwal
     * bisa menghasilkan tenggat ACC yang jatuh SETELAH bunga seharusnya
     * sudah diantar - toko jadi menunggu sesuatu yang sudah tidak ada
     * gunanya ditunggu.
     */
    public function hitung_deadline(array $order, array $toko)
    {
        $tunggu = time() + ((int) $toko['acc_tunggu_jam'] * 3600);

        $kirim = strtotime($order['delivery_date'] . ' ' . $order['delivery_slot']);
        $siap  = $kirim - ((int) $toko['jeda_persiapan_menit'] * 60);

        $pilih = min($tunggu, $siap);

        /* Kalau jadwal kirimnya sudah sangat dekat, batas (b) bisa jatuh di
           masa lalu. Beri minimal 30 menit supaya customer tetap punya
           kesempatan menjawab - lewat itu, dianggap setuju seperti biasa. */
        $minimal = time() + 1800;

        return date('Y-m-d H:i:s', max($pilih, $minimal));
    }

    /**
     * Pesanan yang tenggat ACC-nya lewat tanpa jawaban.
     * Dipakai penyapu berkala.
     */
    public function acc_kedaluwarsa($limit = 50)
    {
        return $this->db->where_in('acc_status', array('menunggu', 'revisi'))
            ->where('acc_deadline IS NOT NULL', NULL, FALSE)
            ->where('acc_deadline <', date('Y-m-d H:i:s'))
            ->limit((int) $limit)
            ->get('orders')->result_array();
    }

    /**
     * Tandai disetujui otomatis karena tenggat lewat.
     *
     * Kata-kata papan ikut dikunci di sini - sesuai aturan: dikunci saat
     * di-ACC, ATAU saat tidak direspons dan revisinya ditutup.
     */
    public function setujui_otomatis($order_id)
    {
        $this->db->where('id', (int) $order_id)
            ->where_in('acc_status', array('menunggu', 'revisi'))
            ->update('orders', array(
                'acc_status'     => 'otomatis',
                'card_locked_at' => date('Y-m-d H:i:s'),
            ));

        if ($this->db->affected_rows() < 1) {
            return FALSE;
        }

        $this->sistem(
            $order_id,
            'Batas waktu persetujuan lewat tanpa jawaban. Rangkaian dianggap '
                . 'disetujui dan kata-kata papan dikunci.'
        );
        return TRUE;
    }
}
