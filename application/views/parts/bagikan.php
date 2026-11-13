<?php
/* Satu tombol ikon "Bagikan" yang membuka pilihan: WhatsApp, Telegram,
   Facebook, X, menu bagikan bawaan HP (kalau didukung), dan salin tautan.
   Parameter: $teks, [$url].

   Memakai <details> supaya menunya tetap bisa dibuka walau JS gagal
   dimuat; favorit.js hanya menambah penutupan otomatis & menu bawaan HP. */
$url   = isset($url) ? $url : current_url();
$isi   = rawurlencode($teks . "\n" . $url);
$u     = rawurlencode($url);
$t     = rawurlencode($teks);
?>
<details class="bagikan" data-bagikan>
  <summary class="ikon-tombol" aria-label="<?= t('Bagikan') ?>" title="<?= t('Bagikan') ?>">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/></svg>
  </summary>
  <div class="bagikan-menu" role="menu">
    <a role="menuitem" href="https://wa.me/?text=<?= $isi ?>" target="_blank" rel="noopener">
      <span class="bagikan-ikon is-wa" aria-hidden="true"><svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.5-3.9-4.7-4.1-.1-.2-1.1-1.5-1.1-2.9s.7-2.1 1-2.4c.3-.3.6-.3.8-.3h.6c.2 0 .4 0 .6.5l.9 2.1c.1.2.1.4 0 .5l-.4.6-.4.4c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1.1c.2-.3.4-.2.7-.1l2 1c.3.1.5.2.5.3.1.1.1.6-.1 1.2z"/></svg></span>
      WhatsApp
    </a>
    <a role="menuitem" href="https://t.me/share/url?url=<?= $u ?>&amp;text=<?= $t ?>" target="_blank" rel="noopener">
      <span class="bagikan-ikon is-tg" aria-hidden="true"><svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M21.5 3.6L2.9 10.8c-1.3.5-1.3 1.2-.2 1.6l4.8 1.5 1.8 5.6c.2.6.1.9.8.9.5 0 .7-.2 1-.5l2.3-2.2 4.8 3.5c.9.5 1.5.2 1.7-.8l3.1-14.7c.3-1.3-.5-1.9-1.5-1.5zM8.7 13.5l9.6-6c.5-.3.9-.1.5.2l-8.2 7.4-.3 3.4-1.6-5z"/></svg></span>
      Telegram
    </a>
    <a role="menuitem" href="https://www.facebook.com/sharer/sharer.php?u=<?= $u ?>" target="_blank" rel="noopener">
      <span class="bagikan-ikon is-fb" aria-hidden="true"><svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M14 8h3V4h-3c-2.8 0-4.5 1.7-4.5 4.5V11H7v4h2.5v7h4v-7h3l.5-4h-3.5V9c0-.6.4-1 1-1z"/></svg></span>
      Facebook
    </a>
    <a role="menuitem" href="https://x.com/intent/post?text=<?= $t ?>&amp;url=<?= $u ?>" target="_blank" rel="noopener">
      <span class="bagikan-ikon is-x" aria-hidden="true"><svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M17.8 3h3.1l-6.8 7.7L22 21h-6.2l-4.9-6.3L5.3 21H2.2l7.2-8.3L2 3h6.4l4.4 5.8L17.8 3zm-1.1 16.2h1.7L7.4 4.7H5.6l11.1 14.5z"/></svg></span>
      X
    </a>
    <button type="button" role="menuitem" class="bagikan-native" data-bagikan-native
            data-judul="<?= html_escape($teks) ?>" data-url="<?= html_escape($url) ?>" hidden>
      <span class="bagikan-ikon" aria-hidden="true"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg></span>
      <?= t('Lainnya') ?>&hellip;
    </button>
    <button type="button" role="menuitem" data-salin="<?= html_escape($url) ?>">
      <span class="bagikan-ikon" aria-hidden="true"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 14a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 10a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg></span>
      <span data-salin-teks><?= t('Salin tautan') ?></span>
    </button>
  </div>
</details>
