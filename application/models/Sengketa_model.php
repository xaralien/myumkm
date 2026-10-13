<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Sengketa_model - pusat sengketa pembeli-penjual.
 * Simpan di: application/models/Sengketa_model.php
 *
 * Alur:
 *   1. Pembeli membuka sengketa dari halaman pesanan (biasanya setelah
 *      refund ditolak, atau barang tidak sesuai/tidak sampai).
 *   2. Pembeli, penjual, dan admin bertukar keterangan & bukti di satu utas.
 *   3. Admin memutuskan. Kalau pembeli menang, permintaan refund dibuat /
 *      disetujui otomatis dan transfernya diproses di Keuangan > Refund.
 */
class Sengketa_model extends CI_Model
{
    public static $alasan = array(
        'Barang tidak sampai', 'Barang tidak sesuai pesanan', 'Barang rusak / cacat',
        'Refund ditolak penjual', 'Penjual tidak merespons', 'Lainnya',
    );

    public static $status = array(
        'terbuka'        => 'Terbuka',
        'ditinjau'       => 'Ditinjau admin',
        'menang_pembeli' => 'Diputuskan: dana dikembalikan',
        'menang_penjual' => 'Diputuskan: pesanan sah',
        'damai'          => 'Selesai damai',
        'ditutup'        => 'Ditutup',
    );

    public function aktif($order_id)
    {
        return $this->db->where('order_id', (int) $order_id)->order_by('id', 'DESC')->limit(1)
            ->get('sengketa')->row_array() ?: NULL;
    }

