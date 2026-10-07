<?php
/* =============================================================================
   Kerangka halaman galat bertema.
   Simpan di: application/views/errors/html/galat_kerangka.php

   BERDIRI SENDIRI, dan itu disengaja. Halaman galat sering tampil justru
   ketika sesuatu di aplikasi sedang rusak - database mati, konfigurasi
   salah, kelas gagal dimuat. Kalau berkas ini ikut memanggil helper atau
   library CodeIgniter, ia bisa ikut gagal, dan pengunjung melihat layar
   putih kosong alih-alih penjelasan.

   Karena itu: tanpa site_url(), tanpa base_url(), tanpa $this. Gaya
   ditulis inline, alamat tautan dihitung dari $_SERVER.

   Dipakai lewat galat_tampilkan(), dipanggil tiap berkas error_*.php.
   ========================================================================== */

if ( ! function_exists('galat_akar')) {
    /**
     * Alamat akar situs, dihitung sendiri tanpa base_url().
     *
     * Dipakai untuk tautan "Beranda" dan kawan-kawan. Kalau salah, tautannya
     * yang rusak - bukan halamannya, dan pengunjung tetap membaca pesannya.
     */
    function galat_akar()
    {
        $skema = (( ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'))
            ? 'https' : 'http';

        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

        // index.php ada di akar project, jadi foldernya = akar situs.
        $dir = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '/';
        $dir = str_replace('\\', '/', $dir);
        $dir = ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');

        return $skema . '://' . $host . $dir . '/';
    }
}

if ( ! function_exists('galat_tampilkan')) {
    /**
     * @param array $d  kode, judul, pesan, saran (array), rincian (opsional)
     */
    function galat_tampilkan(array $d)
    {
        $akar    = galat_akar();
        $kode    = isset($d['kode']) ? $d['kode'] : '';
        $judul   = isset($d['judul']) ? $d['judul'] : 'Ada yang tidak beres';
        $pesan   = isset($d['pesan']) ? $d['pesan'] : '';
        $saran   = isset($d['saran']) ? $d['saran'] : array();
        $rincian = isset($d['rincian']) ? $d['rincian'] : '';
        $e       = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= $e($kode ? $kode . ' - ' . $judul : $judul) ?> &middot; Sapa UMKM</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --tua: #22466C;
    --tua-2: #19344F;
    --biru: #2F6A8A;
    --emas: #CBA02D;
    --emas-muda: #FDD977;
    --samar: #9FB6C9;
    --serif: 'Fraunces', Georgia, serif;
  }

  * { box-sizing: border-box; }

  body {
    margin: 0;
    min-height: 100vh;
    min-height: 100dvh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32px 20px;
    background: linear-gradient(135deg, #2F6A8A 0%, #22466C 56.5%);
    color: #FFFFFF;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    position: relative;
    overflow-x: hidden;
  }

  /* Motif kawung, sama seperti di beranda - supaya halaman galat tetap
     terasa bagian dari situs yang sama, bukan layar asing. */
  body::before {
    content: "";
    position: fixed;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='72' height='72'%3E%3Cg fill='none' stroke='%23FFD97B' stroke-width='1.4'%3E%3Cellipse cx='36' cy='18' rx='10' ry='16'/%3E%3Cellipse cx='36' cy='54' rx='10' ry='16'/%3E%3Cellipse cx='18' cy='36' rx='16' ry='10'/%3E%3Cellipse cx='54' cy='36' rx='16' ry='10'/%3E%3Ccircle cx='36' cy='36' r='2.6' fill='%23FFD97B'/%3E%3C/g%3E%3C/svg%3E");
    background-size: 96px 96px;
    opacity: .1;
    pointer-events: none;
  }

  .kotak { position: relative; width: 100%; max-width: 560px; text-align: center; }

  .merek {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 30px;
    font-family: var(--serif);
    font-size: 17px;
    font-weight: 700;
    letter-spacing: .02em;
    color: #FFFFFF;
    text-decoration: none;
  }
  .merek span { color: var(--emas-muda); }

  .kode {
    font-family: var(--serif);
    font-size: clamp(76px, 22vw, 136px);
    font-weight: 700;
    line-height: .9;
    margin: 0;
    background: linear-gradient(135deg, #FDD977 0%, #CBA02D 58%, #A07B16 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    /* Fallback kalau background-clip tidak didukung: tanpa ini angkanya
       bisa tampil transparan alias tidak terlihat sama sekali. */
    -webkit-text-fill-color: transparent;
  }
  @supports not ((-webkit-background-clip: text) or (background-clip: text)) {
    .kode { color: var(--emas-muda); -webkit-text-fill-color: currentColor; }
  }

  .ikon { margin: 0 auto 18px; display: block; color: var(--emas-muda); }

  h1 {
    font-family: var(--serif);
    font-size: clamp(23px, 5.5vw, 30px);
    font-weight: 700;
    line-height: 1.25;
    margin: 14px 0 10px;
  }

  p.pesan {
    margin: 0 auto 26px;
    max-width: 44ch;
    font-size: 15px;
    line-height: 1.65;
    color: #D7E4EE;
  }

  .aksi { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }

  .tombol {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    height: 46px;
    padding: 0 22px;
    border-radius: 999px;
    border: 1.5px solid rgba(255,255,255,.55);
    color: #FFFFFF;
    font-size: 14.5px;
    font-weight: 600;
    text-decoration: none;
    transition: background .15s ease, transform .15s ease;
  }
  .tombol:hover { background: rgba(255,255,255,.14); transform: translateY(-1px); }

  .tombol.utama {
    background: linear-gradient(135deg, #A07B16 0%, #CBA02D 45%, #FDD977 100%);
    border-color: transparent;
    color: #243B4F;
    font-weight: 700;
  }
  .tombol.utama:hover { filter: brightness(1.06); }

  .tautan {
    margin-top: 28px;
    padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,.16);
    font-size: 13.5px;
    color: var(--samar);
  }
  .tautan a { color: var(--emas-muda); text-decoration: none; }
  .tautan a:hover { text-decoration: underline; }

  .rincian {
    margin-top: 24px;
    text-align: left;
    background: rgba(0,0,0,.22);
    border: 1px solid rgba(255,255,255,.14);
    border-radius: 12px;
    padding: 14px 16px;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 12.5px;
    line-height: 1.6;
    color: #E6EEF5;
    overflow-x: auto;
  }
  .rincian b { display: block; margin-bottom: 6px; color: var(--emas-muda); font-family: 'Inter', sans-serif; }

  @media (prefers-reduced-motion: no-preference) {
    .kotak { animation: naik .35s ease-out both; }
    @keyframes naik { from { opacity: 0; transform: translateY(10px); } }
  }
</style>
</head>
<body>
  <main class="kotak">

    <a class="merek" href="<?= $e($akar) ?>">SAPA <span>UMKM</span></a>

    <?php if ($kode): ?>
      <p class="kode"><?= $e($kode) ?></p>
    <?php else: ?>
      <svg class="ikon" viewBox="0 0 24 24" width="56" height="56" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M10.3 3.6 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.6a2 2 0 0 0-3.4 0z"></path>
        <line x1="12" y1="9" x2="12" y2="13"></line>
        <line x1="12" y1="17" x2="12.01" y2="17"></line>
      </svg>
    <?php endif; ?>

    <h1><?= $e($judul) ?></h1>
    <?php if ($pesan): ?><p class="pesan"><?= $e($pesan) ?></p><?php endif; ?>

    <div class="aksi">
      <a class="tombol utama" href="<?= $e($akar) ?>">
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11l9-8 9 8"></path><path d="M5 10v10h14V10"></path></svg>
        Kembali ke beranda
      </a>
      <a class="tombol" href="<?= $e($akar) ?>shop">Lihat katalog</a>
    </div>

    <?php if ($saran): ?>
      <p class="tautan">
        <?php foreach ($saran as $i => $s): ?>
          <?= $i ? ' &middot; ' : '' ?><a href="<?= $e($akar . $s[1]) ?>"><?= $e($s[0]) ?></a>
        <?php endforeach; ?>
      </p>
    <?php endif; ?>

    <?php if ($rincian): ?>
      <!-- Rincian teknis hanya tampil di mode pengembangan. Di produksi
           CodeIgniter sudah menyembunyikannya, dan memang seharusnya:
           jejak galat memberi tahu penyerang struktur folder dan versi
           pustaka yang dipakai. -->
      <div class="rincian"><b>Rincian teknis (hanya mode pengembangan)</b><?= $rincian ?></div>
    <?php endif; ?>

  </main>
</body>
</html>
<?php
    }
}
