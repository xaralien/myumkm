<!-- application/views/penjual/v_qr.php -->
<div class="panel-judul no-print">
  <div>
    <h1>QR code toko</h1>
    <p class="hint">Cetak dan tempel di etalase, gerobak, kemasan, atau kartu nama. Pembeli cukup memindai dengan kamera HP.</p>
  </div>
  <div class="pintas-baris">
    <button type="button" class="btn btn-primary" onclick="window.print()">🖨 Cetak</button>
    <a href="#" class="btn btn-black-hover-outline" id="unduhQr" download="qr-<?= html_escape($toko['slug']) ?>.png">⬇ Unduh gambar</a>
  </div>
</div>

<div class="qr-kartu" id="qrKartu">
  <p class="qr-ajak">Belanja online di</p>
  <h2><?= html_escape($toko['name']) ?></h2>
  <div id="qrGambar" class="qr-gambar" aria-label="QR code menuju halaman toko"></div>
  <p class="qr-url"><?= html_escape(preg_replace('#^https?://#', '', $url)) ?></p>
  <p class="qr-ajak">Pindai dengan kamera HP &middot; <?= html_escape($this->config->item('nama_brand')) ?></p>
</div>

<p class="hint no-print text-center mt-3">Tautan: <a href="<?= html_escape($url) ?>" target="_blank"><?= html_escape($url) ?></a></p>

<!-- qrcode-generator 1.4.4 (MIT, Kazuhiko Arase) disimpan lokal: tetap jalan
     walau CDN diblokir jaringan kantor atau sinyal lemah. -->
<script src="<?= aset('assets/js/qrcode.js') ?>"></script>
<script>
  (function () {
    var wadah = document.getElementById('qrGambar');
    if (typeof qrcode === 'undefined') {
      wadah.textContent = 'QR gagal dimuat (periksa koneksi internet). Tautan tokomu tetap tertulis di bawah.';
      return;
    }
    var qr = qrcode(0, 'M');
    qr.addData(<?= json_encode($url) ?>);
    qr.make();
    wadah.innerHTML = qr.createSvgTag({ cellSize: 8, margin: 2, scalable: true });
    // Versi PNG untuk diunduh (dibagikan ke WA, dicetak di percetakan).
    var img = new Image();
    img.onload = function () {
      var c = document.createElement('canvas'); c.width = c.height = 800;
      var g = c.getContext('2d'); g.fillStyle = '#fff'; g.fillRect(0, 0, 800, 800); g.drawImage(img, 0, 0, 800, 800);
      document.getElementById('unduhQr').href = c.toDataURL('image/png');
    };
    img.src = qr.createDataURL(20, 4);
  })();
</script>
