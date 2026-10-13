<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Voucher_lib - periksa, hitung, dan catat pemakaian voucher.
 *
 * Simpan di: application/libraries/Voucher_lib.php
 *
 * Dua jenis pemilik:
 *   - voucher TOKO     (store_id terisi)  potongannya mengurangi bagian toko
 *   - voucher PLATFORM (store_id NULL)    ditanggung marketplace
 *
 * Kuota dijaga dengan UPDATE berkondisi (terpakai < kuota), bukan baca lalu
 * tulis - dua pembeli yang memakai voucher terakhir di detik yang sama
 * tidak boleh sama-sama berhasil.
 */
class Voucher_lib
{
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->helper('money');
    }

    public function tersedia()
    {
        return $this->CI->db->table_exists('vouchers');
    }

    public function rapikan_kode($kode)
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $kode));
    }

    /** Voucher aktif berdasarkan kode, atau NULL. */
    public function cari($kode)
    {
        $kode = $this->rapikan_kode($kode);
        if ($kode === '' || ! $this->tersedia()) {
            return NULL;
        }
        return $this->CI->db->where('kode', $kode)->get('vouchers')->row_array() ?: NULL;
    }

    /**
     * Apakah voucher boleh dipakai untuk belanja sebesar $subtotal?
     *
     * @return array ['ok' => bool, 'pesan' => string]
     */
    public function periksa(array $v, $subtotal, $user_id = NULL, $phone = NULL)
    {
        $kini = date('Y-m-d H:i:s');

        if ( ! (int) $v['is_active']) {
            return $this->tolak('Voucher ' . $v['kode'] . ' sudah tidak berlaku.');
        }
        if ($v['mulai'] && $v['mulai'] > $kini) {
            return $this->tolak('Voucher ' . $v['kode'] . ' baru berlaku mulai ' . tgl_id(substr($v['mulai'], 0, 10)) . '.');
        }
        if ($v['selesai'] && $v['selesai'] < $kini) {
            return $this->tolak('Voucher ' . $v['kode'] . ' sudah kedaluwarsa.');
        }
        if ($v['kuota'] !== NULL && (int) $v['terpakai'] >= (int) $v['kuota']) {
            return $this->tolak('Kuota voucher ' . $v['kode'] . ' sudah habis.');
        }
        if ((int) $subtotal < (int) $v['min_belanja']) {
            return $this->tolak('Voucher ' . $v['kode'] . ' berlaku untuk belanja minimal '
                . rupiah($v['min_belanja']) . ($v['store_id'] ? ' di toko ini' : '') . '.');
        }

        // Batas per pembeli: dihitung dari akun, atau nomor HP untuk tamu.
        if ((int) $v['per_pembeli'] > 0 && ($user_id || $phone)) {
            $this->CI->db->from('voucher_pemakaian vp')
                ->join('orders o', 'o.id = vp.order_id')
                ->where('vp.voucher_id', (int) $v['id'])
                ->where('o.order_status !=', 'cancelled');
            $this->CI->db->group_start();
            if ($user_id) {
                $this->CI->db->where('vp.user_id', (int) $user_id);
            }
            if ($phone) {
                $user_id ? $this->CI->db->or_where('vp.phone', $phone) : $this->CI->db->where('vp.phone', $phone);
            }
            $this->CI->db->group_end();

            $this->CI->db->select('COUNT(DISTINCT COALESCE(vp.grup, vp.id)) AS n', FALSE);
            if ((int) $this->CI->db->get()->row()->n >= (int) $v['per_pembeli']) {
                return $this->tolak('Voucher ' . $v['kode'] . ' sudah pernah kamu pakai.');
            }
        }

        return array('ok' => TRUE, 'pesan' => '');
    }

    /**
     * Besar potongan. Voucher ongkir hanya memotong ongkir; voucher lain
     * hanya memotong harga barang - potongan tidak pernah melebihi yang
     * dipotongnya.
     */
    public function potongan(array $v, $subtotal, $ongkir)
    {
        switch ($v['jenis']) {
            case 'ongkir':
                return (int) min((int) $v['nilai'], max(0, (int) $ongkir));
            case 'persen':
                $p = (int) floor((int) $subtotal * min(100, (int) $v['nilai']) / 100);
                if ($v['maks_potongan']) {
                    $p = min($p, (int) $v['maks_potongan']);
                }
                return (int) min($p, (int) $subtotal);
            default:
                return (int) min((int) $v['nilai'], (int) $subtotal);
        }
    }

    /** Kalimat singkat untuk ditampilkan: "Potongan 10% s.d. Rp 20.000". */
    public function keterangan(array $v)
    {
        switch ($v['jenis']) {
            case 'ongkir':
                return 'Gratis ongkir s.d. ' . rupiah($v['nilai']);
            case 'persen':
                return 'Potongan ' . (int) $v['nilai'] . '%'
                    . ($v['maks_potongan'] ? ' s.d. ' . rupiah($v['maks_potongan']) : '');
            default:
                return 'Potongan ' . rupiah($v['nilai']);
        }
    }

    /**
     * Catat pemakaian saat pesanan dibuat.
     * @return bool FALSE kalau kuotanya ternyata baru saja habis
     */
    public function pakai(array $v, $order_id, $potongan, $user_id = NULL, $phone = NULL, $grup = NULL)
    {
        // Kuota hanya bertambah sekali per checkout (grup).
        $sudah = $grup && $this->CI->db->where('voucher_id', (int) $v['id'])->where('grup', $grup)
            ->count_all_results('voucher_pemakaian');

        if ( ! $sudah) {
            $this->CI->db->set('terpakai', 'terpakai + 1', FALSE)
                ->where('id', (int) $v['id'])
                ->group_start()->where('kuota IS NULL', NULL, FALSE)->or_where('terpakai < kuota', NULL, FALSE)->group_end()
                ->update('vouchers');

            if ($this->CI->db->affected_rows() < 1) {
                return FALSE;
            }
        }

        $this->CI->db->insert('voucher_pemakaian', array(
            'voucher_id' => (int) $v['id'],
            'order_id'   => (int) $order_id,
            'user_id'    => $user_id ? (int) $user_id : NULL,
            'phone'      => $phone,
            'potongan'   => (int) $potongan,
            'grup'       => $grup,
        ));
        return TRUE;
    }

    /**
     * Kembalikan kuota voucher dari pesanan yang batal / kedaluwarsa,
     * supaya pembeli bisa memakainya lagi di pesanan berikutnya.
     */
    public function lepas($order_id)
    {
        if ( ! $this->tersedia()) {
            return;
        }
        $baris = $this->CI->db->where('order_id', (int) $order_id)->get('voucher_pemakaian')->result_array();
        $this->CI->db->where('order_id', (int) $order_id)->delete('voucher_pemakaian');

        foreach ($baris as $b) {
            /* Voucher platform yang dipakai bersama pesanan toko lain di
               checkout yang sama: kuotanya baru kembali setelah SEMUA
               pesanan grupnya batal. */
            if ($b['grup'] && $this->CI->db->where('voucher_id', (int) $b['voucher_id'])
                    ->where('grup', $b['grup'])->count_all_results('voucher_pemakaian')) {
                continue;
            }
            $this->CI->db->set('terpakai', 'GREATEST(CAST(terpakai AS SIGNED) - 1, 0)', FALSE)
                ->where('id', (int) $b['voucher_id'])->update('vouchers');
        }
    }

    /** Voucher toko yang sedang berlaku - ditampilkan di halaman toko. */
    public function aktif_toko($store_id, $limit = 6)
    {
        if ( ! $this->tersedia()) {
            return array();
        }
        $kini = date('Y-m-d H:i:s');
        return $this->CI->db->where('store_id', (int) $store_id)
            ->where('is_active', 1)
            ->group_start()->where('mulai IS NULL', NULL, FALSE)->or_where('mulai <=', $kini)->group_end()
            ->group_start()->where('selesai IS NULL', NULL, FALSE)->or_where('selesai >=', $kini)->group_end()
            ->group_start()->where('kuota IS NULL', NULL, FALSE)->or_where('terpakai < kuota', NULL, FALSE)->group_end()
            ->order_by('id', 'DESC')->limit((int) $limit)
            ->get('vouchers')->result_array();
    }

    protected function tolak($pesan)
    {
        return array('ok' => FALSE, 'pesan' => $pesan);
    }
}
