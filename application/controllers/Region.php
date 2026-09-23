<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Region - data wilayah bertingkat untuk dropdown.
 * Simpan di application/controllers/Region.php
 *
 *   GET region/regencies/{province_id}
 *   GET region/districts/{regency_id}
 *   GET region/search?q=batam
 *
 * Dropdown diisi lewat AJAX, bukan sekaligus di HTML. Data kecamatan
 * Indonesia ada ~7.277 baris - memuat semuanya di setiap halaman akan
 * membengkakkan HTML sampai ratusan kilobyte.
 */
class Region extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('region_model');
        $this->load->helper('url');
    }

    protected function json($data)
    {
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    public function provinces()
    {
        return $this->json($this->region_model->provinces());
    }

    public function regencies($province_id = 0)
    {
        return $this->json($this->region_model->regencies($province_id));
    }

    public function districts($regency_id = 0)
    {
        return $this->json($this->region_model->districts($regency_id));
    }

    public function search()
    {
        $q = trim((string) $this->input->get('q', TRUE));
        if (mb_strlen($q) < 3) {
            // Di bawah 3 huruf, hasilnya terlalu banyak dan tidak berguna.
            return $this->json(array());
        }
        return $this->json($this->region_model->search_districts($q));
    }

    /**
     * Cocokkan nama wilayah dari peta ke id di database.
     * GET region/cocok?provinsi=...&kabupaten=...&kecamatan=...
     *
     * Tiap parameter boleh berisi beberapa kandidat dipisah "|", karena
     * OpenStreetMap menaruh kecamatan di kolom yang berbeda-beda.
     */
    public function cocok()
    {
        $pisah = function ($v) {
            $out = array();
            foreach (explode('|', (string) $v) as $x) {
                $x = trim($x);
                if ($x !== '') {
                    $out[] = $x;
                }
            }
            // Maksimal 6 kandidat - membatasi kerja pencocokan dari kiriman
            // yang dibuat-buat.
            return array_slice($out, 0, 6);
        };

        $hasil = $this->region_model->cocokkan(
            $pisah($this->input->get('provinsi', TRUE)),
            $pisah($this->input->get('kabupaten', TRUE)),
            $pisah($this->input->get('kecamatan', TRUE))
        );

        $hasil['ok'] = ($hasil['district_id'] !== NULL);

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($hasil));
    }
}
