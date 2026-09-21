<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Seller - penjual mengelola produk tokonya sendiri.
 * Simpan di: application/controllers/Seller.php
 *
 *   GET  seller                    daftar produk
 *   GET  seller/form/{id}          form produk
 *   POST seller/save
 *   POST seller/toggle/{id}
 *   GET  seller/orders             pesanan masuk
 *
 * ATURAN PENTING: setiap query DIBATASI store_id milik penjual yang login.
 * Tanpa itu, mengganti angka di URL cukup untuk mengubah produk toko lain.
 */
class Seller extends Seller_Controller
{
    public function __construct()
    {
        parent::__construct();
        // Seller_Controller tidak memuat model ini; dibutuhkan untuk daftar
        // kategori dan verifikasi category_id saat menyimpan.
        $this->load->model('product_model');
        $this->load->model('chat_model');
        $this->load->model('order_model');
    }

    const PER_HAL = 15;
    const UNDO_DETIK = 900;  // 15 menit
    const SAPU_JEDA = 300;  // 5 menit

    /**
     * Daftar produk: cari + urutkan + paginasi, semuanya di SERVER.
     *
     * Bukan DataTables. Alasannya bukan soal selera:
     *   - Desain tabelnya memakai CSS Grid, sedangkan DataTables menuntut
     *     elemen <table> sungguhan.
     *   - Sortir sisi-klien mengurutkan "Rp 250.000" sebagai TEKS, jadi
     *     Rp 90.000 dianggap lebih besar dari Rp 250.000.
     *   - Pencarian & paginasi server-side sudah dipakai di katalog, jadi
     *     ini sekalian konsisten dan tetap ringan saat produk bertambah.
     *
     * Sortir dan halaman lewat tautan biasa - tanpa JavaScript sama sekali,
     * dan hasilnya bisa di-bookmark maupun dibagikan.
     */
    public function index()
    {
        $cari = trim((string) $this->input->get('q', TRUE));
        $urut = (string) $this->input->get('sort', TRUE);
        $arah = strtoupper((string) $this->input->get('dir', TRUE)) === 'ASC' ? 'ASC' : 'DESC';

        /* Daftar putih kolom. Input pengguna TIDAK PERNAH masuk langsung ke
           ORDER BY - query builder CodeIgniter tidak melindungi bagian itu,
           dan di situlah celah SQL injection yang paling sering terlewat. */
        $kolom = array(
            'baru' => 'products.id',
            'nama' => 'products.name',
            'kategori' => 'categories.name',
            'harga' => 'products.price',
            'status' => 'products.is_active',
        );
        if (!isset($kolom[$urut])) {
            $urut = 'baru';
        }

        // Syarat dipakai DUA kali (hitung total & ambil isi halaman).
        // Ditulis sebagai closure supaya tidak mungkin berbeda - kalau
        // berbeda, paginasi salah dan halaman terakhir bisa kosong.
        $syarat = function () use ($cari) {
            $this
                ->db
                ->from('products')
                ->join('categories', 'categories.id = products.category_id', 'left')
                ->where('products.store_id', (int) $this->store['id']);

            if ($cari !== '') {
                /* group_start() penting: tanpa ini OR bocor keluar dan
                   mengabaikan syarat store_id - penjual jadi bisa melihat
                   produk toko lain yang namanya mirip. */
                $this
                    ->db
                    ->group_start()
                    ->like('products.name', $cari)
                    ->or_like('products.description', $cari)
                    ->or_like('categories.name', $cari)
                    ->or_like('products.price', $cari)
                    ->group_end();
            }
        };

        $syarat();
        $total = (int) $this->db->count_all_results();
        $hal = max(1, (int) ceil($total / self::PER_HAL));

        $page = (int) $this->input->get('page');
        if ($page < 1) {
            $page = 1;
        }
        if ($page > $hal) {
            $page = $hal;
        }  // ?page=999 -> halaman terakhir
        $offset = ($page - 1) * self::PER_HAL;

        $syarat();
        $products = $this
            ->db
            ->select('products.*, categories.name AS category_name')
            ->order_by($kolom[$urut], $arah)
            ->order_by('products.id', 'DESC')  // pemutus seri, urutan stabil
            ->limit(self::PER_HAL, $offset)
            ->get()
            ->result_array();

        $data = array(
            'products' => $products,
            'cari' => $cari,
            'urut' => $urut,
            'arah' => $arah,
            'total' => $total,
            'page' => $page,
            'halaman' => $hal,
            'dari' => $total ? $offset + 1 : 0,
            'sampai' => min($offset + self::PER_HAL, $total),
        );
        $this->render('seller/v_products', $data);
    }

    public function form($id = NULL)
    {
        $data = array(
            'product' => NULL,
            'variants' => array(),
            'addons' => array(),
            'categories' => $this->product_model->categories_simple(),
            'ai_aktif' => $this->ai_aktif(),
            'ai_kuota' => $this->ai_aktif() ? $this->ai_lib->kuota() : NULL,
        );

        if ($id) {
            $data['product'] = $this->milik($id);
            $data['variants'] = $this
                ->db
                ->where('product_id', (int) $id)
                ->order_by('sort_order', 'ASC')
                ->get('product_variants')
                ->result_array();
            $data['addons'] = $this
                ->db
                ->where('product_id', (int) $id)
                ->order_by('sort_order', 'ASC')
                ->get('product_addons')
                ->result_array();
        }
        $this->render('seller/v_product_form', $data);
    }

    protected function ai_aktif()
    {
        $this->load->library('ai_lib');
        return $this->ai_lib->aktif();
    }

    public function save()
    {
        $id = (int) $this->input->post('id');

        /* Kolom harga dikirim sebagai teks berformat ("285.000") karena
           <input type="number"> tidak bisa menampilkan titik ribuan.
           Dibersihkan di sini, SEBELUM form_validation berjalan - kalau
           tidak, aturan 'integer' akan menolak "285.000".

           Dibersihkan di server, bukan hanya di JavaScript, supaya tetap
           benar walau JS gagal dimuat. */
        $this->bersihkan_angka_post();

        $this->form_validation->set_rules('name', 'Nama produk', 'required|trim|max_length[150]');
        $this->form_validation->set_rules('price', 'Harga', 'required|integer|greater_than[0]');
        $this->form_validation->set_rules('category_id', 'Kategori', 'required|integer');
        $this->form_validation->set_rules('description', 'Deskripsi', 'trim|max_length[2000]');
        $this->form_validation->set_message('greater_than', '{field} harus lebih dari 0.');
        $this->form_validation->set_error_delimiters('<p class="field-error">', '</p>');

        if ($this->form_validation->run() === FALSE) {
            return $this->form($id ?: NULL);
        }

        // id kategori dari form bisa disunting - verifikasi ke database.
        if (!$this->product_model->category_exists($this->input->post('category_id'))) {
            $this->session->set_flashdata('error', 'Kategori tidak valid.');
            return $this->form($id ?: NULL);
        }

        $lama = $id ? $this->milik($id) : NULL;

        $gambar = $this->unggah_gambar($lama ? $lama['image'] : NULL);

        if ($gambar === FALSE) {
            return $this->form($id ?: NULL);  // pesan sudah di-flashdata
        }

        $data = array(
            'store_id' => (int) $this->store['id'],
            'name' => $this->input->post('name', TRUE),
            'slug' => $this->slug_unik($this->input->post('name', TRUE), $id),
            'category_id' => (int) $this->input->post('category_id'),
            'description' => $this->input->post('description', TRUE) ?: NULL,
            'price' => (int) $this->input->post('price'),
            'image' => $gambar,
        );

        if ($id) {
            $this
                ->db
                ->where('id', $id)
                ->where('store_id', (int) $this->store['id'])  // sabuk pengaman kedua
                ->update('products', $data);
        } else {
            $this->db->insert('products', $data);
            $id = (int) $this->db->insert_id();
        }

        $this->simpan_varian($id);
        $this->simpan_addons($id);

        $this->session->set_flashdata('sukses', 'Produk tersimpan.');
        return redirect('seller');
    }

