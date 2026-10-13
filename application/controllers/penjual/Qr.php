<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * penjual/Qr - kartu QR toko siap cetak.  Alamat: seller/qr
 *
 * Ditempel di etalase, gerobak, atau kemasan: pembeli yang memindai langsung
 * sampai di halaman toko. Jembatan dari pembeli offline ke toko online.
 */
class Qr extends Seller_Controller
{
    public function index()
    {
        $this->render('penjual/v_qr', array(
            'url'  => site_url('toko/' . $this->store['slug']),
            'toko' => $this->store,
        ));
    }
}
