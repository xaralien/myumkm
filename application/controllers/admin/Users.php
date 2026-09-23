<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * admin/Users - kelola akun.
 * Simpan di: application/controllers/admin/Users.php
 *
 *   GET  admin/users                daftar akun
 *   POST admin/users/toggle/{id}    aktif / nonaktif
 *   POST admin/users/peran/{id}     jadikan admin / kembalikan jadi pengguna
 *   POST admin/users/sandi/{id}     atur ulang password
 */
class Users extends Admin_Controller {

    public function index()
    {
        $cari = trim((string) $this->input->get('q', TRUE));

        $this->db
            ->select('u.id, u.name, u.email, u.phone, u.role, u.is_active, u.created_at, u.last_login_at,
                      s.id AS store_id, s.name AS store_name, s.is_active AS store_active,
                      (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS jml_pesanan', FALSE)
            ->from('users u')
            ->join('stores s', 's.user_id = u.id', 'left');

        if ($cari !== '') {
            $this->db->group_start()
                     ->like('u.name', $cari)->or_like('u.email', $cari)->or_like('s.name', $cari)
                     ->group_end();
        }

        $data = array(
            'users' => $this->db->order_by('u.id', 'DESC')->limit(200)->get()->result_array(),
            'cari'  => $cari,
        );
        $this->render('admin/v_users', $data);
    }

    public function toggle($id = NULL)
    {
        $u = $this->ambil($id);

        /* Tidak bisa menonaktifkan akun sendiri. Kalau dibiarkan, admin bisa
           mengunci dirinya keluar dari panel dalam satu klik - dan kalau dia
           satu-satunya admin, tidak ada yang bisa mengembalikannya selain
           lewat database. */
        if ((int) $u['id'] === (int) $this->me['id']) {
            $this->session->set_flashdata('error', 'Kamu tidak bisa menonaktifkan akunmu sendiri.');
            return redirect('admin/users');
        }

        $baru = $u['is_active'] ? 0 : 1;
        $this->db->where('id', $u['id'])->update('users', array('is_active' => $baru));

        $this->session->set_flashdata('sukses',
            'Akun ' . $u['email'] . ' ' . ($baru ? 'diaktifkan.' : 'dinonaktifkan.'));
        return redirect('admin/users');
    }

    public function peran($id = NULL)
    {
        $u = $this->ambil($id);

        if ((int) $u['id'] === (int) $this->me['id']) {
            $this->session->set_flashdata('error', 'Kamu tidak bisa mengubah peranmu sendiri.');
            return redirect('admin/users');
        }

        $baru = ($u['role'] === 'admin') ? 'user' : 'admin';

        // Harus selalu ada minimal satu admin yang aktif.
        if ($baru === 'user') {
            $sisa = $this->db->where('role', 'admin')
                             ->where('is_active', 1)
                             ->where('id !=', $u['id'])
                             ->count_all_results('users');
            if ($sisa < 1) {
                $this->session->set_flashdata('error',
                    'Ini admin aktif terakhir. Angkat admin lain dulu sebelum mencabut peran ini.');
                return redirect('admin/users');
            }
        }

        $this->db->where('id', $u['id'])->update('users', array('role' => $baru));

        $this->session->set_flashdata('sukses', $baru === 'admin'
            ? $u['email'] . ' sekarang admin.'
            : $u['email'] . ' dikembalikan jadi pengguna biasa.');
        return redirect('admin/users');
    }

    /**
     * Atur ulang password.
     *
     * Belum ada fitur "lupa password" lewat email, jadi ini satu-satunya
     * jalan resmi memulihkan akun yang terkunci.
     */
    public function sandi($id = NULL)
    {
        $u = $this->ambil($id);

        $baru = (string) $this->input->post('password', FALSE);

        if (strlen($baru) < 8) {
            $this->session->set_flashdata('error', 'Password baru minimal 8 karakter.');
            return redirect('admin/users');
        }

        $this->db->where('id', $u['id'])->update('users', array(
            'password_hash' => password_hash($baru, PASSWORD_DEFAULT),
        ));

        $this->session->set_flashdata('sukses',
            'Password ' . $u['email'] . ' diatur ulang. Sampaikan ke pemiliknya lewat jalur pribadi, '
            . 'dan minta dia menggantinya sendiri di halaman Profil.');
        return redirect('admin/users');
    }

    /* ------------------------------------------------------------------ */

    protected function ambil($id)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $u = $this->db->where('id', (int) $id)->get('users')->row_array();
        if ( ! $u) {
            show_404();
        }
        return $u;
    }

    protected function render($view, $data = array())
    {
        $data['me'] = $this->me;
        $this->load->view('admin/v_admin_header', $data);
        $this->load->view($view, $data);
        $this->load->view('admin/v_admin_footer');
    }
}
