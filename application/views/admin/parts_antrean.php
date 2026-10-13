<?php
/* Antrean kerja admin dari fitur tambahan. Hanya yang ada isinya yang tampil. */
$CI =& get_instance();
$hitung = function ($tabel, $where) use ($CI) {
  if ( ! $CI->db->table_exists($tabel)) { return 0; }
  foreach ($where as $k => $v) { is_array($v) ? $CI->db->where_in($k, $v) : $CI->db->where($k, $v); }
  return (int) $CI->db->count_all_results($tabel);
};
$antre = array(
  array('admin/verifikasi', 'Dokumen legalitas menunggu', $hitung('store_documents', array('status' => 'menunggu'))),
  array('admin/sengketa', 'Sengketa perlu diputuskan', $hitung('sengketa', array('status' => array('terbuka', 'ditinjau')))),
  array('admin/moderasi', 'Laporan pengguna baru', $hitung('laporan', array('status' => 'baru'))),
  array('admin/keuangan/refund', 'Refund disetujui, belum ditransfer', $hitung('refunds', array('status' => 'disetujui'))),
);
$antre = array_filter($antre, function ($a) { return $a[2] > 0; });
?>
<?php if ($antre): ?>
  <div class="antre-baris mb-4">
    <?php foreach ($antre as $a): ?>
      <a href="<?= site_url($a[0]) ?>" class="antre"><strong><?= (int) $a[2] ?></strong><span><?= $a[1] ?></span></a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
