<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Audit_lib - catat siapa mengubah apa di panel admin.
 *
 * Simpan di: application/libraries/Audit_lib.php
 * Pakai    : $this->audit_lib->catat('toko.setujui', 'store', $id, 'Dapur Bu Siti');
 *
 * Kegagalan mencatat TIDAK boleh menggagalkan aksi yang dicatat - log audit
 * adalah pelengkap, bukan penjaga. Jadi galat database ditelan di sini.
 */
class Audit_lib
{
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('auth_lib');
    }

    public function catat($aksi, $objek = NULL, $objek_id = NULL, $detail = NULL)
    {
        if ( ! $this->CI->db->table_exists('audit_logs')) {
            return;
        }

        $u = $this->CI->auth_lib->user();

        $debug = $this->CI->db->db_debug;
        $this->CI->db->db_debug = FALSE;
        $this->CI->db->insert('audit_logs', array(
            'user_id'  => $u ? (int) $u['id'] : NULL,
            'nama'     => $u ? mb_substr($u['name'], 0, 100) : NULL,
            'aksi'     => mb_substr((string) $aksi, 0, 60),
            'objek'    => $objek ? mb_substr($objek, 0, 40) : NULL,
            'objek_id' => $objek_id ? (int) $objek_id : NULL,
            'detail'   => $detail !== NULL ? mb_substr((string) $detail, 0, 500) : NULL,
            'ip'       => $this->CI->input->ip_address(),
        ));
        $this->CI->db->db_debug = $debug;
    }
}
