<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * admin/Pendamping - akun pendamping UMKM dan toko binaannya.
 *
 *   GET  admin/pendamping                 daftar pendamping + penugasan toko
 *   POST admin/pendamping/angkat          jadikan akun (email) pendamping
 *   POST admin/pendamping/cabut/{id}      kembalikan jadi pengguna biasa
 *   POST admin/pendamping/tetapkan        store_id + pendamping_id (0 = lepas)
 */
class Pendamping extends Admin_Controller {

    public function index()
    {
        $cari = trim((string) $this->input->get('q', TRUE));

        // Daftar pendamping diambil LEBIH DULU - query builder hanya bisa
        // menyusun satu query sekaligus.
        $pendamping = $this->db->select('u.id, u.name, u.email, u.phone,
                (SELECT COUNT(*) FROM stores s WHERE s.pendamping_id = u.id) AS jml_toko,
                (SELECT COUNT(*) FROM pendampingan_catatan c WHERE c.pendamping_id = u.id
                   AND c.tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AS catatan_30', FALSE)
            ->where('u.role', 'pendamping')->order_by('u.name')->get('users u')->result_array();

        $this->db->select('s.id, s.name, s.slug, s.pendamping_id, r.name AS regency_name, d.name AS district_name', FALSE)
            ->from('stores s')->join('regencies r', 'r.id = s.regency_id', 'left')->join('districts d', 'd.id = s.district_id', 'left')
            ->where('s.is_active', 1);
        if ($cari !== '') {
            $this->db->group_start()->like('s.name', $cari)->or_like('r.name', $cari)->or_like('d.name', $cari)->group_end();
        }
        $this->render('admin/v_pendamping', array(
            'cari'       => $cari,
            'pendamping' => $pendamping,
            'toko'       => $this->db->order_by('s.pendamping_id IS NULL', 'DESC', FALSE)->order_by('s.name')->limit(300)->get()->result_array(),
        ));
    }

    public function angkat()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $email = strtolower(trim((string) $this->input->post('email', TRUE)));
        $u = $this->db->where('email', $email)->get('users')->row_array();
        if ( ! $u) {
            $this->session->set_flashdata('error', 'Akun ' . $email . ' tidak ditemukan. Minta pendampingnya mendaftar dulu.');
        } elseif ($u['role'] === 'admin') {
            $this->session->set_flashdata('error', 'Admin tidak perlu dijadikan pendamping - admin sudah bisa membuka panel pendamping.');
        } else {
            $this->db->where('id', (int) $u['id'])->update('users', array('role' => 'pendamping'));
            $this->session->set_flashdata('sukses', $u['name'] . ' sekarang pendamping. Panelnya ada di ' . site_url('pendamping') . '.');
        }
        return redirect('admin/pendamping');
    }

    public function cabut($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $this->db->where('id', (int) $id)->where('role', 'pendamping')->update('users', array('role' => 'user'));
        $this->db->where('pendamping_id', (int) $id)->update('stores', array('pendamping_id' => NULL));
        $this->session->set_flashdata('sukses', 'Peran pendamping dicabut dan toko binaannya dilepas.');
        return redirect('admin/pendamping');
    }

    public function tetapkan()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $sid = (int) $this->input->post('store_id');
        $pid = (int) $this->input->post('pendamping_id');
        if ($pid && ! $this->db->where('id', $pid)->where('role', 'pendamping')->count_all_results('users')) {
            show_404();
        }
        $this->db->where('id', $sid)->update('stores', array('pendamping_id' => $pid ?: NULL));
        $this->session->set_flashdata('sukses', $pid ? 'Pendamping ditetapkan.' : 'Toko dilepas dari pendamping.');
        return redirect('admin/pendamping' . ($this->input->post('q') ? '?q=' . rawurlencode($this->input->post('q')) : ''));
    }
}
