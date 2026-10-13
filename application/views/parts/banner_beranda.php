<?php
/* Banner beranda dari admin/banner. Dibaca langsung di sini supaya
   beranda desktop & HP cukup memuat satu partial. */
$CI =& get_instance();
if ( ! $CI->db->table_exists('banners')) { return; }
$kini = date('Y-m-d');
$banner = $CI->db->where('is_active', 1)
  ->group_start()->where('mulai IS NULL', NULL, FALSE)->or_where('mulai <=', $kini)->group_end()
  ->group_start()->where('selesai IS NULL', NULL, FALSE)->or_where('selesai >=', $kini)->group_end()
  ->order_by('urutan', 'ASC')->order_by('id', 'DESC')->limit(6)->get('banners')->result_array();
if ( ! $banner) { return; }
?>
<section class="banner-deret deret-wrap nt-wrap" aria-label="Promo">
  <?php foreach ($banner as $b):
    $url = $b['tautan'] ? (strpos($b['tautan'], 'http') === 0 ? $b['tautan'] : site_url(ltrim($b['tautan'], '/'))) : NULL; ?>
    <<?= $url ? 'a href="' . html_escape($url) . '"' : 'div' ?> class="banner-kartu" style="background-color: <?= html_escape($b['warna'] ?: '#22466C') ?>">
      <?php if ($b['gambar']): ?><img src="<?= base_url('upload/banner/' . $b['gambar']) ?>" alt="" loading="lazy"><?php endif; ?>
      <span class="banner-teks"><strong><?= html_escape($b['judul']) ?></strong><?php if ($b['sub']): ?><em><?= html_escape($b['sub']) ?></em><?php endif; ?></span>
    </<?= $url ? 'a' : 'div' ?>>
  <?php endforeach; ?>
</section>
