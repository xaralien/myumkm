<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * penjual/Akademi - belajar usaha 5 menit sehari.  Alamat: seller/akademi
 *
 *   GET  seller/akademi                  daftar modul + jadwal kelas
 *   GET  seller/akademi/modul/{slug}     isi modul + kuis
 *   POST seller/akademi/jawab/{slug}     kirim jawaban kuis
 *   POST seller/akademi/daftar/{id}      daftar kelas / konsultasi
 *   GET  seller/akademi/sertifikat       sertifikat (semua modul selesai)
 */
class Akademi extends Seller_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('akademi_lib');
    }

    public function index()
    {
        $modul = $this->akademi_lib->modul_aktif($this->store['id']);
        $this->render('penjual/v_akademi', array(
            'modul'   => $modul,
            'selesai' => count(array_filter(array_column($modul, 'selesai_at'))),
            'jadwal'  => $this->akademi_lib->jadwal_mendatang($this->store['id']),
        ));
    }

    public function modul($slug = NULL)
    {
        $m = $this->akademi_lib->modul($slug);
        if ( ! $m) {
            show_404();
        }
        $progres = $this->db->where('store_id', (int) $this->store['id'])->where('modul_id', (int) $m['id'])
            ->get('akademi_progres')->row_array();
        $this->render('penjual/v_akademi_modul', array(
            'm'       => $m,
            'soal'    => $this->akademi_lib->soal($m['id']),
            'embed'   => $this->akademi_lib->embed($m['video_url']),
            'progres' => $progres,
            'hasil'   => $this->session->flashdata('kuis_hasil'),
        ));
    }

    public function jawab($slug = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $m = $this->akademi_lib->modul($slug);
        if ( ! $m) {
            show_404();
        }
        $h = $this->akademi_lib->jawab($this->store['id'], $m, (array) $this->input->post('jawaban'));
        $this->session->set_flashdata('kuis_hasil', $h);
        $this->session->set_flashdata($h['lulus'] ? 'sukses' : 'error', $h['lulus']
            ? 'Selamat, modul selesai! Nilai ' . $h['skor'] . '.'
            : 'Benar ' . $h['benar'] . ' dari ' . $h['total'] . '. Baca lagi materinya, lalu coba sekali lagi.');
        return redirect('seller/akademi/modul/' . $m['slug']);
    }

    public function daftar($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $j = $this->db->where('id', (int) $id)->get('akademi_jadwal')->row_array();
        if ( ! $j) {
            show_404();
        }
        $peserta = $this->db->where('jadwal_id', (int) $j['id'])->count_all_results('akademi_daftar');
        if ($j['kuota'] !== NULL && $peserta >= (int) $j['kuota']) {
            $this->session->set_flashdata('error', 'Kuota kelas sudah penuh.');
        } else {
            $this->db->query('INSERT IGNORE INTO akademi_daftar (jadwal_id, store_id) VALUES (?, ?)', array((int) $j['id'], (int) $this->store['id']));
            $this->session->set_flashdata('sukses', 'Terdaftar di "' . $j['judul'] . '". Pengingat dikirim lewat WhatsApp.');
            $this->load->library('notifikasi_lib');
            $this->notifikasi_lib->wa($this->store['phone'], 'Kamu terdaftar di ' . $j['judul'] . ' pada '
                . tgl_id(substr($j['waktu'], 0, 10)) . ' pukul ' . substr($j['waktu'], 11, 5)
                . ($j['tempat'] ? ' - ' . $j['tempat'] : '') . '.', 'akademi_daftar');
        }
        return redirect('seller/akademi');
    }

    public function sertifikat()
    {
        $modul = $this->akademi_lib->modul_aktif($this->store['id']);
        $selesai = array_filter($modul, function ($m) { return ! empty($m['selesai_at']); });
        if ( ! $modul || count($selesai) < count($modul)) {
            $this->session->set_flashdata('error', 'Selesaikan semua modul dulu untuk mendapat sertifikat.');
            return redirect('seller/akademi');
        }
        $this->load->view('penjual/v_akademi_sertifikat', array(
            'toko'    => $this->store,
            'pemilik' => $this->me['name'],
            'modul'   => $modul,
            'tanggal' => max(array_column($selesai, 'selesai_at')),
            'brand'   => $this->config->item('nama_brand'),
        ));
    }
}
