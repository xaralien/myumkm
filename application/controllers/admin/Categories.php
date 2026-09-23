<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * admin/Categories - kelola kategori produk.
 * Simpan di: application/controllers/admin/Categories.php
 *
 *   GET  admin/categories            daftar
 *   GET  admin/categories/form[/id]  tambah / ubah
 *   POST admin/categories/save
 *   POST admin/categories/toggle/{id}
 *   POST admin/categories/hapus/{id}
 */
class Categories extends Admin_Controller {

    /** Ikon yang dikenali v_home.php. Kunci disimpan di kolom icon. */
    protected $ikon = array(
        'makanan'    => 'Makanan & minuman',
        'kerajinan'  => 'Kerajinan',
        'fashion'    => 'Fashion',
        'kecantikan' => 'Kecantikan',
        'hadiah'     => 'Bunga & hadiah',
        'rumah'      => 'Rumah tangga',
        'umum'       => 'Umum (tas belanja)',
    );

    public function index()
    {
        $data['categories'] = $this->db
            ->select('c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS jml_produk', FALSE)
            ->from('categories c')
            ->order_by('c.sort_order', 'ASC')
            ->order_by('c.name', 'ASC')
            ->get()->result_array();

        $this->render('admin/v_categories', $data);
    }

    public function form($id = NULL)
    {
        $data = array('category' => NULL, 'ikon' => $this->ikon);

        if ($id) {
            $data['category'] = $this->db->where('id', (int) $id)->get('categories')->row_array();
            if ( ! $data['category']) {
                show_404();
            }
        }
        $this->render('admin/v_category_form', $data);
    }

    public function save()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $id = (int) $this->input->post('id');

        $this->form_validation->set_rules('name', 'Nama kategori', 'required|trim|min_length[2]|max_length[80]|callback_nama_unik');
        $this->form_validation->set_rules('keterangan', 'Keterangan', 'trim|max_length[120]');
        $this->form_validation->set_rules('sort_order', 'Urutan', 'trim|integer|greater_than_equal_to[0]|less_than_equal_to[999]');
        $this->form_validation->set_message('required', '{field} wajib diisi.');
        $this->form_validation->set_error_delimiters('<p class="field-error">', '</p>');

        if ($this->form_validation->run() === FALSE) {
            return $this->form($id ?: NULL);
        }

        $nama = trim(preg_replace('/\s+/u', ' ', $this->input->post('name', TRUE)));
        $ikon = $this->input->post('icon', TRUE);

        $simpan = array(
            'name'       => $nama,
            'icon'       => isset($this->ikon[$ikon]) ? $ikon : NULL,
            'keterangan' => trim((string) $this->input->post('keterangan', TRUE)) ?: NULL,
            'sort_order' => (int) $this->input->post('sort_order'),
            'is_active'  => $this->input->post('is_active') ? 1 : 0,
        );

        if ($id) {
            /* Slug SENGAJA tidak ikut berubah saat nama diubah. Slug dipakai
               di alamat katalog (shop?category=...) - mengubahnya memutus
               semua tautan kategori yang sudah terlanjur dibagikan. */
            $this->db->where('id', $id)->update('categories', $simpan);
            $pesan = 'Kategori diperbarui.';
        } else {
            $simpan['slug'] = $this->slug_unik($nama);
            $this->db->insert('categories', $simpan);
            $pesan = 'Kategori ditambahkan.';
        }

        $this->session->set_flashdata('sukses', $pesan);
        return redirect('admin/categories');
    }

    /** Aktif / nonaktif. Kategori nonaktif hilang dari katalog & beranda. */
    public function toggle($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $c = $this->db->where('id', (int) $id)->get('categories')->row_array();
        if ( ! $c) {
            show_404();
        }

        $baru = $c['is_active'] ? 0 : 1;
        $this->db->where('id', $c['id'])->update('categories', array('is_active' => $baru));

        $this->session->set_flashdata('sukses',
            'Kategori "' . $c['name'] . '" ' . ($baru ? 'diaktifkan.' : 'dinonaktifkan.'));
        return redirect('admin/categories');
    }

    /**
     * Hapus - hanya kalau belum dipakai produk.
     *
     * Foreign key di database memakai RESTRICT, jadi penghapusan yang
     * melanggar akan ditolak MySQL. Diperiksa di sini lebih dulu supaya
     * admin mendapat penjelasan, bukan halaman galat database.
     */
    public function hapus($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $c = $this->db->where('id', (int) $id)->get('categories')->row_array();
        if ( ! $c) {
            show_404();
        }

        $jml = $this->db->where('category_id', $c['id'])->count_all_results('products');

        if ($jml > 0) {
            $this->session->set_flashdata('error',
                'Kategori "' . $c['name'] . '" masih dipakai ' . $jml . ' produk, jadi tidak bisa dihapus. '
                . 'Nonaktifkan saja - kategorinya hilang dari katalog tanpa mengganggu produk itu.');
            return redirect('admin/categories');
        }

        $this->db->where('id', $c['id'])->delete('categories');
        $this->session->set_flashdata('sukses', 'Kategori "' . $c['name'] . '" dihapus.');
        return redirect('admin/categories');
    }

    /* ------------------------------------------------------------------ */

    /** WAJIB public - dipanggil form_validation dari luar kelas. */
    public function nama_unik($str)
    {
        $nama = trim(preg_replace('/\s+/u', ' ', (string) $str));
        $id   = (int) $this->input->post('id');

        $this->db->where('name', $nama);
        if ($id) {
            $this->db->where('id !=', $id);
        }

        if ($this->db->count_all_results('categories') > 0) {
            $this->form_validation->set_message('nama_unik',
                'Kategori "' . html_escape($nama) . '" sudah ada.');
            return FALSE;
        }
        return TRUE;
    }

    protected function slug_unik($nama)
    {
        $this->load->helper(array('url', 'text'));

        $dasar = url_title(convert_accented_characters($nama), '-', TRUE) ?: 'kategori';
        $slug  = $dasar;
        $n     = 2;

        while ($this->db->where('slug', $slug)->count_all_results('categories') > 0) {
            $slug = $dasar . '-' . $n++;
        }
        return $slug;
    }

    protected function render($view, $data = array())
    {
        $data['me'] = $this->me;
        $this->load->view('admin/v_admin_header', $data);
        $this->load->view($view, $data);
        $this->load->view('admin/v_admin_footer');
    }
}
