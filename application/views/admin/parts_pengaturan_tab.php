<!-- application/views/admin/parts_pengaturan_tab.php -->
<nav class="fitur-tab mb-4">
  <a href="<?= site_url('admin/pengaturan') ?>" class="<?= $tab === 'pengaturan' ? 'aktif' : '' ?>">Pengaturan</a>
  <a href="<?= site_url('admin/pengaturan/notifikasi') ?>" class="<?= $tab === 'notifikasi' ? 'aktif' : '' ?>">Notifikasi keluar</a>
  <a href="<?= site_url('admin/pengaturan/audit') ?>" class="<?= $tab === 'audit' ? 'aktif' : '' ?>">Log audit</a>
</nav>
