<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/* =============================================================================
   Salin berkas ini menjadi duitku_lokal.php, lalu isi kredensialnya.
   duitku_lokal.php masuk .gitignore - tidak akan ikut ter-commit.

   Ambil dari dashboard Duitku: Project -> Merchant Code & API Key.
   ========================================================================== */

$config['duitku']['merchant_code'] = 'ISI_KODE_MERCHANT';
$config['duitku']['api_key']       = 'ISI_API_KEY';

// Nilai acak panjang untuk memanggil payment/sweep lewat cron.
$config['duitku']['sweep_key']     = 'ISI_NILAI_ACAK_PANJANG';

// Alamat publik untuk callback saat di localhost (lihat catatan di duitku.php).
// $config['duitku']['callback_url'] = 'https://...';
// $config['duitku']['return_url']   = 'https://...';
