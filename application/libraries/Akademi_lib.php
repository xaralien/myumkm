<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Akademi_lib - modul belajar, kuis, progres, dan jadwal kelas.
 * Simpan di: application/libraries/Akademi_lib.php
 */
class Akademi_lib
{
    protected $CI;

    /** Batas lulus kuis, persen. */
    const LULUS = 60;

    public static $kategori = array('Mulai', 'Foto & Produk', 'Pemasaran', 'Keuangan', 'Legalitas', 'Layanan', 'Umum');

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    public function modul_aktif($store_id = NULL)
    {
        $this->CI->db->select('m.*', FALSE)->from('akademi_modul m')->where('m.is_active', 1);
        if ($store_id) {
            $this->CI->db->select('p.selesai_at, p.skor', FALSE)
                ->join('akademi_progres p', 'p.modul_id = m.id AND p.store_id = ' . (int) $store_id, 'left');
        }
        return $this->CI->db->order_by('m.urutan', 'ASC')->order_by('m.id', 'ASC')->get()->result_array();
    }

    public function modul($slug)
    {
        return $this->CI->db->where('slug', (string) $slug)->where('is_active', 1)->get('akademi_modul')->row_array() ?: NULL;
    }

    public function soal($modul_id)
    {
        $out = $this->CI->db->where('modul_id', (int) $modul_id)->order_by('urutan', 'ASC')->order_by('id', 'ASC')
            ->get('akademi_soal')->result_array();
        foreach ($out as &$s) {
            $s['pilihan'] = json_decode($s['pilihan'], TRUE) ?: array();
        }
        return $out;
    }

    /**
     * Nilai jawaban kuis; catat selesai kalau lulus.
     * @return array ['lulus' => bool, 'skor' => int, 'benar' => int, 'total' => int]
     */
    public function jawab($store_id, array $modul, array $jawaban)
    {
        $soal = $this->soal($modul['id']);
        $benar = 0;
        foreach ($soal as $s) {
            if (isset($jawaban[$s['id']]) && (int) $jawaban[$s['id']] === (int) $s['jawaban']) {
                $benar++;
            }
        }
        $total = count($soal);
        $skor = $total ? (int) round($benar / $total * 100) : 100;
        $lulus = $skor >= self::LULUS;
        if ($lulus) {
            $this->CI->db->query('INSERT INTO akademi_progres (store_id, modul_id, skor) VALUES (?, ?, ?)
                                  ON DUPLICATE KEY UPDATE skor = GREATEST(skor, VALUES(skor))',
                                 array((int) $store_id, (int) $modul['id'], $skor));
        }
        return compact('lulus', 'skor', 'benar', 'total');
    }

    public function jadwal_mendatang($store_id = NULL, $limit = 10)
    {
        $this->CI->db->select('j.*, (SELECT COUNT(*) FROM akademi_daftar d WHERE d.jadwal_id = j.id) AS peserta', FALSE)
            ->from('akademi_jadwal j')->where('j.waktu >=', date('Y-m-d H:i:s', time() - 3600));
        if ($store_id) {
            $this->CI->db->select('(SELECT 1 FROM akademi_daftar d2 WHERE d2.jadwal_id = j.id AND d2.store_id = ' . (int) $store_id . ') AS terdaftar', FALSE);
        }
        return $this->CI->db->order_by('j.waktu', 'ASC')->limit((int) $limit)->get()->result_array();
    }

    /**
     * Ubah tautan YouTube/Vimeo jadi alamat embed. Hanya dua penyedia ini
     * yang diterima - iframe dari alamat sembarang bisa dipakai menyisipkan
     * halaman berbahaya.
     */
    public function embed($url)
    {
        $url = (string) $url;
        if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/' . $m[1];
        }
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }
        return NULL;
    }
}
