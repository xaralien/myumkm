<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin > Toko - hanya admin yang boleh membuat akun penjual & tokonya.
 * Simpan di: application/controllers/admin/Stores.php
 *
 *   GET  admin/stores            daftar
 *   GET  admin/stores/form/{id}  form tambah/ubah
 *   POST admin/stores/save
 *   POST admin/stores/toggle/{id}
 */
class Stores extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('region_model');
    }

    public function index()
    {
        $data['stores'] = $this->db
            ->select('s.*, u.email, u.is_active AS user_active,
                      d.name AS district_name, r.name AS regency_name, p.name AS province_name', FALSE)
            ->from('stores s')
            ->join('users u', 'u.id = s.user_id')
            ->join('districts d', 'd.id = s.district_id', 'left')
            ->join('regencies r', 'r.id = s.regency_id', 'left')
            ->join('provinces p', 'p.id = s.province_id', 'left')
            ->order_by('s.created_at', 'DESC')
            ->get()->result_array();

        $this->render('admin/v_stores', $data);
    }

    public function form($id = NULL)
    {
        $data = array(
            'store'     => NULL,
            'provinces' => $this->region_model->provinces(),
        );

        if ($id) {
            $data['store'] = $this->db->select('s.*, u.email, u.name AS user_name', FALSE)
                ->from('stores s')->join('users u', 'u.id = s.user_id')
                ->where('s.id', (int) $id)->get()->row_array();
            if ( ! $data['store']) { show_404(); }
        }

        $this->render('admin/v_store_form', $data);
    }

    public function save()
    {
        $id = (int) $this->input->post('id');

        $this->form_validation->set_rules('store_name', 'Nama toko', 'required|trim|max_length[120]');
        $this->form_validation->set_rules('phone', 'WhatsApp toko', 'required|trim|callback_valid_phone');
        $this->form_validation->set_rules('address', 'Alamat', 'required|trim|max_length[255]');
        $this->form_validation->set_rules('district_id', 'Kecamatan', 'required|integer');
        $this->form_validation->set_rules('owner_name', 'Nama pemilik', 'required|trim|max_length[100]');

        // Email unik hanya diperiksa saat membuat baru.
        $aturan_email = 'required|trim|valid_email|max_length[150]';
        if ( ! $id) { $aturan_email .= '|is_unique[users.email]'; }
        $this->form_validation->set_rules('email', 'Email', $aturan_email);

        if ( ! $id) {
            $this->form_validation->set_rules('password', 'Password', 'required|min_length[8]');
        } else {
            // Saat mengubah, password boleh dikosongkan artinya tidak diganti.
            $this->form_validation->set_rules('password', 'Password', 'min_length[8]');
        }

        $this->form_validation->set_message('is_unique', 'Email ini sudah dipakai akun lain.');
        $this->form_validation->set_message('min_length', '{field} minimal {param} karakter.');
        $this->form_validation->set_error_delimiters('<p class="field-error">', '</p>');

        if ($this->form_validation->run() === FALSE) {
            return $this->form($id ?: NULL);
        }

        // Kecamatan diverifikasi ulang; id dari form bisa disunting.
        $wilayah = $this->region_model->district_full($this->input->post('district_id'));
        if ( ! $wilayah) {
            $this->session->set_flashdata('error', 'Kecamatan tidak valid.');
            return $this->form($id ?: NULL);
        }

        $this->db->trans_begin();

        if ($id) {
            $store = $this->db->where('id', $id)->get('stores')->row_array();
            if ( ! $store) { show_404(); }

            $u = array(
                'name'  => $this->input->post('owner_name', TRUE),
                'email' => $this->input->post('email', TRUE),
            );
            if ($this->input->post('password', FALSE)) {
                $u['password_hash'] = password_hash($this->input->post('password', FALSE), PASSWORD_DEFAULT);
            }
            $this->db->where('id', $store['user_id'])->update('users', $u);
            $user_id = (int) $store['user_id'];
        } else {
            $this->db->insert('users', array(
                'name'          => $this->input->post('owner_name', TRUE),
                'email'         => $this->input->post('email', TRUE),
                'password_hash' => password_hash($this->input->post('password', FALSE), PASSWORD_DEFAULT),
                'role'          => 'seller',
            ));
            $user_id = (int) $this->db->insert_id();
        }

        $data = array(
            'user_id'     => $user_id,
            'name'        => $this->input->post('store_name', TRUE),
            'slug'        => $this->slug_unik($this->input->post('store_name', TRUE), $id),
            'phone'       => $this->normalize_phone($this->input->post('phone', TRUE)),
            'address'     => $this->input->post('address', TRUE),
            'description' => $this->input->post('description', TRUE) ?: NULL,
            'province_id' => (int) $wilayah['province_id'],
            'regency_id'  => (int) $wilayah['regency_id'],
            'district_id' => (int) $wilayah['district_id'],
        );

        if ($id) {
            $this->db->where('id', $id)->update('stores', $data);
        } else {
            $this->db->insert('stores', $data);
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Gagal menyimpan toko.');
            return $this->form($id ?: NULL);
        }
        $this->db->trans_commit();

        $this->session->set_flashdata('sukses', 'Toko tersimpan.');
        return redirect('admin/stores');
    }

    public function toggle($id)
    {
        $store = $this->db->where('id', (int) $id)->get('stores')->row_array();
        if ( ! $store) { show_404(); }

        $baru = $store['is_active'] ? 0 : 1;
        $this->db->where('id', $store['id'])->update('stores', array('is_active' => $baru));
        $this->db->where('id', $store['user_id'])->update('users', array('is_active' => $baru));

        $this->session->set_flashdata('sukses',
            $baru ? 'Toko diaktifkan.' : 'Toko dinonaktifkan. Produknya hilang dari katalog.');
        return redirect('admin/stores');
    }

    /* ------------------------------------------------------------------ */

    public function valid_phone($str)
    {
        $p = $this->normalize_phone($str);
        if (preg_match('/^628[0-9]{7,11}$/', $p)) { return TRUE; }
        $this->form_validation->set_message('valid_phone', '{field} tidak valid. Contoh: 081234567890');
        return FALSE;
    }

    protected function normalize_phone($phone)
    {
        $p = preg_replace('/[^0-9]/', '', $phone);
        if (substr($p, 0, 1) === '0')  { return '62' . substr($p, 1); }
        if (substr($p, 0, 2) === '62') { return $p; }
        return '62' . $p;
    }

    protected function slug_unik($nama, $abaikan_id = NULL)
    {
        $this->load->helper('text');
        $dasar = url_title($nama, '-', TRUE);
        $slug  = $dasar;
        $n     = 2;

        while (TRUE) {
            $this->db->where('slug', $slug);
            if ($abaikan_id) { $this->db->where('id !=', (int) $abaikan_id); }
            if ( ! $this->db->count_all_results('stores')) { return $slug; }
            $slug = $dasar . '-' . $n++;
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
