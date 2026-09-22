<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Auth_lib - login & pendaftaran.
 * Simpan di: application/libraries/Auth_lib.php
 *
 * Peran hanya dua: 'admin' dan 'user'. Seorang user menjadi penjual kalau
 * punya toko - bukan karena perannya. Satu akun bisa belanja dan berjualan.
 */
class Auth_lib
{

    const KEY = 'auth_user';

    protected $CI;

    // Disimpan per permintaan supaya navbar, controller, dan view tidak
    // masing-masing menanyai database untuk data yang sama.
    protected $_row   = FALSE;
    protected $_store = FALSE;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('session');
    }

    /** @return bool */
    public function login($email, $password)
    {
        $user = $this->CI->db->where('email', trim($email))
            ->where('is_active', 1)
            ->get('users')->row_array();

        /* password_verify tetap dijalankan walau user tidak ada, memakai hash
           palsu. Kalau tidak, waktu respons untuk email yang terdaftar dan
           tidak terdaftar jadi berbeda - dan itu bisa dipakai menebak email. */
        $hash = $user ? $user['password_hash'] : '$2y$10$usermissingusermissingusermissingusermissingusermissingus';

        if (! password_verify($password, $hash) || ! $user) {
            return FALSE;
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $this->CI->db->where('id', $user['id'])->update('users', array(
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ));
        }

        $this->masuk($user);

        $this->CI->db->where('id', $user['id'])
            ->update('users', array('last_login_at' => date('Y-m-d H:i:s')));
        return TRUE;
    }

    /**
     * Daftarkan akun baru lalu langsung masukkan.
     *
     * @return array ['ok' => bool, 'pesan' => string]
     */
    public function register($name, $email, $phone, $password)
    {
        $email = strtolower(trim($email));

        if ($this->CI->db->where('email', $email)->count_all_results('users')) {
            return array('ok' => FALSE, 'pesan' => 'Email ini sudah terdaftar. Silakan masuk.');
        }

        /* db_debug dimatikan sesaat. Di mode development CodeIgniter
           menampilkan halaman galat database untuk insert yang gagal -
           padahal di sini kegagalan karena email ganda adalah hal wajar
           yang ingin ditangani sendiri, bukan dipamerkan. */
        $debug = $this->CI->db->db_debug;
        $this->CI->db->db_debug = FALSE;

        $ok = $this->CI->db->insert('users', array(
            'name'          => trim($name),
            'email'         => $email,
            'phone'         => $phone,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role'          => 'user',
            'is_active'     => 1,
        ));

        $this->CI->db->db_debug = $debug;

        /* Pengecekan di atas bisa kalah balapan dengan pendaftaran lain di
           detik yang sama. Indeks unik uq_users_email yang menjadi penjaga
           terakhir - insert-nya gagal, dan itu ditangani di sini. */
        if (! $ok) {
            return array('ok' => FALSE, 'pesan' => 'Email ini sudah terdaftar. Silakan masuk.');
        }

        $user = $this->CI->db->where('id', $this->CI->db->insert_id())->get('users')->row_array();
        $this->masuk($user);

        return array('ok' => TRUE, 'pesan' => '');
    }

    protected function masuk(array $user)
    {
        // Cegah session fixation: ganti id session setelah login.
        $this->CI->session->sess_regenerate(TRUE);

        $this->CI->session->set_userdata(self::KEY, array(
            'id'    => (int) $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ));

        $this->_row = $this->_store = FALSE;
    }

    public function logout()
    {
        $this->CI->session->unset_userdata(self::KEY);
        $this->CI->session->sess_regenerate(TRUE);
        $this->_row = $this->_store = FALSE;
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

    public function is_admin()
    {
        return $this->is('admin');
    }

    /** Baris lengkap akun yang sedang login, beserta nama wilayahnya. */
    public function row()
    {
        if ($this->_row !== FALSE) {
            return $this->_row;
        }

        $id = $this->id();
        $this->_row = $id ? ($this->CI->db
            ->select('u.*, d.name AS district_name, r.name AS regency_name', FALSE)
            ->from('users u')
            ->join('stores s', 's.user_id = u.id', 'left')
            ->join('districts d', 'd.id = s.district_id', 'left')
            ->join('regencies r', 'r.id = s.regency_id', 'left')
            ->where('u.id', $id)
            ->get()->row_array() ?: NULL) : NULL;

        return $this->_row;
    }

    /** Toko milik akun yang sedang login - termasuk yang belum disetujui. */
    public function store()
    {
        if ($this->_store !== FALSE) {
            return $this->_store;
        }

        $id = $this->id();
        $this->_store = $id ? ($this->CI->db
            ->select('s.*, d.name AS district_name, r.name AS regency_name', FALSE)
            ->from('stores s')
            ->join('districts d', 'd.id = s.district_id', 'left')
            ->join('regencies r', 'r.id = s.regency_id', 'left')
            ->where('s.user_id', $id)
            ->get()->row_array() ?: NULL) : NULL;

        return $this->_store;
    }

    /** Perbarui nama di sesi setelah profil diubah. */
    public function segarkan()
    {
        $this->_row = $this->_store = FALSE;
        $row = $this->row();
        if ($row) {
            $s = $this->user();
            $s['name'] = $row['name'];
            $this->CI->session->set_userdata(self::KEY, $s);
        }
    }

    /**
     * Tujuan setelah login. Hanya jalur di dalam situs ini yang diterima -
     * kalau ?next= diterima apa adanya, tautan login bisa dipakai untuk
     * mengarahkan orang ke situs palsu setelah mereka memasukkan password.
     */
    public function tujuan_aman($next)
    {
        $next = ltrim((string) $next, '/');

        if (
            $next === ''
            || strpos($next, '//') !== FALSE
            || strpos($next, '\\') !== FALSE
            || strpos($next, ':') !== FALSE
            || ! preg_match('#^[a-z0-9_/\-]+$#i', $next)
        ) {
            return NULL;
        }
        return $next;
    }
}
