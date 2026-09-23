<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * money_helper - simpan di application/helpers/money_helper.php
 * Muat dengan: $this->load->helper('money');
 */

if ( ! function_exists('rupiah'))
{
    /** 285000 -> "Rp 285.000" */
    function rupiah($angka, $prefix = 'Rp ')
    {
        return $prefix . number_format((int) $angka, 0, ',', '.');
    }
}

if ( ! function_exists('tgl_id'))
{
    /** '2026-08-06' -> "Kamis, 6 Agustus 2026" */
    function tgl_id($tanggal)
    {
        $hari  = array('Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu');
        $bulan = array(1=>'Januari','Februari','Maret','April','Mei','Juni',
                       'Juli','Agustus','September','Oktober','November','Desember');
        $ts = strtotime($tanggal);
        if ( ! $ts) { return $tanggal; }
        return $hari[(int) date('w', $ts)] . ', ' . (int) date('j', $ts)
             . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    }
}

if ( ! function_exists('label_status'))
{
    function label_status($status)
    {
        $peta = array(
            'pending'    => 'Menunggu diproses',
            'confirmed'  => 'Diproses penjual',
            'preparing'  => 'Diproses penjual',
            'delivering' => 'Dikirim',
            'delivered'  => 'Selesai',
            'cancelled'  => 'Dibatalkan',
        );
        return isset($peta[$status]) ? $peta[$status] : $status;
    }
}

if ( ! function_exists('label_bayar'))
{
    function label_bayar($status)
    {
        $peta = array(
            'unpaid'  => 'Belum dibayar',
            'paid'    => 'Lunas',
            'expired' => 'Kedaluwarsa',
            'failed'  => 'Gagal',
        );
        return isset($peta[$status]) ? $peta[$status] : $status;
    }
}
