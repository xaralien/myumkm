<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * penjual/Massal - unggah banyak produk dari Excel, dan duplikat produk.
 * Alamat: seller/massal (lihat routes.php)
 *
 *   GET  seller/massal                 petunjuk + formulir unggah
 *   GET  seller/massal/contoh          unduh templat Excel
 *   POST seller/massal/unggah          proses berkas .xlsx / .csv
 *   POST seller/massal/duplikat/{id}   salin satu produk
 *
 * Produk dari Excel disimpan sebagai DRAF (disembunyikan) karena belum
 * punya foto - pembeli tidak membeli produk tanpa foto. Penjual tinggal
 * menambahkan foto lalu menekan "Tayangkan".
 */
class Massal extends Seller_Controller
{
    const MAKS_BARIS = 300;

    /** Urutan kolom templat. Judul kolom dicocokkan longgar saat dibaca. */
    protected $kolom = array(
        'nama'      => 'Nama produk *',
        'harga'     => 'Harga (Rp) *',
        'stok'      => 'Stok (kosong = tidak dibatasi)',
        'kategori'  => 'Kategori',
        'deskripsi' => 'Deskripsi',
        'tangan'    => 'Buatan tangan (ya/tidak)',
        'preorder'  => 'Pre-order (ya/tidak)',
    );

    public function __construct()
    {
        parent::__construct();
        $this->load->model('product_model');
        $this->load->library('excel_lib');
    }

    public function index()
    {
        $this->render('penjual/v_massal', array(
            'hasil'    => $this->session->flashdata('massal_hasil'),
            'kategori' => $this->product_model->categories_simple(),
            'kolom'    => $this->kolom,
        ));
    }

    public function contoh()
    {
        $kat = array_column($this->product_model->categories_simple(), 'name');
        $this->excel_lib->unduh('templat-produk.xlsx', array(
            'Produk' => array(
                array_values($this->kolom),
                array('Keripik Pisang Cokelat', 15000, 50, isset($kat[0]) ? $kat[0] : '', 'Renyah, manis, tanpa pengawet. Berat 200 gram.', 'ya', 'tidak'),
                array('Tas Anyaman Pandan', 85000, '', isset($kat[1]) ? $kat[1] : '', 'Dianyam tangan, ukuran 30x25 cm.', 'ya', 'ya'),
            ),
            'Daftar kategori' => array_merge(array(array('Tulis salah satu nama kategori ini di kolom Kategori')),
                                             array_map(function ($k) { return array($k); }, $kat)),
        ));
    }

    public function unggah()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $f = isset($_FILES['berkas']) ? $_FILES['berkas'] : NULL;
        if ( ! $f || $f['error'] !== UPLOAD_ERR_OK || ! is_uploaded_file($f['tmp_name'])) {
            $this->session->set_flashdata('error', 'Pilih berkas Excel (.xlsx) atau .csv dulu.');
            return redirect('seller/massal');
        }
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if ( ! in_array($ext, array('xlsx', 'csv'), TRUE) || $f['size'] > 2 * 1024 * 1024) {
            $this->session->set_flashdata('error', 'Berkas harus .xlsx atau .csv, maksimal 2 MB. Di Excel: File > Save As > Excel Workbook.');
            return redirect('seller/massal');
        }

        $baris = $this->excel_lib->baca($f['tmp_name'], $f['name']);
        if ( ! $baris || count($baris) < 2) {
            $this->session->set_flashdata('error', 'Berkas tidak terbaca atau kosong. Pakai templat dari tombol "Unduh templat".');
            return redirect('seller/massal');
        }

        $peta = $this->petakan_judul(array_shift($baris));
        if ( ! isset($peta['nama'], $peta['harga'])) {
            $this->session->set_flashdata('error', 'Kolom "Nama produk" dan "Harga" tidak ditemukan di baris pertama. Pakai templat yang disediakan.');
            return redirect('seller/massal');
        }

        $kategori = array();
        foreach ($this->product_model->categories_simple() as $k) {
            $kategori[mb_strtolower(trim($k['name']))] = (int) $k['id'];
        }
        $kat_bawaan = $kategori ? reset($kategori) : NULL;

