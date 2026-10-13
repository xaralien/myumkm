<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * admin/Banner - banner beranda & halaman promo, tanpa mengubah kode.
 *
 *   GET  admin/banner                     daftar banner & halaman promo
 *   GET  admin/banner/form/{id?}          formulir banner
 *   POST admin/banner/simpan
 *   POST admin/banner/hapus/{id}
 *   GET  admin/banner/promo/{id?}         formulir halaman promo
 *   POST admin/banner/promo_simpan
 */
class Banner extends Admin_Controller {

    public function index()
    {
        $this->render('admin/v_banner', array(
            'banner' => $this->db->order_by('urutan', 'ASC')->order_by('id', 'DESC')->get('banners')->result_array(),
            'promo'  => $this->db->order_by('id', 'DESC')->get('halaman_promo')->result_array(),
        ));
    }

    public function form($id = NULL)
    {
        $b = $id ? $this->db->where('id', (int) $id)->get('banners')->row_array() : NULL;
        if ($id && ! $b) {
            show_404();
        }
        $this->render('admin/v_banner_form', array('b' => $b));
    }

    public function simpan()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $id = (int) $this->input->post('id');
        $lama = $id ? $this->db->where('id', $id)->get('banners')->row_array() : NULL;
        $judul = mb_substr(trim((string) $this->input->post('judul', TRUE)), 0, 120);
        if ($judul === '') {
            $this->session->set_flashdata('error', 'Judul banner wajib diisi.');
            return redirect('admin/banner/form' . ($id ? '/' . $id : ''));
        }
        $tautan = trim((string) $this->input->post('tautan', TRUE));
        // Hanya tautan di dalam situs ini, atau https - bukan javascript: dsb.
        if ($tautan !== '' && ! preg_match('#^(/|https://)#', $tautan)) {
            $tautan = '/' . ltrim($tautan, '/');
        }
        $gambar = $this->unggah($lama ? $lama['gambar'] : NULL);
        if ($gambar === FALSE) {
            return redirect('admin/banner/form' . ($id ? '/' . $id : ''));
        }
        $tgl = function ($k) { $v = (string) $this->input->post($k); return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : NULL; };
        $warna = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $this->input->post('warna')) ? $this->input->post('warna') : NULL;

        $d = array(
            'judul'     => $judul,
            'sub'       => mb_substr(trim((string) $this->input->post('sub', TRUE)), 0, 200) ?: NULL,
            'gambar'    => $gambar,
            'tautan'    => $tautan ?: NULL,
            'warna'     => $warna,
            'mulai'     => $tgl('mulai'),
            'selesai'   => $tgl('selesai'),
            'urutan'    => (int) $this->input->post('urutan'),
            'is_active' => $this->input->post('is_active') ? 1 : 0,
        );
        $id ? $this->db->where('id', $id)->update('banners', $d) : $this->db->insert('banners', $d);
        $this->session->set_flashdata('sukses', 'Banner tersimpan.');
        return redirect('admin/banner');
    }

    public function hapus($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $b = $this->db->where('id', (int) $id)->get('banners')->row_array();
        if ($b && $b['gambar'] && is_file(FCPATH . 'upload/banner/' . basename($b['gambar']))) {
            @unlink(FCPATH . 'upload/banner/' . basename($b['gambar']));
        }
        $this->db->where('id', (int) $id)->delete('banners');
        $this->session->set_flashdata('sukses', 'Banner dihapus.');
        return redirect('admin/banner');
    }

    public function promo($id = NULL)
    {
        $p = $id ? $this->db->where('id', (int) $id)->get('halaman_promo')->row_array() : NULL;
        if ($id && ! $p) {
            show_404();
        }
        $this->load->model('product_model');
        $this->render('admin/v_promo_form', array('p' => $p, 'kategori' => $this->product_model->categories_simple()));
    }

    public function promo_simpan()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $id = (int) $this->input->post('id');
        $judul = mb_substr(trim((string) $this->input->post('judul', TRUE)), 0, 150);
        $this->load->helper('text');
        $slug = url_title(convert_accented_characters($this->input->post('slug', TRUE) ?: $judul), '-', TRUE);
        if ($judul === '' || $slug === '') {
            $this->session->set_flashdata('error', 'Judul wajib diisi.');
            return redirect('admin/banner/promo' . ($id ? '/' . $id : ''));
        }
        $this->db->where('slug', $slug);
        if ($id) {
            $this->db->where('id !=', $id);
        }
        if ($this->db->count_all_results('halaman_promo')) {
            $this->session->set_flashdata('error', 'Alamat /promo/' . $slug . ' sudah dipakai.');
            return redirect('admin/banner/promo' . ($id ? '/' . $id : ''));
        }
        $d = array(
            'slug'        => $slug,
            'judul'       => $judul,
            'isi'         => html_aman($this->input->post('isi', FALSE)) ?: NULL,
            'kategori_id' => (int) $this->input->post('kategori_id') ?: NULL,
            'kata_kunci'  => mb_substr(trim((string) $this->input->post('kata_kunci', TRUE)), 0, 100) ?: NULL,
            'voucher'     => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $this->input->post('voucher'))) ?: NULL,
            'is_active'   => $this->input->post('is_active') ? 1 : 0,
        );
        $id ? $this->db->where('id', $id)->update('halaman_promo', $d) : $this->db->insert('halaman_promo', $d);
        $this->session->set_flashdata('sukses', 'Halaman promo tersimpan: ' . site_url('promo/' . $slug));
        return redirect('admin/banner');
    }

    /** @return string|NULL|FALSE */
    protected function unggah($lama)
    {
        $f = isset($_FILES['gambar']) ? $_FILES['gambar'] : NULL;
        if ( ! $f || $f['error'] === UPLOAD_ERR_NO_FILE) {
            return $lama;
        }
        $info = $f['error'] === UPLOAD_ERR_OK && is_uploaded_file($f['tmp_name']) ? @getimagesize($f['tmp_name']) : FALSE;
        $izin = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp');
        if ( ! $info || ! isset($izin[$info[2]]) || $f['size'] > 3 * 1024 * 1024) {
            $this->session->set_flashdata('error', 'Gambar banner harus JPG/PNG/WEBP, maksimal 3 MB.');
            return FALSE;
        }
        $dir = FCPATH . 'upload/banner/';
        if ( ! is_dir($dir)) {
            @mkdir($dir, 0755, TRUE);
        }
        $nama = bin2hex(random_bytes(16)) . '.' . $izin[$info[2]];
        if ( ! move_uploaded_file($f['tmp_name'], $dir . $nama)) {
            $this->session->set_flashdata('error', 'Folder upload/banner/ tidak bisa ditulisi.');
            return FALSE;
        }
        rapikan_gambar($dir . $nama, 1600);
        if ($lama && is_file($dir . basename($lama))) {
            @unlink($dir . basename($lama));
        }
        return $nama;
    }
}
