<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Referral_lib - kode referral "pembeli ajak pembeli".
 *
 * Simpan di: application/libraries/Referral_lib.php
 *
 * Alurnya:
 *   1. Setiap akun punya kode (dibuat saat pertama kali diminta).
 *   2. Teman mendaftar memakai kode itu (kolom di formulir daftar, atau
 *      tautan auth/register?ref=KODE) -> teman langsung mendapat voucher
 *      khusus akunnya.
 *   3. Begitu pesanan PERTAMA teman itu lunas -> pengajak mendapat voucher.
 *
 * Nilai voucher, minimal belanja, dan masa berlakunya diatur admin di
 * Pengaturan > Referral. Voucher dibuat sebagai voucher platform yang
 * dikunci ke satu akun (vouchers.user_id), sekali pakai.
 *
 * Penjaga penyalahgunaan: kode sendiri ditolak, satu akun hanya bisa
 * diajak sekali (indeks unik referral.teman_id), dan teman dengan nomor
 * WhatsApp yang sama dengan pengajak ditolak.
 */
class Referral_lib
{
    protected $CI;

    // Huruf mudah dibaca: tanpa 0/O dan 1/I/L.
    const HURUF = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('pengaturan_lib');
    }

    public function tersedia()
    {
        return $this->CI->db->table_exists('referral') && $this->CI->db->field_exists('kode_referral', 'users');
    }

    public function aktif()
    {
        return $this->tersedia() && $this->CI->pengaturan_lib->aktif('referral_aktif');
    }

    public function rapikan($kode)
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $kode));
    }

    /** Kode milik akun; dibuatkan kalau belum ada. */
    public function kode_user($user_id)
    {
        $u = $this->CI->db->select('kode_referral')->where('id', (int) $user_id)->get('users')->row_array();
        if ( ! $u) {
            return NULL;
        }
        if ( ! empty($u['kode_referral'])) {
            return $u['kode_referral'];
        }
        for ($i = 0; $i < 8; $i++) {
            $kode = '';
            for ($j = 0; $j < 6; $j++) {
                $kode .= self::HURUF[random_int(0, strlen(self::HURUF) - 1)];
            }
            $debug = $this->CI->db->db_debug;
            $this->CI->db->db_debug = FALSE;
            $this->CI->db->where('id', (int) $user_id)->where('kode_referral IS NULL', NULL, FALSE)
                         ->update('users', array('kode_referral' => $kode));
            $this->CI->db->db_debug = $debug;
            $u = $this->CI->db->select('kode_referral')->where('id', (int) $user_id)->get('users')->row_array();
            if ( ! empty($u['kode_referral'])) {
                return $u['kode_referral'];
            }
        }
        return NULL;
    }

    /** @return array|NULL akun pemilik kode */
    public function pemilik($kode)
    {
        $kode = $this->rapikan($kode);
        if ($kode === '' || ! $this->tersedia()) {
            return NULL;
        }
        return $this->CI->db->select('id, name, phone')->where('kode_referral', $kode)
                            ->where('is_active', 1)->get('users')->row_array() ?: NULL;
    }

    /**
     * Pemeriksaan sebelum akun dibuat (dipakai validasi formulir daftar).
     * @return TRUE|string pesan galat
     */
    public function periksa_kode($kode, $phone_teman = NULL)
    {
        if ($this->rapikan($kode) === '') {
            return TRUE;
        }
        if ( ! $this->aktif()) {
            return 'Program ajak teman sedang tidak berjalan. Kosongkan kode referral.';
        }
        $p = $this->pemilik($kode);
        if ( ! $p) {
            return 'Kode referral tidak dikenal. Periksa lagi, atau kosongkan.';
        }
        if ($phone_teman && $p['phone'] && $p['phone'] === $phone_teman) {
            return 'Kode referral tidak bisa dipakai oleh nomor WhatsApp yang sama dengan pemiliknya.';
        }
        return TRUE;
    }

    /**
     * Catat bahwa $teman_id diajak lewat $kode, lalu beri voucher teman.
     * @return array|NULL baris voucher teman, NULL kalau tidak berlaku
     */
    public function daftarkan($teman_id, $kode)
    {
        if ( ! $this->aktif() || $this->rapikan($kode) === '') {
            return NULL;
        }
        $p = $this->pemilik($kode);
        if ( ! $p || (int) $p['id'] === (int) $teman_id) {
            return NULL;
        }

        $debug = $this->CI->db->db_debug;
        $this->CI->db->db_debug = FALSE;
        $ok = $this->CI->db->insert('referral', array('pengajak_id' => (int) $p['id'], 'teman_id' => (int) $teman_id));
        $this->CI->db->db_debug = $debug;
        if ( ! $ok) {
            return NULL;   // sudah pernah diajak
        }
        $rid = (int) $this->CI->db->insert_id();
        $this->CI->db->where('id', (int) $teman_id)->update('users', array('diajak_oleh' => (int) $p['id']));

        $v = $this->buat_voucher($teman_id, (int) $this->CI->pengaturan_lib->get('referral_teman_nominal'),
                                 'Hadiah daftar lewat ajakan teman');
        if ($v) {
            $this->CI->db->where('id', $rid)->update('referral', array('voucher_teman_id' => (int) $v['id']));
        }
        return $v;
    }

    /**
     * Dipanggil saat pesanan lunas (Order_model::mark_paid). Pesanan lunas
     * pertama milik akun yang diajak -> pengajak mendapat voucher.
     */
    public function pesanan_lunas(array $order)
    {
        if (empty($order['user_id']) || ! $this->aktif()) {
            return;
        }
        $r = $this->CI->db->where('teman_id', (int) $order['user_id'])->where('status', 'daftar')
                          ->get('referral')->row_array();
        if ( ! $r) {
            return;
        }
        // Tandai selesai lebih dulu (bersyarat status) supaya dua callback
        // pembayaran yang datang bersamaan tidak memberi voucher dua kali.
        $this->CI->db->where('id', (int) $r['id'])->where('status', 'daftar')
                     ->update('referral', array('status' => 'selesai', 'order_id' => (int) $order['id'],
                                                'selesai_at' => date('Y-m-d H:i:s')));
        if ($this->CI->db->affected_rows() < 1) {
            return;
        }

        $v = $this->buat_voucher((int) $r['pengajak_id'], (int) $this->CI->pengaturan_lib->get('referral_pengajak_nominal'),
                                 'Hadiah ajak teman');
        if ( ! $v) {
            return;
        }
        $this->CI->db->where('id', (int) $r['id'])->update('referral', array('voucher_pengajak_id' => (int) $v['id']));

        $pengajak = $this->CI->db->select('name, phone')->where('id', (int) $r['pengajak_id'])->get('users')->row_array();
        $teman    = $this->CI->db->select('name')->where('id', (int) $r['teman_id'])->get('users')->row_array();
        if ($pengajak && $pengajak['phone']) {
            $this->CI->load->library('notifikasi_lib');
            $this->CI->load->helper('money');
            $this->CI->notifikasi_lib->wa($pengajak['phone'],
                'Hore! ' . ($teman ? $teman['name'] : 'Temanmu') . ' baru saja belanja pertama kali lewat ajakanmu.' . "\n\n"
                . 'Kamu dapat voucher ' . $v['kode'] . ' senilai ' . rupiah($v['nilai'])
                . ($v['min_belanja'] ? ' (min. belanja ' . rupiah($v['min_belanja']) . ')' : '')
                . ', berlaku sampai ' . tgl_id(substr($v['selesai'], 0, 10)) . '.' . "\n"
                . 'Lihat di: ' . site_url('akun/ajak'), 'referral');
        }
    }

    /** Voucher platform sekali pakai, dikunci ke satu akun. */
    protected function buat_voucher($user_id, $nilai, $nama)
    {
        if ($nilai <= 0) {
            return NULL;
        }
        $p = $this->CI->pengaturan_lib;
        $hari = max(1, (int) $p->get('referral_berlaku_hari'));
        for ($i = 0; $i < 8; $i++) {
            $kode = 'REF';
            for ($j = 0; $j < 6; $j++) {
                $kode .= self::HURUF[random_int(0, strlen(self::HURUF) - 1)];
            }
            $data = array(
                'store_id'    => NULL,
                'user_id'     => (int) $user_id,
                'kode'        => $kode,
                'nama'        => $nama,
                'jenis'       => 'nominal',
                'nilai'       => (int) $nilai,
                'min_belanja' => max(0, (int) $p->get('referral_min_belanja')),
                'kuota'       => 1,
                'per_pembeli' => 1,
                'mulai'       => date('Y-m-d H:i:s'),
                'selesai'     => date('Y-m-d 23:59:59', strtotime('+' . $hari . ' days')),
                'is_active'   => 1,
            );
            $debug = $this->CI->db->db_debug;
            $this->CI->db->db_debug = FALSE;
            $ok = $this->CI->db->insert('vouchers', $data);
            $this->CI->db->db_debug = $debug;
            if ($ok) {
                $data['id'] = (int) $this->CI->db->insert_id();
                return $data;
            }
        }
        return NULL;
    }

    /** Ringkasan untuk halaman "Ajak teman". */
    public function ringkasan($user_id)
    {
        $teman = $this->CI->db
            ->select('r.status, r.created_at, r.selesai_at, u.name')
            ->from('referral r')->join('users u', 'u.id = r.teman_id', 'left')
            ->where('r.pengajak_id', (int) $user_id)->order_by('r.id', 'DESC')->limit(100)
            ->get()->result_array();
        $voucher = $this->CI->db
            ->select('v.kode, v.nama, v.nilai, v.min_belanja, v.selesai, v.terpakai, v.is_active')
            ->from('vouchers v')->where('v.user_id', (int) $user_id)->order_by('v.id', 'DESC')->limit(50)
            ->get()->result_array();
        return array('teman' => $teman, 'voucher' => $voucher);
    }

    /** Voucher milik akun yang masih bisa dipakai (untuk saran di checkout). */
    public function voucher_aktif($user_id)
    {
        if ( ! $this->tersedia() || ! $user_id) {
            return array();
        }
        return $this->CI->db->select('kode, nama, nilai, min_belanja, selesai')
            ->where('user_id', (int) $user_id)->where('is_active', 1)
            ->where('terpakai < kuota', NULL, FALSE)
            ->where('selesai >=', date('Y-m-d H:i:s'))
            ->order_by('selesai', 'ASC')->get('vouchers')->result_array();
    }
}
