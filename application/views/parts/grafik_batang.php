<?php
/* Grafik batang satu seri (SVG, tanpa pustaka).
   Parameter: $judul, $data [['label' => ..., 'nilai' => int, 'teks' => tooltip]],
              [$format] 'rupiah'|'angka', [$id].
   Satu seri: tidak perlu legenda - judul sudah menamainya. Tooltip lewat
   <title> (bisa dibaca pembaca layar) dan sorotan saat disentuh. Tabel
   data ada di <details> di bawahnya untuk yang butuh angka persisnya. */
$format = isset($format) ? $format : 'rupiah';
$fmt = function ($n) use ($format) { return $format === 'rupiah' ? rupiah($n) : number_format($n, 0, ',', '.'); };
$n = count($data);
$maks = 0;
foreach ($data as $d) { $maks = max($maks, (int) $d['nilai']); }
$W = 720; $H = 180; $kiri = 4; $bawah = 2; $atas = 14;
$lebar = $n ? ($W - $kiri) / $n : 0;
$gap = $lebar > 6 ? 2 : 1;
$tinggi_area = $H - $bawah - $atas;
$step_label = max(1, (int) ceil($n / 8));
?>
<figure class="grafik">
  <figcaption class="grafik-judul"><?= html_escape($judul) ?>
    <?php if ($maks > 0): ?><span class="hint">tertinggi <?= $fmt($maks) ?></span><?php endif; ?></figcaption>
  <?php if ($maks <= 0): ?>
    <p class="fitur-kosong mb-0">Belum ada data di periode ini.</p>
  <?php else: ?>
  <svg viewBox="0 0 <?= $W ?> <?= $H ?>" role="img" aria-label="<?= html_escape($judul) ?>" preserveAspectRatio="none" class="grafik-svg">
    <line x1="0" y1="<?= $H - $bawah ?>" x2="<?= $W ?>" y2="<?= $H - $bawah ?>" class="grafik-sumbu"/>
    <line x1="0" y1="<?= $atas ?>" x2="<?= $W ?>" y2="<?= $atas ?>" class="grafik-kisi"/>
    <?php foreach (array_values($data) as $i => $d):
      $h = $maks ? round($d['nilai'] / $maks * $tinggi_area, 1) : 0;
      $x = $kiri + $i * $lebar + $gap / 2;
      $w = max(1, $lebar - $gap);
      $y = $H - $bawah - $h;
      $r = min(4, $w / 2, $h);
    ?>
      <g class="grafik-bar">
        <title><?= html_escape($d['teks'] ?? ($d['label'] . ': ' . $fmt($d['nilai']))) ?></title>
        <rect x="<?= $x ?>" y="<?= $atas ?>" width="<?= $w ?>" height="<?= $tinggi_area ?>" class="grafik-hit"/>
        <?php if ($h > 0): ?>
          <path d="M<?= $x ?>,<?= $H - $bawah ?> V<?= $y + $r ?> Q<?= $x ?>,<?= $y ?> <?= $x + $r ?>,<?= $y ?> H<?= $x + $w - $r ?> Q<?= $x + $w ?>,<?= $y ?> <?= $x + $w ?>,<?= $y + $r ?> V<?= $H - $bawah ?> Z" class="grafik-isi"/>
        <?php endif; ?>
      </g>
    <?php endforeach; ?>
  </svg>
  <!-- Label sumbu di HTML, bukan <text> di SVG: SVG-nya direntangkan
       mengikuti lebar layar, dan teks di dalamnya ikut gepeng. -->
  <div class="grafik-sumbu-x" aria-hidden="true">
    <?php foreach (array_values($data) as $i => $d): if ($i % $step_label !== 0) { continue; } ?>
      <span style="left: <?= round(($kiri + ($i + 0.5) * $lebar) / $W * 100, 2) ?>%"><?= html_escape($d['label']) ?></span>
    <?php endforeach; ?>
  </div>
  <details class="grafik-tabel">
    <summary>Lihat sebagai tabel</summary>
    <table class="panel-tabel">
      <?php foreach ($data as $d): ?><tr><td><?= html_escape($d['teks'] ?? $d['label']) ?></td><td><?= $fmt($d['nilai']) ?></td></tr><?php endforeach; ?>
    </table>
  </details>
  <?php endif; ?>
</figure>
