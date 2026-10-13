<!-- application/views/penjual/v_ongkir.php -->
<div class="panel-judul">
  <div>
    <h1>Cek tarif kurir</h1>
    <p class="hint">Bandingkan ongkos kirim beberapa kurir sekaligus sebelum mengirim paket, supaya tidak salah pilih dan tidak rugi ongkir.</p>
  </div>
</div>

<?php if ( ! $aktif): ?>
  <div class="alert-box alert-warn mb-4">Cek tarif otomatis belum diaktifkan admin. Sementara itu, gunakan daftar kurir di bawah
    untuk membuka situs resminya. Nomor resi yang kamu isi saat menandai pesanan dikirim tetap bisa dilacak pembeli.</div>
<?php endif; ?>

<form method="get" action="<?= site_url('seller/ongkir') ?>" class="form-card">
  <div class="row">
    <div class="col-md-4 mb-3">
      <label class="form-label" for="asal">Kode pos asal (toko)</label>
      <input type="text" inputmode="numeric" maxlength="5" class="form-control form-control-lg" id="asal" name="asal" value="<?= html_escape($f['asal']) ?>" placeholder="40115">
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label" for="tujuan">Kode pos tujuan (pembeli)</label>
      <input type="text" inputmode="numeric" maxlength="5" class="form-control form-control-lg" id="tujuan" name="tujuan" value="<?= html_escape($f['tujuan']) ?>" placeholder="12950">
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label" for="berat">Berat paket (gram)</label>
      <input type="number" min="1" max="50000" class="form-control form-control-lg" id="berat" name="berat" value="<?= $f['berat'] ?: '' ?>" placeholder="1000">
    </div>
  </div>
  <button type="submit" class="btn btn-primary btn-besar"<?= $aktif ? '' : ' disabled' ?>>Cek tarif</button>
</form>

<?php if (is_string($hasil)): ?>
  <div class="alert-box alert-error mt-3"><?= html_escape($hasil) ?></div>
<?php elseif (is_array($hasil)): ?>
  <div class="form-card mt-3">
    <h3 class="form-card-title">Tarif termurah di atas</h3>
    <div class="table-responsive">
      <table class="table tarif-tabel">
        <thead><tr><th>Kurir</th><th>Layanan</th><th>Estimasi</th><th class="text-end">Ongkir</th></tr></thead>
        <tbody>
          <?php foreach ($hasil as $i => $r): ?>
            <tr<?= $i === 0 ? ' class="table-success"' : '' ?>>
              <td><?= html_escape($r['kurir']) ?></td>
              <td><?= html_escape($r['layanan']) ?></td>
              <td><?= html_escape($r['estimasi']) ?></td>
              <td class="text-end"><strong><?= rupiah($r['harga']) ?></strong></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<div class="form-card mt-3">
  <h3 class="form-card-title">Kurir yang dikenali sistem</h3>
  <p class="hint">Saat menandai pesanan dikirim, ketik nama kurir persis seperti di bawah agar pembeli bisa melacak paketnya.</p>
  <p class="mb-0"><?= html_escape(implode(' · ', $kurir)) ?></p>
</div>
