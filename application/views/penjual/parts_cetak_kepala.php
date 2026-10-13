<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= html_escape($judul) ?></title>
<style>
  * { box-sizing: border-box; }
  body { font-family: Arial, Helvetica, sans-serif; color: #000; margin: 0; background: #e9eef2; }
  .alat { position: sticky; top: 0; background: #22466C; color: #fff; padding: 10px 16px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
  .alat a, .alat button { background: #fff; color: #22466C; border: 0; border-radius: 8px; padding: 7px 12px; font-weight: 700; text-decoration: none; cursor: pointer; font-size: 14px; }
  .alat a.aktif { background: #FDD977; }
  .lembar { background: #fff; margin: 16px auto; padding: 0; }
  .ukuran-a6 .lembar { width: 105mm; min-height: 148mm; padding: 6mm; }
  .ukuran-80 .lembar { width: 80mm; padding: 3mm; font-size: 12px; }
  .ukuran-58 .lembar { width: 58mm; padding: 2mm; font-size: 11px; }
  .garis { border-top: 1px dashed #000; margin: 6px 0; }
  .besar { font-size: 1.35em; font-weight: 800; }
  .kecil { font-size: .82em; }
  table { width: 100%; border-collapse: collapse; }
  td { padding: 2px 0; vertical-align: top; }
  td.k { text-align: right; white-space: nowrap; }
  @media print {
    body { background: #fff; }
    .alat { display: none; }
    .lembar { margin: 0; page-break-after: always; box-shadow: none; }
    @page { margin: 0; size: <?= $ukuran === 'a6' ? 'A6' : ($ukuran === '80' ? '80mm auto' : '58mm auto') ?>; }
  }
</style>
</head>
<body class="ukuran-<?= $ukuran ?>">
<?php
  $q = function ($u) use ($ids) {
    return '?' . http_build_query(array('id' => array_values($ids), 'ukuran' => $u));
  };
?>
<div class="alat">
  <strong><?= html_escape($judul) ?></strong>
  <span>Ukuran:</span>
  <a href="<?= current_url() . $q('a6') ?>" class="<?= $ukuran === 'a6' ? 'aktif' : '' ?>">A6 (printer biasa)</a>
  <a href="<?= current_url() . $q('80') ?>" class="<?= $ukuran === '80' ? 'aktif' : '' ?>">Thermal 80 mm</a>
  <a href="<?= current_url() . $q('58') ?>" class="<?= $ukuran === '58' ? 'aktif' : '' ?>">Thermal 58 mm</a>
  <button type="button" onclick="window.print()">🖨 Cetak</button>
  <a href="<?= site_url('seller/orders') ?>">Kembali</a>
</div>
