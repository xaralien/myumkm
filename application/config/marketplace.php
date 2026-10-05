<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -----------------------------------------------------------------------
| Pengaturan marketplace
| Simpan di: application/config/marketplace.php
| -----------------------------------------------------------------------
*/

/* Komisi marketplace, dalam persen dari harga barang (BUKAN dari ongkir).
   Ongkir diteruskan utuh ke penjual karena dia yang membayar kurirnya.

   Mengubah angka ini TIDAK mengubah pesanan lama: bagian tiap toko dihitung
   dan disimpan sekali saat pembayaran diterima. */
$config['komisi_persen'] = 5;

/* Berapa hari uang ditahan setelah pesanan selesai sebelum boleh dicairkan.
   0 = boleh langsung. Ditahan beberapa hari memberi ruang kalau pembeli
   mengajukan refund tepat setelah barang sampai. */
$config['tahan_hari'] = 0;
