<!-- application/views/seller/v_profile.php -->


<?php
$avatar = $store['avatar']
    ? base_url('upload/avatar/' . $store['avatar'])
    : base_url('assets/images/avatar.png');

// Kolom TIME MySQL keluar "HH:MM:SS", input type=time butuh "HH:MM".
$jam = function ($v) {
    return substr((string) $v, 0, 5);
};
?>

<div class="panel-judul">
    <div>
        <h1>Profil Toko</h1>
        <p class="hint">Pengaturan di sini menentukan ongkir, jam kirim, dan batas COD di halaman checkout.</p>
    </div>
</div>

<!-- form_open_MULTIPART - tanpa enctype, berkas avatar tidak ikut terkirim
     sama sekali dan $_FILES kosong tanpa pesan error apa pun. -->
<?= form_open_multipart('seller/update_profile') ?>
<div class="row">

    <!-- ==================== KIRI ==================== -->
    <div class="col-lg-7">

        <div class="form-card">
            <h3 class="form-card-title">Identitas</h3>

            <div class="profil-avatar mb-3">
                <img src="<?= $avatar ?>" alt="Avatar toko" class="img-avatar" id="avatarPreview">
                <div>
                    <label class="form-label" for="avatar">Avatar toko</label>
                    <input type="file" id="avatar" name="avatar" class="form-control"
                        accept="image/jpeg,image/png,image/webp">
                    <p class="hint">JPG, PNG, atau WEBP. Maksimal 1 MB. Kosongkan kalau tidak diganti.</p>
                    <p class="img-info" id="avatarInfo"></p>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-6 mb-3">
                    <label class="form-label" for="store_name">Nama toko *</label>
                    <input type="text" id="store_name" name="store_name" class="form-control"
                        value="<?= set_value('store_name', $store['name']) ?>" required>
                    <?= form_error('store_name') ?>
                </div>
                <div class="col-sm-6 mb-3">
                    <label class="form-label" for="name">Nama pemilik *</label>
                    <input type="text" id="name" name="name" class="form-control"
                        value="<?= set_value('name', $user['name']) ?>" required>
                    <?= form_error('name') ?>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-6 mb-3">
                    <label class="form-label" for="phone">No. Telp / WhatsApp *</label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="081234567890"
                        value="<?= set_value('phone', $store['phone']) ?>" required>
                    <?= form_error('phone') ?>
                </div>
                <div class="col-sm-6 mb-3">
                    <label class="form-label">Email login</label>
                    <input type="email" class="form-control" value="<?= html_escape($user['email']) ?>" disabled>
                    <p class="hint">Hubungi admin untuk mengganti email.</p>
                </div>
            </div>

            <div class="mb-1">
                <label class="form-label" for="description">Deskripsi toko</label>
                <textarea id="description" name="description" class="form-control" rows="3"><?= set_value('description', $store['description']) ?></textarea>
            </div>
        </div>

        <div class="form-card">
            <h3 class="form-card-title">Alamat &amp; wilayah</h3>

            <div class="mb-3">
                <label class="form-label" for="address">Alamat toko *</label>
                <textarea id="address" name="address" class="form-control" rows="2" required><?= set_value('address', $store['address']) ?></textarea>
                <?= form_error('address') ?>
            </div>

            <span class="form-label">Wilayah toko *</span>
            <p class="hint mb-2">
                Menentukan urutan tampil di katalog dan tarif ongkir bertingkat di bawah.
            </p>
            <div class="row">
                <div class="col-sm-4 mb-2">
                    <select id="prov" class="form-control" required>
                        <option value="">Provinsi</option>
                        <?php foreach ($provinces as $pr): ?>
                            <option value="<?= (int) $pr['id'] ?>"
                                <?= (int) $store['province_id'] === (int) $pr['id'] ? 'selected' : '' ?>>
                                <?= html_escape($pr['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-4 mb-2">
                    <select id="reg" class="form-control" required disabled>
                        <option value="">Kabupaten / Kota</option>
                    </select>
                </div>
                <div class="col-sm-4 mb-2">
                    <select id="dis" name="district_id" class="form-control" required disabled>
                        <option value="">Kecamatan</option>
                    </select>
                </div>
            </div>
            <?= form_error('district_id') ?>
        </div>

        <div class="form-card">
  <h3 class="form-card-title">Titik lokasi toko</h3>
  <p class="hint mb-3">
    Geser penanda ke posisi toko kamu. Titik ini dipakai pembeli yang mencari
    &ldquo;toko di sekitar saya&rdquo; &mdash; toko tanpa titik tidak akan muncul
    di pencarian itu, tapi tetap muncul di pencarian per wilayah.
  </p>
 
  <div id="petaToko" class="peta-toko"></div>
 
  <div class="peta-alat">
    <button type="button" class="btn btn-black-hover-outline btn-sm" id="btnLokasiSaya">
      Pakai lokasi saya sekarang
    </button>
    <button type="button" class="btn btn-black-hover-outline btn-sm" id="btnCariAlamat">
      Cari dari alamat di atas
    </button>
  </div>
 
  <p class="peta-info" id="petaInfo"></p>
 
  <!-- Nilai sebenarnya disimpan di input tersembunyi. Peta hanya alat
       bantu memilih - kalau JavaScript gagal dimuat, nilai lama tetap
       terkirim apa adanya dan tidak hilang. -->
  <input type="hidden" name="latitude"  id="inputLat"
         value="<?= $store['latitude'] !== NULL ? html_escape($store['latitude']) : '' ?>">
  <input type="hidden" name="longitude" id="inputLng"
         value="<?= $store['longitude'] !== NULL ? html_escape($store['longitude']) : '' ?>">
  <?= form_error('latitude') ?>
  <?= form_error('longitude') ?>
 
  <div class="mt-3">
    <label class="form-label" for="radius_km">Jangkauan antar (km)</label>
    <input type="number" id="radius_km" name="radius_km" class="form-control"
           min="1" max="200" style="max-width:160px"
           value="<?= $store['radius_km'] !== NULL ? (int) $store['radius_km'] : '' ?>">
    <p class="hint">
      Sejauh mana kamu bersedia mengantar. Kosongkan kalau tidak mau dibatasi
      jarak &mdash; aturan wilayah tetap berlaku.
    </p>
    <?= form_error('radius_km') ?>
  </div>
</div>

        <div class="form-card">
            <h3 class="form-card-title">Ganti password</h3>
            <p class="hint mb-3">Kosongkan semuanya kalau tidak ingin mengganti.</p>

            <div class="mb-3">
                <label class="form-label" for="password_lama">Password sekarang</label>
                <input type="password" id="password_lama" name="password_lama" class="form-control" autocomplete="current-password">
                <?= form_error('password_lama') ?>
            </div>
            <div class="row">
                <div class="col-sm-6 mb-3">
                    <label class="form-label" for="password">Password baru</label>
                    <input type="password" id="password" name="password" class="form-control" minlength="8" autocomplete="new-password">
                    <?= form_error('password') ?>
                </div>
                <div class="col-sm-6 mb-1">
                    <label class="form-label" for="password2">Ulangi password baru</label>
                    <input type="password" id="password2" name="password2" class="form-control" minlength="8" autocomplete="new-password">
                    <?= form_error('password2') ?>
                </div>
            </div>
        </div>

    </div>

    <!-- ==================== KANAN ==================== -->
    <div class="col-lg-5">

        <div class="form-card">
            <h3 class="form-card-title">Ongkos kirim</h3>
            <p class="hint mb-3">
                Tarif ditentukan dari jarak wilayah penerima ke toko kamu.
            </p>

            <div class="mb-3">
                <label class="form-label" for="ongkir_kecamatan">Dalam kecamatan toko *</label>
                <div class="rupiah-wrap">
                    <input type="text" id="ongkir_kecamatan" name="ongkir_kecamatan"
                        class="form-control input-rupiah" inputmode="numeric" autocomplete="off"
                        value="<?= set_value('ongkir_kecamatan', (int) $store['ongkir_kecamatan']) ?>" required>
                </div>
                <?= form_error('ongkir_kecamatan') ?>
            </div>

            <div class="mb-3">
                <label class="form-label" for="ongkir_kota">Dalam kabupaten/kota *</label>
                <div class="rupiah-wrap">
                    <input type="text" id="ongkir_kota" name="ongkir_kota"
                        class="form-control input-rupiah" inputmode="numeric" autocomplete="off"
                        value="<?= set_value('ongkir_kota', (int) $store['ongkir_kota']) ?>" required>
                </div>
                <?= form_error('ongkir_kota') ?>
            </div>

            <div class="mb-3">
                <label class="form-label" for="ongkir_provinsi">Luar kota, dalam provinsi</label>
                <div class="rupiah-wrap">
                    <input type="text" id="ongkir_provinsi" name="ongkir_provinsi"
                        class="form-control input-rupiah" inputmode="numeric" autocomplete="off"
                        value="<?= set_value('ongkir_provinsi', $store['ongkir_provinsi'] === NULL ? '' : (int) $store['ongkir_provinsi']) ?>">
                </div>
                <!-- Kosong dan nol BERBEDA artinya di sini, dan bedanya besar. -->
                <p class="hint">
                    <strong>Kosongkan</strong> kalau kamu tidak melayani luar kota &mdash;
                    pesanan dari sana akan ditolak saat checkout.
                    Isi <strong>0</strong> berarti melayani dengan ongkir gratis.
                </p>
                <?= form_error('ongkir_provinsi') ?>
            </div>

            <div class="mb-3">
                <label class="form-label" for="gratis_ongkir_min">Gratis ongkir mulai *</label>
                <div class="rupiah-wrap">
                    <input type="text" id="gratis_ongkir_min" name="gratis_ongkir_min"
                        class="form-control input-rupiah" inputmode="numeric" autocomplete="off"
                        value="<?= set_value('gratis_ongkir_min', (int) $store['gratis_ongkir_min']) ?>" required>
                </div>
                <p class="hint">Isi 0 untuk mematikan gratis ongkir.</p>
                <?= form_error('gratis_ongkir_min') ?>
            </div>

            <!-- <div class="mb-1">
                <label class="form-label" for="cod_max">Batas nominal COD *</label>
                <div class="rupiah-wrap">
                    <input type="text" id="cod_max" name="cod_max"
                        class="form-control input-rupiah" inputmode="numeric" autocomplete="off"
                        value="<?= set_value('cod_max', (int) $store['cod_max']) ?>" required>
                </div>
                <p class="hint">Isi 0 kalau toko kamu tidak menerima COD sama sekali.</p>
                <?= form_error('cod_max') ?>
            </div> -->
        </div>

        <div class="form-card">
            <h3 class="form-card-title">Jam &amp; jadwal</h3>

            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label" for="open">Jam buka *</label>
                    <input type="time" id="open" name="open" class="form-control" step="900"
                        value="<?= set_value('open', $jam($store['open'])) ?>" required>
                    <?= form_error('open') ?>
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label" for="close">Jam tutup *</label>
                    <input type="time" id="close" name="close" class="form-control" step="900"
                        value="<?= set_value('close', $jam($store['close'])) ?>" required>
                    <?= form_error('close') ?>
                </div>
            </div>



            <div class="mb-3">
                <label class="form-label" for="jeda_persiapan_menit">Jeda persiapan (menit) *</label>
                <input type="number" id="jeda_persiapan_menit" name="jeda_persiapan_menit" class="form-control"
                    min="0" max="1440" step="15"
                    value="<?= set_value('jeda_persiapan_menit', (int) $store['jeda_persiapan_menit']) ?>" required>
                <!-- Angka ini sekaligus MENENTUKAN batas pemesanan hari ini:
             begitu (sekarang + jeda) melewati jam tutup, tanggal hari ini
             otomatis tidak bisa dipilih pembeli. Tidak ada kolom cutoff
             terpisah yang harus dijaga tetap selaras. -->
                <p class="hint">
                    Waktu minimum dari pesanan masuk sampai bunga bisa diantar.
                    Ini juga yang menentukan sampai jam berapa toko masih menerima
                    pesanan untuk hari yang sama.
                </p>
                <?= form_error('jeda_persiapan_menit') ?>
            </div>

            <div class="mb-1">
                <label class="form-label" for="maks_hari_kedepan">Maksimal hari ke depan *</label>
                <input type="number" id="maks_hari_kedepan" name="maks_hari_kedepan" class="form-control"
                    min="1" max="365"
                    value="<?= set_value('maks_hari_kedepan', (int) $store['maks_hari_kedepan']) ?>" required>
                <p class="hint">Berapa jauh ke depan pembeli boleh memilih tanggal kirim.</p>
                <?= form_error('maks_hari_kedepan') ?>
            </div>
        </div>

    </div>
</div>

<button type="submit" class="btn btn-primary">Simpan profil</button>
<?= form_close() ?>

<script>
    window.REGION_URLS = {
        regencies: '<?= site_url('region/regencies') ?>',
        districts: '<?= site_url('region/districts') ?>'
    };
    // Dua dropdown bawah harus dimuat dulu supaya nilai tersimpan kelihatan,
    // bukan kosong seolah belum pernah diisi.
    window.PILIHAN_AWAL = {
        province: <?= (int) $store['province_id'] ?>,
        regency: <?= (int) $store['regency_id'] ?>,
        district: <?= (int) $store['district_id'] ?>
    };
</script>
<script src="<?= base_url('assets/js/region-select.js') ?>"></script>
<script src="<?= base_url('assets/js/format-rupiah.js') ?>"></script>
<script src="<?= base_url('assets/js/avatar-preview.js') ?>"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= base_url('assets/js/map-picker.js') ?>"></script>