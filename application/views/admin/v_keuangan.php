<!-- application/views/admin/v_keuangan.php -->
<div class="panel-judul">
  <div>
    <h1>Keuangan</h1>
    <p class="hint">
      Semua pembayaran masuk ke rekening marketplace. Halaman ini untuk
      meneruskannya ke penjual, dan mengembalikannya ke pembeli saat refund.
    </p>
  </div>
  <a href="<?= site_url('admin/keuangan/refund') ?>" class="btn btn-black-hover-outline">
    Pengembalian dana
    <?php if ($refund_baru): ?><span class="panel-lencana"><?= (int) $refund_baru ?></span><?php endif; ?>
  </a>
</div>

<div class="panel-bagian">
  <h2 class="panel-sub">Siap dicairkan</h2>

  <?php if (! $siap): ?>
    <p class="hint">Belum ada pendapatan toko yang siap dicairkan.</p>
  <?php else: ?>
    <div class="adm-tabel">
      <?php foreach ($siap as $s): ?>
        <div class="adm-baris">
          <div>
            <strong><?= html_escape($s['name']) ?></strong>
            <em>
              <?= (int) $s['pesanan'] ?> pesanan selesai &middot;
              <?php if ($s['bank_nomor']): ?>
                <?= html_escape($s['bank_nama']) ?> <?= html_escape($s['bank_nomor']) ?>
                a.n. <?= html_escape($s['bank_atas_nama']) ?>
              <?php else: ?>
                <span style="color: var(--ord-red);">rekening belum diisi penjual</span>
              <?php endif; ?>
            </em>
          </div>
          <div class="adm-aksi">
            <strong><?= rupiah($s['jumlah']) ?></strong>
            <?php if ($s['bank_nomor']): ?>
              <?= form_open('admin/keuangan/cairkan/' . (int) $s['id'], array('class' => 'inline-form')) ?>
                <button type="submit" class="btn btn-primary btn-sm"
                        onclick="return confirm('Buat pencairan <?= rupiah($s['jumlah']) ?> untuk <?= html_escape($s['name']) ?>?');">
                  Buat pencairan
                </button>
              <?= form_close() ?>
            <?php else: ?>
              <!-- Tombol sengaja tidak ditampilkan: pencairan tanpa rekening
                   hanya akan jadi catatan yang tidak bisa ditindaklanjuti. -->
              <span class="hint">Minta penjual mengisi rekening dulu</span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="panel-bagian">
  <h2 class="panel-sub">Riwayat pencairan</h2>

  <?php if (! $payout): ?>
    <p class="hint">Belum ada pencairan.</p>
  <?php else: ?>
    <div class="adm-tabel">
      <?php foreach ($payout as $p): ?>
        <div class="adm-baris <?= $p['status'] === 'diproses' ? '' : 'is-mati' ?>">
          <div>
            <strong><?= rupiah($p['jumlah']) ?> &middot; <?= html_escape($p['toko']) ?></strong>
            <em>
              <?= (int) $p['jumlah_order'] ?> pesanan &middot;
              <?= tgl_id(substr($p['created_at'], 0, 10)) ?>
              <?php if ($p['bank_nomor']): ?>
                &middot; <?= html_escape($p['bank_nama']) ?> <?= html_escape($p['bank_nomor']) ?>
                a.n. <?= html_escape($p['bank_atas_nama']) ?>
              <?php endif; ?>
            </em>
          </div>

          <div class="adm-aksi">
            <span class="badge-status <?= $p['status'] === 'selesai' ? 'is-ok' : ($p['status'] === 'gagal' ? '' : 'is-wait') ?>">
              <?= $p['status'] === 'selesai' ? 'Sudah ditransfer' : ($p['status'] === 'gagal' ? 'Gagal' : 'Belum ditransfer') ?>
            </span>

            <?php if ($p['status'] === 'diproses'): ?>
              <?= form_open('admin/keuangan/selesai/' . (int) $p['id'], array('class' => 'inline-form adm-sandi')) ?>
                <input type="text" name="bukti" class="form-control form-control-sm"
                       placeholder="No. referensi transfer" maxlength="120"
                       aria-label="Nomor referensi transfer">
                <button type="submit" class="btn btn-primary btn-sm">Tandai ditransfer</button>
              <?= form_close() ?>

              <?= form_open('admin/keuangan/gagal/' . (int) $p['id'], array('class' => 'inline-form')) ?>
                <button type="submit" class="link-btn"
                        onclick="return confirm('Tandai gagal? Pesanannya akan bisa dicairkan ulang.');">Gagal</button>
              <?= form_close() ?>
            <?php elseif ($p['bukti']): ?>
              <span class="hint"><?= html_escape($p['bukti']) ?></span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
