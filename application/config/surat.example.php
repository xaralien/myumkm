<?php
defined('BASEPATH') or exit('No direct script access allowed');

/* =============================================================================
   surat.example.php - pengaturan pengirim email

   Salin berkas ini menjadi surat.php, lalu isi datanya:

       cp application/config/surat.example.php application/config/surat.php

   surat.php TIDAK ikut di-commit (lihat .gitignore). Jangan menaruh sandi di
   berkas contoh ini - yang ini memang masuk ke repo.

   SELAMA 'host' MASIH KOSONG, EMAIL TIDAK DIKIRIM. Isinya ditulis ke
   application/logs/ sebagai gantinya, lengkap dengan tautannya, supaya reset
   sandi tetap bisa diuji di localhost tanpa SMTP. Lihat Surat_lib.
   ========================================================================== */

/* -----------------------------------------------------------------------------
   Pengenal pengirim.

   from_email sebaiknya memakai domain yang sama dengan situsnya. Mengirim
   "dari" alamat Gmail lewat SMTP orang lain adalah cara tercepat masuk folder
   spam: penerima memeriksa apakah server pengirim memang berhak mengirim atas
   nama domain itu (SPF/DKIM), dan Gmail tidak mengizinkan server lain.
   -------------------------------------------------------------------------- */
$config['surat']['from_email'] = 'noreply@kodesis.id';
$config['surat']['from_name']  = 'Sapa UMKM';

// Ke mana balasan pergi kalau ada yang menekan Reply. Dikosongkan berarti
// balasannya hilang ke noreply - dan orang MEMANG membalas email begini.
$config['surat']['reply_to']   = 'halo@kodesis.id';

/* -----------------------------------------------------------------------------
   SMTP.

   Beberapa contoh yang umum dipakai:

   Gmail / Google Workspace
     host  = ssl://smtp.gmail.com     port = 465
     user  = alamat Gmail lengkap
     pass  = App Password 16 huruf, BUKAN sandi akunnya
             (butuh 2FA aktif; buat di myaccount.google.com/apppasswords)
     Batas sekitar 500 email/hari.

   Brevo (dulu Sendinblue)
     host  = smtp-relay.brevo.com     port = 587
     Gratis sampai 300 email/hari, dan reputasi kirimnya lebih baik.

   cPanel di hosting sendiri
     host  = mail.kodesis.id          port = 465 (ssl) atau 587 (tls)
     user  = alamat email penuh yang dibuat di cPanel

   Mailtrap - untuk MENGUJI saja. Semua email tertahan di kotak masuk palsu
   dan tidak pernah sampai ke penerima sungguhan. Cocok dipakai sebelum berani
   mengirim ke email pembeli.
     host  = sandbox.smtp.mailtrap.io port = 2525
   -------------------------------------------------------------------------- */
$config['surat']['host'] = '';          // Kosong = email tidak dikirim, hanya dicatat ke log.
$config['surat']['port'] = 587;
$config['surat']['user'] = '';
$config['surat']['pass'] = '';

/* 'tls' untuk port 587, 'ssl' untuk port 465, '' kalau tanpa enkripsi.

   Jangan dikosongkan di server sungguhan: tanpa enkripsi, sandi SMTP di atas
   melintas sebagai teks biasa. */
$config['surat']['crypto'] = 'tls';

/* Berapa detik menunggu server SMTP sebelum menyerah.

   Dibuat pendek dengan sengaja. Pengunjung sedang menunggu halaman termuat
   saat email dikirim; server SMTP yang menggantung 30 detik berarti halaman
   yang menggantung 30 detik, dan pengunjungnya mengira situsnya rusak. Lebih
   baik emailnya gagal dan dicatat ke log daripada halamannya ikut mati. */
$config['surat']['timeout'] = 8;
