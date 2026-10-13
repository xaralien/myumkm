<?php /* Formulir "Laporkan". Parameter: $jenis (produk|toko|ulasan), $objek_id. */ ?>
<details class="laporkan">
  <summary>⚑ Laporkan</summary>
  <?= form_open('laporkan', array('class' => 'laporkan-form')) ?>
    <input type="hidden" name="jenis" value="<?= html_escape($jenis) ?>">
    <input type="hidden" name="objek_id" value="<?= (int) $objek_id ?>">
    <input type="hidden" name="balik" value="<?= html_escape(uri_string()) ?>">
    <select name="alasan" class="form-select form-select-sm mb-1" required>
      <?php foreach (alasan_laporan() as $a): ?><option><?= html_escape($a) ?></option><?php endforeach; ?>
    </select>
    <input type="text" name="keterangan" class="form-control form-control-sm mb-1" maxlength="500" placeholder="Ceritakan singkat (opsional)">
    <button type="submit" class="btn btn-sm btn-black-hover-outline">Kirim laporan</button>
  <?= form_close() ?>
</details>
