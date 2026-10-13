<!-- application/views/pendamping/v_index.php -->
<div class="panel-judul">
  <div>
    <h1>Toko binaan</h1>
    <p class="hint">Diurutkan dari skor Naik Kelas terendah &mdash; yang paling butuh bantuan ada di atas.</p>
  </div>
</div>
<?php if ( ! $toko): ?>
  <div class="fitur-kosong">Belum ada toko yang ditugaskan kepadamu. Hubungi admin.</div>
<?php else: ?>
  <div class="adm-tabel">
    <?php foreach ($toko as $t): ?>
      <a href="<?= site_url('pendamping/toko/' . (int) $t['id']) ?>" class="adm-baris pdm-baris">
        <div>
          <strong><?= html_escape($t['name']) ?></strong>
          <em><?= html_escape($t['pemilik']) ?> &middot; <?= html_escape($t['district_name']) ?>, <?= html_escape($t['regency_name']) ?>
            &middot; kunjungan terakhir: <?= $t['akhir'] ? tgl_id($t['akhir']['tanggal']) : 'belum pernah' ?></em>
        </div>
        <div class="adm-aksi">
          <span class="lencana <?= $t['nk']['level']['kode'] === 'unggul' ? 'lencana-emas' : ($t['nk']['level']['kode'] === 'berkembang' ? 'lencana-hijau' : '') ?>">
            <?= $t['nk']['level']['ikon'] ?> <?= html_escape($t['nk']['level']['nama']) ?> &middot; <?= (int) $t['nk']['skor'] ?>
          </span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
