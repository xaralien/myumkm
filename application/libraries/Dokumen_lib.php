<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Dokumen_lib - simpan & tampilkan berkas legalitas toko.
 *
 * Simpan di: application/libraries/Dokumen_lib.php
 *
 * Berkas (KTP, NIB, sertifikat) disimpan di application/dokumen_toko/ -
 * BUKAN di upload/, karena upload/ sengaja dibuka untuk gambar & PDF.
 * Folder application/ ditutup application/.htaccess, dan nama berkasnya
 * acak. Satu-satunya jalan membukanya adalah kirim() - dipanggil
 * controller SETELAH memeriksa hak akses (pemilik toko, admin, atau
 * pendamping toko itu).
 *
 * Catatan untuk server Nginx (.htaccess tidak dibaca): pastikan
 *     location ^~ /application/ { deny all; }
 */
class Dokumen_lib
{
    public static $jenis = array(
        'nib'     => array('NIB (Nomor Induk Berusaha)', 'Diurus gratis di oss.go.id. Wajib untuk P-IRT, Halal, dan KUR.'),
        'pirt'    => array('P-IRT', 'Izin produk pangan rumahan dari Dinas Kesehatan kabupaten/kota.'),
        'halal'   => array('Sertifikat Halal', 'Gratis untuk usaha mikro lewat program SEHATI (halal.go.id).'),
        'ktp'     => array('KTP pemilik', 'Untuk memastikan pemilik toko adalah orang yang sebenarnya. Tidak ditampilkan ke pembeli.'),
        'npwp'    => array('NPWP', 'Opsional. Dibutuhkan untuk pinjaman di atas Rp 50 juta.'),
        'lainnya' => array('Dokumen lain', 'Misalnya SNI, BPOM, merek dagang, atau sertifikat pelatihan.'),
    );

    public function folder()
    {
        $f = APPPATH . 'dokumen_toko' . DIRECTORY_SEPARATOR;
        if ( ! is_dir($f)) {
            @mkdir($f, 0750, TRUE);
        }
        return $f;
    }

    /**
     * @return string|NULL|FALSE nama berkas baru, NULL kalau tidak ada unggahan, FALSE kalau ditolak
     */
    public function unggah($field, &$pesan)
    {
        $f = isset($_FILES[$field]) ? $_FILES[$field] : NULL;
        if ( ! $f || $f['error'] === UPLOAD_ERR_NO_FILE || $f['name'] === '') {
            return NULL;
        }
        if ($f['error'] !== UPLOAD_ERR_OK || ! is_uploaded_file($f['tmp_name'])) {
            $pesan = 'Berkas gagal diunggah. Coba lagi.';
            return FALSE;
        }
        if ($f['size'] > 4 * 1024 * 1024) {
            $pesan = 'Berkas maksimal 4 MB. Foto ulang dengan resolusi lebih kecil.';
            return FALSE;
        }

        // Jenis dibaca dari ISI berkas, bukan nama atau header dari browser.
        $ext = NULL;
        $info = @getimagesize($f['tmp_name']);
        if ($info !== FALSE) {
            $izin = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png');
            if (defined('IMAGETYPE_WEBP')) {
                $izin[IMAGETYPE_WEBP] = 'webp';
            }
            $ext = isset($izin[$info[2]]) ? $izin[$info[2]] : NULL;
        } elseif (file_get_contents($f['tmp_name'], FALSE, NULL, 0, 5) === '%PDF-') {
            $ext = 'pdf';
        }
        if ( ! $ext) {
            $pesan = 'Berkas harus foto (JPG/PNG/WEBP) atau PDF.';
            return FALSE;
        }

        $nama = bin2hex(random_bytes(16)) . '.' . $ext;
        if ( ! move_uploaded_file($f['tmp_name'], $this->folder() . $nama)) {
            $pesan = 'Gagal menyimpan berkas. Folder upload/dokumen/ tidak bisa ditulisi.';
            return FALSE;
        }
        @chmod($this->folder() . $nama, 0640);
        if ($ext !== 'pdf') {
            rapikan_gambar($this->folder() . $nama, 1600);
        }
        return $nama;
    }

    public function hapus($nama)
    {
        $p = $this->folder() . basename((string) $nama);
        if ($nama && is_file($p)) {
            @unlink($p);
        }
    }

    /** Kirim berkas ke browser (setelah hak akses diperiksa pemanggil). */
    public function kirim($nama)
    {
        $p = $this->folder() . basename((string) $nama);
        if ( ! $nama || ! is_file($p)) {
            show_404();
        }
        $mime = array('jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'pdf' => 'application/pdf');
        $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
        $CI = &get_instance();
        $CI->output->set_content_type(isset($mime[$ext]) ? $mime[$ext] : 'application/octet-stream')
            ->set_header('Content-Disposition: inline; filename="dokumen.' . $ext . '"')
            ->set_header('Cache-Control: private, no-store')
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_output(file_get_contents($p));
    }
}
