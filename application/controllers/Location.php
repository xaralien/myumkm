<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Location - menyimpan & menghapus pilihan wilayah pengunjung.
 * Simpan di application/controllers/Location.php
 *
 *   POST location/set     district_id
 *   GET  location/clear
 */
class Location extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('location_lib', 'session'));
        $this->load->helper('url');
    }

    public function set()
    {
        $ok = $this->location_lib->set($this->input->post('district_id'));

        if ($this->input->is_ajax_request()) {
            return $this
                ->output
                ->set_status_header($ok ? 200 : 422)
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'ok' => $ok,
                    'label' => $ok ? $this->location_lib->label() : NULL,
                    'message' => $ok ? NULL : 'Kecamatan tidak ditemukan.',
                    'csrf_name' => $this->security->get_csrf_token_name(),
                    'csrf_hash' => $this->security->get_csrf_hash(),
                )));
        }

        if (!$ok) {
            $this->session->set_flashdata('error', 'Kecamatan tidak ditemukan.');
        }
        return redirect($this->input->post('kembali') ?: 'shop');
    }

    public function clear()
    {
        $this->location_lib->clear();
        return redirect('shop');
    }

    public function titik()
    {
        $lat = $this->input->post('lat');
        $lng = $this->input->post('lng');

        // Titik di luar Indonesia hampir pasti salah - biasanya karena
        // lat dan lng tertukar saat dikirim.
        if (!is_numeric($lat) ||
                !is_numeric($lng) ||
                $lat < -11 ||
                $lat > 6 ||
                $lng < 95 ||
                $lng > 141) {
            return $this
                ->output
                ->set_status_header(422)
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'ok' => FALSE,
                    'pesan' => 'Lokasi tidak terbaca dengan benar.',
                )));
        }

        /* Titik ini adalah TUJUAN PENGIRIMAN, bukan sekadar posisi pembeli.
           Keduanya sering sama (beli untuk diri sendiri), tapi tidak selalu:
           kado ke kota lain dikirim ke sana, dan toko dekat tujuan yang
           lebih masuk akal - bukan toko dekat pembelinya. */
        $this->session->set_userdata('dekat', array(
            'lat'   => (float) $lat,
            'lng'   => (float) $lng,
            'label' => 'Lokasi saya',
            'dari'  => 'gps',
        ));

        return $this
            ->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'ok' => TRUE,
                'csrf_name' => $this->security->get_csrf_token_name(),
                'csrf_hash' => $this->security->get_csrf_hash(),
            )));
    }

    /**
     * Pilih tujuan pengiriman dari buku alamat. POST location/tujuan/{id}
     *
     * Alamat tersimpan yang punya koordinat dipakai langsung. Yang belum
     * punya koordinat tetap diterima - kecamatannya saja sudah cukup untuk
     * menghitung ongkir, hanya pengurutan jaraknya yang tidak seakurat
     * titik peta.
     */
    public function tujuan($id = NULL)
    {
        $this->load->library('auth_lib');
        $uid = $this->auth_lib->id();

        if ( ! $uid) {
            show_404();
        }

        $this->load->model('address_model');
        $a = $this->address_model->milik($id, $uid);

        if ( ! $a) {
            show_404();
        }

        $this->session->set_userdata('dekat', array(
            'lat'         => $a['latitude'] !== NULL ? (float) $a['latitude'] : NULL,
            'lng'         => $a['longitude'] !== NULL ? (float) $a['longitude'] : NULL,
            'label'       => $a['label'],
            'dari'        => 'alamat',
            'alamat_id'   => (int) $a['id'],
            'district_id' => (int) $a['district_id'],
            'wilayah'     => trim($a['district_name'] . ', ' . $a['regency_name'], ', '),
        ));

        return redirect($this->input->post('balik') ?: 'shop');
    }

    /**
     * Hapus titik lokasi. GET location/lupakan_titik
     */
    public function lupakan_titik()
    {
        $this->session->unset_userdata('dekat');
        return redirect('shop');
    }
}
