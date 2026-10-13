<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Token_model - tautan sekali-pakai untuk reset sandi & verifikasi email.
 * Simpan di: application/models/Token_model.php
 *
 * Yang masuk database adalah HASH tokennya; tokennya sendiri hanya ada di
 * email penerima dan tidak pernah disimpan. Alasan lengkapnya ada di
 * database/25_reset_sandi_verifikasi.sql.
 */
class Token_model extends CI_Model {

    /* Reset sandi berumur 1 jam.
       Orang yang menekan "lupa password" biasanya membuka emailnya dalam
       hitungan menit. Umur panjang hanya memperbesar jendela waktu kalau
       emailnya ikut bocor - dan email adalah tempat yang sering dibiarkan
       terbuka di laptop bersama. */
    const UMUR_RESET = 3600;

    /* Verifikasi email berumur 2 hari.
       Lebih longgar karena tidak ada yang bisa diambil alih dengan token
       ini - paling buruk seseorang menandai emailnya sendiri sebagai
       terverifikasi. Dan email pendaftaran memang sering baru dibuka
       besoknya. */
    const UMUR_VERIFIKASI = 172800;

    /* Paling banyak 3 permintaan per akun per 15 menit.
       Tanpa ini, satu formulir "lupa password" bisa dipakai membanjiri kotak
       masuk orang lain - alamat email korban diketik berulang-ulang, dan
       situs kita yang jadi pengirim spamnya. Itu juga cara tercepat membuat
       domain kita diblokir penyedia email. */
    const MAKS_PERMINTAAN = 3;
    const JENDELA         = 900;

    /**
     * Buat token baru dan kembalikan bentuk MENTAH-nya untuk dikirim email.
     *
     * @param  int    $user_id
     * @param  string $jenis   'reset' atau 'verifikasi'
     * @return array  ['ok' => bool, 'token' => string, 'pesan' => string]
     */
    public function buat($user_id, $jenis)
    {
        $user_id = (int) $user_id;

        if ($this->terlalu_sering($user_id, $jenis)) {
            return array(
                'ok'    => FALSE,
                'token' => '',
                'pesan' => 'Terlalu banyak permintaan. Coba lagi beberapa menit lagi.',
            );
        }

        /* Token reset yang lama dibatalkan sekarang, bukan dibiarkan hidup
           berdampingan.

           Kalau dibiarkan, tautan dari email yang diminta tiga hari lalu
           masih berfungsi - termasuk kalau email itulah yang bocor. Satu
           akun hanya boleh punya satu tautan reset yang hidup, yaitu yang
           terakhir diminta. */
        $this->batalkan($user_id, $jenis);

        /* random_bytes(), bukan rand() atau uniqid().
           uniqid() berbasis waktu - bisa diperkirakan oleh siapa pun yang
           tahu kira-kira kapan tokennya dibuat. random_bytes() memakai
           sumber acak sistem operasi, dan melempar galat kalau sumber itu
           tidak bisa dipercaya, bukan diam-diam mengembalikan angka lemah.

           32 byte jadi 64 karakter hex. */
        $mentah = bin2hex(random_bytes(32));

        $umur = ($jenis === 'reset') ? self::UMUR_RESET : self::UMUR_VERIFIKASI;

        $this->db->insert('auth_tokens', array(
            'user_id'    => $user_id,
            'jenis'      => $jenis,
            'token_hash' => hash('sha256', $mentah),
            'expires_at' => date('Y-m-d H:i:s', time() + $umur),
            'ip'         => $this->input->ip_address(),
        ));

        // Sesekali bersihkan yang sudah mati, supaya tabelnya tidak tumbuh
        // selamanya. Dipicu acak, bukan tiap kali: pembersihan tidak perlu
        // tepat waktu, dan tidak ada cron yang bisa diandalkan di sini.
        if (random_int(1, 20) === 1) {
            $this->bersihkan();
        }

        return array('ok' => TRUE, 'token' => $mentah, 'pesan' => '');
    }

