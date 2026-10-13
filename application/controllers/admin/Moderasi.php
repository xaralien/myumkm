<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * admin/Moderasi - produk, ulasan, dan laporan pengguna.
 *
 *   GET  admin/moderasi?q=&saring=dilaporkan|diturunkan   produk
 *   POST admin/moderasi/turunkan/{id}      alasan
 *   POST admin/moderasi/pulihkan/{id}
 *   GET  admin/moderasi/ulasan?saring=dilaporkan|disembunyikan|rendah
 *   POST admin/moderasi/ulasan_tukar/{id}  sembunyikan / tampilkan
 *   POST admin/moderasi/laporan/{id}       status=ditindak|diabaikan
 */
class Moderasi extends Admin_Controller {

    public function index()
    {
        $q = trim((string) $this->input->get('q', TRUE));
        $saring = $this->input->get('saring');

        $this->db->select('p.id, p.name, p.slug, p.price, p.image, p.is_active, p.diturunkan_at, p.diturunkan_alasan,
                           s.name AS store_name, s.slug AS store_slug,
                           (SELECT COUNT(*) FROM laporan l WHERE l.jenis = \'produk\' AND l.objek_id = p.id AND l.status = \'baru\') AS laporan', FALSE)
            ->from('products p')->join('stores s', 's.id = p.store_id');
        if ($q !== '') {
            $this->db->group_start()->like('p.name', $q)->or_like('s.name', $q)->or_like('p.description', $q)->group_end();
        }
        if ($saring === 'dilaporkan') {
            $this->db->having('laporan >', 0);
        } elseif ($saring === 'diturunkan') {
            $this->db->where('p.diturunkan_at IS NOT NULL', NULL, FALSE);
        }
        $produk = $this->db->order_by('laporan', 'DESC')->order_by('p.id', 'DESC')->limit(100)->get()->result_array();

        $this->render('admin/v_moderasi', array(
            'tab'     => 'produk',
            'q'       => $q,
            'saring'  => $saring,
            'produk'  => $produk,
            'laporan' => $this->laporan_baru('produk'),
        ));
    }

    public function turunkan($id = NULL)
    {
        $this->wajib_post();
        $p = $this->db->select('p.*, s.phone AS store_phone, s.name AS store_name', FALSE)->from('products p')
            ->join('stores s', 's.id = p.store_id')->where('p.id', (int) $id)->get()->row_array();
        if ( ! $p) {
            show_404();
        }
        $alasan = mb_substr(trim((string) $this->input->post('alasan', TRUE)), 0, 255);
        if ($alasan === '') {
            $this->session->set_flashdata('error', 'Tulis alasan penurunan - penjual perlu tahu apa yang dilanggar.');
            return redirect('admin/moderasi');
        }
        $this->db->where('id', (int) $p['id'])->update('products', array(
            'is_active' => 0, 'diturunkan_at' => date('Y-m-d H:i:s'), 'diturunkan_alasan' => $alasan,
        ));
        $this->db->where('jenis', 'produk')->where('objek_id', (int) $p['id'])->where('status', 'baru')
            ->update('laporan', array('status' => 'ditindak'));

        $this->load->library('notifikasi_lib');
        $this->notifikasi_lib->wa($p['store_phone'], 'Produk "' . $p['name'] . '" di ' . $p['store_name']
            . ' diturunkan admin karena: ' . $alasan . '. Perbaiki produknya lalu hubungi admin untuk ditayangkan lagi.', 'produk_diturunkan');

        $this->session->set_flashdata('sukses', '"' . $p['name'] . '" diturunkan dan penjual sudah diberi tahu.');
        return redirect('admin/moderasi');
    }

    public function pulihkan($id = NULL)
    {
        $this->wajib_post();
        $this->db->where('id', (int) $id)->update('products', array('diturunkan_at' => NULL, 'diturunkan_alasan' => NULL));
        $this->session->set_flashdata('sukses', 'Produk dipulihkan. Penjual bisa menayangkannya lagi.');
        return redirect('admin/moderasi?saring=diturunkan');
    }

    public function ulasan()
    {
        $saring = $this->input->get('saring');
        $this->db->select('r.*, p.name AS produk_nama, s.name AS store_name,
                           (SELECT COUNT(*) FROM laporan l WHERE l.jenis = \'ulasan\' AND l.objek_id = r.id AND l.status = \'baru\') AS laporan', FALSE)
            ->from('reviews r')->join('products p', 'p.id = r.product_id', 'left')->join('stores s', 's.id = r.store_id', 'left');
        if ($saring === 'dilaporkan') {
            $this->db->having('laporan >', 0);
        } elseif ($saring === 'disembunyikan') {
            $this->db->where('r.is_hidden', 1);
        } elseif ($saring === 'rendah') {
            $this->db->where('r.rating <=', 2);
        }
        $this->render('admin/v_moderasi', array(
            'tab'     => 'ulasan',
            'saring'  => $saring,
            'ulasan'  => $this->db->order_by('laporan', 'DESC')->order_by('r.id', 'DESC')->limit(100)->get()->result_array(),
            'laporan' => $this->laporan_baru('ulasan'),
        ));
    }

    public function ulasan_tukar($id = NULL)
    {
        $this->wajib_post();
        $r = $this->db->where('id', (int) $id)->get('reviews')->row_array();
        if ( ! $r) {
            show_404();
        }
        $sembunyi = ! (int) $r['is_hidden'];
        $this->db->where('id', (int) $r['id'])->update('reviews', array(
            'is_hidden'     => $sembunyi ? 1 : 0,
            'hidden_alasan' => $sembunyi ? (mb_substr(trim((string) $this->input->post('alasan', TRUE)), 0, 255) ?: 'Melanggar pedoman') : NULL,
        ));
        if ($sembunyi) {
            $this->db->where('jenis', 'ulasan')->where('objek_id', (int) $r['id'])->where('status', 'baru')
                ->update('laporan', array('status' => 'ditindak'));
        }
        // Rating produk & toko dihitung ulang tanpa ulasan tersembunyi.
        $this->load->model('review_model');
        $this->review_model->hitung_ulang($r['product_id'], $r['store_id']);

        $this->session->set_flashdata('sukses', $sembunyi ? 'Ulasan disembunyikan dari publik.' : 'Ulasan ditampilkan lagi.');
        return redirect('admin/moderasi/ulasan');
    }

    public function laporan($id = NULL)
    {
        $this->wajib_post();
        $st = $this->input->post('status') === 'ditindak' ? 'ditindak' : 'diabaikan';
        $l = $this->db->where('id', (int) $id)->get('laporan')->row_array();
        $this->db->where('id', (int) $id)->update('laporan', array('status' => $st));
        $this->session->set_flashdata('sukses', 'Laporan ditandai ' . $st . '.');
        return redirect($l && $l['jenis'] === 'ulasan' ? 'admin/moderasi/ulasan' : 'admin/moderasi');
    }

    protected function laporan_baru($jenis)
    {
        return $this->db->select('l.*, u.name AS pelapor', FALSE)->from('laporan l')
            ->join('users u', 'u.id = l.user_id', 'left')
            ->where('l.status', 'baru')->where('l.jenis', $jenis)
            ->order_by('l.id', 'DESC')->limit(50)->get()->result_array();
    }

    protected function wajib_post()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
    }
}