    /**
     * Buat deskripsi produk dengan AI.  POST seller/deskripsi
     *
     * Endpoint di server, bukan panggilan langsung dari JavaScript, supaya
     * kunci API tidak pernah sampai ke browser.
     */
    public function deskripsi()
    {
        $this->load->library('ai_lib');
        $this->load->model('product_model');

        $nama = trim((string) $this->input->post('name', TRUE));

        // Nama kategori diambil dari database berdasarkan id, bukan dari
        // teks kiriman browser - supaya isi prompt tidak bisa disetir.
        $kategori = '';
        $kat_id = (int) $this->input->post('category_id');
        if ($kat_id) {
            $row = $this
                ->db
                ->select('name')
                ->where('id', $kat_id)
                ->get('categories')
                ->row_array();
            $kategori = $row ? $row['name'] : '';
        }

        /* Sumber foto ada dua, dan urutannya penting:

           1. Berkas yang BARU dipilih di formulir - belum tersimpan di
              server, jadi harus dikirim browser bersama permintaan ini.
              Ini kasus paling umum: penjual menambah produk baru.
           2. Foto produk yang sudah tersimpan, kalau sedang mengubah
              produk lama dan tidak mengganti fotonya. */
        $gambar = NULL;
        $sementara = NULL;

        if (
            isset($_FILES['gambar']) &&
            $_FILES['gambar']['error'] === UPLOAD_ERR_OK &&
            is_uploaded_file($_FILES['gambar']['tmp_name'])
        ) {
            // Dibaca langsung dari berkas sementara PHP. Tidak disimpan
            // ke folder produk - ini cuma bahan lihat, bukan unggahan.
            if (
                @getimagesize($_FILES['gambar']['tmp_name']) !== FALSE &&
                $_FILES['gambar']['size'] <= 8 * 1024 * 1024
            ) {
                $gambar = $_FILES['gambar']['tmp_name'];
                $sementara = TRUE;
            }
        }

        if ($gambar === NULL) {
            $id = (int) $this->input->post('id');
            if ($id) {
                $p = $this
                    ->db
                    ->select('image')
                    ->where('id', $id)
                    ->where('store_id', (int) $this->store['id'])
                    ->get('products')
                    ->row_array();
                if ($p && $p['image']) {
                    $path = FCPATH . 'upload' . DIRECTORY_SEPARATOR . 'produk'
                        . DIRECTORY_SEPARATOR . basename($p['image']);
                    if (is_file($path)) {
                        $gambar = $path;
                    }
                }
            }
        }

        $hasil = $this->ai_lib->deskripsi_produk(
            $nama,
            $kategori,
            $this->store['name'],
            $gambar
        );

        return $this
            ->output
            ->set_status_header($hasil['ok'] ? 200 : 422)
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'ok' => $hasil['ok'],
                'teks' => $hasil['teks'],
                'pesan' => $hasil['pesan'],
                'kuota' => isset($hasil['kuota']) ? $hasil['kuota'] : $this->ai_lib->kuota(),
                'pakai_gambar' => !empty($hasil['pakai_gambar']),
                'csrf_name' => $this->security->get_csrf_token_name(),
                'csrf_hash' => $this->security->get_csrf_hash(),
            )));
    }

    /**
     * Halaman diagnosa: daftar model yang benar-benar bisa dipakai kunci
     * API ini. Groq merotasi model beberapa kali setahun, jadi halaman ini
     * yang menjawab "nama model apa yang harus ditulis di config sekarang".
     *
     * GET seller/ai_models
     */
    public function ai_models()
    {
        $this->load->library('ai_lib');

        if (!$this->ai_lib->aktif()) {
            show_error('Fitur AI belum diaktifkan. Isi kunci API di config/ai.php.', 503);
        }

        if ($this->input->get('reset')) {
            $this->ai_lib->lupakan_model();
            $this->session->set_flashdata('sukses', 'Ingatan model dikosongkan.');
            return redirect('seller/ai_models');
        }

        $hasil = $this->ai_lib->daftar_model();

        $this->config->load('ai', TRUE);
        $cfg = $this->config->item('ai', 'ai');

        $data = array(
            'models' => isset($hasil['models']) ? $hasil['models'] : array(),
            'galat' => isset($hasil['error']) ? $hasil['error'] : NULL,
            'dipakai_teks' => (array) $cfg['model'],
            'dipakai_vision' => (array) $cfg['model_vision'],
        );
        $this->render('seller/v_ai_models', $data);
    }

    public function toggle($id)
    {
        $p = $this->milik($id);
        $this
            ->db
            ->where('id', $p['id'])
            ->where('store_id', (int) $this->store['id'])
            ->update('products', array('is_active' => $p['is_active'] ? 0 : 1));

        $this->session->set_flashdata(
            'sukses',
            $p['is_active'] ? 'Produk disembunyikan.' : 'Produk ditayangkan.'
        );
        return redirect('seller');
    }

    /* ====================================================== PROFIL TOKO ===
     |  GET  seller/profile
     |  POST seller/update_profile
     |
     |  Menggabungkan data dari DUA tabel: nama & password ada di `users`,
     |  sisanya di `stores`. Keduanya disimpan dalam satu transaksi supaya
     |  tidak pernah ada keadaan setengah tersimpan.
     | ==================================================================== */

    public function profile()
    {
        $this->load->model('region_model');

        $data = array(
            'user' => $this
                ->db
                ->where('id', (int) $this->me['id'])
                ->get('users')
                ->row_array(),
            'store' => $this
                ->db
                ->where('id', (int) $this->store['id'])
                ->get('stores')
                ->row_array(),
            'provinces' => $this->region_model->provinces(),
        );
        $this->render('seller/v_profile', $data);
    }

    public function update_profile()
    {
        $this->load->model('region_model');

        // Kolom nominal dikirim berformat "25.000" - dibersihkan SEBELUM
        // validasi, kalau tidak aturan 'integer' menolaknya.
        $this->bersihkan_angka_profil();

        $this->form_validation->set_rules('name', 'Nama pemilik', 'required|trim|max_length[100]');
        $this->form_validation->set_rules('store_name', 'Nama toko', 'required|trim|max_length[120]');
        $this->form_validation->set_rules('phone', 'No. Telp', 'required|trim|callback_valid_phone');
        $this->form_validation->set_rules('description', 'Deskripsi', 'trim|max_length[1000]');
        $this->form_validation->set_rules('address', 'Alamat', 'required|trim|max_length[255]');
        $this->form_validation->set_rules('district_id', 'Kecamatan', 'required|integer');

        $this->form_validation->set_rules('open', 'Jam buka', 'required|trim|callback_valid_jam');
        $this->form_validation->set_rules('close', 'Jam tutup', 'required|trim|callback_valid_jam');

        $this->form_validation->set_rules('ongkir_kecamatan', 'Ongkir dalam kecamatan', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('ongkir_kota', 'Ongkir dalam kota', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('ongkir_provinsi', 'Ongkir luar kota', 'trim|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('gratis_ongkir_min', 'Gratis ongkir mulai', 'required|integer|greater_than_equal_to[0]');
        // $this->form_validation->set_rules('cod_max',          'Batas COD',              'required|integer|greater_than_equal_to[0]');

        $this->form_validation->set_rules('jeda_persiapan_menit', 'Jeda persiapan', 'required|integer|greater_than_equal_to[0]|less_than_equal_to[1440]');
        $this->form_validation->set_rules('maks_hari_kedepan', 'Maks hari ke depan', 'required|integer|greater_than_equal_to[1]|less_than_equal_to[365]');

        $this->form_validation->set_rules('latitude', 'Titik lokasi', 'trim|callback_valid_koordinat[lat]');
        $this->form_validation->set_rules('longitude', 'Titik lokasi', 'trim|callback_valid_koordinat[lng]');
        $this->form_validation->set_rules('radius_km', 'Jangkauan antar', 'trim|integer|greater_than_equal_to[1]|less_than_equal_to[200]');

        // Password hanya divalidasi kalau memang diisi.
        if ($this->input->post('password', FALSE)) {
            $this->form_validation->set_rules('password_lama', 'Password sekarang', 'required');
            $this->form_validation->set_rules('password', 'Password baru', 'min_length[8]');
            $this->form_validation->set_rules('password2', 'Ulangi password', 'matches[password]');
        }

        $this->form_validation->set_message('greater_than_equal_to', '{field} tidak boleh kurang dari {param}.');
        $this->form_validation->set_message('less_than_equal_to', '{field} tidak boleh lebih dari {param}.');
        $this->form_validation->set_message('matches', 'Ulangi password tidak sama.');
        $this->form_validation->set_message('min_length', '{field} minimal {param} karakter.');
        $this->form_validation->set_error_delimiters('<p class="field-error">', '</p>');

        if ($this->form_validation->run() === FALSE) {
            return $this->profile();
        }

        // Kecamatan diverifikasi ulang - id dari form bisa disunting.
        $wilayah = $this->region_model->district_full($this->input->post('district_id'));
        if (!$wilayah) {
            $this->session->set_flashdata('error', 'Kecamatan tidak valid.');
            return $this->profile();
        }

        // Jam tutup harus setelah jam buka. Aman dibandingkan sebagai teks
        // karena rapikan_jam() menormalkan ke "HH:MM" dengan nol di depan.
        $buka = $this->rapikan_jam($this->input->post('open', TRUE));
        $tutup = $this->rapikan_jam($this->input->post('close', TRUE));

        if ($buka >= $tutup) {
            $this->session->set_flashdata('error', 'Jam tutup harus lebih malam dari jam buka.');
            return $this->profile();
        }

        $lama = $this->db->where('id', (int) $this->me['id'])->get('users')->row_array();

        // Ganti password: password lama WAJIB benar. Tanpa ini, siapa pun
        // yang sempat memakai komputer penjual bisa mengambil alih akunnya.
        $hash_baru = NULL;
        if ($this->input->post('password', FALSE)) {
            if (!password_verify($this->input->post('password_lama', FALSE), $lama['password_hash'])) {
                $this->session->set_flashdata('error', 'Password sekarang salah.');
                return $this->profile();
            }
            $hash_baru = password_hash($this->input->post('password', FALSE), PASSWORD_DEFAULT);
        }

        $store = $this->db->where('id', (int) $this->store['id'])->get('stores')->row_array();
        $avatar = $this->unggah_avatar($store['avatar']);

        if ($avatar === FALSE) {
            return $this->profile();  // pesan sudah di-flashdata
        }

        /* Kosong berarti toko TIDAK melayani luar kotanya - disimpan NULL,
           bukan 0. Nol artinya "gratis", dan itu beda jauh. */
        $luar = $this->input->post('ongkir_provinsi');
        $luar = ($luar === '' || $luar === NULL) ? NULL : (int) $luar;

        $this->db->trans_begin();

        $u = array('name' => $this->input->post('name', TRUE));
        if ($hash_baru) {
            $u['password_hash'] = $hash_baru;
        }
        $this->db->where('id', (int) $this->me['id'])->update('users', $u);

        $this->db->where('id', (int) $this->store['id'])->update('stores', array(
            'name' => $this->input->post('store_name', TRUE),
            'phone' => $this->normalize_phone($this->input->post('phone', TRUE)),
            'description' => $this->input->post('description', TRUE) ?: NULL,
            'address' => $this->input->post('address', TRUE),
            'avatar' => $avatar,
            'province_id' => (int) $wilayah['province_id'],
            'regency_id' => (int) $wilayah['regency_id'],
            'district_id' => (int) $wilayah['district_id'],
            'open' => $buka . ':00',
            'close' => $tutup . ':00',
            'ongkir_kecamatan' => (int) $this->input->post('ongkir_kecamatan'),
            'ongkir_kota' => (int) $this->input->post('ongkir_kota'),
            'ongkir_provinsi' => $luar,
            'gratis_ongkir_min' => (int) $this->input->post('gratis_ongkir_min'),
            // 'cod_max'              => (int) $this->input->post('cod_max'),
            'jeda_persiapan_menit' => (int) $this->input->post('jeda_persiapan_menit'),
            'maks_hari_kedepan' => (int) $this->input->post('maks_hari_kedepan'),
            'latitude' => $this->input->post('latitude') ?: NULL,
            'longitude' => $this->input->post('longitude') ?: NULL,
            'radius_km' => $this->input->post('radius_km') ?: NULL,
        ));

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Gagal menyimpan profil.');
            return $this->profile();
        }
        $this->db->trans_commit();

        // Nama di bilah atas dibaca dari session - ikut diperbarui supaya
        // tidak menampilkan nama lama sampai penjual login ulang.
        $sess = $this->session->userdata('auth_user');
        $sess['name'] = $this->input->post('name', TRUE);
        $this->session->set_userdata('auth_user', $sess);

        $this->session->set_flashdata('sukses', 'Profil tersimpan.');
        return redirect('seller/profile');
    }

    /**
     * Aturan nomor HP. Dipakai lewat 'callback_valid_phone'.
     * WAJIB public - CI memanggilnya dari luar kelas validasi, dan kalau
     * method-nya tidak ada CI menjawab dengan pesan yang membingungkan:
     * "Unable to access an error message corresponding to your field name".
     */
    public function valid_phone($str)
    {
        $p = $this->normalize_phone($str);

        // Setelah dinormalkan semua jadi 628xxxxxxxxx.
        if (preg_match('/^628[0-9]{7,11}$/', $p)) {
            return TRUE;
        }

        $this->form_validation->set_message(
            'valid_phone',
            '{field} tidak valid. Contoh: 081234567890'
        );
        return FALSE;
    }

    public function valid_koordinat($str, $jenis)
    {
        $str = trim((string) $str);

        // Boleh kosong - toko yang belum sempat menaruh titik tetap bisa
        // menyimpan profilnya, hanya tidak muncul di pencarian radius.
        if ($str === '') {
            return TRUE;
        }

        if (!is_numeric($str)) {
            $this->form_validation->set_message('valid_koordinat', '{field} tidak valid.');
            return FALSE;
        }

        $n = (float) $str;
        $batas = ($jenis === 'lat') ? 90 : 180;

        if ($n < -$batas || $n > $batas) {
            $this->form_validation->set_message(
                'valid_koordinat',
                '{field} di luar jangkauan yang wajar.'
            );
            return FALSE;
        }

        /* Indonesia kira-kira di lintang -11..6 dan bujur 95..141.
           Titik di luar itu hampir pasti salah - biasanya karena lat dan
           lng tertukar, kesalahan yang sangat mudah terjadi karena
           Leaflet memakai urutan (lat, lng) sedangkan GeoJSON (lng, lat). */
        $wajar = ($jenis === 'lat')
            ? ($n >= -11 && $n <= 6)
            : ($n >= 95 && $n <= 141);

        if (!$wajar) {
            $this->form_validation->set_message(
                'valid_koordinat',
                'Titik lokasi berada di luar Indonesia. Pastikan lintang dan '
                    . 'bujur tidak tertukar.'
            );
            return FALSE;
        }

        return TRUE;
    }

    /**
     * 08xx / +62xx / 62xx -> semuanya jadi 62xx, siap dipakai WhatsApp.
     */
    protected function normalize_phone($phone)
    {
        $p = preg_replace('/[^0-9]/', '', $phone);

        if (substr($p, 0, 1) === '0') {
            return '62' . substr($p, 1);
        }
        if (substr($p, 0, 2) === '62') {
            return $p;
        }
        return '62' . $p;
    }

    /**
     * Aturan jam "HH:MM". Dipakai lewat callback_valid_jam.
     */
    public function valid_jam($str)
    {
        if ($this->rapikan_jam($str) !== NULL) {
            return TRUE;
        }
        $this->form_validation->set_message('valid_jam', '{field} tidak valid. Contoh: 08:00');
        return FALSE;
    }

    /**
     * Browser mengirim "HH:MM", sebagian "HH:MM:SS" kalau step < 60.
     * Kolom TIME MySQL juga mengembalikan "HH:MM:SS".
     * @return string|NULL "HH:MM"
     */
    protected function rapikan_jam($str)
    {
        if (!preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9])(?::[0-5][0-9])?$/', trim((string) $str), $m)) {
            return NULL;
        }
        return $m[1] . ':' . $m[2];
    }

    protected function bersihkan_angka_profil()
    {
        $kolom = array(
            'ongkir_kecamatan',
            'ongkir_kota',
            'ongkir_provinsi',
            'gratis_ongkir_min',
            'cod_max'
        );

        foreach ($kolom as $k) {
            if (isset($_POST[$k])) {
                $bersih = preg_replace('/[^0-9]/', '', (string) $_POST[$k]);
                $_POST[$k] = $bersih;  // '' tetap '' - dibedakan dari 0
            }
        }
    }

    /**
     * @param  string|NULL $lama nama berkas avatar sekarang
     * @return string|NULL|FALSE nama berkas, avatar lama, atau FALSE bila gagal
     */
    protected function unggah_avatar($lama)
    {
        $f = isset($_FILES['avatar']) ? $_FILES['avatar'] : NULL;

        if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE || $f['name'] === '') {
            return $lama;  // tidak diganti
        }
        if ($f['error'] !== UPLOAD_ERR_OK) {
            $this->session->set_flashdata('error', $this->pesan_galat_upload($f['error']));
            return FALSE;
        }
        if (!is_uploaded_file($f['tmp_name'])) {
            $this->session->set_flashdata('error', 'Berkas avatar tidak sah.');
            return FALSE;
        }
        if ($f['size'] > 1024 * 1024) {
            $this->session->set_flashdata('error', 'Avatar maksimal 1 MB.');
            return FALSE;
        }

        // getimagesize() membaca ISI berkas - ekstensi dan header MIME
        // sama-sama bisa dipalsukan.
        $info = @getimagesize($f['tmp_name']);
        $izin = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png');
        if (defined('IMAGETYPE_WEBP')) {
            $izin[IMAGETYPE_WEBP] = 'webp';
        }

        if ($info === FALSE || !isset($izin[$info[2]])) {
            $this->session->set_flashdata('error', 'Avatar harus JPG, PNG, atau WEBP.');
            return FALSE;
        }

        $folder = FCPATH . 'upload' . DIRECTORY_SEPARATOR . 'avatar' . DIRECTORY_SEPARATOR;
        if (!is_dir($folder)) {
            @mkdir($folder, 0755, TRUE);
        }
        $nyata = realpath($folder);
        if ($nyata === FALSE || !is_writable($nyata)) {
            $this->session->set_flashdata(
                'error',
                'Folder upload/avatar/ tidak bisa ditulisi. Buat foldernya dulu.'
            );
            return FALSE;
        }

        // Nama diacak: nama dari pengguna bisa berisi "../" atau berakhiran
        // ganda seperti "foto.php.jpg".
        $nama_baru = bin2hex(random_bytes(16)) . '.' . $izin[$info[2]];

        if (!move_uploaded_file($f['tmp_name'], $nyata . DIRECTORY_SEPARATOR . $nama_baru)) {
            $this->session->set_flashdata('error', 'Gagal menyimpan avatar.');
            return FALSE;
        }
        @chmod($nyata . DIRECTORY_SEPARATOR . $nama_baru, 0644);

        if ($lama) {
            $path_lama = $nyata . DIRECTORY_SEPARATOR . basename($lama);
            if (is_file($path_lama)) {
                @unlink($path_lama);
            }
        }
        return $nama_baru;
    }

    /* ------------------------------------------------------------------ */

    /**
     * Proses unggah gambar.
     *
     * @param  string|NULL $gambar_lama nama file yang sedang dipakai
     * @return string|FALSE nama file baru, nama file lama, atau FALSE bila gagal
     */
    protected function unggah_gambar($gambar_lama)
    {
        $f = isset($_FILES['image']) ? $_FILES['image'] : NULL;

        $ada_file = $f && $f['error'] !== UPLOAD_ERR_NO_FILE && $f['name'] !== '';

        if (!$ada_file) {
            if ($gambar_lama) {
                return $gambar_lama;  // mengubah produk tanpa ganti gambar
            }
            $this->session->set_flashdata('upload_error', 'Gambar produk wajib diunggah.');
            return FALSE;
        }

        // ---- 1. Galat dari PHP sendiri ---------------------------------
        if ($f['error'] !== UPLOAD_ERR_OK) {
            $this->session->set_flashdata('upload_error', $this->pesan_galat_upload($f['error']));
            return FALSE;
        }

        /* is_uploaded_file memastikan berkasnya benar-benar datang dari
           unggahan HTTP, bukan path lain di server yang disisipkan lewat
           $_FILES palsu. */
        if (!is_uploaded_file($f['tmp_name'])) {
            $this->session->set_flashdata('upload_error', 'Berkas tidak sah.');
            return FALSE;
        }

        if ($f['size'] > 2 * 1024 * 1024) {
            $this->session->set_flashdata(
                'upload_error',
                'Ukuran ' . round($f['size'] / 1048576, 1) . ' MB, maksimal 2 MB.'
            );
            return FALSE;
        }

        /* ---- 2. Tipe berkas -------------------------------------------
         | getimagesize() MEMBACA ISI berkasnya, jadi jauh lebih dapat
         | dipercaya daripada ekstensi nama file atau header MIME dari
         | browser - keduanya bisa dipalsukan siapa saja.
         |
         | Ini juga menghindari tabel mimes.php CodeIgniter, yang di versi
         | lama belum mengenal webp dan bergantung pada ekstensi fileinfo
         | aktif di server. Dua hal itulah penyebab pesan
         | "The filetype you are attempting to upload is not allowed". */
        $info = @getimagesize($f['tmp_name']);

        $izin = array(
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
        );
        if (defined('IMAGETYPE_WEBP')) {  // PHP 7.1+
            $izin[IMAGETYPE_WEBP] = 'webp';
        }

        if ($info === FALSE) {
            $this->session->set_flashdata(
                'upload_error',
                'Berkas itu bukan gambar yang sah.'
            );
            return FALSE;
        }

        if (!isset($izin[$info[2]])) {
            // Sebutkan apa yang terdeteksi - pesan "tidak diizinkan" tanpa
            // menyebut jenisnya membuat orang menebak-nebak.
            $terbaca = isset($info['mime']) ? $info['mime'] : 'tidak dikenal';
            $this->session->set_flashdata(
                'upload_error',
                'Jenis berkas ' . $terbaca . ' tidak didukung. Pakai JPG, PNG, atau WEBP.'
            );
            return FALSE;
        }

        if ($info[0] > 4000 || $info[1] > 4000) {
            $this->session->set_flashdata(
                'upload_error',
                'Dimensi ' . $info[0] . 'x' . $info[1] . ' piksel terlalu besar. Maksimal 4000x4000.'
            );
            return FALSE;
        }

        // ---- 3. Folder tujuan ------------------------------------------
        $folder = $this->folder_gambar();
        if ($folder === FALSE) {
            return FALSE;
        }

        /* ---- 4. Nama acak ---------------------------------------------
         | Nama asli TIDAK dipakai sama sekali. Nama dari pengguna bisa
         | berisi "../" untuk keluar folder, atau berakhiran ganda seperti
         | "foto.php.jpg" yang pada sebagian konfigurasi server tetap
         | dieksekusi sebagai PHP. Ekstensi diambil dari hasil
         | getimagesize(), bukan dari nama yang dikirim. */
        $nama_baru = bin2hex(random_bytes(16)) . '.' . $izin[$info[2]];
        $tujuan = $folder . $nama_baru;

        if (!move_uploaded_file($f['tmp_name'], $tujuan)) {
            $this->session->set_flashdata(
                'upload_error',
                'Gagal memindahkan berkas ke ' . $folder . ' - periksa izin foldernya.'
            );
            return FALSE;
        }

        @chmod($tujuan, 0644);  // berkas data, tidak perlu bisa dieksekusi

        if ($gambar_lama) {
            $this->hapus_gambar($gambar_lama);
        }

        return $nama_baru;
    }

    protected function pesan_galat_upload($kode)
    {
        switch ($kode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'Berkas melebihi batas upload_max_filesize di php.ini.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'Berkas melebihi batas yang ditentukan formulir.';
            case UPLOAD_ERR_PARTIAL:
                return 'Berkas hanya terunggah sebagian. Coba lagi.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Folder sementara PHP tidak ada. Periksa upload_tmp_dir di php.ini.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Server gagal menulis berkas ke disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'Unggahan dihentikan oleh salah satu ekstensi PHP.';
            default:
                return 'Unggahan gagal (kode ' . $kode . ').';
        }
    }

    /**
     * Cari folder tujuan unggahan dan pastikan benar-benar bisa dipakai.
     *
     * CI hanya berkata "The upload path does not appear to be valid" tanpa
     * menyebut path mana yang dicoba, jadi hampir mustahil dilacak. Di sini
     * path-nya diselesaikan sendiri dan disebutkan di pesan error.
     *
     * @return string|FALSE
     */
    protected function folder_gambar()
    {
        $target = FCPATH . 'upload' . DIRECTORY_SEPARATOR . 'produk' . DIRECTORY_SEPARATOR;

        // Folder dibuat kalau belum ada, daripada langsung menyerah.
        if (!is_dir($target)) {
            @mkdir($target, 0755, TRUE);
        }

        /* CI menjalankan realpath() pada upload_path. Kalau gagal (folder
           tidak ada, atau ada symlink yang tidak bisa ditelusuri), pesannya
           persis "upload path does not appear to be valid". Diselesaikan
           di sini supaya penyebabnya terlihat. */
        $nyata = realpath($target);

        if ($nyata === FALSE || !is_dir($nyata)) {
            $this->session->set_flashdata(
                'upload_error',
                'Folder tujuan tidak ditemukan: ' . $target
                    . ' - buat foldernya, lalu coba lagi.'
            );
            return FALSE;
        }

        if (!is_writable($nyata)) {
            $this->session->set_flashdata(
                'upload_error',
                'Folder ' . $nyata . ' tidak bisa ditulisi. '
                    . 'Di Linux jalankan: chmod 755 upload/produk'
            );
            return FALSE;
        }

        // CI membandingkan path dengan pemisah "/", termasuk di Windows.
        return str_replace('\\', '/', $nyata) . '/';
    }

    protected function hapus_gambar($nama)
    {
        // basename() memastikan tidak ada "../" yang menyelinap dari database.
        // basename() memastikan tidak ada "../" yang menyelinap dari database.
        $path = FCPATH . 'upload' . DIRECTORY_SEPARATOR . 'produk' . DIRECTORY_SEPARATOR . basename($nama);

        /* Berkas contoh bawaan (flower1.png dan sejenisnya) berada di
           assets/images, BUKAN di folder unggahan, jadi path di atas tidak
           akan menemukannya dan is_file() di bawah otomatis melewatinya.
           Nama file unggahan selalu acak, jadi tidak mungkin bentrok. */
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Ambil produk HANYA kalau milik toko yang sedang login.
     */
    protected function milik($id)
    {
        $p = $this
            ->db
            ->where('id', (int) $id)
            ->where('store_id', (int) $this->store['id'])
            ->get('products')
            ->row_array();
        if (!$p) {
            // 404, bukan 403. Memberi tahu "produk ini ada tapi bukan milikmu"
            // berarti membocorkan keberadaan data toko lain.
            show_404();
        }
        return $p;
    }

    /**
     * Ubah "285.000" -> 285000 pada semua kolom nominal.
     */
    protected function bersihkan_angka_post()
    {
        $tunggal = array('price');
        $jamak = array('varian_delta', 'addons_delta');

        foreach ($tunggal as $k) {
            if (isset($_POST[$k])) {
                $_POST[$k] = $this->angka($_POST[$k]);
            }
        }

        foreach ($jamak as $k) {
            if (isset($_POST[$k]) && is_array($_POST[$k])) {
                $_POST[$k] = array_map(array($this, 'angka'), $_POST[$k]);
            }
        }
    }

    /**
     * Buang titik, spasi, dan "Rp". Tanda minus di depan DIPERTAHANKAN -
     * varian boleh bernilai negatif untuk ukuran yang lebih kecil.
     */
    protected function angka($nilai)
    {
        $bersih = preg_replace('/[^0-9-]/', '', (string) $nilai);

        // Minus hanya sah di posisi paling depan.
        $minus = substr($bersih, 0, 1) === '-';
        $bersih = str_replace('-', '', $bersih);

        if ($bersih === '') {
            return '';
        }
        return ($minus ? '-' : '') . $bersih;
    }

    protected function simpan_varian($product_id)
    {
        $this->simpan_baris(
            'product_variants',
            $product_id,
            'varian_nama',
            'varian_delta',
            'varian_gambar',
            'varian_gambar_lama',
            FALSE  // boleh negatif - ukuran lebih kecil menurunkan harga
        );
    }

    protected function simpan_addons($product_id)
    {
        $this->simpan_baris(
            'product_addons',
            $product_id,
            'addons_nama',
            'addons_delta',
            'addons_gambar',
            'addons_gambar_lama',
            TRUE  // tambahan tidak pernah mengurangi harga
        );
    }

    protected function simpan_baris(
        $tabel,
        $product_id,
        $f_nama,
        $f_delta,
        $f_gambar,
        $f_gambar_lama,
        $tanpa_minus
    ) {
        $nama = (array) $this->input->post($f_nama, TRUE);
        $delta = (array) $this->input->post($f_delta);
        $lama = (array) $this->input->post($f_gambar_lama, TRUE);
        $hapus = (array) $this->input->post($f_gambar . '_hapus');

        // Gambar yang tidak lagi terpakai dikumpulkan dulu, dihapus dari
        // disk SETELAH database berhasil diperbarui.
        $sebelumnya = $this
            ->db
            ->select('image')
            ->where('product_id', (int) $product_id)
            ->get($tabel)
            ->result_array();
        $dipakai = array();

        $this->db->where('product_id', (int) $product_id)->delete($tabel);

        $urut = 1;
        foreach ($nama as $i => $n) {
            $n = trim($n);
            if ($n === '') {
                continue;
            }

            // Urutan: berkas baru > tandai hapus > gambar lama.
            $gambar = $this->ambil_gambar_baris($f_gambar, $i);

            if ($gambar === FALSE) {
                // Gagal unggah - pesannya sudah di-flashdata. Pakai yang
                // lama supaya tidak ikut hilang.
                $gambar = isset($lama[$i]) ? $lama[$i] : NULL;
            } elseif ($gambar === NULL) {
                $gambar = !empty($hapus[$i])
                    ? NULL
                    : (isset($lama[$i]) ? $lama[$i] : NULL);
            }

            if ($gambar) {
                $dipakai[] = $gambar;
            }

            $nilai = isset($delta[$i]) ? (int) $delta[$i] : 0;

            $this->db->insert($tabel, array(
                'product_id' => (int) $product_id,
                'name' => $n,
                'image' => $gambar,
                'price_delta' => $tanpa_minus ? max(0, $nilai) : $nilai,
                'sort_order' => $urut++,
            ));
        }

        // Bersihkan berkas yang sudah tidak dirujuk baris mana pun.
        foreach ($sebelumnya as $s) {
            if ($s['image'] && !in_array($s['image'], $dipakai, TRUE)) {
                $this->hapus_gambar($s['image']);
            }
        }
    }

    protected function ambil_gambar_baris($field, $i)
    {
        if (!isset($_FILES[$field]['name'][$i])) {
            return NULL;
        }

        $galat = $_FILES[$field]['error'][$i];
        if ($galat === UPLOAD_ERR_NO_FILE || $_FILES[$field]['name'][$i] === '') {
            return NULL;
        }
        if ($galat !== UPLOAD_ERR_OK) {
            $this->session->set_flashdata(
                'upload_error',
                'Gambar baris ke-' . ($i + 1) . ': ' . $this->pesan_galat_upload($galat)
            );
            return FALSE;
        }

        $tmp = $_FILES[$field]['tmp_name'][$i];

        if (!is_uploaded_file($tmp)) {
            return FALSE;
        }
        if ($_FILES[$field]['size'][$i] > 2 * 1024 * 1024) {
            $this->session->set_flashdata(
                'upload_error',
                'Gambar baris ke-' . ($i + 1) . ' melebihi 2 MB.'
            );
            return FALSE;
        }

        // getimagesize() membaca ISI berkas - ekstensi dan header MIME
        // sama-sama bisa dipalsukan.
        $info = @getimagesize($tmp);
        $izin = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png');
        if (defined('IMAGETYPE_WEBP')) {
            $izin[IMAGETYPE_WEBP] = 'webp';
        }

        if ($info === FALSE || !isset($izin[$info[2]])) {
            $this->session->set_flashdata(
                'upload_error',
                'Gambar baris ke-' . ($i + 1) . ' harus JPG, PNG, atau WEBP.'
            );
            return FALSE;
        }

        $folder = $this->folder_gambar();
        if ($folder === FALSE) {
            return FALSE;
        }

        // Nama diacak; ekstensi diambil dari hasil pembacaan berkas,
        // bukan dari nama yang dikirim browser.
        $baru = bin2hex(random_bytes(16)) . '.' . $izin[$info[2]];

        if (!move_uploaded_file($tmp, $folder . $baru)) {
            $this->session->set_flashdata(
                'upload_error',
                'Gagal menyimpan gambar baris ke-' . ($i + 1) . '.'
            );
            return FALSE;
        }
        @chmod($folder . $baru, 0644);

        return $baru;
    }

    protected function slug_unik($nama, $abaikan_id = NULL)
    {
        $this->load->helper('text');
        $dasar = url_title($nama, '-', TRUE);
        $slug = $dasar;
        $n = 2;

        while (TRUE) {
            // Slug hanya perlu unik DI DALAM toko ini, bukan seluruh sistem -
            // dua toko boleh sama-sama punya "buket-mawar-merah".
            $this->db->where('store_id', (int) $this->store['id'])->where('slug', $slug);
            if ($abaikan_id) {
                $this->db->where('id !=', (int) $abaikan_id);
            }
            if (!$this->db->count_all_results('products')) {
                return $slug;
            }
            $slug = $dasar . '-' . $n++;
        }
    }

    protected function alur_status()
    {
        return array(
            'pending' => 'confirmed',
            'confirmed' => 'preparing',
            'preparing' => 'delivering',
            'delivering' => 'delivered',
        );
    }

    public function orders()
    {
        $cari = trim((string) $this->input->get('q', TRUE));

        $this->db->from('orders')->where('store_id', (int) $this->store['id']);

        if ($cari !== '') {
            // group_start() penting: tanpa ini OR bocor keluar dan
            // mengabaikan syarat store_id.
            $this
                ->db
                ->group_start()
                ->like('order_number', $cari)
                ->or_like('customer_name', $cari)
                ->or_like('customer_phone', $cari)
                ->or_like('recipient_name', $cari)
                ->or_like('recipient_city', $cari)
                ->group_end();
        }

        $orders = $this
            ->db
            ->order_by('created_at', 'DESC')
            ->limit(100)
            ->get()
            ->result_array();

        $alur = $this->alur_status();

        /* Keputusan "boleh apa" dihitung di controller, bukan di view.
           View yang memutuskan sendiri cepat berbeda dengan aturan server,
           dan tombol yang muncul tapi ditolak server lebih membingungkan
           daripada tombol yang tidak muncul sama sekali. */
        $belum = $this->chat_model->belum_dibaca_toko($this->store['id']);

        foreach ($orders as &$o) {
            $o['belum_chat'] = isset($belum[$o['id']]) ? (int) $belum[$o['id']] : 0;
            $o['bisa_chat']  = ($o['payment_status'] === 'paid');

            $o['boleh_batal'] = !in_array($o['order_status'], array('cancelled', 'delivered'), TRUE);
            $o['boleh_maju'] = FALSE;
            $o['status_baru'] = NULL;  // kode saja, labelnya di view
            $o['alasan'] = NULL;  // kode alasan, kalimatnya di view
            $o['boleh_undo'] = FALSE;
            $o['sisa_undo'] = 0;

            if ($o['order_status'] === 'cancelled') {
                $o['alasan'] = 'dibatalkan';
            } elseif (!isset($alur[$o['order_status']])) {
                $o['alasan'] = 'selesai';
            } elseif ($o['payment_status'] !== 'paid' && $o['payment_method'] !== 'cod') {
                // Bunga tidak dirangkai sebelum uangnya masuk. COD
                // dikecualikan - uangnya memang baru ada saat kurir tiba.
                $o['alasan'] = 'belum_bayar';
            } else {
                $o['boleh_maju'] = TRUE;
                $o['status_baru'] = $alur[$o['order_status']];
            }

            // Jendela pembatalan, dihitung dari catatan perubahan terakhir.
            $log = $this
                ->db
                ->select('status, created_at')
                ->where('order_id', (int) $o['id'])
                ->where('note', 'Diubah penjual')
                ->order_by('id', 'DESC')
                ->limit(1)
                ->get('order_logs')
                ->row_array();

            /* Hanya boleh dibatalkan kalau status sekarang MEMANG hasil
               perubahan itu - kalau sudah berubah lagi (misalnya oleh
               callback pembayaran), pembatalan tidak lagi masuk akal. */
            if ($log && $log['status'] === $o['order_status']) {
                $lewat = time() - strtotime($log['created_at']);
                if ($lewat < self::UNDO_DETIK) {
                    $o['boleh_undo'] = TRUE;
                    $o['sisa_undo'] = (int) ceil((self::UNDO_DETIK - $lewat) / 60);
                }
            }
        }
        unset($o);

        $data = array(
            'orders' => $orders,
            'cari' => $cari,
            'undo_menit' => (int) (self::UNDO_DETIK / 60),
        );
        $this->render('seller/v_orders', $data);
    }

    /**
     * Majukan status satu langkah.  POST seller/ubah_status/{id}
     *
     * WAJIB POST. Tautan GET yang mengubah data bisa terpicu sendiri oleh
     * prefetch browser, pemindai antivirus, atau preview tautan WhatsApp -
     * tanpa ada yang menekan apa pun.
     */
    public function ubah_status($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $order = $this->pesanan_milik($id);
        $alur = $this->alur_status();

        if ($order['order_status'] === 'cancelled') {
            $this->session->set_flashdata('error', 'Pesanan sudah dibatalkan.');
            return redirect('seller/orders');
        }
        if (!isset($alur[$order['order_status']])) {
            $this->session->set_flashdata('error', 'Status pesanan sudah final.');
            return redirect('seller/orders');
        }
        if ($order['payment_status'] !== 'paid' && $order['payment_method'] !== 'cod') {
            $this->session->set_flashdata(
                'error',
                'Pesanan ini belum dibayar. Tunggu pembayaran masuk dulu.'
            );
            return redirect('seller/orders');
        }

        // Status tujuan dari alur di SERVER, tidak dikirim lewat form.
        $baru = $alur[$order['order_status']];

        $this->db->trans_begin();

        $this
            ->db
            ->where('id', (int) $order['id'])
            ->where('store_id', (int) $this->store['id'])  // sabuk pengaman kedua
            ->where('order_status', $order['order_status'])  // cegah klik ganda
            ->update('orders', array('order_status' => $baru));

        if ($this->db->affected_rows() < 1) {
            $this->db->trans_rollback();
            $this->session->set_flashdata(
                'error',
                'Status sudah berubah di tempat lain. Muat ulang halaman.'
            );
            return redirect('seller/orders');
        }

        /* DUA baris catatan. Yang pertama menandai perubahan oleh penjual
           (dipakai menghitung jendela pembatalan), yang kedua menyimpan
           status SEBELUMNYA - tanpa itu tidak ada cara tahu harus
           dikembalikan ke mana. */
        $this->db->insert('order_logs', array(
            'order_id' => (int) $order['id'],
            'status' => $baru,
            'note' => 'Diubah penjual',
        ));
        $this->db->insert('order_logs', array(
            'order_id' => (int) $order['id'],
            'status' => $order['order_status'],
            'note' => 'sebelum',
        ));

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Gagal mengubah status.');
            return redirect('seller/orders');
        }
        $this->db->trans_commit();

        $this->session->set_flashdata(
            'sukses',
            'Status ' . $order['order_number'] . ' diperbarui. Salah pencet? '
                . 'Masih bisa dibatalkan dalam ' . (self::UNDO_DETIK / 60) . ' menit.'
        );

        return redirect('seller/orders');
    }

    /**
     * Batalkan perubahan status terakhir.  POST seller/undo_status/{id}
     *
     * Konfirmasi saja tidak cukup mencegah salah pencet - orang terbiasa
     * menekan "Ya" tanpa membaca. Jendela ini menangkap kesalahan yang
     * sudah terlanjur terjadi.
     */
    public function undo_status($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $order = $this->pesanan_milik($id);

        $log = $this
            ->db
            ->select('id, status, created_at')
            ->where('order_id', (int) $order['id'])
            ->where('note', 'Diubah penjual')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('order_logs')
            ->row_array();

        if (!$log || $log['status'] !== $order['order_status']) {
            $this->session->set_flashdata('error', 'Tidak ada perubahan yang bisa dibatalkan.');
            return redirect('seller/orders');
        }

        if ((time() - strtotime($log['created_at'])) >= self::UNDO_DETIK) {
            $this->session->set_flashdata(
                'error',
                'Batas waktu pembatalan sudah lewat (' . (self::UNDO_DETIK / 60)
                    . ' menit). Hubungi admin kalau perlu diperbaiki.'
            );
            return redirect('seller/orders');
        }

        // Status sebelumnya dicatat tepat SESUDAH baris 'Diubah penjual',
        // jadi id-nya pasti lebih besar.
        $sebelum = $this
            ->db
            ->select('id, status')
            ->where('order_id', (int) $order['id'])
            ->where('note', 'sebelum')
            ->where('id >', (int) $log['id'])
            ->order_by('id', 'ASC')
            ->limit(1)
            ->get('order_logs')
            ->row_array();

        if (!$sebelum) {
            $this->session->set_flashdata('error', 'Status sebelumnya tidak tercatat.');
            return redirect('seller/orders');
        }

        $this->db->trans_begin();

        $this
            ->db
            ->where('id', (int) $order['id'])
            ->where('store_id', (int) $this->store['id'])
            ->where('order_status', $order['order_status'])
            ->update('orders', array('order_status' => $sebelum['status']));

        /* Kedua catatan dihapus. Riwayat yang dilihat pembeli sebaiknya
           menampilkan perjalanan pesanan yang sebenarnya - bukan kesalahan
           penjual lalu koreksinya, yang justru menimbulkan kecemasan. */
        $this
            ->db
            ->where_in('id', array((int) $log['id'], (int) $sebelum['id']))
            ->delete('order_logs');

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Gagal membatalkan.');
            return redirect('seller/orders');
        }
        $this->db->trans_commit();

        $this->session->set_flashdata(
            'sukses',
            'Status ' . $order['order_number'] . ' dikembalikan ke tahap sebelumnya.'
        );

        return redirect('seller/orders');
    }

    /**
     * Ambil pesanan HANYA kalau milik toko yang sedang login.
     *
     * Tanpa ini, mengganti angka id di URL cukup untuk mengubah status
     * pesanan toko lain. 404, bukan 403 - memberi tahu "pesanan ini ada
     * tapi bukan milikmu" berarti membocorkan keberadaan data toko lain.
     */

    protected function render($view, $data = array())
    {
        $data['me'] = $this->me;
        $data['store'] = $this->store;
        $this->load->view('admin/v_admin_header', $data);
        $this->load->view($view, $data);
        $this->load->view('admin/v_admin_footer');
    }

    public function batal_pesanan($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $order = $this->pesanan_milik($id);
        $alasan = trim((string) $this->input->post('alasan', TRUE));

        if ($order['order_status'] === 'cancelled') {
            $this->session->set_flashdata('error', 'Pesanan ini sudah dibatalkan.');
            return redirect('seller/orders');
        }

        /* Yang sudah sampai tidak bisa dibatalkan - bunganya sudah di tangan
           penerima. Kalau ada masalah setelah itu, urusannya refund, bukan
           pembatalan. */
        if ($order['order_status'] === 'delivered') {
            $this->session->set_flashdata(
                'error',
                'Pesanan yang sudah diterima tidak bisa dibatalkan.'
            );
            return redirect('seller/orders');
        }

        if (mb_strlen($alasan) < 5) {
            // Alasan diwajibkan: pembeli berhak tahu kenapa pesanannya
            // dibatalkan, dan penjual jadi berpikir dua kali.
            $this->session->set_flashdata(
                'error',
                'Tulis alasan pembatalan minimal 5 karakter.'
            );
            return redirect('seller/orders');
        }

        $this->db->trans_begin();

        $this
            ->db
            ->where('id', (int) $order['id'])
            ->where('store_id', (int) $this->store['id'])
            ->where('order_status !=', 'cancelled')
            ->update('orders', array('order_status' => 'cancelled'));

        if ($this->db->affected_rows() < 1) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Status sudah berubah. Muat ulang halaman.');
            return redirect('seller/orders');
        }

        $this->db->insert('order_logs', array(
            'order_id' => (int) $order['id'],
            'status' => 'cancelled',
            'note' => 'Dibatalkan toko: ' . $alasan,
        ));

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Gagal membatalkan pesanan.');
            return redirect('seller/orders');
        }
        $this->db->trans_commit();

        /* Uang TIDAK dikembalikan otomatis. Duitku tidak menyediakan refund
           lewat API, jadi ini harus ditransfer manual ke pembeli - dan
           penjual perlu diingatkan, bukan dibiarkan mengira sudah beres. */
        $pesan = 'Pesanan ' . $order['order_number'] . ' dibatalkan.';
        if ($order['payment_status'] === 'paid') {
            $pesan .= ' PESANAN INI SUDAH DIBAYAR ' . rupiah($order['total'])
                . ' - hubungi pembeli dan kembalikan uangnya secara manual.';
        }
        $this->session->set_flashdata('sukses', $pesan);

        return redirect('seller/orders');
    }

    protected function sapu_ringan()
    {
        /* Dibatasi sekali per 5 menit. Tanpa ini, menekan F5 sepuluh kali
           berarti sepuluh kali query pembersihan. */
        $lalu = (int) $this->session->userdata('sapu_terakhir');
        if ($lalu && (time() - $lalu) < self::SAPU_JEDA) {
            return;
        }
        $this->session->set_userdata('sapu_terakhir', time());

        $this->config->load('duitku', TRUE);
        $cfg = $this->config->item('duitku', 'duitku');

        // Batas kedaluwarsa diturunkan dari masa berlaku invoice, bukan
        // angka terpisah yang harus dijaga tetap selaras.
        $jam = (int) ceil((int) $cfg['expiry_period'] / 60);

        $n = $this->order_model->tandai_kedaluwarsa($jam);
        $n += $this->order_model->bersihkan_menggantung();

        foreach ($this->chat_model->acc_kedaluwarsa(50) as $o) {
            $this->chat_model->setujui_otomatis($o['id']);
        }

        if ($n > 0) {
            $this->session->set_flashdata(
                'info',
                $n . ' pesanan yang tidak terbayar otomatis dibatalkan.'
            );
        }
    }


    public function kirim_foto_acc($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $order = $this->pesanan_milik($id);

        // Bunga tidak dirangkai sebelum uangnya masuk.
        if ($order['payment_status'] !== 'paid') {
            $this->session->set_flashdata('error', 'Pesanan ini belum dibayar.');
            return redirect('seller/orders');
        }

        if (in_array($order['acc_status'], array('disetujui', 'otomatis', 'ditutup'), TRUE)) {
            $this->session->set_flashdata(
                'error',
                'Rangkaian sudah disetujui, tidak perlu kirim foto lagi.'
            );
            return redirect('seller/chat/' . $order['id']);
        }

        $gambar = $this->unggah_foto_chat('foto_acc');

        if ($gambar === FALSE) {
            return redirect('seller/chat/' . $order['id']);   // pesan sudah di-flashdata
        }
        if ($gambar === NULL) {
            $this->session->set_flashdata('error', 'Pilih foto rangkaiannya dulu.');
            return redirect('seller/chat/' . $order['id']);
        }

        $toko = $this->db->where('id', (int) $this->store['id'])
            ->get('stores')->row_array();

        $deadline = $this->chat_model->hitung_deadline($order, $toko);

        $this->db->where('id', (int) $order['id'])->update('orders', array(
            'acc_status'   => 'menunggu',
            'acc_deadline' => $deadline,
        ));

        $this->chat_model->kirim(
            $order['id'],
            'seller',
            'foto_acc',
            trim((string) $this->input->post('catatan', TRUE)) ?: NULL,
            $gambar,
            $this->me['id']
        );

        /* Tenggatnya dicatat di percakapan, bukan cuma di kolom database.
           Saat ada sengketa, urutan kejadiannya terbaca utuh dalam satu
           daftar - kapan foto dikirim, kapan tenggatnya, siapa menyetujui. */
        $this->chat_model->sistem(
            $order['id'],
            'Foto rangkaian dikirim. Menunggu persetujuan sampai '
                . date('d/m/Y H:i', strtotime($deadline))
                . '. Lewat itu, rangkaian dianggap disetujui.'
        );

        $this->session->set_flashdata(
            'sukses',
            'Foto terkirim. Customer punya waktu sampai '
                . date('d/m H:i', strtotime($deadline)) . '.'
        );

        return redirect('seller/chat/' . $order['id']);
    }

    /**
     * Kirim foto di lokasi pengiriman.
     * POST seller/kirim_foto_lokasi/{id}
     *
     * Pemesan bunga biasanya TIDAK ada di tempat saat bunga sampai -
     * foto ini pengganti kehadiran mereka.
     */
    public function kirim_foto_lokasi($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $order  = $this->pesanan_milik($id);
        $gambar = $this->unggah_foto_chat('foto_lokasi');

        if ($gambar === FALSE) {
            return redirect('seller/chat/' . $order['id']);
        }
        if ($gambar === NULL) {
            $this->session->set_flashdata('error', 'Pilih fotonya dulu.');
            return redirect('seller/chat/' . $order['id']);
        }

        $this->chat_model->kirim(
            $order['id'],
            'seller',
            'foto_lokasi',
            trim((string) $this->input->post('catatan', TRUE)) ?: NULL,
            $gambar,
            $this->me['id']
        );

        $this->session->set_flashdata('sukses', 'Foto pengiriman terkirim.');
        return redirect('seller/chat/' . $order['id']);
    }

    /**
     * Tutup revisi sepihak.
     * POST seller/tutup_revisi/{id}
     */
    public function tutup_revisi($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $order  = $this->pesanan_milik($id);
        $alasan = trim((string) $this->input->post('alasan', TRUE));

        if (in_array($order['acc_status'], array('disetujui', 'otomatis', 'ditutup'), TRUE)) {
            $this->session->set_flashdata('error', 'Revisi sudah tertutup.');
            return redirect('seller/chat/' . $order['id']);
        }
        if (mb_strlen($alasan) < 5) {
            // Ini keputusan sepihak yang merugikan customer, jadi harus ada
            // penjelasan yang bisa mereka baca.
            $this->session->set_flashdata('error', 'Tulis alasan minimal 5 karakter.');
            return redirect('seller/chat/' . $order['id']);
        }

        $this->db->where('id', (int) $order['id'])->update('orders', array(
            'acc_status'     => 'ditutup',
            'card_locked_at' => date('Y-m-d H:i:s'),
        ));

        $this->chat_model->sistem(
            $order['id'],
            'Toko menutup revisi: ' . $alasan
                . ' Kata-kata papan dikunci dan rangkaian dikirim sesuai foto terakhir.'
        );

        $this->session->set_flashdata('sukses', 'Revisi ditutup.');
        return redirect('seller/chat/' . $order['id']);
    }

    /** Halaman percakapan satu pesanan. GET seller/chat/{id} */
    public function chat($id = NULL)
    {
        $order = $this->pesanan_milik($id);
        $this->chat_model->tandai_dibaca($order['id'], 'seller');

        $toko = $this->db->where('id', (int) $this->store['id'])
            ->get('stores')->row_array();

        $data = array(
            'order'    => $order,
            'pesan'    => $this->chat_model->pesan($order['id']),
            'toko'     => $toko,
            'terkunci' => (bool) $order['card_locked_at'],
        );
        $this->render('seller/v_chat', $data);
    }

    /** Kirim pesan teks. POST seller/chat_kirim/{id} [AJAX] */
    public function chat_kirim($id = NULL)
    {
        $order = $this->pesanan_milik($id);
        $isi   = trim((string) $this->input->post('isi', TRUE));

        if ($isi === '') {
            return $this->json_chat(array('ok' => FALSE, 'pesan' => 'Pesan kosong.'), 422);
        }

        $this->chat_model->kirim($order['id'], 'seller', 'teks', $isi, NULL, $this->me['id']);
        return $this->json_chat(array('ok' => TRUE));
    }

    /** Ambil pesan baru. GET seller/chat_baru/{id}?sejak=N&dibaca=1 [AJAX] */
    public function chat_baru($id = NULL)
    {
        $order = $this->pesanan_milik($id);
        $sejak = (int) $this->input->get('sejak');

        $baru = $this->chat_model->pesan($order['id'], $sejak);

        /* Ditandai dibaca hanya kalau halamannya benar-benar dilihat.
           Halaman chat penjual selalu "terbuka", tapi tab-nya bisa di
           latar - JS mengirim dibaca=0 saat itu. */
        if ($baru && $this->input->get('dibaca') !== '0') {
            $this->chat_model->tandai_dibaca($order['id'], 'seller');
        }

        return $this->json_chat(array(
            'ok'         => TRUE,
            'pesan'      => $baru,
            'acc_status' => $order['acc_status'],
            'terkunci'   => (bool) $order['card_locked_at'],
        ));
    }

    /** Ubah kata-kata papan. POST seller/ubah_kartu/{id} */
    public function ubah_kartu($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $order = $this->pesanan_milik($id);

        /* Kunci diperiksa DI SERVER, bukan cuma menyembunyikan formnya.
           Form yang disembunyikan CSS masih bisa dikirim lewat DevTools -
           dan setelah dikunci, mengubah kata-kata papan berarti papan yang
           sudah dicetak jadi salah. */
        if ($order['card_locked_at']) {
            $this->session->set_flashdata(
                'error',
                'Kata-kata papan sudah dikunci sejak '
                    . date('d/m/Y H:i', strtotime($order['card_locked_at'])) . '.'
            );
            return redirect('seller/chat/' . $order['id']);
        }

        $baru = trim((string) $this->input->post('card_message', TRUE));

        $this->db->where('id', (int) $order['id'])
            ->where('card_locked_at IS NULL', NULL, FALSE)   // pengaman balapan
            ->update('orders', array('card_message' => $baru ?: NULL));

        if ($this->db->affected_rows() < 1) {
            $this->session->set_flashdata('error', 'Kata-kata papan baru saja dikunci.');
            return redirect('seller/chat/' . $order['id']);
        }

        $this->chat_model->sistem(
            $order['id'],
            'Kata-kata papan diubah menjadi: "' . $baru . '"'
        );

        $this->session->set_flashdata('sukses', 'Kata-kata papan diperbarui.');
        return redirect('seller/chat/' . $order['id']);
    }
 
    /* ------------------------------------------------------------------ */

    /**
     * Unggah satu foto untuk percakapan.
     *
     * Berdiri sendiri, tidak memanggil helper dari patch lain.
     *
     * @param  string $field nama input file
     * @return string|NULL|FALSE nama berkas, NULL bila kosong, FALSE bila gagal
     */
    protected function unggah_foto_chat($field)
    {
        $f = isset($_FILES[$field]) ? $_FILES[$field] : NULL;

        if (! $f || $f['error'] === UPLOAD_ERR_NO_FILE || $f['name'] === '') {
            return NULL;
        }

        if ($f['error'] !== UPLOAD_ERR_OK) {
            $pesan = ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE)
                ? 'Foto terlalu besar untuk diunggah server.'
                : 'Gagal mengunggah foto (kode ' . $f['error'] . ').';
            $this->session->set_flashdata('error', $pesan);
            return FALSE;
        }

        if (! is_uploaded_file($f['tmp_name'])) {
            $this->session->set_flashdata('error', 'Berkas tidak sah.');
            return FALSE;
        }

        if ($f['size'] > 4 * 1024 * 1024) {
            $this->session->set_flashdata(
                'error',
                'Foto maksimal 4 MB. Ukuran berkas ini '
                    . round($f['size'] / 1048576, 1) . ' MB.'
            );
            return FALSE;
        }

        /* getimagesize() membaca ISI berkas. Ekstensi dan header MIME
           sama-sama bisa dipalsukan - hanya isinya yang tidak bisa. */
        $info = @getimagesize($f['tmp_name']);
        $izin = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png');
        if (defined('IMAGETYPE_WEBP')) {
            $izin[IMAGETYPE_WEBP] = 'webp';
        }

        if ($info === FALSE || ! isset($izin[$info[2]])) {
            $this->session->set_flashdata('error', 'Foto harus JPG, PNG, atau WEBP.');
            return FALSE;
        }

        $folder = FCPATH . 'upload' . DIRECTORY_SEPARATOR . 'produk' . DIRECTORY_SEPARATOR;
        if (! is_dir($folder)) {
            @mkdir($folder, 0755, TRUE);
        }

        $nyata = realpath($folder);
        if ($nyata === FALSE || ! is_writable($nyata)) {
            $this->session->set_flashdata(
                'error',
                'Folder upload/produk/ tidak bisa ditulisi.'
            );
            return FALSE;
        }

        /* Nama diacak; ekstensi diambil dari hasil pembacaan berkas, bukan
           dari nama kiriman browser - nama seperti "foto.php.jpg" tidak
           akan menghasilkan berkas .php. */
        $nama = bin2hex(random_bytes(16)) . '.' . $izin[$info[2]];

        if (! move_uploaded_file($f['tmp_name'], $nyata . DIRECTORY_SEPARATOR . $nama)) {
            $this->session->set_flashdata('error', 'Gagal menyimpan foto.');
            return FALSE;
        }
        @chmod($nyata . DIRECTORY_SEPARATOR . $nama, 0644);

        return $nama;
    }

    protected function json_chat($data, $kode = 200)
    {
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();

        return $this->output->set_status_header($kode)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    /**
     * Ambil pesanan HANYA kalau milik toko yang sedang login.
     *
     * LEWATI kalau method ini sudah ada di Seller.php kamu (dari patch
     * ubah status). Kalau belum ada, sisipkan juga.
     *
     * Tanpa ini, mengganti angka id di URL cukup untuk membaca percakapan
     * toko lain. 404, bukan 403 - memberi tahu "pesanan ini ada tapi bukan
     * milikmu" berarti membocorkan keberadaan data toko lain.
     */
    protected function pesanan_milik($id)
    {
        $o = $this->db->where('id', (int) $id)
            ->where('store_id', (int) $this->store['id'])
            ->get('orders')->row_array();
        if (! $o) {
            show_404();
        }
        return $o;
    }

    public function notif()
    {
        $this->load->model('chat_model');

        $belum = $this->chat_model->belum_dibaca_toko($this->store['id']);

        /* Pesanan yang butuh perhatian - dipakai memberi tanda di daftar
           walau tidak ada pesan baru. Tenggat ACC berjalan terus, dan
           pesanan yang menunggu persetujuan perlu terlihat. */
        $menunggu = $this->db->select('id')
            ->where('store_id', (int) $this->store['id'])
            ->where('acc_status', 'menunggu')
            ->get('orders')->result_array();

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'ok'       => TRUE,
                'belum'    => (object) $belum,          // {order_id: jumlah}
                'total'    => array_sum($belum),
                'menunggu' => array_column($menunggu, 'id'),
            )));
    }
}
