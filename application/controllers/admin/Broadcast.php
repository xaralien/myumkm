<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * admin/Broadcast - pengumuman ke penjual (di panel + WhatsApp).
 *
 *   GET  admin/broadcast           riwayat + formulir
 *   POST admin/broadcast/kirim     judul, isi, regency_id, category_id, kirim_wa
 *
 * Contoh: jadwal pelatihan di kecamatan tertentu, perubahan kebijakan,
 * atau ajakan ikut kampanye "Pekan Produk Lokal".
 */
class Broadcast extends Admin_Controller {

    /** Batas WA per broadcast - gerbang WA lokal biasanya membatasi kiriman. */
    const MAKS_WA = 300;

    public function index()
    {
        $this->load->model('product_model');
        $this->render('admin/v_broadcast', array(
            'riwayat'  => $this->db->select('b.*, (SELECT COUNT(*) FROM broadcast_toko t WHERE t.broadcast_id = b.id AND t.dibaca_at IS NOT NULL) AS dibaca', FALSE)
                ->order_by('id', 'DESC')->limit(50)->get('broadcast b')->result_array(),
            'kota'     => $this->db->select('r.id, r.name, COUNT(s.id) AS n', FALSE)->from('regencies r')
                ->join('stores s', 's.regency_id = r.id AND s.is_active = 1')->group_by('r.id')->order_by('r.name')->get()->result_array(),
            'kategori' => $this->product_model->categories_simple(),
        ));
    }

    public function kirim()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $judul = mb_substr(trim((string) $this->input->post('judul', TRUE)), 0, 150);
        $isi   = trim((string) $this->input->post('isi', TRUE));
        if ($judul === '' || mb_strlen($isi) < 10) {
            $this->session->set_flashdata('error', 'Judul dan isi pengumuman wajib diisi.');
            return redirect('admin/broadcast');
        }
        $reg = (int) $this->input->post('regency_id') ?: NULL;
        $kat = (int) $this->input->post('category_id') ?: NULL;

        $this->db->select('s.id, s.phone')->from('stores s')->where('s.is_active', 1);
        if ($reg) {
            $this->db->where('s.regency_id', $reg);
        }
        if ($kat) {
            $this->db->where('EXISTS (SELECT 1 FROM products p WHERE p.store_id = s.id AND p.category_id = ' . $kat . ')', NULL, FALSE);
        }
        $toko = $this->db->get()->result_array();
        if ( ! $toko) {
            $this->session->set_flashdata('error', 'Tidak ada toko yang cocok dengan sasaran itu.');
            return redirect('admin/broadcast');
        }

        $wa = (bool) $this->input->post('kirim_wa');
        $this->db->insert('broadcast', array(
            'judul' => $judul, 'isi' => mb_substr($isi, 0, 4000), 'regency_id' => $reg, 'category_id' => $kat,
            'kirim_wa' => $wa ? 1 : 0, 'penerima' => count($toko), 'dibuat_oleh' => (int) $this->me['id'],
        ));
        $bid = (int) $this->db->insert_id();
        foreach (array_chunk($toko, 200) as $potong) {
            $this->db->insert_batch('broadcast_toko', array_map(function ($t) use ($bid) {
                return array('broadcast_id' => $bid, 'store_id' => (int) $t['id']);
            }, $potong));
        }

        $terkirim = 0;
        if ($wa) {
            $this->load->library('notifikasi_lib');
            foreach (array_slice($toko, 0, self::MAKS_WA) as $t) {
                if ($this->notifikasi_lib->wa($t['phone'], '📢 ' . $judul . "\n\n" . $isi, 'broadcast')) {
                    $terkirim++;
                }
            }
            $this->db->where('id', $bid)->update('broadcast', array('terkirim_wa' => $terkirim));
        }

        $this->session->set_flashdata('sukses', 'Pengumuman dikirim ke ' . count($toko) . ' toko'
            . ($wa ? ' (' . $terkirim . ' lewat WhatsApp)' : '') . '. Muncul di panel penjual sampai dibaca.');
        return redirect('admin/broadcast');
    }
}
