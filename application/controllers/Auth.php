<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Auth - masuk, daftar, keluar.
 *
 *   GET/POST auth/login      ?next=jalur/tujuan
 *   GET/POST auth/register   ?next=jalur/tujuan
 *   GET/POST auth/logout
 *
 *   GET/POST auth/lupa              minta tautan reset sandi
 *   GET/POST auth/reset/{token}     pasang sandi baru
 *   GET      auth/verifikasi/{token} tandai email terverifikasi
 *   POST     auth/kirim_verifikasi  kirim ulang email verifikasi
 */
class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('auth_lib', 'session', 'form_validation'));
        $this->load->helper(array('url', 'form'));
    }

    /** Ke mana setelah berhasil masuk. */
    protected function sesudah_masuk()
    {
        // Admin selalu ke panel admin. Sebelumnya diarahkan ke 'admin' -
        // rute itu tidak ada, jadi admin mendarat di halaman 404.
        if ($this->auth_lib->is_admin()) {
            return 'admin/stores';
        }
        $next = $this->auth_lib->tujuan_aman($this->input->get_post('next', TRUE));
        return $next ?: '';
    }

    public function login()
    {
        if ($this->auth_lib->user()) {
            return redirect($this->sesudah_masuk());
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email');
            $this->form_validation->set_rules('password', 'Password', 'required');
            $this->form_validation->set_error_delimiters('<p class="field-error">', '</p>');

            if ($this->form_validation->run()) {
                $ok = $this->auth_lib->login(
                    $this->input->post('email', TRUE),
                    (string) $this->input->post('password', FALSE)   // JANGAN di-XSS-clean
                );
                if ($ok) {
                    return redirect($this->sesudah_masuk());
                }
                // Sengaja tidak membedakan "email salah" dan "password salah".
                $this->session->set_flashdata('error', 'Email atau password salah.');
                return redirect('auth/login' . $this->query_next());
            }
        }

        $data = array('next' => $this->auth_lib->tujuan_aman($this->input->get('next', TRUE)));
        $data['pages'] = 'v_login';
        $this->load->view('index', $data);
    }

    public function register()
    {
        if ($this->auth_lib->user()) {
            return redirect($this->sesudah_masuk());
        }

        if ($this->input->method() === 'post') {

            /* Jebakan bot. Kolom 'website' disembunyikan dari manusia lewat
               CSS; bot pengisi formulir otomatis biasanya mengisi semua
               kolom. Terisi = hampir pasti bot, ditolak tanpa penjelasan. */
            if (trim((string) $this->input->post('website')) !== '') {
                return redirect('auth/register');
            }

            $this->form_validation->set_rules('name',      'Nama',            'required|trim|min_length[2]|max_length[100]');
            $this->form_validation->set_rules('email',     'Email',           'required|trim|valid_email|max_length[150]');
            $this->form_validation->set_rules('phone',     'Nomor WhatsApp',  'required|trim|callback_valid_phone');
            $this->form_validation->set_rules('password',  'Password',        'required|min_length[8]');
            $this->form_validation->set_rules('password2', 'Ulangi password', 'required|matches[password]');

            $this->form_validation->set_message('required',   '{field} wajib diisi.');
            $this->form_validation->set_message('min_length', '{field} minimal {param} karakter.');
            $this->form_validation->set_message('matches',    'Password tidak sama.');
            $this->form_validation->set_message('valid_email','{field} tidak valid.');
            $this->form_validation->set_error_delimiters('<p class="field-error">', '</p>');

            if ($this->form_validation->run()) {
                $hasil = $this->auth_lib->register(
                    $this->input->post('name', TRUE),
                    $this->input->post('email', TRUE),
                    $this->normalize_phone($this->input->post('phone', TRUE)),
                    (string) $this->input->post('password', FALSE)
                );

                if ($hasil['ok']) {

                    /* Email verifikasi dikirim, tapi TIDAK menghalangi apa
                       pun: akunnya sudah aktif dan sudah login. Yang belum
                       verifikasi hanya melihat spanduk pengingat di halaman
                       akun, dan baru benar-benar diwajibkan saat membuka
                       toko - lihat Akun::buka_toko().

                       Dibuat longgar dengan sengaja. Setiap email yang masuk
                       folder spam adalah satu calon pembeli yang hilang,
                       sementara risiko email tak terverifikasi bagi pembeli
                       biasa hampir tidak ada - yang dipertaruhkan baru muncul
                       kalau dia menerima uang. */
                    $row = $this->auth_lib->row();
                    if ($row) {
                        $this->kirim_tautan_verifikasi($row);
                    }

                    $this->session->set_flashdata('sukses', 'Akun dibuat. Selamat datang!');
                    return redirect($this->sesudah_masuk());
                }
                $this->session->set_flashdata('error', $hasil['pesan']);
            }
        }

        $data = array('next' => $this->auth_lib->tujuan_aman($this->input->get_post('next', TRUE)));
        $data['pages'] = 'v_register';
        $this->load->view('index', $data);
    }

    public function logout()
    {
        $this->auth_lib->logout();
        $this->session->set_flashdata('sukses', 'Kamu sudah keluar.');
        return redirect('');
    }

    /* ============================================================ LUPA SANDI */

    /** Formulir "lupa password" dan pengirim tautannya. */
    public function lupa()
    {
        if ($this->auth_lib->user()) {
            return redirect('akun/profil');
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email');
            $this->form_validation->set_message('valid_email', 'Email tidak valid.');
            $this->form_validation->set_error_delimiters('<p class="field-error">', '</p>');

            if ($this->form_validation->run()) {
                $email = strtolower(trim((string) $this->input->post('email', TRUE)));

                $user = $this->db->select('id, name, email')
                                 ->where('email', $email)
                                 ->where('is_active', 1)
                                 ->get('users')->row_array();

                /* Pesannya SAMA, ada akunnya atau tidak.

                   Kalau dibedakan, formulir ini berubah jadi alat pemeriksa:
                   siapa pun bisa memasukkan daftar email dan mengetahui mana
                   yang terdaftar di sini. Itu data yang berharga bagi
                   penipu - mereka tahu siapa yang pantas dikirimi email
                   palsu "pesananmu bermasalah" atas nama Sapa UMKM. */
                if ($user) {
                    $this->kirim_tautan_reset($user);
                }

                /* Teks biasa, tanpa html_escape dan tanpa tag: spanduk yang
                   menampilkannya (v_navbar / header mobile) sudah memanggil
                   html_escape sendiri, jadi meng-escape di sini membuat
                   tanda kutip dan & tampil sebagai &amp;. */
                $this->session->set_flashdata('sukses',
                    'Kalau ' . $email . ' terdaftar, tautan untuk mengganti sandi '
                    . 'sudah dikirim ke sana. Cek juga folder spam.');

                return redirect('auth/lupa');
            }
        }

        $data = array('pages' => 'v_lupa');
        $this->load->view('index', $data);
    }

    /** Halaman pasang sandi baru. Token dari tautan di email. */
    public function reset($token = NULL)
    {
        $token = (string) $token;

        $this->load->model('token_model');

        /* GET hanya MEMERIKSA, POST yang memakai tokennya. Alasannya ada di
           komentar Token_model::periksa(). */
        $cek = $this->token_model->periksa($token, 'reset');

        if (! $cek['ok']) {
            $this->session->set_flashdata('error', $this->pesan_token($cek['alasan']));
            return redirect('auth/lupa');
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('password',  'Password baru',    'required|min_length[8]');
            $this->form_validation->set_rules('password2', 'Ulangi password',  'required|matches[password]');

            $this->form_validation->set_message('required',   '{field} wajib diisi.');
            $this->form_validation->set_message('min_length', '{field} minimal {param} karakter.');
            $this->form_validation->set_message('matches',    'Password tidak sama.');
            $this->form_validation->set_error_delimiters('<p class="field-error">', '</p>');

            if ($this->form_validation->run()) {

                // Baru DI SINI tokennya dipakai - setelah sandinya lolos
                // validasi. Dipakai lebih dulu, sandi yang terlalu pendek
                // berarti tautannya hangus dan orangnya harus minta lagi.
                $pakai = $this->token_model->pakai($token, 'reset');

                if (! $pakai['ok']) {
                    $this->session->set_flashdata('error', $this->pesan_token($pakai['alasan']));
                    return redirect('auth/lupa');
                }

                $this->auth_lib->ganti_sandi(
                    $pakai['user_id'],
                    (string) $this->input->post('password', FALSE)   // JANGAN di-XSS-clean
                );

                $this->session->set_flashdata('sukses',
                    'Sandi berhasil diganti. Silakan masuk dengan sandi baru.');

                return redirect('auth/login');
            }
        }

        $data = array('token' => $token, 'pages' => 'v_reset');
        $this->load->view('index', $data);
    }

    /* ========================================================== VERIFIKASI */

    /** Tautan verifikasi dari email. */
    public function verifikasi($token = NULL)
    {
        $this->load->model('token_model');

        $hasil = $this->token_model->pakai((string) $token, 'verifikasi');

        if (! $hasil['ok']) {
            $this->session->set_flashdata('error', $this->pesan_token($hasil['alasan'], 'verifikasi'));
            return redirect($this->auth_lib->user() ? 'akun/profil' : 'auth/login');
        }

        $this->db->where('id', $hasil['user_id'])
                 ->where('email_verified_at IS NULL', NULL, FALSE)
                 ->update('users', array('email_verified_at' => date('Y-m-d H:i:s')));

        $this->auth_lib->segarkan();

        $this->session->set_flashdata('sukses', 'Email kamu sudah terverifikasi. Terima kasih!');

        /* Yang mengklik belum tentu sedang login - tautannya bisa dibuka di
           HP padahal daftarnya di laptop. Yang belum login diarahkan ke
           halaman masuk, bukan ke halaman akun yang akan menolaknya. */
        return redirect($this->auth_lib->user() ? 'akun/profil' : 'auth/login');
    }

    /** Kirim ulang email verifikasi untuk akun yang sedang login. */
    public function kirim_verifikasi()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $row = $this->auth_lib->row();
        if (! $row) {
            return redirect('auth/login');
        }

        /* terverifikasi(), bukan $row['email_verified_at'] langsung: kolomnya
           belum ada kalau migrasi 25 belum dijalankan, dan membacanya begitu
           saja memunculkan peringatan PHP di halaman profil. */
        if ($this->auth_lib->terverifikasi() !== FALSE) {
            $this->session->set_flashdata('sukses', 'Email kamu sudah terverifikasi.');
            return redirect('akun/profil');
        }

        $hasil = $this->kirim_tautan_verifikasi($row);

        $this->session->set_flashdata(
            $hasil['ok'] ? 'sukses' : 'error',
            $hasil['pesan']
        );

        return redirect('akun/profil');
    }

    /* ------------------------------------------------------- pengirim surat */

    /**
     * Buat token reset lalu kirim emailnya.
     *
     * protected, dan TIDAK mengembalikan apa pun yang dipakai memberi pesan
     * ke pengunjung: hasil pengiriman sengaja tidak dibocorkan di halaman
     * lupa sandi, karena "gagal kirim" juga menjawab pertanyaan "apakah email
     * ini terdaftar".
     */
    protected function kirim_tautan_reset(array $user)
    {
        $this->load->model('token_model');
        $this->load->library('surat_lib');

        $t = $this->token_model->buat($user['id'], 'reset');
        if (! $t['ok']) {
            return;
        }

        $this->surat_lib->kirim(
            $user['email'],
            'Ganti sandi Sapa UMKM',
            'reset',
            array(
                'nama'  => $user['name'],
                'tautan'=> site_url('auth/reset/' . $t['token']),
                'jam'   => (int) (Token_model::UMUR_RESET / 3600),
            )
        );
    }

    /**
     * Buat token verifikasi lalu kirim emailnya.
     *
     * @return array ['ok' => bool, 'pesan' => string]
     */
    protected function kirim_tautan_verifikasi(array $user)
    {
        $this->load->model('token_model');
        $this->load->library('surat_lib');

        $t = $this->token_model->buat($user['id'], 'verifikasi');
        if (! $t['ok']) {
            return array('ok' => FALSE, 'pesan' => $t['pesan']);
        }

        $kirim = $this->surat_lib->kirim(
            $user['email'],
            'Verifikasi email Sapa UMKM',
            'verifikasi',
            array(
                'nama'   => $user['name'],
                'tautan' => site_url('auth/verifikasi/' . $t['token']),
            )
        );

        if ($kirim) {
            return array('ok' => TRUE, 'pesan' => 'Email verifikasi dikirim ke ' . $user['email'] . '.');
        }

        /* Pesannya dibedakan antara "SMTP belum diatur" dan "SMTP ada tapi
           gagal". Yang pertama adalah pekerjaan pemilik situs dan tidak ada
           gunanya menyuruh pengguna mencoba lagi; yang kedua kadang berhasil
           kalau diulang. */
        return array(
            'ok'    => FALSE,
            'pesan' => $this->surat_lib->siap()
                ? 'Email gagal dikirim. Coba lagi beberapa saat lagi.'
                : 'Pengiriman email belum diatur di server ini. Hubungi pengelola.',
        );
    }

    /* ------------------------------------------------------------------ */

    /** Pesan untuk token yang tidak bisa dipakai. */
    protected function pesan_token($alasan, $jenis = 'reset')
    {
        $apa = ($jenis === 'verifikasi') ? 'verifikasi' : 'penggantian sandi';

        switch ($alasan) {
            case 'kedaluwarsa':
                return 'Tautan ' . $apa . ' sudah kedaluwarsa. Minta tautan baru di bawah.';

            case 'sudah_dipakai':
                return 'Tautan ' . $apa . ' itu sudah pernah dipakai. '
                     . 'Kalau sandimu belum berganti, minta tautan baru di bawah.';

            default:
                return 'Tautan ' . $apa . ' tidak dikenali. '
                     . 'Pastikan tautannya tersalin lengkap, atau minta yang baru di bawah.';
        }
    }

    /* ------------------------------------------------------------------ */

    protected function query_next()
    {
        $n = $this->auth_lib->tujuan_aman($this->input->get_post('next', TRUE));
        return $n ? '?next=' . rawurlencode($n) : '';
    }

    /** Callback validasi. WAJIB public - dipanggil dari luar kelas. */
    public function valid_phone($str)
    {
        if (preg_match('/^628[0-9]{7,11}$/', $this->normalize_phone($str))) {
            return TRUE;
        }
        $this->form_validation->set_message('valid_phone', '{field} tidak valid. Contoh: 081234567890');
        return FALSE;
    }

    protected function normalize_phone($phone)
    {
        $p = preg_replace('/[^0-9]/', '', (string) $phone);
        if (substr($p, 0, 1) === '0')  { return '62' . substr($p, 1); }
        if (substr($p, 0, 2) === '62') { return $p; }
        return '62' . $p;
    }
}
