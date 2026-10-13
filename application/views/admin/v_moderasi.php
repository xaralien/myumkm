<!-- application/views/admin/v_moderasi.php -->
<div class="panel-judul">
  <div>
    <h1>Moderasi</h1>
    <p class="hint">Turunkan produk terlarang/menyesatkan dan sembunyikan ulasan kasar atau spam. Penjual selalu diberi tahu alasannya.</p>
  </div>
</div>
<nav class="fitur-tab mb-3">
  <a href="<?= site_url('admin/moderasi') ?>" class="<?= $tab === 'produk' ? 'aktif' : '' ?>">Produk</a>
  <a href="<?= site_url('admin/moderasi/ulasan') ?>" class="<?= $tab === 'ulasan' ? 'aktif' : '' ?>">Ulasan</a>
</nav>

<?php if ($laporan): ?>
  <div class="alert-box alert-warn mb-3">
    <strong><?= count($laporan) ?> laporan baru dari pengguna</strong>
    <?php foreach ($laporan as $l): ?>
      <div class="laporan-baris">
        <span>#<?= (int) $l['objek_id'] ?> &middot; <strong><?= html_escape($l['alasan']) ?></strong>
          <?= $l['keterangan'] ? '&mdash; ' . html_escape($l['keterangan']) : '' ?>
          <em class="hint">(<?= html_escape($l['pelapor'] ?: 'tamu') ?>, <?= tgl_id(substr($l['created_at'], 0, 10)) ?>)</em></span>
        <?= form_open('admin/moderasi/laporan/' . (int) $l['id'], array('class' => 'inline-form')) ?>
          <button name="status" value="diabaikan" class="link-btn">Abaikan</button>
        <?= form_close() ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($tab === 'produk'): ?>
  <form method="get" class="fitur-baris-form mb-2">
    <input type="search" name="q" class="form-control" placeholder="Cari produk atau toko" value="<?= html_escape($q) ?>">
    <button class="btn btn-black-hover-outline">Cari</button>
  </form>
  <nav class="fitur-saring mb-3">
    <?php foreach (array('' => 'Semua', 'dilaporkan' => 'Dilaporkan', 'diturunkan' => 'Diturunkan') as $k => $l): ?>
      <a href="<?= site_url('admin/moderasi' . ($k ? '?saring=' . $k : '')) ?>" class="<?= (string) $saring === $k ? 'aktif' : '' ?>"><?= $l ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="adm-tabel">
    <?php foreach ($produk as $p): ?>
      <div class="adm-baris">
        <div>
          <strong><a href="<?= site_url('produk/' . $p['store_slug'] . '/' . $p['slug']) ?>" target="_blank"><?= html_escape($p['name']) ?></a></strong>
          <?php if ($p['laporan']): ?><span class="lencana lencana-merah"><?= (int) $p['laporan'] ?> laporan</span><?php endif; ?>
          <em><?= html_escape($p['store_name']) ?> &middot; <?= rupiah($p['price']) ?> &middot; <?= $p['is_active'] ? 'tayang' : 'tersembunyi' ?>
            <?= $p['diturunkan_at'] ? '&middot; <strong>diturunkan:</strong> ' . html_escape($p['diturunkan_alasan']) : '' ?></em>
        </div>
        <div class="adm-aksi">
          <?php if ($p['diturunkan_at']): ?>
            <?= form_open('admin/moderasi/pulihkan/' . (int) $p['id'], array('class' => 'inline-form')) ?>
              <button class="btn btn-sm btn-black-hover-outline">Pulihkan</button>
            <?= form_close() ?>
          <?php else: ?>
            <details class="tolak-detail">
              <summary class="btn btn-sm btn-black-hover-outline">Turunkan</summary>
              <?= form_open('admin/moderasi/turunkan/' . (int) $p['id']) ?>
                <select name="alasan" class="form-select form-select-sm mb-1" required>
                  <option value="">Pilih alasan</option>
                  <option>Barang terlarang / ilegal</option><option>Foto atau deskripsi menyesatkan</option>
                  <option>Klaim halal/izin palsu</option><option>Melanggar hak cipta / merek</option>
                  <option>Harga tidak wajar / penipuan</option><option>Konten tidak pantas</option>
                </select>
                <button class="btn btn-sm btn-primary">Turunkan &amp; beri tahu</button>
              <?= form_close() ?>
            </details>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <nav class="fitur-saring mb-3">
    <?php foreach (array('' => 'Terbaru', 'dilaporkan' => 'Dilaporkan', 'rendah' => 'Bintang 1-2', 'disembunyikan' => 'Disembunyikan') as $k => $l): ?>
      <a href="<?= site_url('admin/moderasi/ulasan' . ($k ? '?saring=' . $k : '')) ?>" class="<?= (string) $saring === $k ? 'aktif' : '' ?>"><?= $l ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="adm-tabel">
    <?php foreach ($ulasan as $r): ?>
      <div class="adm-baris">
        <div>
          <strong><?= str_repeat('★', (int) $r['rating']) ?><?= str_repeat('☆', 5 - (int) $r['rating']) ?></strong>
          <?= html_escape($r['nama']) ?> &rarr; <?= html_escape($r['produk_nama']) ?> (<?= html_escape($r['store_name']) ?>)
          <?php if ($r['laporan']): ?><span class="lencana lencana-merah"><?= (int) $r['laporan'] ?> laporan</span><?php endif; ?>
          <em><?= html_escape(mb_strimwidth((string) $r['isi'], 0, 260, '…')) ?>
            <?= $r['is_hidden'] ? '&middot; <strong>disembunyikan:</strong> ' . html_escape($r['hidden_alasan']) : '' ?></em>
        </div>
        <div class="adm-aksi">
          <?= form_open('admin/moderasi/ulasan_tukar/' . (int) $r['id'], array('class' => 'inline-form')) ?>
            <?php if ( ! $r['is_hidden']): ?><input type="hidden" name="alasan" value="Kasar, spam, atau data pribadi"><?php endif; ?>
            <button class="btn btn-sm btn-black-hover-outline"><?= $r['is_hidden'] ? 'Tampilkan' : 'Sembunyikan' ?></button>
          <?= form_close() ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
