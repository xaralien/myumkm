<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Controller - kelas dasar untuk halaman yang butuh login.
 * Simpan di: application/core/MY_Controller.php
 *
 * Tiga tingkat:
 *   Member_Controller  - siapa pun yang sudah login (profil, riwayat pesanan)
 *   Seller_Controller  - sudah login DAN punya toko
 *   Admin_Controller   - peran admin
 */

class Member_Controller extends CI_Controller {

    protected $me;
    protected $akun;

    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('auth_lib', 'session', 'form_validation'));
        $this->load->helper(array('url', 'form', 'money'));

        $this->me = $this->auth_lib->user();

        if ( ! $this->me) {
            $this->session->set_flashdata('error', 'Silakan masuk dulu.');
            // Kembali ke halaman yang tadi dituju setelah login.
            redirect('auth/login?next=' . rawurlencode(uri_string()));
        }

        $this->akun = $this->auth_lib->row();

        // Akun dihapus atau dinonaktifkan admin saat sesinya masih hidup.
        if ( ! $this->akun || ! (int) $this->akun['is_active']) {
            $this->auth_lib->logout();
            $this->session->set_flashdata('error', 'Akun ini tidak aktif.');
            redirect('auth/login');
        }
    }
}


class Admin_Controller extends CI_Controller {

    protected $me;

    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('auth_lib', 'session', 'form_validation'));
        $this->load->helper(array('url', 'form', 'money'));

        $this->me = $this->auth_lib->user();

        if ( ! $this->me || $this->me['role'] !== 'admin') {
            $this->session->set_flashdata('error', 'Silakan masuk sebagai admin.');
            redirect('auth/login');
        }

        /* Setiap aksi POST admin dicatat otomatis di log audit - termasuk
           halaman admin yang ditambahkan nanti, tanpa perlu ingat memanggil
           pencatat di tiap method. Isian rahasia tidak ikut dicatat. */
        if ($this->input->method() === 'post') {
            $this->load->library('audit_lib');
            $seg   = $this->uri->segment_array();
            $objek = isset($seg[2]) ? $seg[2] : NULL;
            $id    = NULL;
            foreach (array_reverse($seg) as $x) {
                if (ctype_digit((string) $x)) { $id = (int) $x; break; }
            }
            $kunci = array();
            foreach ((array) $this->input->post() as $k => $v) {
                if (preg_match('/csrf|pass|sandi|token|kunci|secret/i', $k)) {
                    continue;
                }
                $kunci[] = $k . (is_scalar($v) && mb_strlen((string) $v) <= 40 ? '=' . $v : '');
            }
            $this->audit_lib->catat(implode('/', array_slice($seg, 1, 2)) ?: 'admin', $objek, $id,
                                    $kunci ? implode(', ', $kunci) : NULL);
        }
    }

    protected function render($view, $data = array())
    {
        $data['me'] = $this->me;
        $this->load->view('admin/v_admin_header', $data);
        $this->load->view($view, $data);
        $this->load->view('admin/v_admin_footer');
    }
}


class Seller_Controller extends CI_Controller {

    protected $me;
    protected $store;

    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('auth_lib', 'session', 'form_validation'));
        $this->load->helper(array('url', 'form', 'money'));

        $this->me = $this->auth_lib->user();

        if ( ! $this->me) {
            $this->session->set_flashdata('error', 'Silakan masuk dulu.');
            redirect('auth/login?next=' . rawurlencode(uri_string()));
        }

        /* Yang menentukan akses panel penjual adalah PUNYA TOKO, bukan
           peran. Sebelumnya dicek role === 'seller' - sekarang semua akun
           berperan 'user', dan yang punya toko otomatis bisa masuk. */
        $this->store = $this->auth_lib->store();

        if ( ! $this->store) {
            $this->session->set_flashdata('info', 'Buka tokomu dulu untuk mulai berjualan.');
            redirect('akun/buka_toko');
        }

        /* Toko yang BELUM disetujui admin tetap boleh masuk panel - pemiliknya
           bisa menyiapkan produk sambil menunggu. Produknya tidak tampil di
           katalog, karena semua query katalog mensyaratkan s.is_active = 1. */
    }

    protected function render($view, $data = array())
    {
        $data['me'] = $this->me;
        $data['store'] = $this->store;
        $this->load->view('admin/v_admin_header', $data);
        $this->load->view($view, $data);
        $this->load->view('admin/v_admin_footer');
    }
}
