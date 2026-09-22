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
}
