<!-- application/views/akun/v_ajak.php -->
<div class="hero hero-page">
  <div class="container"><div class="row"><div class="col-lg-12">
    <div class="intro-excerpt text-center"><h1>Ajak teman</h1></div>
  </div></div></div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container"><div class="row justify-content-center"><div class="col-lg-8">

    <div class="form-card ajak-kartu">
      <p class="mb-3">
        Ajak teman belanja dari UMKM lokal.
        <?php if ($hadiah_teman > 0): ?>Temanmu dapat voucher <strong><?= rupiah($hadiah_teman) ?></strong> saat mendaftar<?php endif; ?><?php if ($hadiah_teman > 0 && $hadiah_ajak > 0): ?>, dan <?php endif; ?>
        <?php if ($hadiah_ajak > 0): ?>kamu dapat voucher <strong><?= rupiah($hadiah_ajak) ?></strong> setelah pesanan pertamanya lunas<?php endif; ?>.
        <?php if ($min_belanja > 0): ?><span class="hint">Voucher berlaku untuk belanja minimal <?= rupiah($min_belanja) ?>.</span><?php endif; ?>
      </p>

      <p class="hint mb-1">Kode referral kamu</p>
      <div class="ajak-kode">
        <strong id="kodeRef"><?= html_escape($kode) ?></strong>
        <button type="button" class="btn btn-sm btn-black-hover-outline" data-salin="<?= html_escape($kode) ?>">Salin kode</button>
      </div>

      <p class="hint mb-1 mt-3">Atau bagikan tautan ini - kodenya terisi otomatis</p>
      <div class="ajak-tautan">
        <input type="text" class="form-control" readonly value="<?= html_escape($tautan) ?>" onclick="this.select()">
        <button type="button" class="btn btn-sm btn-black-hover-outline" data-salin="<?= html_escape($tautan) ?>">Salin</button>
      </div>

      <?php $teks_wa = 'Yuk belanja produk UMKM lokal di ' . $nama_brand . '! Daftar pakai kode ' . $kode
          . ($hadiah_teman > 0 ? ' dan dapat voucher ' . rupiah($hadiah_teman) : '') . ': ' . $tautan; ?>
      <a class="btn btn-primary mt-3" target="_blank" rel="noopener"
         href="https://wa.me/?text=<?= rawurlencode($teks_wa) ?>">Bagikan lewat WhatsApp</a>
    </div>

    <div class="form-card">
      <h3 class="form-card-title">Teman yang kamu ajak (<?= count($teman) ?>)</h3>
      <?php if ( ! $teman): ?>
        <p class="hint mb-0">Belum ada. Bagikan kodemu ke grup WhatsApp keluarga atau teman.</p>
      <?php else: ?>
        <ul class="ajak-daftar">
          <?php foreach ($teman as $t):
            // Nama disamarkan: "Rina Ayu" -> "Rina A."
            $bagian = preg_split('/\s+/', trim((string) $t['name']));
            $nama = $bagian[0] . (isset($bagian[1]) ? ' ' . mb_substr($bagian[1], 0, 1) . '.' : '');
          ?>
            <li>
              <span><?= html_escape($nama) ?> <span class="hint">&middot; daftar <?= tgl_id(substr($t['created_at'], 0, 10)) ?></span></span>
              <?php if ($t['status'] === 'selesai'): ?>
                <span class="lencana lencana-hijau">Sudah belanja &middot; hadiah diberikan</span>
              <?php else: ?>
                <span class="lencana">Belum belanja</span>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <div class="form-card">
      <h3 class="form-card-title">Voucher hadiahmu</h3>
      <?php if ( ! $voucher): ?>
        <p class="hint mb-0">Belum ada voucher hadiah.</p>
      <?php else: ?>
        <ul class="ajak-daftar">
          <?php foreach ($voucher as $v):
            $habis = (int) $v['terpakai'] > 0; $lewat = $v['selesai'] && $v['selesai'] < date('Y-m-d H:i:s'); ?>
            <li>
              <span><strong><?= html_escape($v['kode']) ?></strong> &middot; <?= rupiah($v['nilai']) ?>
                <span class="hint">&middot; <?= html_escape($v['nama']) ?>, berlaku sampai <?= tgl_id(substr($v['selesai'], 0, 10)) ?></span></span>
              <?php if ($habis): ?><span class="lencana">Sudah dipakai</span>
              <?php elseif ($lewat || ! (int) $v['is_active']): ?><span class="lencana lencana-merah">Kedaluwarsa</span>
              <?php else: ?><span class="lencana lencana-hijau">Bisa dipakai</span><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
        <p class="hint mb-0 mt-2">Masukkan kodenya di kolom voucher saat checkout.</p>
      <?php endif; ?>
    </div>

  </div></div></div>
</div>
<script>
document.querySelectorAll('[data-salin]').forEach(function (b) {
  b.addEventListener('click', function () {
    var t = b.getAttribute('data-salin'), lama = b.textContent, ok = function () { b.textContent = 'Tersalin ✓'; setTimeout(function () { b.textContent = lama; }, 1500); };
    if (navigator.clipboard) { navigator.clipboard.writeText(t).then(ok); }
    else { var i = document.createElement('input'); i.value = t; document.body.appendChild(i); i.select(); document.execCommand('copy'); i.remove(); ok(); }
  });
});
</script>