    public function ambil($id)
    {
        return $this->db->select('g.*, o.order_number, o.access_token, o.total, o.customer_name, o.customer_phone,
                                  o.order_status, o.payment_status, s.name AS store_name, s.phone AS store_phone', FALSE)
            ->from('sengketa g')->join('orders o', 'o.id = g.order_id')->join('stores s', 's.id = g.store_id', 'left')
            ->where('g.id', (int) $id)->get()->row_array() ?: NULL;
    }

    /** Pesanan yang boleh disengketakan: sudah dibayar dan belum lama selesai. */
    public function boleh_dibuka(array $o)
    {
        if ($o['payment_status'] !== 'paid' || $o['order_status'] === 'cancelled') {
            return FALSE;
        }
        if ($o['order_status'] === 'delivered' && ! empty($o['completed_at'])
            && strtotime($o['completed_at']) < strtotime('-14 days')) {
            return FALSE;
        }
        $s = $this->aktif($o['id']);
        return ! $s || in_array($s['status'], array('damai', 'ditutup'), TRUE);
    }

    public function buka(array $o, array $in)
    {
        $alasan = in_array($in['alasan'] ?? '', self::$alasan, TRUE) ? $in['alasan'] : 'Lainnya';
        $isi = trim((string) ($in['cerita'] ?? ''));
        if (mb_strlen($isi) < 15) {
            return array('ok' => FALSE, 'pesan' => 'Ceritakan masalahnya minimal 15 karakter supaya admin bisa memahami.');
        }
        $tuntutan = in_array($in['tuntutan'] ?? '', array('refund_penuh', 'refund_sebagian', 'tukar_barang', 'lainnya'), TRUE)
            ? $in['tuntutan'] : 'refund_penuh';
        $jumlah = $tuntutan === 'refund_sebagian'
            ? min((int) $o['total'], (int) preg_replace('/[^0-9]/', '', (string) ($in['jumlah'] ?? '')))
            : ($tuntutan === 'refund_penuh' ? (int) $o['total'] : NULL);

        $this->db->insert('sengketa', array(
            'order_id' => (int) $o['id'], 'store_id' => (int) $o['store_id'],
            'alasan'   => $alasan, 'tuntutan' => $tuntutan, 'jumlah' => $jumlah ?: NULL,
        ));
        $id = (int) $this->db->insert_id();
        $this->kirim($id, 'pembeli', $isi);

        $this->load->library('notifikasi_lib');
        $toko = $this->db->select('phone, name')->where('id', (int) $o['store_id'])->get('stores')->row_array();
        $this->notifikasi_lib->wa($toko['phone'] ?? '', 'Pembeli membuka sengketa untuk pesanan ' . $o['order_number']
            . ' (' . $alasan . '). Berikan penjelasanmu di: ' . site_url('seller/sengketa/lihat/' . $id), 'sengketa_baru');
        $wa_admin = $this->config->item('wa_admin');
        if ($wa_admin) {
            $this->notifikasi_lib->wa($wa_admin, 'Sengketa baru #' . $id . ': ' . $o['order_number'] . ' - ' . $alasan
                . '. ' . site_url('admin/sengketa/lihat/' . $id), 'sengketa_admin');
        }
        return array('ok' => TRUE, 'pesan' => 'Sengketa dibuka. Admin akan meninjau dalam 2x24 jam; penjual juga diminta penjelasannya.', 'id' => $id);
    }

    public function kirim($id, $pengirim, $isi)
    {
        $isi = trim((string) $isi);
        if ($isi === '') {
            return FALSE;
        }
        $this->db->insert('sengketa_pesan', array(
            'sengketa_id' => (int) $id, 'pengirim' => $pengirim, 'isi' => mb_substr($isi, 0, 4000),
        ));
        // Ada tanggapan baru dari salah satu pihak: admin perlu melihatnya.
        if ($pengirim !== 'admin') {
            $this->db->where('id', (int) $id)->where('status', 'terbuka')->update('sengketa', array('status' => 'ditinjau'));
        }
        return TRUE;
    }

    public function pesan($id)
    {
        return $this->db->where('sengketa_id', (int) $id)->order_by('id', 'ASC')->get('sengketa_pesan')->result_array();
    }

    /**
     * Putusan admin. Pembeli menang -> refund disetujui sejumlah putusan.
     */
    public function putus($id, $status, $keputusan, $jumlah = NULL)
    {
        $g = $this->ambil($id);
        if ( ! $g || ! isset(self::$status[$status]) || in_array($status, array('terbuka', 'ditinjau'), TRUE)) {
            return array('ok' => FALSE, 'pesan' => 'Putusan tidak valid.');
        }
        $keputusan = trim((string) $keputusan);
        if (mb_strlen($keputusan) < 10) {
            return array('ok' => FALSE, 'pesan' => 'Tulis alasan putusan - kedua pihak berhak tahu.');
        }

        $this->db->trans_begin();
        $this->db->where('id', (int) $id)->update('sengketa', array(
            'status' => $status, 'keputusan' => $keputusan, 'diputus_at' => date('Y-m-d H:i:s'),
        ));
        $this->kirim($id, 'admin', 'PUTUSAN: ' . self::$status[$status] . '. ' . $keputusan);

        if ($status === 'menang_pembeli') {
            $jumlah = max(1, min((int) $g['total'], (int) ($jumlah ?: $g['jumlah'] ?: $g['total'])));
            $ada = $this->db->where('order_id', (int) $g['order_id'])->order_by('id', 'DESC')->limit(1)->get('refunds')->row_array();
            if ($ada && $ada['status'] !== 'selesai') {
                $this->db->where('id', (int) $ada['id'])->update('refunds', array(
                    'status' => 'disetujui', 'jumlah' => $jumlah, 'diputus_at' => date('Y-m-d H:i:s'),
                    'catatan_penjual' => 'Diputuskan admin lewat sengketa #' . (int) $id,
                ));
            } elseif ( ! $ada) {
                $o = $this->db->where('id', (int) $g['order_id'])->get('orders')->row_array();
                $this->db->insert('refunds', array(
                    'order_id' => (int) $o['id'], 'store_id' => (int) $o['store_id'], 'user_id' => $o['user_id'],
                    'jumlah' => $jumlah, 'alasan' => 'Putusan sengketa #' . (int) $id . ': ' . $g['alasan'],
                    'status' => 'disetujui', 'diputus_at' => date('Y-m-d H:i:s'),
                    'catatan_penjual' => 'Diputuskan admin lewat sengketa #' . (int) $id,
                ));
            }
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return array('ok' => FALSE, 'pesan' => 'Gagal menyimpan putusan.');
        }
        $this->db->trans_commit();

        $this->load->library('notifikasi_lib');
        $teks = 'Putusan sengketa pesanan ' . $g['order_number'] . ': ' . self::$status[$status] . '. ' . $keputusan;
        $this->notifikasi_lib->wa($g['customer_phone'], $teks . ' Detail: ' . site_url('checkout/done/' . $g['order_number'] . '/' . $g['access_token']), 'sengketa_putus');
        $this->notifikasi_lib->wa($g['store_phone'], $teks, 'sengketa_putus');

        return array('ok' => TRUE, 'pesan' => 'Putusan disimpan dan dikirim ke kedua pihak.'
            . ($status === 'menang_pembeli' ? ' Refund ' . rupiah($jumlah) . ' menunggu transfer di Keuangan > Refund.' : ''));
    }
}
