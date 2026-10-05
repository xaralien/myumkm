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

        $this->buka_percakapan($order);
        $this->potong_stok($order);

        /* Bagian toko dihitung dan disimpan di sini, sekali. Persentase
           komisi bisa berubah tahun depan; pesanan lama harus tetap memakai
           angka yang berlaku saat pesanan ini dibayar. */
        $this->load->library('pendapatan_lib');
        $this->pendapatan_lib->catat($order);

        return TRUE;
    }

    /**
     * Buat percakapan pesanan dan isi pesan pembuka begitu pembayaran masuk.
     *
     * Dipanggil dari mark_paid() yang sudah punya pengaman balapan, jadi
     * pesan ini dijamin hanya dibuat SEKALI - callback Duitku bisa datang
     * dua kali untuk pembayaran yang sama.
     *
     * Kenapa lewat chat, bukan sistem pemberitahuan tersendiri: pesanan
     * baru langsung naik ke atas kotak masuk penjual dengan lencana merah,
     * dan gelembung chat sudah ada di semua halaman panel. Penjual
     * melihatnya tanpa perlu membuka halaman pesanan.
     */
    /**
     * Kurangi stok, dan beri tahu penjual kalau ternyata tidak cukup.
     *
     * Pesanan TIDAK dibatalkan saat stok kurang: uangnya sudah masuk, dan
     * membatalkan sepihak jauh lebih merugikan pembeli daripada penjual
     * yang kelebihan satu pesanan. Penjual yang memutuskan - dia bisa
     * membuat satu lagi, atau membatalkan dan merefund.
     */
    protected function potong_stok(array $order)
    {
        $this->load->library('stok_lib');

        $hasil = $this->stok_lib->kurangi($order['id']);

        if ($hasil['ok']) {
            return;
        }

        $this->load->model('chat_model');
        $conv = $this->chat_model->conv($order['store_id'], $order['user_id'], $order['id']);

        if ($conv) {
            $this->chat_model->sistem_conv($conv,
                'Perhatian: stok tidak mencukupi untuk ' . implode(', ', $hasil['gagal'])
                . '. Pesanan tetap masuk karena sudah dibayar. '
                . 'Hubungi pembeli kalau barangnya tidak bisa disiapkan.');
        }
    }

    protected function buka_percakapan(array $order)
    {
        $this->load->model('chat_model');
        $this->load->helper('money');

        $conv = $this->chat_model->conv($order['store_id'], $order['user_id'], $order['id']);
        if ( ! $conv) {
            return;
        }

        $item = $this->db->select('product_name, qty')
                         ->where('order_id', $order['id'])
                         ->get('order_items')->result_array();

        $baris = array();
        foreach ($item as $i) {
            $baris[] = $i['qty'] . 'x ' . $i['product_name'];
        }

        /* Isi pesanan ikut ditulis di pesannya. Penjual yang membalas dari
           gelembung chat jadi tahu apa yang dipesan tanpa pindah halaman. */
        $isi = 'Pesanan baru masuk dan sudah dibayar.' . "\n"
             . $order['order_number'] . "\n"
             . implode("\n", $baris) . "\n"
             . 'Total ' . rupiah($order['total']);

        $this->chat_model->sistem_conv($conv, $isi);
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
     * Batalkan pesanan.
     *
     * @param  string $oleh    'customer', 'seller', atau 'system'
     * @param  string $alasan
     * @return array  ['ok' => bool, 'pesan' => string]
     */
    public function batalkan(array $order, $oleh = 'customer', $alasan = NULL)
    {
        if ($order['order_status'] === 'cancelled') {
            return array('ok' => FALSE, 'pesan' => 'Pesanan ini sudah dibatalkan.');
        }

        if (in_array($order['order_status'], array('delivering', 'delivered'), TRUE)) {
            return array('ok' => FALSE, 'pesan' => 'Pesanan sudah dikirim dan tidak bisa dibatalkan.');
        }

        /* Pembeli hanya boleh membatalkan SEBELUM membayar. Setelah uangnya
           masuk, membatalkan berarti ada uang yang harus dikembalikan - dan
           itu keputusan penjual, lewat alur refund, bukan satu tombol di
           halaman pesanan. */
        if ($oleh === 'customer' && $order['payment_status'] === 'paid') {
            return array(
                'ok' => FALSE,
                'pesan' => 'Pesanan sudah dibayar. Hubungi penjual lewat chat untuk pembatalan dan pengembalian dana.',
            );
        }

        $this->db->where('id', $order['id'])
                 ->where('order_status !=', 'cancelled')   // penjaga balapan
                 ->update('orders', array(
                     'order_status'  => 'cancelled',
                     'cancelled_by'  => $oleh,
                     'cancel_reason' => $alasan ? mb_substr($alasan, 0, 255) : NULL,
                     'cancelled_at'  => date('Y-m-d H:i:s'),
                 ));

        if ($this->db->affected_rows() < 1) {
            return array('ok' => FALSE, 'pesan' => 'Pesanan ini sudah dibatalkan.');
        }

        // Stok hanya dikurangi saat pembayaran diterima, jadi hanya pesanan
        // lunas yang perlu dikembalikan stoknya.
        if ($order['payment_status'] === 'paid') {
            $this->load->library('stok_lib');
            $this->stok_lib->kembalikan($order['id']);
        }

        $this->db->insert('order_logs', array(
            'order_id' => $order['id'],
            'status'   => 'cancelled',
            'note'     => 'Dibatalkan oleh ' . $oleh . ($alasan ? ': ' . $alasan : ''),
        ));

        $this->catat_pembatalan($order, $oleh, $alasan);

        return array('ok' => TRUE, 'pesan' => 'Pesanan dibatalkan.');
    }

    /** Pesan sistem di percakapan pesanan, supaya kedua pihak tahu. */
    protected function catat_pembatalan(array $order, $oleh, $alasan)
    {
        $this->load->model('chat_model');

        $conv = $this->chat_model->conv($order['store_id'], $order['user_id'], $order['id']);
        if ( ! $conv) {
            return;
        }

        $siapa = array(
            'customer' => 'Pembeli membatalkan pesanan ini.',
            'seller'   => 'Penjual membatalkan pesanan ini.',
            'system'   => 'Pesanan dibatalkan otomatis karena pembayaran tidak diterima tepat waktu.',
        );

        $this->chat_model->sistem_conv($conv,
            ($siapa[$oleh] ?? $siapa['system']) . ($alasan ? ' Alasan: ' . $alasan : ''));
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
