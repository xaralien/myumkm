<?php
/* =============================================================================
   Panel filter ulasan.
   Diperlukan: $ringkasan, $filter (rating, media), $url (fungsi: array -> url)
   ========================================================================== */
?>
<aside class="ul-filter">
  <h3 class="ul-filter-judul">Filter ulasan</h3>

  <p class="ul-filter-label">Media</p>
  <a href="<?= html_escape($url(array('media' => $filter['media'] ? NULL : '1'))) ?>"
     class="ul-filter-item <?= $filter['media'] ? 'is-aktif' : '' ?>
            <?= (int) $ringkasan['media'] === 0 ? 'is-kosong' : '' ?>">
    <span>Dengan foto &amp; video</span>
    <b><?= (int) $ringkasan['media'] ?></b>
  </a>

  <p class="ul-filter-label">Rating</p>
  <?php foreach (array(5, 4, 3, 2, 1) as $b): ?>
    <?php
      $aktif = (int) $filter['rating'] === $b;
      $n = (int) $ringkasan['sebaran'][$b];
    ?>
    <a href="<?= html_escape($url(array('bintang' => $aktif ? NULL : $b))) ?>"
       class="ul-filter-item <?= $aktif ? 'is-aktif' : '' ?> <?= $n === 0 ? 'is-kosong' : '' ?>">
      <span class="ul-filter-bintang">
        <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path d="M12 2.5l2.9 5.9 6.6.9-4.8 4.6 1.2 6.5L12 17.4 6.1 20.4l1.2-6.5L2.5 9.3l6.6-.9z" fill="currentColor"></path></svg>
        <?= $b ?>
      </span>
      <b><?= $n ?></b>
    </a>
  <?php endforeach; ?>

  <?php if ($filter['rating'] || $filter['media']): ?>
    <a href="<?= html_escape($url(array('bintang' => NULL, 'media' => NULL))) ?>" class="ul-filter-hapus">
      Hapus filter
    </a>
  <?php endif; ?>
</aside>
