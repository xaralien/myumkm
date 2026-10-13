<!-- application/views/penjual/v_akademi_modul.php -->
<div class="panel-judul">
  <div>
    <p class="hint mb-1"><?= html_escape($m['kategori']) ?> &middot; <?= (int) $m['durasi_menit'] ?> menit</p>
    <h1><?= html_escape($m['judul']) ?></h1>
  </div>
  <a href="<?= site_url('seller/akademi') ?>" class="btn btn-black-hover-outline">Semua modul</a>
</div>

<div class="row">
  <div class="col-lg-8">
    <?php if ($embed): ?>
      <div class="video-bingkai mb-3">
        <iframe src="<?= html_escape($embed) ?>" title="<?= html_escape($m['judul']) ?>" loading="lazy"
                allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
      </div>
    <?php endif; ?>
    <div class="form-card modul-isi"><?= html_aman($m['isi']) ?></div>
  </div>
  <div class="col-lg-4">
    <div class="form-card">
      <h3 class="form-card-title"><?= $soal ? 'Kuis singkat' : 'Selesai membaca?' ?></h3>
      <?php if ($progres): ?>
        <p><span class="badge-status is-ok">✓ Modul selesai</span><?= $progres['skor'] !== NULL ? ' &middot; nilai ' . (int) $progres['skor'] : '' ?></p>
      <?php endif; ?>
      <?= form_open('seller/akademi/jawab/' . $m['slug']) ?>
        <?php foreach ($soal as $i => $s): ?>
          <fieldset class="mb-3">
            <legend class="form-label"><?= $i + 1 ?>. <?= html_escape($s['pertanyaan']) ?></legend>
            <?php foreach ($s['pilihan'] as $j => $p): ?>
              <label class="kuis-pilihan">
                <input type="radio" name="jawaban[<?= (int) $s['id'] ?>]" value="<?= $j ?>" required>
                <span><?= html_escape($p) ?></span>
              </label>
            <?php endforeach; ?>
          </fieldset>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-primary w-100"><?= $soal ? 'Kirim jawaban' : 'Tandai selesai' ?></button>
      <?= form_close() ?>
    </div>
  </div>
</div>
