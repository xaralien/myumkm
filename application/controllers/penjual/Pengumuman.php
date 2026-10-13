<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * penjual/Pengumuman - tandai pengumuman admin sudah dibaca.
 *   POST seller/pengumuman/baca/{id}
 */
class Pengumuman extends Seller_Controller
{
    public function baca($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $this->db->where('broadcast_id', (int) $id)->where('store_id', (int) $this->store['id'])
            ->update('broadcast_toko', array('dibaca_at' => date('Y-m-d H:i:s')));
        $balik = $this->auth_lib->tujuan_aman($this->input->post('balik', TRUE));
        return redirect($balik ?: 'seller');
    }
}