        $hasil = array('ok' => 0, 'galat' => array());
        foreach (array_slice($baris, 0, self::MAKS_BARIS) as $i => $r) {
            $no = $i + 2;   // nomor baris seperti terlihat di Excel
            $ambil = function ($k) use ($r, $peta) {
                return isset($peta[$k], $r[$peta[$k]]) ? trim((string) $r[$peta[$k]]) : '';
            };
            $nama = mb_substr($ambil('nama'), 0, 150);
            if ($nama === '' && $ambil('harga') === '') {
                continue;   // baris kosong
            }
            $harga = (int) preg_replace('/[^0-9]/', '', $ambil('harga'));
            if ($nama === '' || $harga <= 0) {
                $hasil['galat'][] = 'Baris ' . $no . ': nama dan harga wajib diisi.';
                continue;
            }
            $stok_teks = $ambil('stok');
            $kat_teks = mb_strtolower($ambil('kategori'));
            if ($kat_teks !== '' && ! isset($kategori[$kat_teks])) {
                $hasil['galat'][] = 'Baris ' . $no . ' (' . $nama . '): kategori "' . $ambil('kategori') . '" tidak dikenal, dipakai kategori pertama.';
            }

            $data = array(
                'store_id'    => (int) $this->store['id'],
                'category_id' => isset($kategori[$kat_teks]) ? $kategori[$kat_teks] : $kat_bawaan,
                'name'        => $nama,
                'slug'        => $this->slug_unik($nama),
                'description' => $ambil('deskripsi') !== '' ? '<p>' . nl2br(html_escape(mb_substr($ambil('deskripsi'), 0, 4000))) . '</p>' : NULL,
                'price'       => $harga,
                'stock'       => $stok_teks === '' ? NULL : max(0, (int) preg_replace('/[^0-9]/', '', $stok_teks)),
                'image'       => NULL,
                'is_active'   => 0,
            );
            $tambahan = array(
                'buatan_tangan' => $this->ya($ambil('tangan')),
                'is_preorder'   => $this->ya($ambil('preorder')),
            );
            $data += array_intersect_key($tambahan, array_flip($this->db->list_fields('products')));

            $debug = $this->db->db_debug;
            $this->db->db_debug = FALSE;
            $ok = $this->db->insert('products', $data);
            $this->db->db_debug = $debug;

            if ($ok) {
                $hasil['ok']++;
            } else {
                $hasil['galat'][] = 'Baris ' . $no . ' (' . $nama . '): gagal disimpan.';
            }
        }

        if (count($baris) > self::MAKS_BARIS) {
            $hasil['galat'][] = 'Hanya ' . self::MAKS_BARIS . ' baris pertama yang diproses. Unggah sisanya di berkas terpisah.';
        }

        $this->session->set_flashdata('massal_hasil', $hasil);
        $this->session->set_flashdata($hasil['ok'] ? 'sukses' : 'error', $hasil['ok']
            ? $hasil['ok'] . ' produk ditambahkan sebagai draf. Tambahkan foto, lalu tekan "Tayangkan".'
            : 'Tidak ada produk yang tersimpan.');
        return redirect('seller/massal');
    }

    public function duplikat($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $p = $this->db->where('id', (int) $id)->where('store_id', (int) $this->store['id'])->get('products')->row_array();
        if ( ! $p) {
            show_404();
        }

        $lama_id = (int) $p['id'];
        unset($p['id'], $p['created_at'], $p['updated_at']);
        foreach (array('rating_avg', 'rating_count', 'terjual_count', 'stock_habis_at') as $k) {
            if (array_key_exists($k, $p)) {
                $p[$k] = in_array($k, array('stock_habis_at'), TRUE) ? NULL : 0;
            }
        }
        $p['name'] = mb_substr($p['name'] . ' (salinan)', 0, 150);
        $p['slug'] = $this->slug_unik($p['name']);
        $p['is_active'] = 0;   // disembunyikan sampai diperiksa penjual

        $this->db->trans_begin();
        $this->db->insert('products', $p);
        $baru = (int) $this->db->insert_id();

        /* Foto varian/tambahan memakai nama berkas YANG SAMA dengan aslinya.
           Aman selama keduanya ada; Seller::simpan_baris hanya menghapus
           berkas yang tidak lagi dirujuk produk mana pun. */
        foreach (array('product_variants', 'product_addons', 'product_tiers') as $tabel) {
            if ( ! $this->db->table_exists($tabel)) {
                continue;
            }
            foreach ($this->db->where('product_id', $lama_id)->get($tabel)->result_array() as $r) {
                unset($r['id']);
                $r['product_id'] = $baru;
                $this->db->insert($tabel, $r);
            }
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Gagal menyalin produk.');
            return redirect('seller');
        }
        $this->db->trans_commit();

        $this->session->set_flashdata('sukses', 'Salinan dibuat dan disembunyikan. Ubah nama/harganya, lalu tayangkan.');
        return redirect('seller/form/' . $baru);
    }

    /* ------------------------------------------------------------------ */

    /** Cocokkan judul kolom berkas ke kunci internal, longgar. */
    protected function petakan_judul(array $judul)
    {
        $kunci = array(
            'nama' => '/nama/', 'harga' => '/harga/', 'stok' => '/stok/', 'kategori' => '/kategori/',
            'deskripsi' => '/deskripsi|keterangan/', 'tangan' => '/tangan|handmade/', 'preorder' => '/pre.?order|po\b/',
        );
        $peta = array();
        foreach ($judul as $i => $j) {
            $j = mb_strtolower(trim((string) $j));
            foreach ($kunci as $k => $pola) {
                if ( ! isset($peta[$k]) && preg_match($pola, $j)) {
                    $peta[$k] = $i;
                    break;
                }
            }
        }
        return $peta;
    }

    protected function ya($v)
    {
        return in_array(mb_strtolower(trim((string) $v)), array('ya', 'y', 'yes', '1', 'iya', 'v', 'x'), TRUE) ? 1 : 0;
    }

    protected function slug_unik($nama)
    {
        $this->load->helper('text');
        $dasar = url_title(convert_accented_characters($nama), '-', TRUE) ?: 'produk';
        $slug = $dasar;
        $n = 2;
        while ($this->db->where('store_id', (int) $this->store['id'])->where('slug', $slug)->count_all_results('products')) {
            $slug = $dasar . '-' . $n++;
        }
        return $slug;
    }
}
