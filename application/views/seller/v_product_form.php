<!-- application/views/seller/v_product_form.php -->
<div class="panel-judul">
  <h1><?= $product ? 'Ubah Produk' : 'Tambah Produk' ?></h1>
  <a href="<?= site_url('seller') ?>" class="btn btn-black-hover-outline">Kembali</a>
</div>

<!-- form_open_MULTIPART, bukan form_open. Tanpa enctype="multipart/form-data"
     browser hanya mengirim nama file sebagai teks - filenya tidak ikut sama
     sekali dan $_FILES kosong, tanpa pesan error apa pun. -->
<?= form_open_multipart('seller/save') ?>
<div class="row">
  <div class="col-lg-8">
    <input type="hidden" name="id" value="<?= $product ? (int) $product['id'] : '' ?>">

    <div class="form-card">
      <h3 class="form-card-title">Data produk</h3>

      <div class="mb-3">
        <label class="form-label" for="image">
          Gambar produk <?= $product ? '(kosongkan kalau tidak diganti)' : '*' ?>
        </label>

        <!-- Tanpa atribut value: <input type="file"> memang tidak bisa diisi
             dari server, browser melarangnya demi keamanan.
             `required` hanya saat menambah baru - saat mengubah produk,
             memaksa unggah ulang cuma untuk memperbaiki harga itu menyebalkan. -->
        <input type="file" id="image" name="image" class="form-control"
          accept="image/jpeg,image/png,image/webp"
          <?= $product ? '' : 'required' ?>>

        <p class="hint">JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
        <p class="img-info" id="imgInfo"></p>
        <?= form_error('image') ?>
        <?php if ($this->session->flashdata('upload_error')): ?>
          <p class="field-error"><?= html_escape($this->session->flashdata('upload_error')) ?></p>
        <?php endif; ?>
      </div>

      <div class="mb-3 text-center" id="imgPreviewWrap" <?= $product && $product['image'] ? '' : ' hidden' ?>>
        <img id="imgPreview" class="img-fluid img-preview" alt="Pratinjau gambar produk"
          src="<?= $product && $product['image'] ? base_url('upload/produk/' . $product['image']) : '' ?>">
      </div>

      <div class="mb-3">
        <label class="form-label" for="name">Nama produk *</label>
        <input type="text" id="name" name="name" class="form-control"
          value="<?= set_value('name', $product ? $product['name'] : '') ?>" required>
        <?= form_error('name') ?>
      </div>

      <div class="row">
        <div class="col-sm-6 mb-3">
          <label class="form-label" for="price">Harga dasar *</label>
          <!-- type="text", bukan number: input number menolak titik ribuan,
               .value-nya jadi kosong begitu ada "285.000".
               inputmode="numeric" tetap memunculkan papan ketik angka di HP. -->
          <div class="rupiah-wrap">
            <input type="text" id="price" name="price" class="form-control input-rupiah"
              inputmode="numeric" autocomplete="off"
              value="<?= set_value('price', $product ? (int) $product['price'] : '') ?>" required>
          </div>
          <?= form_error('price') ?>
        </div>
      </div>

      <div class="mb-3">
        <span class="form-label">Kategori *</span>

        <?php
        /* Kartu radio, bukan <select>. Daftar <option> digambar oleh sistem
             operasi dan tidak bisa di-style sama sekali - warna, jarak, font
             semuanya di luar jangkauan CSS.

             Untuk jumlah kategori sedikit ini juga lebih baik dipakai:
             semua pilihan terlihat sekaligus, cukup satu ketukan, dan
             deskripsi tiap kategori ikut terbaca. Radio biasa, jadi tetap
             bisa dinavigasi tombol panah dan berfungsi tanpa JavaScript. */
        $kat_terpilih = (int) set_value('category_id', $product ? $product['category_id'] : 0);
        ?>

        <div class="kat-grid" role="radiogroup" aria-label="Kategori produk">
          <?php foreach ($categories as $c): ?>
            <label class="kat-kartu">
              <input type="radio" name="category_id" value="<?= (int) $c['id'] ?>"
                <?= $kat_terpilih === (int) $c['id'] ? 'checked' : '' ?> required>
              <span class="kat-isi">
                <span class="kat-nama"><?= html_escape($c['name']) ?></span>
                <?php if (!empty($c['description'])): ?>
                  <span class="kat-ket"><?= html_escape($c['description']) ?></span>
                <?php endif; ?>
              </span>
            </label>
          <?php endforeach; ?>
        </div>

        <?= form_error('category_id') ?>
      </div>

      <div class="mb-1">
        <div class="label-baris">
          <label class="form-label" for="description">Deskripsi</label>

          <?php if (!empty($ai_aktif)): ?>
            <!-- <button type="button" class="btn-ai" id="btnAi">
              <span class="btn-ai-teks">Buatkan dengan AI</span>
            </button> -->
          <?php endif; ?>
        </div>

        <textarea id="description" name="description" class="form-control" rows="4"><?= set_value('description', $product ? $product['description'] : '') ?></textarea>
        <p class="ai-info" id="aiInfo"></p>
      </div>
    </div>

  </div>

  <div class="col-lg-4">

    <div class="form-card">
  <h3 class="form-card-title">Ukuran / varian</h3>
  <p class="hint mb-3">
    Kosongkan kalau produk ini tidak punya pilihan ukuran. Angkanya adalah
    <strong>selisih</strong> dari harga dasar &mdash; isi 0 untuk ukuran terkecil.
    Gambar bersifat opsional; kalau diisi, foto utama di halaman produk ikut
    berubah saat pembeli memilih ukuran ini.
  </p>
 
  <div class="baris-dinamis" id="varianList">
    <?php
    $baris_varian = $variants ?: array(array('name' => '', 'price_delta' => 0, 'image' => NULL));
    foreach ($baris_varian as $v):
      $img = isset($v['image']) ? $v['image'] : NULL;
      ?>
      <div class="varian-baris varian-baris--gambar">
 
        <!-- Kotak gambar. Klik di mana saja pada kotak membuka pemilih
             berkas - target sentuh jadi jauh lebih besar daripada tombol
             "Choose file" bawaan browser. -->
        <label class="baris-gambar">
          <img class="baris-gambar-pratinjau<?= $img ? '' : ' is-kosong' ?>"
               src="<?= $img ? base_url('upload/produk/' . $img) : '' ?>" alt="">
          <span class="baris-gambar-teks"><?= $img ? 'Ganti' : 'Foto' ?></span>
          <input type="file" name="varian_gambar[]" accept="image/jpeg,image/png,image/webp">
        </label>
 
        <!-- Nama berkas lama WAJIB ikut terkirim. simpan_varian() menulis
             ulang seluruh baris, jadi tanpa ini gambar akan hilang setiap
             kali produk disimpan - bahkan saat cuma mengubah harga. -->
        <input type="hidden" name="varian_gambar_lama[]" value="<?= html_escape($img) ?>">
        <input type="hidden" name="varian_gambar_hapus[]" value="0">
 
        <input type="text" name="varian_nama[]" class="form-control"
               placeholder="Misalnya: 250 gram, ukuran L" value="<?= html_escape($v['name']) ?>">
 
        <input type="text" name="varian_delta[]" class="form-control input-rupiah"
               inputmode="numeric" autocomplete="off" data-minus="1"
               placeholder="0" value="<?= (int) $v['price_delta'] ?>">
 
        <button type="button" class="link-btn" data-hapus-baris>Hapus</button>
      </div>
    <?php endforeach; ?>
  </div>
 
  <button type="button" class="btn btn-black-hover-outline mt-2" data-tambah-baris="varianList">
    Tambah varian
  </button>
