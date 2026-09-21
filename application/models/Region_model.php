<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Region_model - simpan di application/models/Region_model.php
 */
class Region_model extends CI_Model {

    public function provinces()
    {
        return $this->db->order_by('name', 'ASC')->get('provinces')->result_array();
    }

    public function regencies($province_id)
    {
        return $this->db->where('province_id', (int) $province_id)
                        ->order_by('name', 'ASC')
                        ->get('regencies')->result_array();
    }

    public function districts($regency_id)
    {
        return $this->db->where('regency_id', (int) $regency_id)
                        ->order_by('name', 'ASC')
                        ->get('districts')->result_array();
    }

    /**
     * Satu kecamatan lengkap dengan kabupaten dan provinsinya.
     * Dipakai untuk memvalidasi id yang datang dari cookie atau form -
     * sekaligus jadi bukti bahwa kecamatan itu memang ada.
     */
    public function district_full($district_id)
    {
        $row = $this->db
            ->select('d.id AS district_id, d.name AS district_name,
                      r.id AS regency_id,  r.name AS regency_name,
                      p.id AS province_id, p.name AS province_name', FALSE)
            ->from('districts d')
            ->join('regencies r', 'r.id = d.regency_id')
            ->join('provinces p', 'p.id = r.province_id')
            ->where('d.id', (int) $district_id)
            ->get()->row_array();

        return $row ?: NULL;
    }

    /** Cari kecamatan lintas wilayah, untuk kolom pencarian cepat di modal. */
    public function search_districts($q, $limit = 15)
    {
        return $this->db
            ->select('d.id, d.name AS district_name, r.name AS regency_name, p.name AS province_name', FALSE)
            ->from('districts d')
            ->join('regencies r', 'r.id = d.regency_id')
            ->join('provinces p', 'p.id = r.province_id')
            ->group_start()->like('d.name', $q)->or_like('r.name', $q)->group_end()
            ->order_by('d.name', 'ASC')
            ->limit((int) $limit)
            ->get()->result_array();
    }
}
