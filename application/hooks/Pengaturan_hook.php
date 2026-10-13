<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Pengaturan_hook - terapkan pengaturan dari panel admin ke config.
 *
 * Dipasang di config/hooks.php (post_controller_constructor). View lama
 * membaca $this->config->item('nama_brand') dan sejenisnya; dengan hook
 * ini nilainya ikut berubah begitu admin menyimpan Pengaturan, tanpa
 * mengubah setiap view satu per satu.
 */
class Pengaturan_hook
{
    public function terapkan()
    {
        $CI = &get_instance();
        if ( ! isset($CI->db)) {
            return;
        }

        $CI->load->library('pengaturan_lib');

        foreach (array('nama_brand', 'slogan', 'wa_admin') as $k) {
            $v = $CI->pengaturan_lib->semua()[$k] ?? NULL;
            if ($v !== NULL && $v !== '') {
                $CI->config->set_item($k, $v);
            }
        }
    }
}
