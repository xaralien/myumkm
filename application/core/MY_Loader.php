<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * MY_Loader - menyelaraskan zona waktu sesi MySQL dengan zona waktu PHP.
 *
 * Simpan di: application/core/MY_Loader.php
 *
 * Kolom seperti created_at diisi MySQL (DEFAULT CURRENT_TIMESTAMP) memakai
 * zona waktu server MySQL, sedangkan kode membandingkannya dengan date()
 * dari PHP. Kalau keduanya berbeda zona, perbandingan meleset berjam-jam.
 * Di sini setiap koneksi database yang dibuka - termasuk yang di-autoload -
 * diberi `SET time_zone` sesuai selisih zona PHP (config 'zona_waktu'),
 * jadi NOW()/CURRENT_TIMESTAMP selalu sama dengan date().
 *
 * Dipakai selisih jam (+07:00), bukan nama zona (Asia/Jakarta): nama zona
 * butuh tabel zona waktu MySQL yang sering belum diisi di hosting.
 */
class MY_Loader extends CI_Loader
{
    /** Koneksi yang sudah diselaraskan (spl_object_id => TRUE). */
    protected static $_sudah = array();

    public function database($params = '', $return = FALSE, $query_builder = NULL)
    {
        $hasil = parent::database($params, $return, $query_builder);

        if ($return === TRUE && is_object($hasil)) {
            $this->samakan_zona_waktu($hasil);
        } else {
            $CI = &get_instance();
            if (isset($CI->db) && is_object($CI->db)) {
                $this->samakan_zona_waktu($CI->db);
            }
        }
        return $hasil;
    }

    protected function samakan_zona_waktu($db)
    {
        $id = spl_object_id($db);
        if (empty($db->conn_id) || isset(self::$_sudah[$id]) || strpos((string) $db->dbdriver, 'mysql') === FALSE) {
            return;
        }
        // date('P') = selisih zona PHP saat ini, mis. "+07:00".
        $db->simple_query("SET time_zone = '" . date('P') . "'");
        self::$_sudah[$id] = TRUE;
    }
}
