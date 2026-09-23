<!-- application/views/admin/v_users.php -->
<div class="panel-judul">
  <div>
    <h1>Akun</h1>
    <p class="hint">Satu akun bisa belanja sekaligus punya satu toko.</p>
  </div>
  <form class="seller-cari" action="<?= site_url('admin/users') ?>" method="get" role="search">
    <input type="search" name="q" class="form-control" placeholder="Cari nama, email, atau toko"
           value="<?= html_escape($cari) ?>" aria-label="Cari akun">
    <button type="submit" class="btn btn-black-hover-outline">Cari</button>
  </form>
</div>

<?php if (! $users): ?>
  <div class="empty-state">
    <p class="empty-title">Tidak ada akun yang cocok</p>
    <?php if ($cari !== ''): ?>
      <a href="<?= site_url('admin/users') ?>" class="btn btn-black-hover-outline">Tampilkan semua</a>
    <?php endif; ?>
  </div>
<?php else: ?>

  <div class="adm-tabel">
    <?php foreach ($users as $u): ?>
      <?php $saya = ((int) $u['id'] === (int) $me['id']); ?>
      <div class="adm-baris <?= $u['is_active'] ? '' : 'is-mati' ?>">
        <div>
          <strong>
            <?= html_escape($u['name']) ?>
            <?php if ($saya): ?><span class="badge-status">kamu</span><?php endif; ?>
            <?php if ($u['role'] === 'admin'): ?><span class="badge-status is-ok">admin</span><?php endif; ?>
          </strong>
          <em>
            <?= html_escape($u['email']) ?>
            <?php if ($u['phone']): ?> &middot; <?= html_escape($u['phone']) ?><?php endif; ?>
            <?php if ($u['store_name']): ?>
              &middot; toko: <?= html_escape($u['store_name']) ?>
              <?= (int) $u['store_active'] ? '' : '(belum disetujui)' ?>
            <?php endif; ?>
            &middot; <?= (int) $u['jml_pesanan'] ?> pesanan
          </em>
        </div>

        <div class="adm-aksi">
          <span class="hint">
            <?= $u['last_login_at']
                  ? 'masuk ' . date('d/m/Y', strtotime($u['last_login_at']))
                  : 'belum pernah masuk' ?>
          </span>

          <?php if (! $saya): ?>
            <?= form_open('admin/users/peran/' . $u['id'], array('class' => 'inline-form')) ?>
              <button type="submit" class="btn btn-black-hover-outline btn-sm"
                      onclick="return confirm('<?= $u['role'] === 'admin' ? 'Cabut peran admin dari' : 'Jadikan admin:' ?> <?= html_escape($u['email']) ?>?');">
                <?= $u['role'] === 'admin' ? 'Cabut admin' : 'Jadikan admin' ?>
              </button>
            <?= form_close() ?>

            <?= form_open('admin/users/toggle/' . $u['id'], array('class' => 'inline-form')) ?>
              <button type="submit" class="btn btn-black-hover-outline btn-sm">
                <?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
              </button>
            <?= form_close() ?>
          <?php endif; ?>

          <!-- Atur ulang password: belum ada fitur "lupa password" lewat
               email, jadi ini satu-satunya jalan memulihkan akun terkunci. -->
          <?= form_open('admin/users/sandi/' . $u['id'], array('class' => 'inline-form adm-sandi')) ?>
            <input type="password" name="password" class="form-control form-control-sm"
                   placeholder="Password baru" minlength="8" required
                   aria-label="Password baru untuk <?= html_escape($u['email']) ?>">
            <button type="submit" class="link-btn"
                    onclick="return confirm('Atur ulang password <?= html_escape($u['email']) ?>?');">Atur ulang</button>
          <?= form_close() ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <p class="hint mt-3">
    Menonaktifkan akun membuat pemiliknya tidak bisa masuk, tapi tokonya tetap
    apa adanya. Untuk menutup tokonya dari katalog, pakai halaman
    <a href="<?= site_url('admin/stores') ?>">Daftar Toko</a>.
  </p>
<?php endif; ?>
