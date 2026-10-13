<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Laporkan - pembeli melaporkan produk, toko, atau ulasan bermasalah.
 *
 *   POST laporkan   jenis, objek_id, alasan, keterangan, balik
 *
 * Tamu boleh melapor (penipuan sering ditemukan sebelum orang mendaftar),
 * tapi dibatasi 5 laporan per jam per sesi supaya tidak dipakai menyerang
 * toko pesaing beramai-ramai.
 */
class Laporkan extends CI_Controller
{
    public function index()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $this->load->library(array('session', 'auth_lib'));
        $balik = $this->auth_lib->tujuan_aman($this->input->post('balik', TRUE)) ?: '';

        $jenis = $this->input->post('jenis');
        $id    = (int) $this->input->post('objek_id');
        $tabel = array('produk' => 'products', 'toko' => 'stores', 'ulasan' => 'reviews');
        if ( ! isset($tabel[$jenis]) || ! $this->db->where('id', $id)->count_all_results($tabel[$jenis])) {
            show_404();
        }
        $alasan = in_array($this->input->post('alasan'), alasan_laporan(), TRUE) ? $this->input->post('alasan') : 'Lainnya';

        $riwayat = array_filter((array) $this->session->userdata('laporan_waktu'), function ($t) { return $t > time() - 3600; });
        if (count($riwayat) >= 5) {
            $this->session->set_flashdata('error', 'Terlalu banyak laporan. Coba lagi nanti.');
            return redirect($balik);
        }
        $riwayat[] = time();
        $this->session->set_userdata('laporan_waktu', array_values($riwayat));

        $this->db->insert('laporan', array(
            'jenis'      => $jenis,
            'objek_id'   => $id,
            'user_id'    => $this->auth_lib->id(),
            'alasan'     => $alasan,
            'keterangan' => mb_substr(trim((string) $this->input->post('keterangan', TRUE)), 0, 500) ?: NULL,
            'ip'         => $this->input->ip_address(),
        ));
        $this->session->set_flashdata('sukses', 'Terima kasih, laporanmu sudah kami terima dan akan diperiksa admin.');
        return redirect($balik);
    }
}