    /**
     * Pakai satu token. Hanya berhasil sekali.
     *
     * @return array ['ok' => bool, 'user_id' => int, 'alasan' => string]
     *               alasan: 'tidak_ada' | 'kedaluwarsa' | 'sudah_dipakai'
     */
    public function pakai($mentah, $jenis)
    {
        $mentah = (string) $mentah;

        // Bentuknya diperiksa dulu supaya token sampah tidak perlu
        // menyentuh database sama sekali.
        if (! preg_match('/^[a-f0-9]{64}$/', $mentah)) {
            return array('ok' => FALSE, 'user_id' => 0, 'alasan' => 'tidak_ada');
        }

        $hash = hash('sha256', $mentah);

        /* Satu UPDATE bersyarat, bukan SELECT lalu UPDATE.

           Dengan dua perintah terpisah, dua permintaan di detik yang sama
           bisa sama-sama lolos SELECT dan sama-sama menganggap tokennya sah -
           dan tautan sekali-pakai jadi dua kali pakai. Di sini syaratnya ada
           di dalam UPDATE, jadi yang kedua mendapat affected_rows() = 0.

           Ini terjadi lebih sering dari dugaan: beberapa klien email memuat
           tautan lebih dulu untuk pratinjau, lalu pengguna menekannya. */
        $this->db->where('token_hash', $hash)
                 ->where('jenis', $jenis)
                 ->where('used_at IS NULL', NULL, FALSE)
                 ->where('expires_at >', date('Y-m-d H:i:s'))
                 ->update('auth_tokens', array('used_at' => date('Y-m-d H:i:s')));

        if ($this->db->affected_rows() === 1) {
            $baris = $this->db->select('user_id')
                              ->where('token_hash', $hash)
                              ->get('auth_tokens')->row_array();

            return array('ok' => TRUE, 'user_id' => (int) $baris['user_id'], 'alasan' => '');
        }

        /* Gagal - sekarang dicari sebabnya, supaya pesannya berguna.

           Membedakan "sudah dipakai" dari "tidak ada" tidak membocorkan apa
           pun: yang bertanya sudah memegang tokennya. Dan bedanya besar bagi
           penggunanya - "sudah pernah dipakai" berarti sandinya mungkin sudah
           berhasil diganti, sedangkan "tidak ada" berarti tautannya salah
           salin. */
        $ada = $this->db->select('used_at, expires_at')
                        ->where('token_hash', $hash)
                        ->where('jenis', $jenis)
                        ->get('auth_tokens')->row_array();

        if (! $ada) {
            $alasan = 'tidak_ada';
        } elseif ($ada['used_at'] !== NULL) {
            $alasan = 'sudah_dipakai';
        } else {
            $alasan = 'kedaluwarsa';
        }

        return array('ok' => FALSE, 'user_id' => 0, 'alasan' => $alasan);
    }

    /**
     * Periksa token TANPA memakainya.
     *
     * Dipakai saat halaman reset dibuka. Kalau tokennya langsung dipakai di
     * situ, formulirnya tampil tapi tombol simpannya pasti gagal - tokennya
     * sudah hangus sebelum sandinya diketik. Lebih buruk lagi, sebagian
     * klien email memuat tautan lebih dulu untuk membuat pratinjau, jadi
     * tautannya mati sebelum penerimanya menekan apa pun.
     *
     * Jadi: dibuka = diperiksa, disimpan = dipakai.
     *
     * @return array ['ok' => bool, 'user_id' => int, 'alasan' => string]
     */
    public function periksa($mentah, $jenis)
    {
        $mentah = (string) $mentah;

        if (! preg_match('/^[a-f0-9]{64}$/', $mentah)) {
            return array('ok' => FALSE, 'user_id' => 0, 'alasan' => 'tidak_ada');
        }

        $baris = $this->db->select('user_id, used_at, expires_at')
                          ->where('token_hash', hash('sha256', $mentah))
                          ->where('jenis', $jenis)
                          ->get('auth_tokens')->row_array();

        if (! $baris) {
            return array('ok' => FALSE, 'user_id' => 0, 'alasan' => 'tidak_ada');
        }
        if ($baris['used_at'] !== NULL) {
            return array('ok' => FALSE, 'user_id' => 0, 'alasan' => 'sudah_dipakai');
        }
        if (strtotime($baris['expires_at']) <= time()) {
            return array('ok' => FALSE, 'user_id' => 0, 'alasan' => 'kedaluwarsa');
        }

        return array('ok' => TRUE, 'user_id' => (int) $baris['user_id'], 'alasan' => '');
    }

    /** Matikan semua token hidup milik satu akun untuk satu jenis. */
    public function batalkan($user_id, $jenis)
    {
        $this->db->where('user_id', (int) $user_id)
                 ->where('jenis', $jenis)
                 ->where('used_at IS NULL', NULL, FALSE)
                 ->update('auth_tokens', array('used_at' => date('Y-m-d H:i:s')));
    }

    /* ------------------------------------------------------------------ */

    protected function terlalu_sering($user_id, $jenis)
    {
        $n = (int) $this->db
            ->where('user_id', (int) $user_id)
            ->where('jenis', $jenis)
            ->where('created_at >', date('Y-m-d H:i:s', time() - self::JENDELA))
            ->count_all_results('auth_tokens');

        return $n >= self::MAKS_PERMINTAAN;
    }

    /**
     * Hapus token yang sudah kedaluwarsa lebih dari 7 hari.
     *
     * Tidak langsung setelah kedaluwarsa: baris yang masih ada memungkinkan
     * pesan "tautannya sudah kedaluwarsa, minta yang baru". Dihapus terlalu
     * cepat, pesannya berubah jadi "tautan tidak valid" - yang membuat orang
     * mengira mereka salah salin dan mencoba lagi dengan tautan yang sama.
     */
    public function bersihkan()
    {
        $this->db->where('expires_at <', date('Y-m-d H:i:s', time() - 604800))
                 ->delete('auth_tokens');
    }
}
