<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * penjual/Mulai - panduan langkah awal "Toko siap jualan".
 * Alamat: seller/mulai
 *
 * Penjual baru sering bingung harus mulai dari mana. Halaman ini memecahnya
 * jadi lima langkah berurutan, masing-masing satu tombol ke halaman yang
 * tepat dan (kalau ada) video/modul singkat dari Akademi.
 */
class Mulai extends Seller_Controller
{
    public function index()
    {
        $this->load->library('naik_kelas_lib');
        $this->render('penjual/v_mulai', array('l' => $this->naik_kelas_lib->langkah_awal($this->store['id'])));
    }
}
