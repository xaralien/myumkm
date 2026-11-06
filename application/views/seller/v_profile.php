<!-- application/views/seller/v_profile.php -->


<?php
$avatar = $store['avatar']
    ? base_url('upload/avatar/' . $store['avatar'])
    : base_url('assets/images/avatar.png');

// Kolom TIME MySQL keluar "HH:MM:SS", input type=time butuh "HH:MM".
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
 
  <p class="hint mb-2">
    Klik di peta atau geser penandanya &mdash; kolom <strong>Alamat</strong> dan
    <strong>Wilayah</strong> di atas ikut terisi. Hasilnya perkiraan, jadi
    periksa dan perbaiki sendiri kalau kurang tepat.
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

            <?php
              /* Saklar jangkauan. Tidak dicentang = toko tidak melayani tingkat
                 itu; pesanan dari sana ditolak saat checkout. Bertingkat: kalau
                 luar kecamatan tidak dilayani, yang lebih jauh juga tidak
                 (diatur jangkauan.js di bawah dan diperiksa lagi di server). */
              $centang = function ($nama, $bawaan) {
                  $v = $this->input->post($nama);
                  return ($this->input->method() === 'post') ? ($v === '1') : $bawaan;
              };
              $layani = array(
                  'kota'     => $centang('layani_kota', ! isset($store['layani_kota']) || (int) $store['layani_kota']),
                  'provinsi' => $centang('layani_provinsi', $store['ongkir_provinsi'] !== NULL),
                  'luar'     => $centang('layani_luar', $store['ongkir_luar'] !== NULL),
              );
              $tingkat = array(
                  'kota'     => array('ongkir_kota', 'Melayani luar kecamatan', 'dalam kabupaten/kota yang sama',
                                      (int) $store['ongkir_kota']),
                  'provinsi' => array('ongkir_provinsi', 'Melayani luar kabupaten/kota', 'masih dalam provinsi yang sama',
                                      $store['ongkir_provinsi'] === NULL ? '' : (int) $store['ongkir_provinsi']),
                  'luar'     => array('ongkir_luar', 'Melayani luar provinsi', 'pengiriman jauh - pastikan barangmu tahan perjalanan',
                                      $store['ongkir_luar'] === NULL ? '' : (int) $store['ongkir_luar']),
              );
            ?>
            <div class="jangkauan" id="jangkauanToko">
              <?php foreach ($tingkat as $k => $t): ?>
                <div class="jangkauan-tingkat mb-3" data-tingkat="<?= $k ?>">
                  <label class="form-check jangkauan-saklar">
                    <input type="checkbox" class="form-check-input" name="layani_<?= $k ?>" value="1"
                           id="layani_<?= $k ?>" <?= $layani[$k] ? 'checked' : '' ?>>
                    <span><strong><?= $t[1] ?></strong> <span class="hint">(<?= $t[2] ?>)</span></span>
                  </label>
                  <div class="jangkauan-tarif" <?= $layani[$k] ? '' : 'hidden' ?>>
                    <label class="form-label" for="<?= $t[0] ?>">Ongkir</label>
                    <div class="rupiah-wrap">
                      <input type="text" id="<?= $t[0] ?>" name="<?= $t[0] ?>"
                             class="form-control input-rupiah" inputmode="numeric" autocomplete="off"
                             placeholder="0 = gratis ongkir"
                             value="<?= set_value($t[0], $t[3]) ?>">
                    </div>
                    <?= form_error($t[0]) ?>
                  </div>
                </div>
              <?php endforeach; ?>
              <p class="hint">Yang tidak dicentang tidak dilayani: pembeli dari wilayah itu tidak bisa checkout
                dan diarahkan bertanya lewat chat. Isi <strong>0</strong> untuk gratis ongkir.</p>
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

        </div>

        <div class="form-card">
            <h3 class="form-card-title">Kapasitas pesanan</h3>
            <div class="mb-1">
                <label class="form-label" for="maks_pesanan_harian">Maksimal pesanan per hari</label>
                <input type="number" id="maks_pesanan_harian" name="maks_pesanan_harian" class="form-control"
                       min="1" max="999" inputmode="numeric" placeholder="Kosong = tidak dibatasi"
                       value="<?= set_value('maks_pesanan_harian', ! empty($store['maks_pesanan_harian']) ? (int) $store['maks_pesanan_harian'] : '') ?>">
                <p class="hint">Misalnya 5. Setelah 5 pesanan masuk hari ini, pembeli tidak bisa checkout
                    sampai besok, dan halaman toko menampilkan "Kuota hari ini penuh". Pesanan yang dibatalkan
                    tidak dihitung.</p>
                <?= form_error('maks_pesanan_harian') ?>
            </div>
        </div>

        <div class="form-card">
            <h3 class="form-card-title">Waktu proses</h3>
            <div class="mb-1">
                <label class="form-label" for="waktu_proses_hari">Pesanan dikirim dalam *</label>
                <div class="input-satuan">
                    <input type="number" id="waktu_proses_hari" name="waktu_proses_hari" class="form-control"
                        min="1" max="30" inputmode="numeric"
                        value="<?= set_value('waktu_proses_hari', max(1, (int) $store['waktu_proses_hari'])) ?>" required>
                    <span>hari kerja</span>
                </div>
                <p class="hint">
                    Dihitung sejak pembayaran diterima. Tampil di halaman produk dan
                    checkout &mdash; isi dengan jujur, karena pembeli menunggu sesuai angka ini.
                    Produk yang dibuat setelah dipesan (pre-order) biasanya butuh lebih lama.
                </p>
                <?= form_error('waktu_proses_hari') ?>
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
<script src="<?= aset('assets/js/region-select.js') ?>"></script>
<script>
  /* Jangkauan bertingkat: mematikan satu tingkat ikut mematikan yang lebih
     jauh, menyalakan satu tingkat ikut menyalakan yang lebih dekat. */
  (function () {
    var urut = ['kota', 'provinsi', 'luar'];
    var kotak = urut.map(function (k) { return document.getElementById('layani_' + k); });
    function tampil() {
      kotak.forEach(function (c) {
        if (!c) { return; }
        c.closest('.jangkauan-tingkat').querySelector('.jangkauan-tarif').hidden = !c.checked;
      });
    }
    kotak.forEach(function (c, i) {
      if (!c) { return; }
      c.addEventListener('change', function () {
        for (var j = 0; j < kotak.length; j++) {
          if (c.checked && j < i) { kotak[j].checked = true; }
          if (!c.checked && j > i) { kotak[j].checked = false; }
        }
        tampil();
      });
    });
    tampil();
  })();
</script>
<script src="<?= aset('assets/js/format-rupiah.js') ?>"></script>
<script src="<?= aset('assets/js/avatar-preview.js') ?>"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  window.PETA_ALAMAT = {
    peta: 'petaToko',
    alamat: 'address',
    info: 'petaInfo',
    cari: 'btnCariAlamat',
    lokasiSaya: 'btnLokasiSaya',
    lat: 'inputLat',
    lng: 'inputLng',
    // Menerjemahkan nama wilayah dari OpenStreetMap ke id di database.
    urlCocok: '<?= site_url('region/cocok') ?>'
  };
</script>
<script src="<?= aset('assets/js/peta-alamat.js') ?>"></script>