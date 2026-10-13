<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Auth - masuk, daftar, keluar.
 *
 *   GET/POST auth/login      ?next=jalur/tujuan
 *   GET/POST auth/register   ?next=jalur/tujuan
 *   GET/POST auth/logout
 *   GET/POST auth/lupa            minta tautan reset kata sandi
 *   GET/POST auth/reset/{token}   buat kata sandi baru
 *   GET/POST auth/otp             masuk dengan kode WhatsApp
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
        if ($this->auth_lib->is('pendamping') && ! $this->auth_lib->tujuan_aman($this->input->get_post('next', TRUE))) {
            return 'pendamping';
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

    /* ======================================================= LUPA SANDI ===
     |  Pembeli/penjual memulihkan akunnya sendiri lewat WhatsApp atau email.
     |  Sebelumnya satu-satunya jalan adalah meminta admin.
     | ==================================================================== */

    public function lupa()
    {
        if ($this->input->method() === 'post') {
            $isian = trim((string) $this->input->post('akun', TRUE));
            $user  = $this->cari_akun($isian);

            if ($user) {
                $t = $this->auth_lib->buat_token($user['id'], 'reset', 60);
                if ($t['ok']) {
                    $this->load->library('notifikasi_lib');
                    $tautan = site_url('auth/reset/' . $t['token']);
                    $isi = 'Halo ' . $user['name'] . ', ada permintaan atur ulang kata sandi akunmu.' . "\n\n"
                         . 'Buka tautan ini dalam 60 menit: ' . $tautan . "\n\n"
                         . 'Abaikan pesan ini kalau kamu tidak memintanya.';
                    $this->notifikasi_lib->wa($user['phone'], $isi, 'reset_sandi');
                    $this->notifikasi_lib->email($user['email'], 'Atur ulang kata sandi', $isi, 'reset_sandi');
                }
            }

            /* Pesan SAMA untuk akun ada maupun tidak - kalau berbeda, form
               ini bisa dipakai menebak email/nomor siapa saja yang terdaftar. */
            $this->session->set_flashdata('sukses',
                'Kalau akunnya terdaftar, tautan atur ulang sudah dikirim ke WhatsApp dan email akun itu. '
                . 'Belum sampai dalam beberapa menit? Hubungi admin.');
            return redirect('auth/lupa');
        }

        $this->load->view('index', array('pages' => 'v_lupa'));
    }

    public function reset($token = NULL)
    {
        $row = $token ? $this->auth_lib->cek_token('reset', $token) : NULL;
        if ( ! $row) {
            $this->session->set_flashdata('error', 'Tautan sudah kedaluwarsa atau pernah dipakai. Minta tautan baru.');
            return redirect('auth/lupa');
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('password',  'Kata sandi baru', 'required|min_length[8]');
            $this->form_validation->set_rules('password2', 'Ulangi kata sandi', 'required|matches[password]');
            $this->form_validation->set_message('min_length', '{field} minimal {param} karakter.');
            $this->form_validation->set_message('matches', 'Kata sandi tidak sama.');
            $this->form_validation->set_error_delimiters('<p class="field-error">', '</p>');

            if ($this->form_validation->run() && $this->auth_lib->pakai_token($row['id'])) {
                $this->db->where('id', (int) $row['user_id'])->update('users', array(
                    'password_hash' => password_hash((string) $this->input->post('password', FALSE), PASSWORD_DEFAULT),
                ));
                $this->auth_lib->masuk_id($row['user_id']);
                $this->session->set_flashdata('sukses', 'Kata sandi baru tersimpan. Kamu sudah masuk.');
                return redirect($this->sesudah_masuk());
            }
        }

        $this->load->view('index', array('pages' => 'v_reset', 'token' => $token));
    }

    /* ========================================================= OTP WA ===
     |  Masuk cukup dengan nomor WhatsApp + kode 6 angka. Untuk pengguna
     |  yang sering lupa kata sandi - dan WhatsApp selalu mereka buka.
     | ================================================================== */

    public function otp()
    {
        if ($this->auth_lib->user()) {
            return redirect($this->sesudah_masuk());
        }

        $tahap = $this->session->userdata('otp_uid') ? 'kode' : 'nomor';

        if ($this->input->method() === 'post' && $this->input->post('aksi') === 'kirim') {
            $nomor = $this->normalize_phone($this->input->post('phone', TRUE));
            $akun  = $this->db->where('phone', $nomor)->where('is_active', 1)
                ->order_by('last_login_at', 'DESC')->get('users')->result_array();

            if (count($akun) > 1) {
                $this->session->set_flashdata('error', 'Nomor ini dipakai lebih dari satu akun. Masuk dengan email.');
                return redirect('auth/login');
            }

            /* Nomor tidak terdaftar tetap "seolah-olah" dikirimi kode, supaya
               halaman ini tidak bisa dipakai mengecek nomor siapa yang
               punya akun. Kodenya memang tidak akan pernah cocok. */
            $uid = $akun ? (int) $akun[0]['id'] : 0;
            if ($uid) {
                $t = $this->auth_lib->buat_token($uid, 'otp', 5, TRUE);
                if ( ! $t['ok']) {
                    $this->session->set_flashdata('error', $t['pesan']);
                    return redirect('auth/otp' . $this->query_next());
                }
                $this->load->library('notifikasi_lib');
                $this->notifikasi_lib->wa($nomor,
                    'Kode masuk ' . $this->config->item('nama_brand') . ': ' . $t['token'] . "\n"
                    . 'Berlaku 5 menit. JANGAN berikan kode ini kepada siapa pun, termasuk yang mengaku petugas.',
                    'otp');
            }
            $this->session->set_userdata(array('otp_uid' => $uid, 'otp_nomor' => $nomor));
            $this->session->set_flashdata('sukses', 'Kode 6 angka sudah dikirim ke WhatsApp ' . $this->samarkan($nomor) . '.');
            return redirect('auth/otp' . $this->query_next());
        }

        if ($this->input->method() === 'post' && $this->input->post('aksi') === 'cek') {
            $uid  = (int) $this->session->userdata('otp_uid');
            $kode = preg_replace('/[^0-9]/', '', (string) $this->input->post('kode'));
            $row  = $uid ? $this->auth_lib->cek_token('otp', $kode, $uid) : NULL;

            if ($row && $this->auth_lib->pakai_token($row['id']) && $this->auth_lib->masuk_id($uid)) {
                $this->session->unset_userdata(array('otp_uid', 'otp_nomor'));
                return redirect($this->sesudah_masuk());
            }
            $this->session->set_flashdata('error', 'Kode salah atau sudah kedaluwarsa.');
            return redirect('auth/otp' . $this->query_next());
        }

        if ($this->input->get('ulang')) {
            $this->session->unset_userdata(array('otp_uid', 'otp_nomor'));
            return redirect('auth/otp' . $this->query_next());
        }

        $this->load->view('index', array(
            'pages' => 'v_otp',
            'tahap' => $tahap,
            'nomor' => $this->samarkan((string) $this->session->userdata('otp_nomor')),
            'next'  => $this->auth_lib->tujuan_aman($this->input->get_post('next', TRUE)),
        ));
    }

    /** Email, atau nomor WA yang hanya dipakai satu akun. */
    protected function cari_akun($isian)
    {
        if ($isian === '') {
            return NULL;
        }
        if (strpos($isian, '@') !== FALSE) {
            return $this->db->where('email', strtolower($isian))->where('is_active', 1)
                ->get('users')->row_array() ?: NULL;
        }
        $akun = $this->db->where('phone', $this->normalize_phone($isian))->where('is_active', 1)
            ->get('users')->result_array();
        return count($akun) === 1 ? $akun[0] : NULL;
    }

    protected function samarkan($nomor)
    {
        return strlen($nomor) > 6 ? substr($nomor, 0, 4) . str_repeat('•', strlen($nomor) - 7) . substr($nomor, -3) : $nomor;
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
