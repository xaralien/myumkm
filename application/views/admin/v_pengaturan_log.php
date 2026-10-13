<!-- application/views/admin/v_pengaturan_log.php -->
<div class="panel-judul">
  <div>
    <h1><?= $tab === 'audit' ? 'Log audit' : 'Notifikasi keluar' ?></h1>
    <p class="hint">
      <?= $tab === 'audit'
        ? 'Setiap aksi admin yang mengubah data tercatat di sini, beserta waktunya dan alamat IP.'
        : 'Semua WhatsApp & email yang dikirim sistem. "Dilewati" berarti gerbangnya belum diaktifkan di Pengaturan.' ?>
    </p>
  </div>
</div>

<?php $this->load->view('admin/parts_pengaturan_tab', array('tab' => $tab)); ?>

<?php if ($tab === 'notifikasi'): ?>
  <nav class="fitur-saring mb-3">
    <?php foreach (array('' => 'Semua', 'terkirim' => 'Terkirim', 'gagal' => 'Gagal', 'dilewati' => 'Dilewati') as $k => $l): ?>
      <a href="<?= site_url('admin/pengaturan/notifikasi' . ($k ? '?status=' . $k : '')) ?>"
         class="<?= (string) $status === $k ? 'aktif' : '' ?>"><?= $l ?></a>
    <?php endforeach; ?>
  </nav>
<?php else: ?>
  <form method="get" class="fitur-baris-form mb-3">
    <input type="search" name="q" class="form-control" placeholder="Cari aksi, nama, atau detail" value="<?= html_escape($cari) ?>">
    <button class="btn btn-outline-secondary">Cari</button>
  </form>
<?php endif; ?>

<?php if ( ! $baris): ?>
  <p class="hint">Belum ada catatan.</p>
<?php else: ?>
  <div class="panel-tabel-bungkus">
    <table class="panel-tabel">
      <thead>
        <?php if ($tab === 'audit'): ?>
          <tr><th>Waktu</th><th>Admin</th><th>Aksi</th><th>Objek</th><th>Detail</th><th>IP</th></tr>
        <?php else: ?>
          <tr><th>Waktu</th><th>Kanal</th><th>Tujuan</th><th>Acara</th><th>Isi</th><th>Status</th></tr>
        <?php endif; ?>
      </thead>
      <tbody>
        <?php foreach ($baris as $b): ?>
          <?php if ($tab === 'audit'): ?>
            <tr>
              <td><?= html_escape($b['created_at']) ?></td>
              <td><?= html_escape($b['nama']) ?></td>
              <td><code><?= html_escape($b['aksi']) ?></code></td>
              <td><?= html_escape($b['objek']) ?><?= $b['objek_id'] ? ' #' . (int) $b['objek_id'] : '' ?></td>
              <td class="fitur-sel-panjang"><?= html_escape($b['detail']) ?></td>
              <td><?= html_escape($b['ip']) ?></td>
            </tr>
          <?php else: ?>
            <tr>
              <td><?= html_escape($b['created_at']) ?></td>
              <td><?= $b['kanal'] === 'wa' ? 'WhatsApp' : 'Email' ?></td>
              <td><?= html_escape($b['tujuan']) ?></td>
              <td><?= html_escape($b['acara']) ?></td>
              <td class="fitur-sel-panjang"><?= nl2br(html_escape(mb_strimwidth($b['isi'], 0, 220, '…'))) ?></td>
              <td>
                <span class="badge-status <?= $b['status'] === 'terkirim' ? 'is-ok' : ($b['status'] === 'gagal' ? 'is-bad' : 'is-wait') ?>">
                  <?= ucfirst($b['status']) ?>
                </span>
                <?php if ($b['galat']): ?><br><small class="hint"><?= html_escape($b['galat']) ?></small><?php endif; ?>
              </td>
            </tr>
          <?php endif; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
