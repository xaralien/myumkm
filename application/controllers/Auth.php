<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Auth - simpan di application/controllers/Auth.php
 *   GET/POST auth/login
 *   GET      auth/logout
 */
class Auth extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('auth_lib', 'session', 'form_validation'));
        $this->load->helper(array('url', 'form'));
    }

    public function login()
    {
        if ($this->auth_lib->user()) {
            return redirect($this->auth_lib->is('admin') ? 'admin' : 'seller');
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
                    return redirect($this->auth_lib->is('admin') ? 'admin' : 'seller');
                }
                // Pesan sengaja tidak memisahkan "email salah" dan "password salah".
                $this->session->set_flashdata('error', 'Email atau password salah.');
            }
        }

        $data['pages'] = 'v_login';
        $this->load->view('index', $data);
    }

    public function logout()
    {
        $this->auth_lib->logout();
        return redirect('auth/login');
    }
}
