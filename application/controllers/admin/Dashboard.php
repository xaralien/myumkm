<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * admin/Dashboard - ringkasan marketplace.
 * Simpan di: application/controllers/admin/Dashboard.php
 *
 *   GET admin  (lewat routes.php)  -> ringkasan
 */
class Dashboard extends Admin_Controller {

    public function index()
    {
        $hari_ini = date('Y-m-d');

        $data['angka'] = array(
            'toko_aktif'    => $this->hitung('stores', array('is_active' => 1)),
            'toko_menunggu' => $this->hitung('stores', array('is_active' => 0)),
            'produk_aktif'  => $this->hitung('products', array('is_active' => 1)),
            'akun'          => $this->hitung('users', array()),

            // Pesanan yang sudah dibayar tapi belum disentuh penjual - ini
            // yang paling perlu diperhatikan admin, karena pembelinya
            // sudah mengeluarkan uang dan sedang menunggu.
            'perlu_diproses' => $this->db->where('payment_status', 'paid')
                                         ->where('order_status', 'pending')
                                         ->count_all_results('orders'),

            'pesanan_hari_ini' => $this->db->where('DATE(created_at)', $hari_ini)
                                           ->count_all_results('orders'),
        );

        /* Omzet dihitung dari pesanan LUNAS saja. Memasukkan pesanan yang
           belum dibayar membuat angkanya terlihat besar padahal uangnya
           belum ada. */
        $data['omzet_bulan'] = (int) $this->db
            ->select_sum('total')
            ->where('payment_status', 'paid')
            ->where('created_at >=', date('Y-m-01 00:00:00'))
            ->get('orders')->row()->total;

        $data['menunggu'] = $this->db
            ->select('s.id, s.name, s.created_at, u.name AS pemilik, u.email,
                      d.name AS district_name, r.name AS regency_name', FALSE)
            ->from('stores s')
            ->join('users u', 'u.id = s.user_id', 'left')
            ->join('districts d', 'd.id = s.district_id', 'left')
            ->join('regencies r', 'r.id = s.regency_id', 'left')
            ->where('s.is_active', 0)
            ->order_by('s.created_at', 'ASC')
            ->limit(10)
            ->get()->result_array();

        $data['pesanan'] = $this->db
            ->select('o.order_number, o.total, o.payment_status, o.order_status, o.created_at,
                      s.name AS store_name', FALSE)
            ->from('orders o')
            ->join('stores s', 's.id = o.store_id', 'left')
            ->order_by('o.created_at', 'DESC')
            ->limit(8)
            ->get()->result_array();

        $this->render('admin/v_dashboard', $data);
    }

    protected function hitung($tabel, array $where)
    {
        if ($where) {
            $this->db->where($where);
        }
        return (int) $this->db->count_all_results($tabel);
    }

    protected function render($view, $data = array())
    {
        $data['me'] = $this->me;
        $this->load->view('admin/v_admin_header', $data);
        $this->load->view($view, $data);
        $this->load->view('admin/v_admin_footer');
    }
}
