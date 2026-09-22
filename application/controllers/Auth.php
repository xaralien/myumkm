<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Auth - masuk, daftar, keluar.
 *
 *   GET/POST auth/login      ?next=jalur/tujuan
 *   GET/POST auth/register   ?next=jalur/tujuan
 *   GET/POST auth/logout
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
