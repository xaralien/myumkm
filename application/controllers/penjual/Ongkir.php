<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * penjual/Ongkir - bandingkan tarif kurir.  Alamat: seller/ongkir
 *
 * Penjual memasukkan kode pos asal, tujuan, dan berat paket, lalu melihat
 * tarif beberapa kurir sekaligus (termurah di atas). Tanpa kunci API dari
 * admin, halaman tetap berguna: menampilkan tautan lacak resmi tiap kurir.
 */
class Ongkir extends Seller_Controller
{
    public function index()
    {
        $this->load->library('ekspedisi_lib');
        $f = array(
            'asal'   => preg_replace('/\D/', '', (string) $this->input->get('asal', TRUE)),
            'tujuan' => preg_replace('/\D/', '', (string) $this->input->get('tujuan', TRUE)),
            'berat'  => (int) $this->input->get('berat'),
        );
        $hasil = NULL;
        if ($f['asal'] !== '' || $f['tujuan'] !== '') {
            if (strlen($f['asal']) !== 5 || strlen($f['tujuan']) !== 5) {
                $hasil = 'Kode pos harus 5 angka.';
            } elseif ($f['berat'] < 1 || $f['berat'] > 50000) {
                $hasil = 'Isi berat paket dalam gram (1 - 50.000).';
            } else {
                $hasil = $this->ekspedisi_lib->tarif($f['asal'], $f['tujuan'], $f['berat']);
            }
        }
        $this->render('penjual/v_ongkir', array(
            'f'      => $f,
            'hasil'  => $hasil,
            'aktif'  => $this->ekspedisi_lib->api_aktif(),
            'kurir'  => $this->ekspedisi_lib->daftar(),
        ));
    }
}
