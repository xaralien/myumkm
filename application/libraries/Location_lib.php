<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Location_lib - menyimpan pilihan wilayah pengunjung.
 *
 * Simpan di: application/libraries/Location_lib.php
 * Pakai    : $this->load->library('location_lib');
 *
 * Disimpan di DUA tempat:
 *   session -> dipakai selama menjelajah, cepat
 *   cookie  -> supaya pilihan bertahan setelah browser ditutup
 *
 * Kalau hanya session, pengunjung akan ditanya lokasi lagi setiap kali datang,
 * dan itu terasa seperti bug. Cookie disegarkan tiap kali dibaca.
 *
 * ID wilayah dari cookie SELALU diperiksa ulang ke database. Cookie bisa
 * disunting siapa saja, dan id yang tidak ada akan membuat query filter
 * mengembalikan hasil kosong tanpa penjelasan.
 */
class Location_lib {

    const KEY    = 'lokasi';
    const COOKIE = 'lokasi_id';
    const UMUR   = 15552000;   // 180 hari

    protected $CI;
    protected $cache = NULL;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library('session');
        $this->CI->load->model('region_model');
        $this->CI->load->helper('cookie');
    }

    /**
     * @return array|NULL ['province_id','province_name','regency_id',
     *                     'regency_name','district_id','district_name']
     */
    public function get()
    {
        if ($this->cache !== NULL) {
            return $this->cache ?: NULL;
        }

        $data = $this->CI->session->userdata(self::KEY);

        if ( ! $data) {
            // Belum ada di session - coba pulihkan dari cookie.
            $id = get_cookie(self::COOKIE);
            if ($id) {
                $data = $this->CI->region_model->district_full((int) $id);
                if ($data) {
                    $this->CI->session->set_userdata(self::KEY, $data);
                }
            }
        }

        $this->cache = $data ?: FALSE;
        return $data ?: NULL;
    }

    public function has()
    {
        return $this->get() !== NULL;
    }

    public function district_id()
    {
        $d = $this->get();
        return $d ? (int) $d['district_id'] : NULL;
    }

    public function regency_id()
    {
        $d = $this->get();
        return $d ? (int) $d['regency_id'] : NULL;
    }

    public function province_id()
    {
        $d = $this->get();
        return $d ? (int) $d['province_id'] : NULL;
    }

    /** Teks singkat untuk bilah lokasi: "Batam Kota, Kota Batam" */
    public function label()
    {
        $d = $this->get();
        return $d ? $d['district_name'] . ', ' . $d['regency_name'] : NULL;
    }

    /**
     * @return bool FALSE kalau id kecamatannya tidak ada di database
     */
    public function set($district_id)
    {
        $data = $this->CI->region_model->district_full((int) $district_id);
        if ( ! $data) {
            return FALSE;
        }

        $this->CI->session->set_userdata(self::KEY, $data);
        $this->cache = $data;

        set_cookie(array(
            'name'   => self::COOKIE,
            'value'  => (string) $data['district_id'],
            'expire' => self::UMUR,
            'path'   => '/',
            'httponly' => TRUE,
        ));
        return TRUE;
    }

    public function clear()
    {
        $this->CI->session->unset_userdata(self::KEY);
        delete_cookie(self::COOKIE);
        $this->cache = FALSE;
    }
}