</div>
 
<div class="form-card">
  <h3 class="form-card-title">Tambahan (addons)</h3>
  <p class="hint mb-3">
    Kosongkan kalau produk ini tidak punya tambahan. Angkanya
    <strong>ditambahkan</strong> ke harga dasar &mdash; isi 0 untuk
    tambahan gratis. Tidak boleh negatif.
  </p>
 
  <div class="baris-dinamis" id="addonsList">
    <?php
    $baris_addon = $addons ?: array(array('name' => '', 'price_delta' => 0, 'image' => NULL));
    foreach ($baris_addon as $a):
      $img = isset($a['image']) ? $a['image'] : NULL;
      ?>
      <div class="varian-baris varian-baris--gambar">
 
        <label class="baris-gambar">
          <img class="baris-gambar-pratinjau<?= $img ? '' : ' is-kosong' ?>"
               src="<?= $img ? base_url('upload/produk/' . $img) : '' ?>" alt="">
          <span class="baris-gambar-teks"><?= $img ? 'Ganti' : 'Foto' ?></span>
          <input type="file" name="addons_gambar[]" accept="image/jpeg,image/png,image/webp">
        </label>
 
        <input type="hidden" name="addons_gambar_lama[]" value="<?= html_escape($img) ?>">
        <input type="hidden" name="addons_gambar_hapus[]" value="0">
 
        <input type="text" name="addons_nama[]" class="form-control"
               placeholder="Cokelat" value="<?= html_escape($a['name']) ?>">
 
        <input type="text" name="addons_delta[]" class="form-control input-rupiah"
               inputmode="numeric" autocomplete="off"
               placeholder="0" value="<?= (int) $a['price_delta'] ?>">
 
        <button type="button" class="link-btn" data-hapus-baris>Hapus</button>
      </div>
    <?php endforeach; ?>
  </div>
 
  <button type="button" class="btn btn-black-hover-outline mt-2" data-tambah-baris="addonsList">
    Tambah tambahan
  </button>
</div>

  </div>
</div>

<button type="submit" class="btn btn-primary">Simpan</button>
<?= form_close() ?>

<script src="<?= base_url('assets/js/image-preview.js') ?>"></script>
<script src="<?= base_url('assets/js/baris-dinamis.js') ?>"></script>
<script src="<?= base_url('assets/js/format-rupiah.js') ?>"></script>
<script src="<?= base_url('assets/js/baris-gambar.js') ?>"></script>

<?php if (!empty($ai_aktif)): ?>
  <script>
    window.AI_URL = '<?= site_url('seller/deskripsi') ?>';
    window.CSRF = {
      name: '<?= $this->security->get_csrf_token_name() ?>',
      hash: '<?= $this->security->get_csrf_hash() ?>'
    };
  </script>
  <script src="<?= base_url('assets/js/ai-deskripsi.js') ?>"></script>
<?php endif; ?>