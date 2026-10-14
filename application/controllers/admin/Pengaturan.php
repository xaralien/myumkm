<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * admin/Pengaturan - pengaturan sistem, notifikasi keluar, dan log audit.
 * Simpan di: application/controllers/admin/Pengaturan.php
 *
 *   GET  admin/pengaturan             formulir pengaturan
 *   POST admin/pengaturan/simpan      simpan
 *   POST admin/pengaturan/tes_wa      kirim WA uji ke nomor admin
 *   POST admin/pengaturan/server_wa   geseran WhatsApp: mati / bind / fast_wa / kirimi
 *   GET  admin/pengaturan/notifikasi  notifikasi WA/email yang keluar
 *   GET  admin/pengaturan/audit       log aktivitas admin
 */
class Pengaturan extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('pengaturan_lib');
    }

    public function index()
    {
        $this->render('admin/v_pengaturan', array(
            'daftar' => $this->pengaturan_lib->daftar(),
            'nilai'  => $this->pengaturan_lib->semua(),
            'p'      => $this->pengaturan_lib,
            'tab'    => 'pengaturan',
            'wa'     => $this->info_server_wa(),
        ));
    }

    /** Server WA yang bisa dipilih. Kuncinya = nilai utility.server_wa yang dibaca Api_Whatsapp. */
    const SERVER_WA = array(
        'bind'    => array('Bind', 'Server WhatsApp sendiri (bawaan).'),
        'fast_wa' => array('FastWA', 'Layanan app.fastwa.com.'),
        'kirimi'  => array('Kirimi', 'Layanan api.kirimi.id.'),
    );

    /**
     * Keadaan gerbang WA milik sendiri (application/libraries/Api_Whatsapp.php).
     * Api_Whatsapp memilih server dari baris pertama tabel `utility`; nilai
     * selain fast_wa/kirimi berarti server bind.
     */
    protected function info_server_wa()
    {
        $info = array(
            'ada_lib'   => is_file(APPPATH . 'libraries/Api_Whatsapp.php'),
            'ada_tabel' => $this->db->table_exists('utility') && $this->db->field_exists('server_wa', 'utility'),
            'server'    => NULL,
            'pilihan'   => self::SERVER_WA,
        );
        if ($info['ada_tabel']) {
            $baris = $this->db->select('server_wa')->limit(1)->get('utility')->row_array();
            if ($baris) {
                $info['server'] = isset(self::SERVER_WA[$baris['server_wa']]) ? $baris['server_wa'] : 'bind';
            }
        }
        return $info;
    }

    /**
     * Geseran WhatsApp: 'mati' mematikan semua notifikasi WA; bind/fast_wa/
     * kirimi menyalakannya sekaligus memilih server di utility.server_wa.
     */
    public function server_wa()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $baru = (string) $this->input->post('server_wa', TRUE);
        $info = $this->info_server_wa();
        $nyala_lama = $this->pengaturan_lib->aktif('wa_aktif');

        if ($baru === 'mati') {
            $this->pengaturan_lib->simpan(array('wa_aktif' => '0'));
            $this->session->set_flashdata('sukses', 'Notifikasi WhatsApp dimatikan.');
            return redirect('admin/pengaturan#whatsapp');
        }
        if ( ! isset(self::SERVER_WA[$baru])) {
            $this->session->set_flashdata('error', 'Pilihan server WhatsApp tidak dikenal.');
            return redirect('admin/pengaturan#whatsapp');
        }
        if ( ! $info['ada_lib'] || ! $info['ada_tabel'] || $info['server'] === NULL) {
            $this->session->set_flashdata('error', 'Server WhatsApp belum bisa dipilih: '
                . ( ! $info['ada_lib'] ? 'Api_Whatsapp.php belum terpasang.' : 'tabel utility (kolom server_wa) belum ada atau masih kosong.'));
            return redirect('admin/pengaturan#whatsapp');
        }

        $this->pengaturan_lib->simpan(array('wa_aktif' => '1'));

        if ($baru !== $info['server']) {
            /* Semua baris diubah, bukan hanya satu: Api_Whatsapp membaca
               "baris pertama" tanpa urutan, jadi kalau barisnya lebih dari
               satu, semuanya harus sama supaya hasilnya pasti. */
            $this->db->update('utility', array('server_wa' => $baru));

            $this->load->library('audit_lib');
            $this->audit_lib->catat('server_wa', 'utility', NULL,
                'Server WhatsApp: ' . self::SERVER_WA[$info['server']][0] . ' -> ' . self::SERVER_WA[$baru][0]);
        }

        $this->session->set_flashdata('sukses', ($baru === $info['server'] && $nyala_lama)
            ? self::SERVER_WA[$baru][0] . ' sudah aktif.'
            : 'Notifikasi WhatsApp aktif lewat ' . self::SERVER_WA[$baru][0] . '. Kirim pesan uji untuk memastikan.');
        return redirect('admin/pengaturan#whatsapp');
    }

    public function simpan()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $ubah = $this->pengaturan_lib->simpan((array) $this->input->post(NULL, TRUE));

        $this->session->set_flashdata('sukses', $ubah
            ? 'Pengaturan tersimpan (' . count($ubah) . ' diubah).'
            : 'Tidak ada yang berubah.');
        return redirect('admin/pengaturan');
    }

    public function tes_wa()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $this->load->library('notifikasi_lib');

        $nomor = $this->input->post('nomor', TRUE) ?: $this->pengaturan_lib->get('wa_admin');
        if ( ! $nomor) {
            $this->session->set_flashdata('error', 'Isi nomor tujuan uji, atau isi WhatsApp admin dulu.');
            return redirect('admin/pengaturan');
        }

        $ok = $this->notifikasi_lib->wa($nomor,
            'Uji notifikasi dari ' . $this->pengaturan_lib->get('nama_brand') . '. Kalau pesan ini sampai, gerbang WA sudah benar.',
            'uji');

        $this->session->set_flashdata($ok ? 'sukses' : 'error', $ok
            ? 'Pesan uji terkirim ke ' . $nomor . '.'
            : 'Pesan uji tidak terkirim. Lihat alasannya di tab Notifikasi keluar.');
        return redirect('admin/pengaturan');
    }

    public function notifikasi()
    {
        $status = $this->input->get('status', TRUE);
        if (in_array($status, array('terkirim', 'gagal', 'dilewati'), TRUE)) {
            $this->db->where('status', $status);
        }
        $this->render('admin/v_pengaturan_log', array(
            'tab'    => 'notifikasi',
            'status' => $status,
            'baris'  => $this->db->order_by('id', 'DESC')->limit(200)->get('notifikasi_keluar')->result_array(),
        ));
    }

    public function audit()
    {
        $cari = trim((string) $this->input->get('q', TRUE));
        if ($cari !== '') {
            $this->db->group_start()->like('aksi', $cari)->or_like('nama', $cari)->or_like('detail', $cari)->group_end();
        }
        $this->render('admin/v_pengaturan_log', array(
            'tab'   => 'audit',
            'cari'  => $cari,
            'baris' => $this->db->order_by('id', 'DESC')->limit(300)->get('audit_logs')->result_array(),
        ));
    }
}
