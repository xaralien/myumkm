<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tagihan_lib - satu pembayaran Duitku untuk satu atau beberapa pesanan.
 *
 * Simpan di: application/libraries/Tagihan_lib.php
 *
 * Dipakai untuk:
 *   grup      - checkout beberapa toko sekaligus: tiap toko mendapat
 *               pesanannya sendiri, pembeli cukup membayar sekali.
 *   dp        - uang muka pre-order satu toko.
 *   pelunasan - sisa pembayaran pre-order.
 *
 * Pesanan satu toko yang dibayar penuh TETAP memakai alur lama (nomor
 * pesanan sebagai merchantOrderId) - tidak ada yang berubah untuknya.
 *
 * Kode tagihan berawalan "TG-" supaya callback bisa langsung membedakannya
 * dari nomor pesanan biasa.
 */
class Tagihan_lib
{
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('duitku');
        $this->CI->load->model('order_model');
    }

    public static function kode_tagihan($kode)
    {
        return strncmp((string) $kode, 'TG-', 3) === 0;
    }

    /**
     * Buat tagihan untuk pesanan-pesanan ini.
     *
     * @param  string $jenis   grup | dp | pelunasan
     * @param  array  $orders  baris orders (lengkap)
     * @return array|FALSE     baris tagihan
     */
    public function buat($jenis, array $orders)
    {
        if ( ! $orders) {
            return FALSE;
        }

        $baris = array();
        $jumlah = 0;
        foreach ($orders as $o) {
            $n = $this->jumlah_untuk($jenis, $o);
            if ($n <= 0) {
                continue;
            }
            $baris[] = array('order' => $o, 'jumlah' => $n);
            $jumlah += $n;
        }
        if ( ! $baris) {
            return FALSE;
        }

        $pertama = $orders[0];
        $tagihan = array(
            'kode'           => $this->kode_baru(),
            'access_token'   => bin2hex(random_bytes(20)),
            'jenis'          => $jenis,
            'jumlah'         => $jumlah,
            'status'         => 'unpaid',
            'customer_name'  => $pertama['customer_name'],
            'customer_phone' => $pertama['customer_phone'],
            'customer_email' => $pertama['customer_email'],
        );

        $this->CI->db->trans_begin();
        $this->CI->db->insert('tagihan', $tagihan);
        $tagihan['id'] = (int) $this->CI->db->insert_id();

        foreach ($baris as $b) {
            $this->CI->db->insert('tagihan_order', array(
                'tagihan_id' => $tagihan['id'],
                'order_id'   => (int) $b['order']['id'],
                'jumlah'     => $b['jumlah'],
            ));
            // Tagihan pertama pesanan dicatat di pesanannya. Pelunasan tidak -
            // kolom ini menunjuk pembayaran yang membuka pesanan.
            if ($jenis !== 'pelunasan') {
                $this->CI->db->where('id', (int) $b['order']['id'])
                    ->update('orders', array('tagihan_id' => $tagihan['id']));
            }
        }

        if ($this->CI->db->trans_status() === FALSE) {
            $this->CI->db->trans_rollback();
            return FALSE;
        }
        $this->CI->db->trans_commit();

        $this->buat_invoice($tagihan);
        return $this->ambil($tagihan['kode']);
    }

    /** Nominal yang ditagih dari satu pesanan untuk jenis tagihan ini. */
    public function jumlah_untuk($jenis, array $o)
    {
        $dp = isset($o['dp_jumlah']) && $o['dp_jumlah'] !== NULL ? (int) $o['dp_jumlah'] : NULL;
        if ($jenis === 'pelunasan') {
            return $dp !== NULL ? max(0, (int) $o['total'] - $dp) : 0;
        }
        return $dp !== NULL ? $dp : (int) $o['total'];
    }

    /**
     * Buat (ulang) invoice Duitku untuk tagihan. Aman dipanggil lagi kalau
     * pembuatan sebelumnya gagal - kodenya sama, karena Duitku belum pernah
     * menerimanya.
     */
    public function buat_invoice(array $tagihan)
    {
        $orders = $this->orders($tagihan['id']);

        $item = array();
        foreach ($orders as $o) {
            $item[] = array(
                'name'     => ($tagihan['jenis'] === 'pelunasan' ? 'Pelunasan ' : ($tagihan['jenis'] === 'dp' ? 'Uang muka ' : 'Pesanan '))
                              . $o['order_number'] . ' - ' . $o['store_name'],
                'price'    => (int) $o['tagihan_jumlah'],
                'quantity' => 1,
            );
        }

        // Duitku::create_invoice membaca kolom gaya pesanan.
        $semu = array(
            'order_number'   => $tagihan['kode'],
            'total'          => (int) $tagihan['jumlah'],
            'shipping_fee'   => 0,
            'customer_name'  => $tagihan['customer_name'],
            'customer_phone' => $tagihan['customer_phone'],
            'customer_email' => $tagihan['customer_email'],
        );

        $inv = $this->CI->duitku->create_invoice($semu, $item);

        if (isset($inv['error']) || empty($inv['reference'])) {
            $this->CI->db->where('id', (int) $tagihan['id'])->update('tagihan', array('status' => 'failed'));
            $this->CI->order_model->log_callback($tagihan['kode'], json_encode($inv), FALSE, 'Gagal membuat tagihan');
            return FALSE;
        }

        $this->CI->db->where('id', (int) $tagihan['id'])->update('tagihan', array(
            'duitku_reference'   => $inv['reference'],
            'duitku_payment_url' => isset($inv['payment_url']) ? $inv['payment_url'] : NULL,
            'status'             => 'unpaid',
        ));
        return TRUE;
    }

    public function ambil($kode)
    {
        return $this->CI->db->where('kode', (string) $kode)->get('tagihan')->row_array() ?: NULL;
    }

    public function ambil_token($kode, $token)
    {
        $t = $this->ambil($kode);
        return ($t && hash_equals($t['access_token'], (string) $token)) ? $t : NULL;
    }

    /** Pesanan dalam tagihan, beserta bagian nominalnya. */
    public function orders($tagihan_id)
    {
        return $this->CI->db
            ->select('o.*, tor.jumlah AS tagihan_jumlah, s.name AS store_name', FALSE)
            ->from('tagihan_order tor')
            ->join('orders o', 'o.id = tor.order_id')
            ->join('stores s', 's.id = o.store_id', 'left')
            ->where('tor.tagihan_id', (int) $tagihan_id)
            ->order_by('o.id', 'ASC')
            ->get()->result_array();
    }

    /** Tagihan pelunasan yang masih bisa dibayar untuk pesanan ini. */
    public function pelunasan_aktif($order_id)
    {
        return $this->CI->db->select('t.*')
            ->from('tagihan t')
            ->join('tagihan_order tor', 'tor.tagihan_id = t.id')
            ->where('tor.order_id', (int) $order_id)
            ->where('t.jenis', 'pelunasan')
            ->where_in('t.status', array('unpaid', 'failed'))
            ->order_by('t.id', 'DESC')->limit(1)
            ->get()->row_array() ?: NULL;
    }

    /**
     * Tandai tagihan lunas dan teruskan ke pesanan-pesanannya.
     * Aman dipanggil berulang (callback ganda, cek status bersamaan).
     *
     * @return bool TRUE kalau status berubah pada panggilan ini
     */
    public function tandai_lunas($kode, $reference = NULL)
    {
        $t = $this->ambil($kode);
        if ( ! $t || $t['status'] === 'paid') {
            return FALSE;
        }

        $this->CI->db->where('id', (int) $t['id'])->where('status !=', 'paid')
            ->update('tagihan', array(
                'status'           => 'paid',
                'paid_at'          => date('Y-m-d H:i:s'),
                'duitku_reference' => $reference ?: $t['duitku_reference'],
            ));
        if ($this->CI->db->affected_rows() < 1) {
            return FALSE;
        }

        foreach ($this->orders($t['id']) as $o) {
            if ($t['jenis'] === 'pelunasan') {
                $this->lunasi($o);
                continue;
            }
            $this->CI->order_model->mark_paid($o['order_number'], $reference);
            // Dibayar penuh di tagihan grup: langsung lunas.
            if ($o['dp_jumlah'] === NULL) {
                $this->CI->db->where('id', (int) $o['id'])
                    ->update('orders', array('lunas_at' => date('Y-m-d H:i:s')));
            }
        }
        return TRUE;
    }

    /** Sisa pre-order sudah dibayar: pesanan boleh dikirim. */
    protected function lunasi(array $o)
    {
        $this->CI->db->where('id', (int) $o['id'])->where('lunas_at IS NULL', NULL, FALSE)
            ->update('orders', array('lunas_at' => date('Y-m-d H:i:s')));
        if ($this->CI->db->affected_rows() < 1) {
            return;
        }

        $this->CI->db->insert('order_logs', array(
            'order_id' => (int) $o['id'],
            'status'   => $o['order_status'],
            'note'     => 'Pelunasan diterima',
        ));

        $this->CI->load->model('chat_model');
        $this->CI->load->helper('money');
        $this->CI->chat_model->sistem($o['id'], 'Pelunasan ' . rupiah((int) $o['total'] - (int) $o['dp_jumlah'])
            . ' sudah diterima. Pesanan siap dikirim.');

        $this->CI->load->library('notifikasi_lib');
        $toko = $this->CI->db->select('phone')->where('id', (int) $o['store_id'])->get('stores')->row_array();
        if ($toko) {
            $this->CI->notifikasi_lib->wa($toko['phone'], 'Pelunasan pesanan ' . $o['order_number']
                . ' sudah diterima. Pesanan boleh dikirim: ' . site_url('seller/orders'), 'pelunasan_toko');
        }
    }

    public function tandai_gagal($kode, $status = 'expired')
    {
        $this->CI->db->where('kode', (string) $kode)->where('status !=', 'paid')
            ->update('tagihan', array('status' => $status));
    }

    /** Tagihan yang masih menunggu - dipakai penyapu berkala. */
    public function belum_lunas($jam, $limit = 50)
    {
        return $this->CI->db->where('status', 'unpaid')
            ->where('duitku_reference IS NOT NULL', NULL, FALSE)
            ->where('created_at >=', date('Y-m-d H:i:s', time() - ((int) $jam * 3600)))
            ->order_by('created_at', 'ASC')->limit((int) $limit)
            ->get('tagihan')->result_array();
    }

    /** Tagihan yang lewat masa berlaku ditandai kedaluwarsa. */
    public function kedaluwarsa($jam)
    {
        $this->CI->db->where_in('status', array('unpaid', 'failed'))
            ->where('created_at <', date('Y-m-d H:i:s', time() - ((int) $jam * 3600)))
            ->update('tagihan', array('status' => 'expired'));
        return $this->CI->db->affected_rows();
    }

    protected function kode_baru()
    {
        $abjad = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $acak = '';
            for ($i = 0; $i < 6; $i++) {
                $acak .= $abjad[random_int(0, strlen($abjad) - 1)];
            }
            $kode = 'TG-' . date('Ymd') . '-' . $acak;
        } while ($this->ambil($kode));
        return $kode;
    }
}
