<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Voucher_model - kelola voucher (dipakai panel penjual & admin).
 * Simpan di: application/models/Voucher_model.php
 *
 * $store_id NULL berarti voucher PLATFORM. Setiap query dikunci ke pemilik
 * yang sama, supaya penjual tidak bisa membuka voucher toko lain atau
 * voucher platform hanya dengan mengganti angka di alamat.
 */
class Voucher_model extends CI_Model
{
    public function daftar($store_id)
    {
        $this->pemilik($store_id);
        return $this->db->select('v.*, (SELECT COALESCE(SUM(potongan), 0) FROM voucher_pemakaian vp WHERE vp.voucher_id = v.id) AS total_potongan', FALSE)
            ->from('vouchers v')->order_by('v.id', 'DESC')->get()->result_array();
    }

    public function ambil($id, $store_id)
    {
        $this->pemilik($store_id);
        return $this->db->where('v.id', (int) $id)->get('vouchers v')->row_array() ?: NULL;
    }

    protected function pemilik($store_id)
    {
        $store_id === NULL
            ? $this->db->where('v.store_id IS NULL', NULL, FALSE)
            : $this->db->where('v.store_id', (int) $store_id);
    }

    /**
     * Validasi & simpan dari $_POST.
     * @return array ['ok' => bool, 'pesan' => string]
     */
    public function simpan($id, $store_id, array $in)
    {
        $kode  = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $in['kode']));
        $angka = function ($k) use ($in) {
            $v = preg_replace('/[^0-9]/', '', (string) (isset($in[$k]) ? $in[$k] : ''));
            return $v === '' ? NULL : (int) $v;
        };
        $jenis = in_array($in['jenis'] ?? '', array('persen', 'nominal', 'ongkir'), TRUE) ? $in['jenis'] : 'nominal';

        $d = array(
            'store_id'      => $store_id,
            'kode'          => $kode,
            'nama'          => mb_substr(trim((string) $in['nama']), 0, 100),
            'jenis'         => $jenis,
            'nilai'         => (int) $angka('nilai'),
            'maks_potongan' => $jenis === 'persen' ? $angka('maks_potongan') : NULL,
            'min_belanja'   => (int) $angka('min_belanja'),
            'kuota'         => $angka('kuota'),
            'per_pembeli'   => max(0, min(99, (int) ($in['per_pembeli'] ?? 1))),
            'mulai'         => $this->tgl($in['mulai'] ?? '', '00:00:00'),
            'selesai'       => $this->tgl($in['selesai'] ?? '', '23:59:59'),
            'is_active'     => ! empty($in['is_active']) ? 1 : 0,
        );

        if (strlen($kode) < 4 || strlen($kode) > 30) {
            return array('ok' => FALSE, 'pesan' => 'Kode voucher 4-30 huruf/angka, tanpa spasi.');
        }
        if ($d['nama'] === '') {
            return array('ok' => FALSE, 'pesan' => 'Nama voucher wajib diisi.');
        }
        if ($d['nilai'] <= 0 || ($jenis === 'persen' && $d['nilai'] > 90)) {
            return array('ok' => FALSE, 'pesan' => $jenis === 'persen' ? 'Persen potongan 1-90.' : 'Nilai potongan harus lebih dari 0.');
        }
        if ($d['mulai'] && $d['selesai'] && $d['selesai'] < $d['mulai']) {
            return array('ok' => FALSE, 'pesan' => 'Tanggal selesai sebelum tanggal mulai.');
        }

        $this->db->where('kode', $kode);
        if ($id) {
            $this->db->where('id !=', (int) $id);
        }
        if ($this->db->count_all_results('vouchers')) {
            return array('ok' => FALSE, 'pesan' => 'Kode ' . $kode . ' sudah dipakai. Pilih kode lain.');
        }

        if ($id) {
            $this->pemilik($store_id);
            $this->db->where('v.id', (int) $id)->update('vouchers v', $d);
        } else {
            $this->db->insert('vouchers', $d);
        }
        return array('ok' => TRUE, 'pesan' => 'Voucher ' . $kode . ' tersimpan.');
    }

    public function tukar($id, $store_id)
    {
        $v = $this->ambil($id, $store_id);
        if ( ! $v) {
            return NULL;
        }
        $this->db->where('id', (int) $v['id'])->update('vouchers', array('is_active' => $v['is_active'] ? 0 : 1));
        return ! $v['is_active'];
    }

    protected function tgl($s, $jam)
    {
        $s = trim((string) $s);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ? $s . ' ' . $jam : NULL;
    }
}
