<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Periode_lib - pilihan rentang tanggal laporan, dibaca dari ?periode=.
 * Dipakai pembukuan, laporan penjualan, dan analitik admin supaya pilihan
 * dan labelnya sama di mana-mana.
 */
class Periode_lib
{
    public function pilihan()
    {
        return array(
            'bulan_ini'  => 'Bulan ini',
            'bulan_lalu' => 'Bulan lalu',
            '7'          => '7 hari',
            '30'         => '30 hari',
            '90'         => '3 bulan',
            '365'        => '1 tahun',
        );
    }

    /** @return array ['kode', 'dari', 'sampai', 'label'] */
    public function baca($kode)
    {
        $kode = array_key_exists((string) $kode, $this->pilihan()) ? (string) $kode : 'bulan_ini';
        switch ($kode) {
            case 'bulan_lalu':
                $dari = date('Y-m-01', strtotime('first day of last month'));
                $sampai = date('Y-m-t', strtotime($dari));
                break;
            case 'bulan_ini':
                $dari = date('Y-m-01');
                $sampai = date('Y-m-d');
                break;
            default:
                $dari = date('Y-m-d', strtotime('-' . ((int) $kode - 1) . ' days'));
                $sampai = date('Y-m-d');
        }
        return array('kode' => $kode, 'dari' => $dari, 'sampai' => $sampai, 'label' => $this->pilihan()[$kode]);
    }
}
