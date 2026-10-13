<!-- application/views/admin/v_akademi_modul.php -->
<div class="panel-judul">
  <h1><?= $m ? 'Ubah modul' : 'Modul baru' ?></h1>
  <a href="<?= site_url('admin/akademi') ?>" class="btn btn-black-hover-outline">Kembali</a>
</div>
<?= form_open('admin/akademi/modul_simpan') ?>
<input type="hidden" name="id" value="<?= $m ? (int) $m['id'] : '' ?>">
<div class="row">
  <div class="col-lg-8">
    <div class="form-card">
      <div class="mb-3"><label class="form-label">Judul *</label>
        <input type="text" name="judul" class="form-control" maxlength="150" required value="<?= html_escape($m['judul'] ?? '') ?>"></div>
      <div class="mb-3"><label class="form-label">Ringkasan (1 kalimat)</label>
        <input type="text" name="ringkas" class="form-control" maxlength="255" value="<?= html_escape($m['ringkas'] ?? '') ?>"></div>
      <div class="mb-3"><label class="form-label">Tautan video YouTube/Vimeo <span class="hint">(opsional, 1-3 menit paling efektif)</span></label>
        <input type="url" name="video_url" class="form-control" value="<?= html_escape($m['video_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=..."></div>
      <div class="mb-3"><label class="form-label">Isi materi</label>
        <textarea name="isi" id="isiModul" class="form-control" rows="12"><?= html_escape($m['isi'] ?? '') ?></textarea>
        <p class="hint">Boleh pakai paragraf, daftar, tebal/miring. Gunakan bahasa sehari-hari dan kalimat pendek.</p></div>
    </div>
    <div class="form-card">
      <h3 class="form-card-title">Kuis <span class="hint">(opsional - tanpa kuis, penjual cukup menekan "Tandai selesai")</span></h3>
      <div class="baris-dinamis" id="soalList">
        <?php $daftar = $soal ?: array(array('pertanyaan' => '', 'pilihan' => array('', '', ''), 'jawaban' => 0)); ?>
        <?php foreach ($daftar as $i => $s): ?>
          <div class="varian-baris soal-baris">
            <input type="text" name="soal_tanya[]" class="form-control" placeholder="Pertanyaan" value="<?= html_escape($s['pertanyaan']) ?>">
            <?php for ($j = 0; $j < 3; $j++): ?>
              <input type="text" name="soal_pilihan[<?= $i ?>][]" class="form-control" placeholder="Pilihan <?= chr(65 + $j) ?>" value="<?= html_escape($s['pilihan'][$j] ?? '') ?>">
            <?php endfor; ?>
            <select name="soal_benar[]" class="form-select">
              <?php for ($j = 0; $j < 3; $j++): ?><option value="<?= $j ?>" <?= (int) $s['jawaban'] === $j ? 'selected' : '' ?>>Jawaban benar: <?= chr(65 + $j) ?></option><?php endfor; ?>
            </select>
          </div>
        <?php endforeach; ?>
      </div>
      <p class="hint mt-2 mb-0">Simpan dulu untuk menambah soal berikutnya (baris kosong baru muncul setelah disimpan).</p>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="form-card">
      <div class="mb-3"><label class="form-label">Kategori</label>
        <select name="kategori" class="form-select"><?php foreach ($kategori as $k): ?><option <?= ($m['kategori'] ?? '') === $k ? 'selected' : '' ?>><?= $k ?></option><?php endforeach; ?></select></div>
      <div class="mb-3"><label class="form-label">Durasi (menit)</label>
        <input type="number" name="durasi_menit" class="form-control" min="1" max="120" value="<?= (int) ($m['durasi_menit'] ?? 5) ?>"></div>
      <div class="mb-3"><label class="form-label">Urutan</label>
        <input type="number" name="urutan" class="form-control" value="<?= (int) ($m['urutan'] ?? 0) ?>"></div>
      <div class="form-check mb-3"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="aktif" <?= ($m['is_active'] ?? 1) ? 'checked' : '' ?>>
        <label for="aktif" class="form-check-label">Tampilkan ke penjual</label></div>
      <button type="submit" class="btn btn-primary w-100">Simpan modul</button>
    </div>
  </div>
</div>
<?= form_close() ?>
<?php if ($soal): ?>
<script>
  // Satu baris soal kosong tambahan untuk soal berikutnya.
  (function () { var l = document.getElementById('soalList'); var b = l.lastElementChild.cloneNode(true); var n = l.children.length;
    b.querySelectorAll('input').forEach(function (i) { i.value = ''; i.name = i.name.replace(/\[\d+\]/, '[' + n + ']'); }); l.appendChild(b); })();
</script>
<?php endif; ?>
