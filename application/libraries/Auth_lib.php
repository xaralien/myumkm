<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth_lib - login admin & penjual.
 * Simpan di: application/libraries/Auth_lib.php
 *
 * Pembeli TIDAK punya akun. Hanya admin dan penjual yang login ke sini.
 */
class Auth_lib {

    const KEY = 'auth_user';

    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library('session');
    }

    /**
     * @return bool
     */
    public function login($email, $password)
    {
        $user = $this->CI->db->where('email', $email)
                             ->where('is_active', 1)
                             ->get('users')->row_array();

        /* password_verify dijalankan walaupun user tidak ada, memakai hash
           palsu. Kalau tidak, waktu respons untuk email yang ada dan tidak
           ada jadi berbeda, dan itu bisa dipakai menebak email terdaftar. */
        $hash = $user ? $user['password_hash'] : '$2y$10$usermissingusermissingusermissingusermissingusermissingus';

        if ( ! password_verify($password, $hash) || ! $user) {
            return FALSE;
        }

        // Naikkan kekuatan hash otomatis kalau standar PHP sudah berubah.
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $this->CI->db->where('id', $user['id'])->update('users', array(
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ));
        }

        // Cegah session fixation: ganti id session setelah login berhasil.
        $this->CI->session->sess_regenerate(TRUE);

        $this->CI->session->set_userdata(self::KEY, array(
            'id'    => (int) $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ));

        $this->CI->db->where('id', $user['id'])
                     ->update('users', array('last_login_at' => date('Y-m-d H:i:s')));
        return TRUE;
    }

    public function logout()
    {
        $this->CI->session->unset_userdata(self::KEY);
        $this->CI->session->sess_regenerate(TRUE);
    }

    public function user()
    {
        return $this->CI->session->userdata(self::KEY) ?: NULL;
    }

    public function id()
    {
        $u = $this->user();
        return $u ? (int) $u['id'] : NULL;
    }

    public function is($role)
    {
        $u = $this->user();
        return $u && $u['role'] === $role;
    }

    /** Toko milik penjual yang sedang login. */
    public function store()
    {
        $id = $this->id();
        if ( ! $id) {
            return NULL;
        }
        return $this->CI->db->where('user_id', $id)->get('stores')->row_array() ?: NULL;
    }
}
