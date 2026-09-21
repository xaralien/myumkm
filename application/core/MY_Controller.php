<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Controller - kelas dasar untuk halaman yang butuh login.
 * Simpan di: application/core/MY_Controller.php
 *
 * CodeIgniter memuat file ini otomatis karena awalannya MY_ (lihat
 * $config['subclass_prefix'] di config.php).
 */
class Admin_Controller extends CI_Controller {

    protected $me;

    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('auth_lib', 'session', 'form_validation'));
        $this->load->helper(array('url', 'form', 'money'));

        $this->me = $this->auth_lib->user();

        if ( ! $this->me || $this->me['role'] !== 'admin') {
            $this->session->set_flashdata('error', 'Silakan login sebagai admin.');
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

        if ( ! $this->me || $this->me['role'] !== 'seller') {
            $this->session->set_flashdata('error', 'Silakan login sebagai penjual.');
            redirect('auth/login');
        }

        $this->store = $this->auth_lib->store();

        if ( ! $this->store) {
            // Akun penjual tanpa toko tidak bisa berbuat apa-apa. Ini terjadi
            // kalau admin membuat user tapi lupa membuat tokonya.
            show_error('Akun ini belum punya toko. Hubungi admin.', 403, 'Toko belum ada');
        }
    }
}
