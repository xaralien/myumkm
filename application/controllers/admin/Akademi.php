<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * admin/Akademi - kelola modul belajar, kuis, dan jadwal kelas.
 *
 *   GET  admin/akademi                      modul, jadwal, statistik
 *   GET  admin/akademi/modul/{id?}          formulir modul + soal
 *   POST admin/akademi/modul_simpan
 *   POST admin/akademi/modul_tukar/{id}     tampil / sembunyikan
 *   GET  admin/akademi/jadwal/{id?}         formulir jadwal + peserta
 *   POST admin/akademi/jadwal_simpan
 *   POST admin/akademi/jadwal_hapus/{id}
 */
class Akademi extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('akademi_lib');
    }

    public function index()
    {
        $this->render('admin/v_akademi', array(
            'modul'  => $this->db->select('m.*, (SELECT COUNT(*) FROM akademi_progres p WHERE p.modul_id = m.id) AS lulus,
                                          (SELECT COUNT(*) FROM akademi_soal s WHERE s.modul_id = m.id) AS jml_soal', FALSE)
                ->order_by('urutan', 'ASC')->order_by('id', 'ASC')->get('akademi_modul m')->result_array(),
            'jadwal' => $this->db->select('j.*, (SELECT COUNT(*) FROM akademi_daftar d WHERE d.jadwal_id = j.id) AS peserta', FALSE)
                ->order_by('waktu', 'DESC')->limit(30)->get('akademi_jadwal j')->result_array(),
            'toko_belajar' => (int) $this->db->select('COUNT(DISTINCT store_id) AS n', FALSE)->get('akademi_progres')->row()->n,
        ));
    }

    public function modul($id = NULL)
    {
        $m = $id ? $this->db->where('id', (int) $id)->get('akademi_modul')->row_array() : NULL;
        if ($id && ! $m) {
            show_404();
        }
        $this->render('admin/v_akademi_modul', array(
            'm'        => $m,
            'soal'     => $m ? $this->akademi_lib->soal($m['id']) : array(),
            'kategori' => Akademi_lib::$kategori,
        ));
    }

    public function modul_simpan()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $id    = (int) $this->input->post('id');
        $judul = mb_substr(trim((string) $this->input->post('judul', TRUE)), 0, 150);
        if ($judul === '') {
            $this->session->set_flashdata('error', 'Judul modul wajib diisi.');
            return redirect('admin/akademi/modul' . ($id ? '/' . $id : ''));
        }
        $video = trim((string) $this->input->post('video_url', TRUE));
        if ($video !== '' && ! $this->akademi_lib->embed($video)) {
            $this->session->set_flashdata('error', 'Tautan video harus dari YouTube atau Vimeo.');
            return redirect('admin/akademi/modul' . ($id ? '/' . $id : ''));
        }
        $kat = in_array($this->input->post('kategori'), Akademi_lib::$kategori, TRUE) ? $this->input->post('kategori') : 'Umum';

        $d = array(
            'judul'        => $judul,
            'kategori'     => $kat,
            'ringkas'      => mb_substr(trim((string) $this->input->post('ringkas', TRUE)), 0, 255) ?: NULL,
            'isi'          => html_aman($this->input->post('isi', FALSE)) ?: NULL,
            'video_url'    => $video ?: NULL,
            'durasi_menit' => max(1, min(120, (int) $this->input->post('durasi_menit'))),
            'urutan'       => (int) $this->input->post('urutan'),
            'is_active'    => $this->input->post('is_active') ? 1 : 0,
        );

        $this->db->trans_begin();
        if ($id) {
            $this->db->where('id', $id)->update('akademi_modul', $d);
        } else {
            $this->load->helper('text');
            $dasar = url_title(convert_accented_characters($judul), '-', TRUE) ?: 'modul';
            $slug = $dasar; $n = 2;
            while ($this->db->where('slug', $slug)->count_all_results('akademi_modul')) {
                $slug = $dasar . '-' . $n++;
            }
            $this->db->insert('akademi_modul', $d + array('slug' => $slug));
            $id = (int) $this->db->insert_id();
        }

        // Soal ditulis ulang seluruhnya - lebih sederhana daripada mencocokkan id.
        $this->db->where('modul_id', $id)->delete('akademi_soal');
        $tanya = (array) $this->input->post('soal_tanya', TRUE);
        $pil   = (array) $this->input->post('soal_pilihan', TRUE);
        $benar = (array) $this->input->post('soal_benar');
        $urut = 1;
        foreach ($tanya as $i => $t) {
            $t = trim((string) $t);
            $opsi = array_values(array_filter(array_map('trim', (array) ($pil[$i] ?? array())), 'strlen'));
            if ($t === '' || count($opsi) < 2) {
                continue;
            }
            $this->db->insert('akademi_soal', array(
                'modul_id'   => $id,
                'pertanyaan' => mb_substr($t, 0, 255),
                'pilihan'    => json_encode($opsi, JSON_UNESCAPED_UNICODE),
                'jawaban'    => min(count($opsi) - 1, max(0, (int) ($benar[$i] ?? 0))),
                'urutan'     => $urut++,
            ));
        }
        $this->db->trans_complete();

        $this->session->set_flashdata('sukses', 'Modul "' . $judul . '" tersimpan.');
        return redirect('admin/akademi');
    }

    public function modul_tukar($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $this->db->set('is_active', '1 - is_active', FALSE)->where('id', (int) $id)->update('akademi_modul');
        $this->session->set_flashdata('sukses', 'Status modul diubah.');
        return redirect('admin/akademi');
    }

    public function jadwal($id = NULL)
    {
        $j = $id ? $this->db->where('id', (int) $id)->get('akademi_jadwal')->row_array() : NULL;
        if ($id && ! $j) {
            show_404();
        }
        $this->render('admin/v_akademi_jadwal', array(
            'j'       => $j,
            'peserta' => $j ? $this->db->select('s.name, s.phone, s.slug, d.created_at', FALSE)->from('akademi_daftar d')
                ->join('stores s', 's.id = d.store_id')->where('d.jadwal_id', (int) $j['id'])->get()->result_array() : array(),
        ));
    }

    public function jadwal_simpan()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $id = (int) $this->input->post('id');
        $waktu = trim((string) $this->input->post('tanggal')) . ' ' . (trim((string) $this->input->post('jam')) ?: '09:00') . ':00';
        if ( ! strtotime($waktu) || trim((string) $this->input->post('judul')) === '') {
            $this->session->set_flashdata('error', 'Judul, tanggal, dan jam wajib diisi.');
            return redirect('admin/akademi/jadwal' . ($id ? '/' . $id : ''));
        }
        $kuota = trim((string) $this->input->post('kuota'));
        $d = array(
            'judul'      => mb_substr(trim((string) $this->input->post('judul', TRUE)), 0, 150),
            'jenis'      => $this->input->post('jenis') === 'konsultasi' ? 'konsultasi' : 'kelas',
            'waktu'      => date('Y-m-d H:i:s', strtotime($waktu)),
            'tempat'     => mb_substr(trim((string) $this->input->post('tempat', TRUE)), 0, 255) ?: NULL,
            'keterangan' => mb_substr(trim((string) $this->input->post('keterangan', TRUE)), 0, 500) ?: NULL,
            'kuota'      => $kuota === '' ? NULL : max(1, (int) $kuota),
        );
        $id ? $this->db->where('id', $id)->update('akademi_jadwal', $d) : $this->db->insert('akademi_jadwal', $d);
        $this->session->set_flashdata('sukses', 'Jadwal tersimpan.');
        return redirect('admin/akademi');
    }

    public function jadwal_hapus($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $this->db->where('id', (int) $id)->delete('akademi_jadwal');
        $this->session->set_flashdata('sukses', 'Jadwal dihapus.');
        return redirect('admin/akademi');
    }
}
