<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * penjual/Template - balasan cepat chat.  Alamat: seller/template
 *
 * Pertanyaan pembeli UMKM berulang: "ready kak?", "bisa COD?", "ongkir ke
 * mana?". Template membuat jawabannya cukup satu ketukan di layar chat.
 *
 *   GET  seller/template              daftar + formulir
 *   POST seller/template/simpan       tambah / ubah
 *   POST seller/template/hapus/{id}
 */
class Template extends Seller_Controller
{
    /** Contoh awal untuk toko yang belum punya template sama sekali. */
    protected $contoh = array(
        array('Ready', 'Halo kak, produknya ready ya. Silakan langsung dipesan lewat tombol Beli. Terima kasih!'),
        array('Proses', 'Terima kasih sudah memesan, kak. Pesanan sedang kami siapkan dan segera dikirim.'),
        array('Dikirim', 'Pesanan sudah dikirim ya kak. Nomor resinya ada di halaman pesanan.'),
        array('Habis', 'Mohon maaf kak, stoknya sedang habis. Insyaallah ada lagi minggu depan.'),
    );

    public function index()
    {
        $daftar = $this->daftar();
        if ( ! $daftar && ! $this->db->where('store_id', (int) $this->store['id'])->count_all_results('chat_templates')) {
            foreach ($this->contoh as $i => $c) {
                $this->db->insert('chat_templates', array('store_id' => (int) $this->store['id'], 'judul' => $c[0], 'isi' => $c[1], 'urutan' => $i));
            }
            $daftar = $this->daftar();
        }
        $ubah = NULL;
        if ($this->input->get('ubah')) {
            $ubah = $this->milik($this->input->get('ubah'));
        }
        $this->render('penjual/v_template', array('daftar' => $daftar, 'ubah' => $ubah));
    }

    public function simpan()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $judul = mb_substr(trim((string) $this->input->post('judul', TRUE)), 0, 40);
        $isi   = mb_substr(trim((string) $this->input->post('isi', TRUE)), 0, 1000);
        if ($judul === '' || $isi === '') {
            $this->session->set_flashdata('error', 'Judul dan isi template wajib diisi.');
            return redirect('seller/template');
        }
        $id = (int) $this->input->post('id');
        if ($id) {
            $this->milik($id);
            $this->db->where('id', $id)->update('chat_templates', array('judul' => $judul, 'isi' => $isi));
        } else {
            if (count($this->daftar()) >= 20) {
                $this->session->set_flashdata('error', 'Maksimal 20 template.');
                return redirect('seller/template');
            }
            $this->db->insert('chat_templates', array('store_id' => (int) $this->store['id'], 'judul' => $judul, 'isi' => $isi, 'urutan' => 99));
        }
        $this->session->set_flashdata('sukses', 'Template "' . $judul . '" tersimpan.');
        return redirect('seller/template');
    }

    public function hapus($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $t = $this->milik($id);
        $this->db->where('id', (int) $t['id'])->delete('chat_templates');
        $this->session->set_flashdata('sukses', 'Template dihapus.');
        return redirect('seller/template');
    }

    protected function daftar()
    {
        return $this->db->where('store_id', (int) $this->store['id'])
            ->order_by('urutan', 'ASC')->order_by('id', 'ASC')->get('chat_templates')->result_array();
    }

    protected function milik($id)
    {
        $t = $this->db->where('id', (int) $id)->where('store_id', (int) $this->store['id'])
            ->get('chat_templates')->row_array();
        if ( ! $t) {
            show_404();
        }
        return $t;
    }
}
