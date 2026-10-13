<!-- application/views/admin/v_peta.php -->
<div class="panel-judul"><div><h1>Peta sebaran UMKM</h1>
  <p class="hint"><?= count($titik) ?> toko punya titik lokasi. Hijau = disetujui, abu-abu = menunggu. Tabel di bawah menunjukkan kecamatan yang paling butuh pembinaan.</p></div>
  <a href="<?= site_url('admin/analitik/ekspor') ?>" class="btn btn-black-hover-outline">⬇ Excel</a></div>

<div id="petaSebaran" class="peta-sebaran mb-4" role="img" aria-label="Peta lokasi toko UMKM"></div>

<h2 class="panel-sub">Per kecamatan</h2>
<div class="panel-tabel-bungkus">
  <table class="panel-tabel">
    <thead><tr><th>Kota/Kab.</th><th>Kecamatan</th><th>Toko</th><th>Disetujui</th><th>Menjual 30 hari</th><th>Omzet 30 hari</th><th>Perlu dibina</th></tr></thead>
    <tbody>
      <?php foreach ($sebaran as $s): $pasif = (int) $s['aktif'] - (int) $s['menjual']; ?>
        <tr><td><?= html_escape($s['kota']) ?></td><td><?= html_escape($s['kecamatan']) ?></td><td><?= (int) $s['toko'] ?></td>
          <td><?= (int) $s['aktif'] ?></td><td><?= (int) $s['menjual'] ?></td><td><?= rupiah($s['omzet']) ?></td>
          <td><?= $pasif > 0 ? '<span class="lencana lencana-emas">' . $pasif . ' toko belum menjual</span>' : '-' ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  (function () {
    var titik = <?= json_encode(array_map(function ($t) {
      return array('lat' => (float) $t['lat'], 'lng' => (float) $t['lng'], 'nama' => $t['name'], 'aktif' => (int) $t['is_active'],
                   'wil' => $t['kecamatan'] . ', ' . $t['kota'], 'url' => site_url('toko/' . $t['slug']));
    }, $titik)) ?>;
    var el = document.getElementById('petaSebaran');
    if (typeof L === 'undefined') { el.textContent = 'Peta gagal dimuat (periksa koneksi internet). Lihat tabel di bawah.'; el.classList.add('fitur-kosong'); return; }
    var peta = L.map(el).setView([-2.5, 118], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '&copy; OpenStreetMap' }).addTo(peta);
    var batas = [];
    titik.forEach(function (t) {
      var m = L.circleMarker([t.lat, t.lng], { radius: 8, weight: 2, color: '#fff', fillOpacity: .9,
        fillColor: t.aktif ? '#1E6B3A' : '#8A98A8' }).addTo(peta);
      var a = document.createElement('a'); a.href = t.url; a.target = '_blank'; a.textContent = t.nama;
      var d = document.createElement('div'); d.appendChild(a); d.appendChild(document.createElement('br')); d.appendChild(document.createTextNode(t.wil));
      m.bindPopup(d);
      batas.push([t.lat, t.lng]);
    });
    if (batas.length) { peta.fitBounds(batas, { padding: [30, 30], maxZoom: 13 }); }
  })();
</script>
