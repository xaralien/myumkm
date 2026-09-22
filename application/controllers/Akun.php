<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Akun - halaman milik pengguna yang sudah masuk.
 * Simpan di: application/controllers/Akun.php
 *
 *   GET      akun              -> akun/pesanan
 *   GET      akun/pesanan      riwayat belanja
 *   GET/POST akun/profil       ubah profil
 *   GET/POST akun/buka_toko    buka toko (1 akun = 1 toko)
 */
class Akun extends Member_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('region_model', 'toko_model'));
    }

    public function index()
    {
        return redirect('akun/pesanan');
    }

    /* ------------------------------------------------------ riwayat belanja */

    public function pesanan()
    {
        $data = array(
            'akun'    => $this->akun,
            'pesanan' => $this->db
                ->select('o.*, s.name AS store_name', FALSE)
                ->from('orders o')
                ->join('stores s', 's.id = o.store_id', 'left')
                ->where('o.user_id', (int) $this->me['id'])
                ->order_by('o.created_at', 'DESC')
                ->limit(50)
                ->get()->result_array(),
        );
        $data['pages'] = 'akun/v_pesanan';
        $this->load->view('index', $data);
    }

    /* --------------------------------------------------------------- profil */

    public function profil()
    {
        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('name',        'Nama',           'required|trim|min_length[2]|max_length[100]');
            $this->form_validation->set_rules('phone',       'Nomor WhatsApp', 'required|trim|callback_valid_phone');
            $this->form_validation->set_rules('address',     'Alamat',         'trim|max_length[255]');
            $this->form_validation->set_rules('district_id', 'Kecamatan',      'trim|integer');

            if ($this->input->post('password', FALSE)) {
                $this->form_validation->set_rules('password_lama', 'Password sekarang', 'required');
                $this->form_validation->set_rules('password',      'Password baru',     'min_length[8]');
                $this->form_validation->set_rules('password2',     'Ulangi password',   'matches[password]');
            }

            $this->form_validation->set_message('required',   '{field} wajib diisi.');
            $this->form_validation->set_message('min_length', '{field} minimal {param} karakter.');
            $this->form_validation->set_message('matches',    'Password baru tidak sama.');
            $this->form_validation->set_error_delimiters('<p class="field-error">', '</p>');

            if ($this->form_validation->run()) {
                return $this->simpan_profil();
            }
        }

        $data = array(
            'akun'      => $this->akun,
            'provinces' => $this->region_model->provinces(),
        );
        $data['pages'] = 'akun/v_profil';
        $this->load->view('index', $data);
    }

    protected function simpan_profil()
    {
        $u = array(
            'name'    => trim($this->input->post('name', TRUE)),
            'phone'   => $this->normalize_phone($this->input->post('phone', TRUE)),
            'address' => trim((string) $this->input->post('address', TRUE)) ?: NULL,
        );

        // Wilayah opsional, tapi kalau diisi harus kecamatan yang benar-benar ada.
        $dis = (int) $this->input->post('district_id');
        if ($dis) {
            $w = $this->region_model->district_full($dis);
            if (! $w) {
                $this->session->set_flashdata('error', 'Kecamatan tidak valid.');
                return redirect('akun/profil');
            }
            $u['province_id'] = (int) $w['province_id'];
            $u['regency_id']  = (int) $w['regency_id'];
            $u['district_id'] = (int) $w['district_id'];
        }

        // Ganti password: password lama WAJIB benar. Tanpa ini, siapa pun
        // yang sempat memakai perangkat pemilik akun bisa mengambil alihnya.
        if ($this->input->post('password', FALSE)) {
            if (! password_verify((string) $this->input->post('password_lama', FALSE), $this->akun['password_hash'])) {
                $this->session->set_flashdata('error', 'Password sekarang salah.');
                return redirect('akun/profil');
            }
            $u['password_hash'] = password_hash((string) $this->input->post('password', FALSE), PASSWORD_DEFAULT);
        }

        $avatar = $this->unggah_avatar('avatar', $this->akun['avatar']);
        if ($avatar === FALSE) {
            return redirect('akun/profil');
        }
        $u['avatar'] = $avatar;

        $this->db->where('id', (int) $this->me['id'])->update('users', $u);

        // Nama di menu dibaca dari sesi - diperbarui supaya langsung berubah.
        $this->auth_lib->segarkan();

        $this->session->set_flashdata('sukses', 'Profil tersimpan.');
        return redirect('akun/profil');
    }

    /* ------------------------------------------------------------ buka toko */

    public function buka_toko()
    {
        // 1 akun 1 toko. Yang sudah punya diarahkan ke pengaturan tokonya.
        if ($this->auth_lib->store()) {
            return redirect('seller/profile');
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('store_name',  'Nama toko',   'required|trim|min_length[3]|max_length[120]|callback_nama_toko_unik');
            $this->form_validation->set_rules('phone',       'WhatsApp toko', 'required|trim|callback_valid_phone');
            $this->form_validation->set_rules('address',     'Alamat toko', 'required|trim|max_length[255]');
            $this->form_validation->set_rules('district_id', 'Kecamatan',   'required|integer');
            $this->form_validation->set_rules('description', 'Deskripsi',   'trim|max_length[1000]');
            $this->form_validation->set_rules('setuju',      'Persetujuan', 'required');

            $this->form_validation->set_message('required',   '{field} wajib diisi.');
            $this->form_validation->set_message('min_length', '{field} minimal {param} karakter.');
            $this->form_validation->set_error_delimiters('<p class="field-error">', '</p>');

            if ($this->form_validation->run()) {
                $w = $this->region_model->district_full((int) $this->input->post('district_id'));

                if (! $w) {
                    $this->session->set_flashdata('error', 'Kecamatan tidak valid.');
                    return redirect('akun/buka_toko');
                }

                $id = $this->toko_model->buat($this->me['id'], array(
                    'name'        => $this->input->post('store_name', TRUE),
                    'phone'       => $this->normalize_phone($this->input->post('phone', TRUE)),
                    'address'     => trim($this->input->post('address', TRUE)),
                    'description' => trim((string) $this->input->post('description', TRUE)) ?: NULL,
                    'province_id' => (int) $w['province_id'],
                    'regency_id'  => (int) $w['regency_id'],
                    'district_id' => (int) $w['district_id'],
                ));

                if (! $id) {
                    /* Lolos pengecekan tapi ditolak database: nama baru saja
                       diambil orang lain, atau tombol ditekan dua kali. */
                    $this->session->set_flashdata(
                        'error',
                        'Nama toko itu baru saja dipakai. Coba nama lain.'
                    );
                    return redirect('akun/buka_toko');
                }

                $this->session->set_flashdata(
                    'sukses',
                    'Toko dibuat dan menunggu persetujuan admin. Sambil menunggu, '
                        . 'kamu sudah bisa menambahkan produk - produknya tampil di '
                        . 'katalog begitu toko disetujui.'
                );
                return redirect('seller');
            }
        }

        $data = array(
            'akun'      => $this->akun,
            'provinces' => $this->region_model->provinces(),
        );
        $data['pages'] = 'akun/v_buka_toko';
        $this->load->view('index', $data);
    }

    /* ------------------------------------------------------ callback validasi */

    /** WAJIB public - dipanggil form_validation dari luar kelas. */
    public function nama_toko_unik($str)
    {
        if ($this->toko_model->nama_dipakai($str)) {
            $this->form_validation->set_message(
                'nama_toko_unik',
                'Nama toko "' . html_escape($this->toko_model->rapikan($str))
                    . '" sudah dipakai. Coba tambahkan nama kota atau ciri khasmu.'
            );
            return FALSE;
        }
        return TRUE;
    }

    public function valid_phone($str)
    {
        if (preg_match('/^628[0-9]{7,11}$/', $this->normalize_phone($str))) {
            return TRUE;
        }
        $this->form_validation->set_message('valid_phone', '{field} tidak valid. Contoh: 081234567890');
        return FALSE;
    }

    /* ------------------------------------------------------------ pembantu */

    protected function normalize_phone($phone)
    {
        $p = preg_replace('/[^0-9]/', '', (string) $phone);
        if (substr($p, 0, 1) === '0') {
            return '62' . substr($p, 1);
        }
        if (substr($p, 0, 2) === '62') {
            return $p;
        }
        return '62' . $p;
    }

    /**
     * @return string|NULL|FALSE nama berkas, avatar lama bila tak diganti, FALSE bila gagal
     */
    protected function unggah_avatar($field, $lama)
    {
        $f = isset($_FILES[$field]) ? $_FILES[$field] : NULL;

        if (! $f || $f['error'] === UPLOAD_ERR_NO_FILE || $f['name'] === '') {
            return $lama;
        }
        if ($f['error'] !== UPLOAD_ERR_OK || ! is_uploaded_file($f['tmp_name'])) {
            $this->session->set_flashdata('error', 'Gagal mengunggah foto.');
            return FALSE;
        }
        if ($f['size'] > 1024 * 1024) {
            $this->session->set_flashdata('error', 'Foto profil maksimal 1 MB.');
            return FALSE;
        }

        // getimagesize() membaca ISI berkas - ekstensi dan MIME bisa dipalsukan.
        $info = @getimagesize($f['tmp_name']);
        $izin = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png');
        if (defined('IMAGETYPE_WEBP')) {
            $izin[IMAGETYPE_WEBP] = 'webp';
        }

        if ($info === FALSE || ! isset($izin[$info[2]])) {
            $this->session->set_flashdata('error', 'Foto harus JPG, PNG, atau WEBP.');
            return FALSE;
        }

        $folder = FCPATH . 'upload' . DIRECTORY_SEPARATOR . 'avatar' . DIRECTORY_SEPARATOR;
        if (! is_dir($folder)) {
            @mkdir($folder, 0755, TRUE);
        }

        $nama = bin2hex(random_bytes(16)) . '.' . $izin[$info[2]];
        if (! move_uploaded_file($f['tmp_name'], $folder . $nama)) {
            $this->session->set_flashdata('error', 'Gagal menyimpan foto.');
            return FALSE;
        }

        if ($lama && is_file($folder . basename($lama))) {
            @unlink($folder . basename($lama));
        }
        return $nama;
    }
}
