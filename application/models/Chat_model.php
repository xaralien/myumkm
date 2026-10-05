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

    /* =====================================================================
       DAFTAR PERCAKAPAN (kotak masuk)

       Satu percakapan = satu pesanan. Yang ditampilkan: nama lawan bicara,
       cuplikan pesan terakhir, waktunya, status pesanan, dan jumlah pesan
       yang belum dibaca.
       ===================================================================== */

    /**
     * Bagian bersama kedua kotak masuk.
     *
     * Pesan terakhir dan jumlah belum dibaca diambil lewat subquery, bukan
     * JOIN + GROUP BY: dengan JOIN, pesanan yang belum punya pesan sama
     * sekali akan hilang dari daftar - padahal justru itu yang sering perlu
     * dihubungi lebih dulu.
     */
    protected function inbox_dasar($kolom_dibaca)
    {
        $this->db
            ->select("o.id, o.order_number, o.order_status, o.payment_status,
                      o.total, o.created_at,
                      (SELECT m.isi FROM order_messages m
                        WHERE m.order_id = o.id ORDER BY m.id DESC LIMIT 1) AS pesan_akhir,
                      (SELECT m.image FROM order_messages m
                        WHERE m.order_id = o.id ORDER BY m.id DESC LIMIT 1) AS gambar_akhir,
                      (SELECT m.created_at FROM order_messages m
                        WHERE m.order_id = o.id ORDER BY m.id DESC LIMIT 1) AS waktu_akhir,
                      (SELECT COUNT(*) FROM order_messages m
                        WHERE m.order_id = o.id AND m.{$kolom_dibaca} = 0) AS belum", FALSE)
            ->from('orders o');

        return $this;
    }

    /** Kotak masuk penjual: semua pesanan tokonya yang sudah dibayar. */
    public function inbox_toko($store_id, $limit = 60)
    {
        return $this->inbox_dasar('dibaca_seller')->db
            ->select('o.customer_name AS lawan, o.recipient_city AS keterangan', FALSE)
            ->where('o.store_id', (int) $store_id)
            ->where('o.payment_status', 'paid')
            /* Yang ada pesan barunya naik ke atas; sisanya urut dari
               pesanan terbaru. Penjual membuka halaman ini untuk membalas,
               bukan untuk melihat riwayat. */
            ->order_by('belum > 0', 'DESC', FALSE)
            ->order_by('waktu_akhir IS NULL', 'ASC', FALSE)
            ->order_by('COALESCE(waktu_akhir, o.created_at)', 'DESC', FALSE)
            ->limit((int) $limit)
            ->get()->result_array();
    }

    /** Kotak masuk pembeli: semua pesanan miliknya. */
    public function inbox_user($user_id, $limit = 60)
    {
        return $this->inbox_dasar('dibaca_customer')->db
            ->select('s.name AS lawan, s.slug AS keterangan, o.access_token', FALSE)
            ->join('stores s', 's.id = o.store_id', 'left')
            ->where('o.user_id', (int) $user_id)
            ->where('o.payment_status', 'paid')
            ->order_by('belum > 0', 'DESC', FALSE)
            ->order_by('waktu_akhir IS NULL', 'ASC', FALSE)
            ->order_by('COALESCE(waktu_akhir, o.created_at)', 'DESC', FALSE)
            ->limit((int) $limit)
            ->get()->result_array();
    }

    /* =====================================================================
       PERCAKAPAN (wadah)

       Percakapan pesanan dan percakapan "tanya toko" memakai wadah yang
       sama; bedanya hanya order_id yang terisi atau tidak.
       ===================================================================== */

    /**
     * Cari wadah percakapan, buat kalau belum ada.
     *
     * @param  int      $store_id
     * @param  int|NULL $user_id   NULL untuk pemesan tamu
     * @param  int|NULL $order_id  NULL untuk tanya sebelum beli
     * @return int id percakapan
     */
    public function conv($store_id, $user_id = NULL, $order_id = NULL)
    {
        $this->db->where('store_id', (int) $store_id);

        if ($order_id) {
            $this->db->where('order_id', (int) $order_id);
        } else {
            $this->db->where('order_id IS NULL', NULL, FALSE)
                     ->where('user_id', (int) $user_id);
        }

        $c = $this->db->get('conversations')->row_array();
        if ($c) {
            return (int) $c['id'];
        }

        /* Indeks unik di tabel yang menjaga agar tidak ada dua wadah untuk
           pasangan yang sama. Kalau permintaan lain menyelipkan barisnya
           lebih dulu, insert ini gagal - dan yang dipakai adalah punya
           mereka, bukan membuat ruang kedua yang memecah percakapan. */
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;

        $ok = $this->db->insert('conversations', array(
            'store_id' => (int) $store_id,
            'user_id'  => $user_id ? (int) $user_id : NULL,
            'order_id' => $order_id ? (int) $order_id : NULL,
        ));

        $this->db->db_debug = $debug;

        if ($ok) {
            return (int) $this->db->insert_id();
        }

        return (int) $this->conv_cari($store_id, $user_id, $order_id);
    }

    protected function conv_cari($store_id, $user_id, $order_id)
    {
        $this->db->select('id')->where('store_id', (int) $store_id);
        if ($order_id) {
            $this->db->where('order_id', (int) $order_id);
        } else {
            $this->db->where('order_id IS NULL', NULL, FALSE)->where('user_id', (int) $user_id);
        }
        $c = $this->db->get('conversations')->row_array();
        return $c ? $c['id'] : 0;
    }

    /** Satu wadah beserta keterangan toko & pesanannya. */
    public function conv_row($id)
    {
        return $this->db
            ->select('c.*, s.name AS store_name, s.slug AS store_slug, s.avatar AS store_avatar,
                      o.order_number, o.access_token, o.order_status, o.payment_status', FALSE)
            ->from('conversations c')
            ->join('stores s', 's.id = c.store_id')
            ->join('orders o', 'o.id = c.order_id', 'left')
            ->where('c.id', (int) $id)
            ->get()->row_array();
    }

    /* ---------------------------------------------------- pesan per wadah --- */

    public function pesan_conv($conv_id, $sejak_id = 0)
    {
        $baris = $this->db
            ->select('m.*, p.name AS produk_nama, p.slug AS produk_slug, p.price AS produk_harga,
                      p.image AS produk_gambar, s2.slug AS produk_toko', FALSE)
            ->from('order_messages m')
            ->join('products p', 'p.id = m.product_id', 'left')
            ->join('stores s2', 's2.id = p.store_id', 'left')
            ->where('m.conversation_id', (int) $conv_id)
            ->where('m.id >', (int) $sejak_id)
            ->order_by('m.id', 'ASC')
            ->get()->result_array();

        /* Harga diformat dan alamat produk disusun DI SINI, bukan di
           JavaScript: aturan penulisan rupiah dan susunan alamat produk
           sudah ada di PHP, dan menyalinnya ke browser berarti dua tempat
           yang harus diubah bersamaan setiap kali salah satunya berubah. */
        $this->load->helper(array('money', 'url'));

        foreach ($baris as &$m) {
            if (empty($m['product_id']) || empty($m['produk_nama'])) {
                continue;
            }
            $m['produk_harga_teks'] = rupiah($m['produk_harga']);
            $m['produk_url'] = site_url('produk/' . $m['produk_toko'] . '/' . $m['produk_slug']);
        }

        return $baris;
    }

    public function kirim_conv($conv_id, $pengirim, $tipe, $isi = NULL,
                               $image = NULL, $user_id = NULL, $product_id = NULL)
    {
        $conv = $this->db->where('id', (int) $conv_id)->get('conversations')->row_array();
        if ( ! $conv) {
            return 0;
        }

        $this->db->insert('order_messages', array(
            'conversation_id' => (int) $conv_id,
            // Tetap diisi supaya kode lama yang membaca lewat order_id ikut jalan.
            'order_id'  => $conv['order_id'],
            'pengirim'  => $pengirim,
            'user_id'   => $user_id ? (int) $user_id : NULL,
            'tipe'      => $tipe,
            'isi'       => $isi,
            'image'     => $image,
            'product_id' => $product_id ? (int) $product_id : NULL,

            'dibaca_customer' => ($pengirim === 'customer') ? 1 : 0,
            'dibaca_seller'   => ($pengirim === 'seller')   ? 1 : 0,
        ));

        $id = (int) $this->db->insert_id();

        // Dipakai mengurutkan kotak masuk tanpa perlu subquery tiap kali.
        $this->db->where('id', (int) $conv_id)
                 ->update('conversations', array('last_message_at' => date('Y-m-d H:i:s')));

        return $id;
    }

    public function sistem_conv($conv_id, $isi)
    {
        return $this->kirim_conv($conv_id, 'sistem', 'sistem', $isi);
    }

    public function tandai_dibaca_conv($conv_id, $sisi)
    {
        $kolom = ($sisi === 'customer') ? 'dibaca_customer' : 'dibaca_seller';

        $this->db->where('conversation_id', (int) $conv_id)
                 ->where($kolom, 0)
                 ->update('order_messages', array($kolom => 1));
    }

    public function belum_dibaca_conv($conv_id, $sisi)
    {
        $kolom = ($sisi === 'customer') ? 'dibaca_customer' : 'dibaca_seller';

        return (int) $this->db->where('conversation_id', (int) $conv_id)
                              ->where($kolom, 0)
                              ->count_all_results('order_messages');
    }

    /* ------------------------------------------------- kotak masuk wadah --- */

    protected function inbox_conv($kolom_dibaca)
    {
        return $this->db
            ->select("c.id, c.order_id, c.store_id, c.user_id, c.created_at,
                      o.order_number, o.order_status, o.access_token,
                      (SELECT m.isi FROM order_messages m
                        WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS pesan_akhir,
                      (SELECT m.image FROM order_messages m
                        WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS gambar_akhir,
                      (SELECT m.product_id FROM order_messages m
                        WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS produk_akhir,
                      COALESCE(c.last_message_at, c.created_at) AS waktu_akhir,
                      (SELECT COUNT(*) FROM order_messages m
                        WHERE m.conversation_id = c.id AND m.{$kolom_dibaca} = 0) AS belum", FALSE)
            ->from('conversations c')
            ->join('orders o', 'o.id = c.order_id', 'left');
    }

    public function inbox_toko_conv($store_id, $limit = 60)
    {
        return $this->inbox_conv('dibaca_seller')
            ->select('COALESCE(o.customer_name, u.name, "Pembeli") AS lawan', FALSE)
            ->join('users u', 'u.id = c.user_id', 'left')
            ->where('c.store_id', (int) $store_id)
            ->order_by('belum > 0', 'DESC', FALSE)
            ->order_by('waktu_akhir', 'DESC', FALSE)
            ->limit((int) $limit)
            ->get()->result_array();
    }

    public function inbox_user_conv($user_id, $limit = 60)
    {
        return $this->inbox_conv('dibaca_customer')
            ->select('s.name AS lawan', FALSE)
            ->join('stores s', 's.id = c.store_id')
            ->where('c.user_id', (int) $user_id)
            ->order_by('belum > 0', 'DESC', FALSE)
            ->order_by('waktu_akhir', 'DESC', FALSE)
            ->limit((int) $limit)
            ->get()->result_array();
    }
}
