<!doctype html>
<html lang="id"><head><meta charset="utf-8"><title>Sertifikat Akademi UMKM</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,700&display=swap">
<style>
  body { margin: 0; background: #e9eef2; font-family: Arial, sans-serif; color: #19344F; }
  .alat { text-align: center; padding: 12px; } .alat button { background: #22466C; color: #fff; border: 0; border-radius: 8px; padding: 10px 18px; font-weight: 700; cursor: pointer; }
  .serti { width: 277mm; height: 190mm; margin: 0 auto 20px; background: #fff; box-sizing: border-box; padding: 18mm; text-align: center;
    border: 10px solid #22466C; outline: 3px solid #CBA02D; outline-offset: -18px; position: relative; }
  h1 { font-family: 'Fraunces', Georgia, serif; font-size: 44px; margin: 8px 0 4px; }
  .nama { font-family: 'Fraunces', Georgia, serif; font-size: 34px; margin: 18px 0 2px; border-bottom: 2px solid #CBA02D; display: inline-block; padding: 0 30px 6px; }
  ul { list-style: none; padding: 0; margin: 14px 0; font-size: 14px; columns: 2; } li { margin: 3px 0; }
  @media print { body { background: #fff; } .alat { display: none; } .serti { margin: 0; } @page { size: A4 landscape; margin: 0; } }
</style></head><body>
<div class="alat"><button onclick="window.print()">🖨 Cetak / Simpan PDF</button></div>
<div class="serti">
  <div style="letter-spacing:.3em;font-size:13px">AKADEMI UMKM &middot; <?= html_escape(strtoupper($brand)) ?></div>
  <h1>Sertifikat Penyelesaian</h1>
  <div>diberikan kepada</div>
  <div class="nama"><?= html_escape($pemilik) ?></div>
  <div style="margin-top:6px">pemilik <strong><?= html_escape($toko['name']) ?></strong></div>
  <p>telah menyelesaikan <?= count($modul) ?> modul pembelajaran usaha:</p>
  <ul><?php foreach ($modul as $m): ?><li>✓ <?= html_escape($m['judul']) ?></li><?php endforeach; ?></ul>
  <p style="margin-top:18px"><?= tgl_id(substr($tanggal, 0, 10)) ?></p>
</div>
</body></html>
