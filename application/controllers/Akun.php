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

    /**
     * Kotak masuk pesan pembeli.  GET akun/pesan[/{nomor}]
     *
     * Berisi percakapan untuk tiap pesanan yang pernah dibuat sambil masuk.
     */
    public function pesan($nomor = NULL)
    {
        $this->load->model('chat_model');

        $data = array(
            'daftar' => $this->chat_model->inbox_user($this->me['id']),
            'aktif'  => NULL,
            'pesan'  => array(),
        );

        if ($nomor) {
            /* Dicocokkan ke pesanan MILIK akun ini, bukan sekadar nomornya.
               Tanpa itu, mengetik nomor pesanan orang lain di alamat cukup
               untuk membaca percakapannya. */
            $o = $this->db->where('order_number', $nomor)
                          ->where('user_id', (int) $this->me['id'])
                          ->get('orders')->row_array();
            if ( ! $o) {
                show_404();
            }

            $this->chat_model->tandai_dibaca($o['id'], 'customer');

            $data['aktif']  = $o;
            $data['pesan']  = $this->chat_model->pesan($o['id']);
            $data['daftar'] = $this->chat_model->inbox_user($this->me['id']);
        }

        $data['pages'] = 'akun/v_pesan';
        $this->load->view('index', $data);
    }

    /* =====================================================================
       BUKU ALAMAT
       ===================================================================== */

    /** Daftar alamat tersimpan. GET akun/alamat */
    public function alamat()
    {
        $this->load->model('address_model');

        $data = array(
            'daftar' => $this->address_model->daftar($this->me['id']),
            'maks'   => Address_model::MAKS,
            'pages'  => 'v_alamat',
        );

        $this->load->view('index', $data);
    }

    /** Formulir tambah/ubah. GET akun/alamat_form[/{id}] */
    public function alamat_form($id = NULL)
    {
        $this->load->model(array('address_model', 'region_model'));

        $alamat = $id ? $this->address_model->milik($id, $this->me['id']) : NULL;

        if ($id && ! $alamat) {
            show_404();
        }

        $data = array(
            'alamat'    => $alamat,
            'provinsi'  => $this->region_model->provinces(),
            'pages'     => 'v_alamat_form',
        );

        $this->load->view('index', $data);
    }

    /** POST akun/alamat_simpan[/{id}] */
    public function alamat_simpan($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $this->load->model('address_model');
        $this->load->library('form_validation');

        $this->form_validation->set_rules('label', 'Nama alamat', 'trim|required|max_length[40]');
        $this->form_validation->set_rules('recipient_name', 'Nama penerima', 'trim|required|max_length[100]');
        $this->form_validation->set_rules('recipient_phone', 'Nomor HP', 'trim|required|max_length[25]');
        $this->form_validation->set_rules('address', 'Alamat', 'trim|required|max_length[500]');
        $this->form_validation->set_rules('district_id', 'Kecamatan', 'trim|required|integer');

        if ( ! $this->form_validation->run()) {
            return $this->alamat_form($id);
        }

        $hasil = $this->address_model->simpan($this->input->post(NULL, TRUE), $this->me['id'], $id);

        $this->session->set_flashdata($hasil['ok'] ? 'sukses' : 'error', $hasil['pesan']);

        /* Kembali ke checkout kalau pembeli datang dari sana - dia sedang di
           tengah membeli, dan melemparnya ke halaman daftar alamat berarti
           dia harus mencari jalan kembali sendiri. */
        $balik = $this->input->post('balik');

        return redirect($balik === 'checkout' ? 'checkout' : 'akun/alamat');
    }

    /** POST akun/alamat_utama/{id} */
    public function alamat_utama($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $this->load->model('address_model');
        $ok = $this->address_model->jadikan_utama($id, $this->me['id']);

        $this->session->set_flashdata($ok ? 'sukses' : 'error',
            $ok ? 'Alamat utama diperbarui.' : 'Alamat tidak ditemukan.');

        return redirect($this->input->post('balik') === 'checkout' ? 'checkout' : 'akun/alamat');
    }

    /** POST akun/alamat_hapus/{id} */
    public function alamat_hapus($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $this->load->model('address_model');
        $ok = $this->address_model->hapus($id, $this->me['id']);

        $this->session->set_flashdata($ok ? 'sukses' : 'error',
            $ok ? 'Alamat dihapus. Pesanan lama tidak terpengaruh.' : 'Alamat tidak ditemukan.');

        return redirect('akun/alamat');
    }

    /**
     * Angka untuk titik merah & hitungan di menu akun.  GET akun/notif [AJAX]
     *
     * Satu endpoint untuk kedua peran: pembeli (pesanan sendiri yang belum
     * selesai, pesan belum dibaca) dan penjual (pesanan masuk yang belum
     * dikirim, pesan dari pembeli). Dipisah dua permintaan hanya menambah
     * beban tanpa menambah apa pun - keduanya dibutuhkan bersamaan.
     */
    public function notif()
    {
        $this->load->model('chat_model');

        /* Pesanan pembeli yang masih berjalan: sudah dibayar tapi belum
           diterima. Yang belum dibayar tidak dihitung - itu belum tentu
           jadi, dan menandainya penting hanya membuat titik merahnya
           menyala terus. */
        $pesanan_saya = (int) $this->db
            ->where('user_id', (int) $this->me['id'])
            ->where('payment_status', 'paid')
            ->where_in('order_status', array('pending', 'confirmed', 'preparing', 'delivering'))
            ->count_all_results('orders');

        $pesan_saya = 0;
        foreach ($this->chat_model->inbox_user_conv($this->me['id'], 60) as $d) {
            $pesan_saya += (int) $d['belum'];
        }

        $data = array(
            'ok'            => TRUE,
            'pesanan_saya'  => $pesanan_saya,
            'pesan_saya'    => $pesan_saya,
            'toko_pesanan'  => 0,
            'toko_pesan'    => 0,
        );

        $toko = $this->auth_lib->store();

        if ($toko) {
            $data['toko_pesanan'] = (int) $this->db
                ->where('store_id', (int) $toko['id'])
                ->where('payment_status', 'paid')
                ->where_in('order_status', array('pending', 'confirmed', 'preparing'))
                ->count_all_results('orders');

            foreach ($this->chat_model->inbox_toko_conv($toko['id'], 60) as $d) {
                $data['toko_pesan'] += (int) $d['belum'];
            }
        }

        /* Titik merah di tab Akun hanya untuk yang BENAR-BENAR perlu
           ditindaklanjuti: pesan belum dibaca dan pesanan masuk yang
           menunggu dikirim. Pesanan pembeli sendiri sengaja tidak dihitung -
           menunggu kiriman bukan hal yang perlu dia kerjakan, dan titik
           merah yang menyala berhari-hari akan diabaikan. */
        $data['penting'] = $data['pesan_saya'] + $data['toko_pesanan'] + $data['toko_pesan'];

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    /**
     * Daftar percakapan untuk gelembung chat.  GET akun/inbox_json [AJAX]
     *
     * Berisi KEDUA sisi sekaligus untuk pengguna yang punya toko: percakapan
     * belanjanya sendiri DAN percakapan tokonya. Sebelumnya sisi ditentukan
     * dari alamat halaman, jadi penjual yang sedang berada di situs publik
     * tidak melihat percakapan tokonya sama sekali - padahal justru itu yang
     * perlu dibalas cepat.
     *
     * Tiap baris membawa penanda 'sisi' supaya tampilan bisa menyaringnya
     * dan tahu gelembung mana yang "milik saya" di dalam percakapan.
     */
    public function inbox_json()
    {
        $this->load->model('chat_model');

        $daftar = array();
        $total  = 0;

        // --- percakapan belanja ---
        foreach ($this->chat_model->inbox_user_conv($this->me['id'], 30) as $d) {
            $total += (int) $d['belum'];

            $daftar[] = array(
                'id'      => (int) $d['id'],
                'sisi'    => 'customer',
                'nomor'   => $d['order_number'] ?: 'Tanya sebelum beli',
                'lawan'   => $d['lawan'] ?: 'Toko',
                'status'  => $d['order_status'],
                'status_teks' => $d['order_id'] ? label_status($d['order_status']) : 'Belum memesan',
                'cuplik'  => $d['pesan_akhir'] ?: ($d['gambar_akhir'] ? 'Mengirim foto' : ''),
                'waktu'   => $d['waktu_akhir'] ?: $d['created_at'],
                'belum'   => (int) $d['belum'],
                'url_baru'  => site_url('chat/conv_baru/' . (int) $d['id']),
                'url_kirim' => site_url('chat/conv_kirim/' . (int) $d['id']),
                'url_buka'  => site_url('chat/conv/' . (int) $d['id']),
            );
        }

        // --- percakapan toko, kalau punya ---
        $toko = $this->auth_lib->store();

        if ($toko) {
            foreach ($this->chat_model->inbox_toko_conv($toko['id'], 30) as $d) {
                $total += (int) $d['belum'];

                $daftar[] = array(
                    'id'      => (int) $d['id'],
                    'sisi'    => 'seller',
                    'nomor'   => $d['order_number'] ?: 'Tanya sebelum beli',
                    'lawan'   => $d['lawan'] ?: 'Pembeli',
                    'status'  => $d['order_status'],
                    'status_teks' => $d['order_id'] ? label_status($d['order_status']) : 'Belum memesan',
                    'cuplik'  => $d['pesan_akhir'] ?: ($d['gambar_akhir'] ? 'Mengirim foto' : ''),
                    'waktu'   => $d['waktu_akhir'] ?: $d['created_at'],
                    'belum'   => (int) $d['belum'],
                    'url_baru'  => site_url('seller/conv_baru/' . (int) $d['id']),
                    'url_kirim' => site_url('seller/conv_kirim/' . (int) $d['id']),
                    'url_buka'  => site_url('seller/pesan/' . (int) $d['id']),
                );
            }

            /* Digabung lalu diurutkan ulang: yang ada pesan barunya di atas,
               sisanya menurut waktu. Tanpa ini, semua percakapan belanja
               akan menumpuk di atas semua percakapan toko hanya karena
               urutan pengambilannya. */
            usort($daftar, function ($a, $b) {
                if (($a['belum'] > 0) !== ($b['belum'] > 0)) {
                    return $a['belum'] > 0 ? -1 : 1;
                }
                return strcmp($b['waktu'], $a['waktu']);
            });
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'ok' => TRUE, 'total' => $total, 'daftar' => $daftar,
                'punya_toko' => (bool) $toko,
                'csrf_name' => $this->security->get_csrf_token_name(),
                'csrf_hash' => $this->security->get_csrf_hash(),
            )));
    }


    /* ------------------------------------------------------------------
       profil, simpan_profil, dan buka_toko DIKEMBALIKAN.

       Ketiganya hilang di commit faee68f, sementara view, tautan menu, dan
       callback validasinya (nama_toko_unik, valid_phone) tetap ada - jadi
       tombol "Buka toko" di beranda, navbar, footer, dan lembar akun HP
       semuanya berujung 404.
       ------------------------------------------------------------------ */

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

        /* Avatar cukup 400px - ditampilkan paling besar 60px, jadi ukuran di
           atas itu hanya memberatkan unduhan tanpa terlihat bedanya. */
        rapikan_gambar($folder . $nama, 400);

        if ($lama && is_file($folder . basename($lama))) {
            @unlink($folder . basename($lama));
        }
        return $nama;
    }
}
