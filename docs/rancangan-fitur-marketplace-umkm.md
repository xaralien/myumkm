# Rancangan Fitur Marketplace UMKM + Gap Analysis Repository

Disusun dari sudut pandang Product Manager & UI/UX Strategist, lalu dicocokkan
dengan kode yang ada di repository ini (CodeIgniter 3: `application/controllers`,
`models`, `libraries`, `views`, dan migrasi `database/15–30`).

Pembaruan: fitur "Tambah produk dari foto saja", "Penjualan offline / kasir mini (POS)",
COD, dan ambil sendiri di toko dikeluarkan dari rancangan. Semua fitur yang
tersisa kini sudah dibangun (migrasi `database/24–30`).

**Keterangan status**

- ✅ = sudah ada di repository (catatan menjelaskan batasannya bila hanya sebagian)
- ❌ = belum ada
- ⭐ = fitur pembeda dari marketplace besar
- 🧓 = dirancang khusus untuk UMKM / pengguna gagap teknologi (low-tech friendly)

Format tiap fitur: **Nama fitur** — *Pain point* → *Fungsi utama* (bukti di repo / catatan).

---

## Ringkasan Skor

| Kelompok | Total fitur | ✅ Ada | ❌ Belum |
|---|---|---|---|
| Pembeli | 35 | 35 | 0 |
| Penjual / UMKM | 39 | 39 | 0 |
| Admin | 22 | 22 | 0 |
| **Total** | **96** | **96** | **0** |

---

## 1. Fitur untuk Pembeli (Customer / Buyer)

### A. Akun & Akses

- ✅ **Daftar & masuk akun** — *Pain:* pembeli perlu riwayat pesanan tersimpan → *Fungsi:* registrasi dengan nama, email, nomor WhatsApp. (`Auth.php`)
- ✅ 🧓 **Belanja tanpa akun (guest checkout)** — *Pain:* pembeli lokal malas daftar hanya untuk sekali beli → *Fungsi:* checkout cukup nama + nomor WA; pesanan dibuka lewat token. (`orders.user_id` boleh NULL, `Checkout::done/{token}`)
- ✅ 🧓 **Lacak pesanan dengan nomor pesanan + nomor WA** — *Pain:* pembeli tamu tidak punya akun untuk mengecek status → *Fungsi:* form lacak sederhana tanpa login. (`Track.php`)
- ✅ 🧓 **Login OTP WhatsApp / tanpa kata sandi** — *Pain:* pembeli sering lupa sandi → *Fungsi:* masuk cukup dengan kode OTP dikirim ke WA. (`Auth::otp` + `Auth_lib::buat_token` — kode 6 angka dikirim lewat gerbang WA.)
- ✅ **Lupa kata sandi mandiri** — *Pain:* saat ini reset sandi harus lewat admin → *Fungsi:* tautan/OTP reset sandi sendiri. (`Auth::lupa` / `Auth::reset` — tautan reset via WA/email, token sekali pakai.)
- ✅ **Profil & ubah sandi** — *Pain:* data kontak berubah → *Fungsi:* ubah nama, foto, nomor, sandi. (`Akun::profil`)

### B. Penemuan Produk

- ✅ ⭐ **Katalog berbasis lokasi ("dekat saya")** — *Pain:* pembeli ingin produk UMKM tetangga, ongkir murah & cepat → *Fungsi:* pilih kecamatan atau titik GPS, urutan produk mendahulukan toko terdekat. (`Location.php`, `Product_model::proximity_sql`, `toko_sekitar`)
- ✅ **Pencarian & filter** — *Pain:* sulit menemukan produk spesifik → *Fungsi:* cari kata kunci, filter kategori & rentang harga. (`Shop.php`, `Product_model::build_filter`, `price_range`)
- ✅ **Urutkan hasil** — *Pain:* ingin yang termurah/terbaik dulu → *Fungsi:* terbaru, ulasan terbaik, harga, nama. (`Product_model::sort_options`)
- ✅ **Kategori UMKM dengan ikon** — *Pain:* pengguna awam lebih cepat mengenali gambar daripada teks → *Fungsi:* kartu kategori berikon di beranda. (`15_kategori_umkm.sql`)
- ✅ **Halaman toko** — *Pain:* pembeli ingin melihat reputasi & produk lain penjual → *Fungsi:* profil toko, nilai rata-rata, daftar produk. (`Toko.php`, `parts/toko_nilai.php`)
- ✅ **Detail produk dengan varian & add-on** — *Pain:* produk UMKM sering punya ukuran/rasa/tambahan → *Fungsi:* pilih varian (dengan foto & selisih harga) dan add-on. (`Product_model::variants`, `addons_for_product`)
- ✅ **Wishlist / simpan produk favorit** — *Pain:* pembeli ingin membeli nanti → *Fungsi:* tombol ♥ dan daftar favorit. (`Favorit` controller, tabel `wishlists`, tombol ♥ di kartu & halaman produk.)
- ✅ **Ikuti toko** — *Pain:* pelanggan setia tidak tahu kalau toko langganannya punya produk baru → *Fungsi:* follow toko + kabar produk baru. (`Favorit::ikuti`, tabel `store_follows`; deret "Baru dari toko yang kamu ikuti" di beranda.)
- ✅ **Rekomendasi & "terlaris"** — *Pain:* pembeli bingung memilih → *Fungsi:* rekomendasi berdasarkan riwayat, label terlaris di sekitar. (urutan `terlaris` di katalog (kolom `terjual_count`), deret rekomendasi dari riwayat beli di beranda.)
- ✅ ⭐ **Label "Produk Lokal / Halal / P-IRT / Buatan Tangan"** — *Pain:* pembeli ingin yakin akan keamanan & keaslian produk UMKM → *Fungsi:* lencana terverifikasi di kartu produk. (kolom `buatan_tangan`, `bahan_lokal`, `halal_no`, `pirt_no` + lencana; P-IRT/Halal terverifikasi admin.)
- ✅ 🧓 **Bagikan produk ke WhatsApp** — *Pain:* promosi UMKM paling efektif lewat grup WA → *Fungsi:* tombol share dengan pesan siap kirim. (`parts/bagikan.php` — tombol WA dengan pesan siap kirim + salin tautan.)

### C. Keranjang, Checkout & Pembayaran

- ✅ **Keranjang & "Beli Langsung"** — *Pain:* ingin beli cepat satu barang → *Fungsi:* keranjang per toko dan jalur beli langsung. (`Cart.php`, `Cart_lib::mode_langsung`)
- ✅ **Buku alamat** — *Pain:* mengetik alamat berulang; penerima sering bukan pembeli → *Fungsi:* simpan banyak alamat ("Rumah", "Rumah Ibu"), pilih saat checkout. (`22_buku_alamat.sql`, `Akun::alamat`)
- ✅ **Ongkir otomatis berdasarkan wilayah** — *Pain:* ongkir tidak jelas sebelum bayar → *Fungsi:* tarif kecamatan/kota/provinsi/luar provinsi dari toko, plus gratis ongkir minimal belanja. (`Cart_lib::shipping_fee`)
- ✅ **Pembayaran online (VA/QRIS/e-wallet via Duitku)** — *Pain:* transfer manual rawan salah & lama dicek → *Fungsi:* pembayaran terverifikasi otomatis via callback. (`Payment.php`, `libraries/Duitku.php`)
- ✅ **Voucher & promo** — *Pain:* pembeli mencari potongan harga → *Fungsi:* kode voucher toko/platform. (`Voucher_lib` — kode voucher toko & platform di checkout.)
- ✅ ⭐ **Checkout multi-toko** — *Pain:* keranjang saat ini hanya untuk satu toko → *Fungsi:* beli dari beberapa toko dalam satu pembayaran. (`Cart_lib::per_toko` + `Tagihan_lib` — satu pembayaran Duitku (prefiks TG-) untuk banyak toko.)
- ✅ ⭐ **Pre-order fleksibel (DP, tanggal ambil, kuota)** — *Pain:* kue, katering, kerajinan dibuat berdasarkan pesanan → *Fungsi:* pilih tanggal kirim/ambil, bayar DP, kuota harian. (tanggal kirim, kuota harian, DP + pelunasan (`Checkout::lunasi`).)

### D. Pasca-Pembelian

- ✅ **Riwayat & status pesanan** — *Pain:* pembeli cemas menunggu → *Fungsi:* daftar pesanan dengan linimasa status & nomor resi. (`Akun::pesanan`, `Order_model::logs`)
- ✅ **Batalkan pesanan dengan alasan** — *Pain:* salah pesan → *Fungsi:* pembatalan oleh pembeli, stok dikembalikan. (`Checkout::batal`, `Stok_lib::kembalikan`)
- ✅ **Konfirmasi pesanan diterima** — *Pain:* dana penjual tertahan tanpa kepastian → *Fungsi:* tombol "Pesanan diterima". (`Checkout::terima`)
- ✅ **Ajukan pengembalian dana (refund)** — *Pain:* barang tidak sesuai → *Fungsi:* form refund yang ditinjau penjual lalu diproses admin. (`Checkout::refund`, `Refund_model`)
- ✅ **Ulasan dengan foto/video** — *Pain:* pembeli lain butuh bukti nyata → *Fungsi:* rating bintang + media, hanya dari pembeli asli, filter ulasan. (`Ulasan.php`, `19_ulasan.sql`)
- ✅ **Chat dengan toko (sebelum & sesudah beli) + lampiran** — *Pain:* ingin bertanya sebelum membeli tanpa pindah ke WA → *Fungsi:* "Tanya ke toko" dan percakapan per pesanan. (`Chat.php`, `18_tanya_toko.sql`)
- ✅ **Notifikasi di dalam situs** — *Pain:* tidak tahu ada balasan/perubahan status → *Fungsi:* lonceng notifikasi & inbox. (`Akun::notif`, `inbox_json`)
- ✅ 🧓 **Notifikasi WhatsApp / email otomatis** — *Pain:* pengguna jarang membuka web, tapi selalu membuka WA → *Fungsi:* kabar status pesanan dikirim ke WA. (`Notifikasi_lib` — WA (format Fonnte) & email; aktifkan di Admin > Pengaturan.)

### E. Pengalaman & Aksesibilitas

- ✅ 🧓 **Tampilan khusus HP otomatis** — *Pain:* mayoritas pengguna UMKM memakai HP murah → *Fungsi:* deteksi perangkat & tampilan mobile terpisah, bisa ditukar manual. (`Tampilan_lib`, `views/mobile/`)
- ✅ 🧓 **PWA / mode hemat kuota** — *Pain:* sinyal lemah di daerah → *Fungsi:* bisa dipasang di layar utama, gambar ringan, cache offline. (`manifest.webmanifest`, `sw.js`, halaman offline; mode hemat kuota (tanpa font web).)
- ✅ 🧓 **Mode huruf besar & bahasa daerah** — *Pain:* pengguna lansia/kurang terbiasa bahasa formal → *Fungsi:* pengaturan ukuran huruf, pilihan bahasa. (`Preferensi` controller + `t()`; huruf besar, bahasa Jawa & Sunda.)

---

## 2. Fitur untuk Penjual / Pelaku UMKM (Seller / Merchant)

### A. Onboarding & Profil Toko

- ✅ 🧓 **Buka toko langsung dari akun pembeli** — *Pain:* pendaftaran penjual di marketplace besar panjang & membingungkan → *Fungsi:* satu akun = satu toko, form singkat. (`Akun::buka_toko`)
- ✅ **Profil toko dengan titik peta** — *Pain:* pembeli & kurir sulit menemukan lokasi → *Fungsi:* alamat, kecamatan, titik koordinat via peta (Leaflet/OSM). (`Seller::update_profile`, `valid_koordinat`)
- ✅ **Rekening pencairan** — *Pain:* penjual perlu tempat menerima uang → *Fungsi:* simpan bank, nomor, atas nama. (`Seller::rekening`)
- ✅ 🧓 **Wizard onboarding bertahap + video panduan** — *Pain:* UMKM gaptek bingung harus mulai dari mana → *Fungsi:* checklist "Toko siap jualan" (foto profil → produk pertama → ongkir → rekening) dengan video 1 menit per langkah. (`seller/mulai` — checklist "Toko siap jualan" + tautan modul Akademi.)
- ✅ ⭐ **Bantuan legalitas (NIB, P-IRT, Halal)** — *Pain:* UMKM sulit naik kelas karena legalitas → *Fungsi:* panduan langkah-langkah, unggah dokumen, lencana terverifikasi di toko. (`seller/legalitas` — panduan NIB/P-IRT/Halal, unggah dokumen privat, lencana setelah diverifikasi.)
- ✅ **Mode libur / tutup sementara** — *Pain:* penjual pulang kampung, pesanan tetap masuk → *Fungsi:* toggle libur dengan tanggal buka kembali. (`seller/libur` — tanggal mulai/buka, pesan untuk pembeli, checkout otomatis ditolak.)

### B. Manajemen Produk

- ✅ **Tambah/ubah/aktifkan produk** — *Pain:* katalog perlu diperbarui cepat → *Fungsi:* form produk, toggle aktif/nonaktif. (`Seller::form/save/toggle`)
- ✅ **Varian & add-on dengan foto** — *Pain:* satu produk banyak pilihan → *Fungsi:* varian (selisih harga + gambar), add-on opsional. (`v_product_form.php`)
- ✅ **Stok produk otomatis** — *Pain:* overselling → *Fungsi:* stok dipotong saat lunas, dikembalikan saat batal. (`Stok_lib`)
- ✅ **Cari & urutkan produk di panel** — *Pain:* sulit mengelola katalog besar → *Fungsi:* pencarian & sortir server-side dengan paginasi. (`Seller::index`)
- ✅ ⭐ 🧓 **AI penulis deskripsi produk (dari nama + foto)** — *Pain:* UMKM tidak pandai menulis copywriting → *Fungsi:* satu klik menghasilkan deskripsi; ada kuota pemakaian. (`Ai_lib::deskripsi_produk`, `Seller::deskripsi`)
- ✅ **Kompres/rapikan gambar otomatis** — *Pain:* foto HP berukuran besar memperlambat situs → *Fungsi:* pemrosesan gambar saat unggah. (`gambar_helper.php`, `tools/rapikan_gambar_lama.php`)
- ✅ **Peringatan stok menipis** — *Pain:* kehabisan stok tanpa sadar → *Fungsi:* notifikasi saat stok di bawah ambang. (ambang di Pengaturan; kabar WA saat pesanan lunas + kartu di dasbor penjual.)
- ✅ **Unggah massal (Excel/CSV) & duplikat produk** — *Pain:* input satu per satu melelahkan → *Fungsi:* template Excel sederhana, tombol "duplikat". (`seller/massal` — template .xlsx/.csv, impor & ekspor; tombol duplikat produk.)
- ✅ ⭐ **Harga grosir / minimal beli bertingkat** — *Pain:* UMKM juga melayani reseller → *Fungsi:* harga berbeda berdasarkan jumlah. (tabel `product_tiers`, harga otomatis di keranjang.)
- ✅ ⭐ **Pengaturan pre-order per produk** — *Pain:* produk dibuat sesuai pesanan → *Fungsi:* lama produksi per produk, kuota harian, DP, kalender tanggal tersedia. (kolom `is_preorder`, `po_hari`, `po_kuota_harian`, `po_dp_persen`.)

### C. Pesanan & Pengiriman

- ✅ 🧓 **Alur status pesanan satu tombol + Undo 15 menit** — *Pain:* penjual gaptek takut salah klik → *Fungsi:* tombol "lanjut ke status berikutnya" dan pembatalan aksi selama 15 menit. (`Seller::ubah_status`, `undo_status`, `UNDO_DETIK`)
- ✅ **Antrean pesanan berprioritas** — *Pain:* pesanan penting tertimbun → *Fungsi:* pesanan lunas yang belum diproses tampil paling atas. (`Seller::orders`)
- ✅ ⭐ **Kurir lokal / antar sendiri dengan tarif bertingkat** — *Pain:* tarif ekspedisi nasional mahal untuk pengiriman dekat → *Fungsi:* penjual menetapkan ongkir sendiri per kecamatan/kota/provinsi/luar, resi opsional. (`Cart_lib::shipping_fee`, `23_ongkir_luar.sql`)
- ✅ **Gratis ongkir minimal belanja per toko** — *Pain:* butuh pemicu pembelian lebih banyak → *Fungsi:* atur batas nominal gratis ongkir. (`gratis_ongkir_min`)
- ✅ **Waktu proses pesanan** — *Pain:* pembeli menuntut cepat padahal produk dibuat dulu → *Fungsi:* toko mengatur 1–30 hari waktu proses. (`waktu_proses_hari`)
- ✅ **Kirim foto bukti paket ke pembeli** — *Pain:* sengketa "barang tidak sesuai" → *Fungsi:* foto tersimpan di percakapan pesanan. (`Seller::kirim_foto`)
- ✅ **Batalkan pesanan oleh penjual** — *Pain:* stok habis/bahan tidak tersedia → *Fungsi:* pembatalan beralasan + pengembalian stok. (`Seller::batal_pesanan`)
- ✅ **Hubungi pembeli via WhatsApp** — *Pain:* butuh konfirmasi cepat → *Fungsi:* tautan wa.me ke nomor pembeli. (`seller/v_orders.php`)
- ✅ **Integrasi ekspedisi & cek resi otomatis (JNE, J&T, GoSend, Grab)** — *Pain:* penjual luar kota harus cek tarif manual → *Fungsi:* tarif real-time dan pelacakan otomatis via agregator. (`Ekspedisi_lib` — daftar kurir, lacak resi di halaman pesanan, `seller/ongkir` bandingkan tarif; perlu kunci agregator (Biteship) di Pengaturan, tanpa kunci tetap ada tautan lacak resmi. Tarif otomatis di checkout pembeli belum.)
- ✅ 🧓 **Cetak label pengiriman & nota/struk** — *Pain:* menulis alamat tangan memakan waktu → *Fungsi:* cetak label + invoice PDF / printer thermal. (`seller/cetak` — label & nota siap cetak (A4/thermal 58mm).)
- ✅ **Notifikasi pesanan baru ke WhatsApp penjual** — *Pain:* penjual tidak selalu membuka panel → *Fungsi:* pesan WA otomatis saat ada pesanan lunas. (`Notifikasi_lib::pesanan_dibayar`.)

### D. Komunikasi & Reputasi

- ✅ **Inbox chat pembeli** — *Pain:* pertanyaan tersebar di WA pribadi → *Fungsi:* inbox terpusat per pembeli/pesanan. (`Seller::pesan`, `chat`, `inbox_json`)
- ✅ **Balas ulasan** — *Pain:* ulasan buruk tidak bisa diklarifikasi → *Fungsi:* balasan publik penjual. (`Seller::balas`, `Review_model::balas`)
- ✅ **Kelola refund** — *Pain:* keluhan butuh alur jelas → *Fungsi:* setujui/tolak permintaan refund. (`Seller::refund`, `refund_putus`)
- ✅ 🧓 **Balasan cepat (template chat)** — *Pain:* pertanyaan berulang ("ready kak?") → *Fungsi:* template jawaban satu ketuk. (`seller/template` + tombol template di chat.)

### E. Keuangan & Naik Kelas (Pembeda Utama)

- ✅ **Ringkasan pendapatan & status dana** — *Pain:* penjual tidak tahu uangnya di mana → *Fungsi:* dana tertahan / siap cair / sudah cair, potongan komisi tercatat. (`Seller::pendapatan`, `Pendapatan_lib`)
- ✅ ⭐ 🧓 **Pembukuan sederhana (kas masuk-keluar)** — *Pain:* mayoritas UMKM tidak mencatat keuangan sehingga sulit akses modal → *Fungsi:* penjualan marketplace tercatat otomatis, penjual menambah pengeluaran (bahan, sewa) dengan tombol besar, laporan laba sederhana harian/bulanan. (`seller/pembukuan` — penjualan marketplace masuk otomatis, kas manual.)
- ✅ **Laporan penjualan & statistik toko** — *Pain:* tidak tahu produk mana yang laku → *Fungsi:* grafik omzet, produk terlaris, jam ramai; ekspor Excel/PDF. (`seller/laporan` — grafik omzet, terlaris, jam ramai, ekspor Excel.)
- ✅ ⭐ **Laporan keuangan siap ajukan KUR/pinjaman** — *Pain:* bank meminta laporan keuangan → *Fungsi:* ekspor laporan rapi dari data pembukuan. (`seller/laporan/kur` — laba rugi & arus kas siap cetak.)
- ✅ **Voucher & promo toko** — *Pain:* sulit bersaing harga → *Fungsi:* diskon persentase/nominal, voucher pelanggan setia. (`seller/voucher` — persen/nominal, minimal belanja, kuota, khusus pelanggan lama.)
- ✅ ⭐ **Akademi UMKM (pelatihan & pendampingan)** — *Pain:* UMKM tidak tahu cara foto produk, menentukan harga, atau pemasaran → *Fungsi:* modul video pendek, kuis, sertifikat, jadwal konsultasi dengan pendamping/mentor. (`seller/akademi` — modul, kuis, sertifikat, jadwal pelatihan.)
- ✅ ⭐ **Skor "Naik Kelas" toko** — *Pain:* tidak ada arah pengembangan usaha → *Fungsi:* level toko (Pemula → Berkembang → Unggul) berdasarkan kelengkapan profil, legalitas, kecepatan respons, rating, dengan saran langkah berikutnya. (`Naik_kelas_lib` — skor 0–100, level Pemula/Berkembang/Unggul, saran langkah berikutnya.)
- ✅ **QR code / kartu nama toko digital** — *Pain:* promosi offline sulit diarahkan ke toko online → *Fungsi:* QR siap cetak menuju halaman toko. (`seller/qr` — QR & kartu siap cetak (generator QR lokal).)

---

## 3. Fitur untuk Admin / Pengelola Platform (Admin Panel)

### A. Monitoring

- ✅ **Dashboard ringkasan** — *Pain:* admin butuh gambaran cepat → *Fungsi:* toko aktif/menunggu, produk aktif, akun, pesanan perlu diproses, pesanan hari ini, omzet bulan berjalan. (`admin/Dashboard.php`)
- ✅ **Laporan & analitik platform** — *Pain:* tidak bisa mengukur pertumbuhan → *Fungsi:* GMV per periode/wilayah/kategori, pertumbuhan toko, ekspor data. (`admin/analitik` — GMV per periode/wilayah/kategori, ekspor Excel.)
- ✅ ⭐ **Peta sebaran UMKM** — *Pain:* pemda/mitra ingin tahu wilayah yang perlu dibina → *Fungsi:* peta toko per kecamatan beserta tingkat aktivitasnya. (`admin/peta` — peta Leaflet per kecamatan + tingkat aktivitas.)

### B. Pengguna & Toko

- ✅ **Persetujuan & kelola toko** — *Pain:* mencegah toko fiktif → *Fungsi:* daftar toko menunggu, ubah data, aktif/nonaktifkan. (`admin/Stores.php`)
- ✅ **Kelola pengguna** — *Pain:* akun bermasalah → *Fungsi:* aktif/nonaktif, ubah peran, reset sandi. (`admin/Users.php`)
- ✅ **Verifikasi dokumen (KTP/NIB/Halal) & lencana** — *Pain:* kepercayaan pembeli → *Fungsi:* antrean verifikasi dokumen, pemberian lencana. (`admin/verifikasi` — antrean dokumen, setujui/tolak, lencana otomatis.)
- ✅ ⭐ **Manajemen pendamping UMKM** — *Pain:* pembinaan tidak terpantau → *Fungsi:* tetapkan pendamping per toko, catatan kunjungan, progres "naik kelas". (`admin/pendamping` + panel `pendamping` — catatan kunjungan & progres.)

### C. Katalog & Konten

- ✅ **Kelola kategori + ikon** — *Pain:* kategori berantakan → *Fungsi:* tambah/ubah/nonaktifkan/hapus kategori aman. (`admin/Categories.php`)
- ✅ **Moderasi produk** — *Pain:* produk terlarang/menyesatkan lolos → *Fungsi:* daftar produk semua toko, takedown, laporan pengguna. (`admin/moderasi` — turunkan/pulihkan produk, laporan pengguna (`Laporkan`).)
- ✅ **CMS banner & halaman promo** — *Pain:* kampanye musiman perlu deploy kode → *Fungsi:* atur banner beranda, halaman kampanye ("Pekan Produk Lokal"). (`admin/banner` + `/promo/{slug}`.)
- ✅ **Kelola konten Akademi UMKM** — *Pain:* materi pelatihan perlu diperbarui → *Fungsi:* unggah modul, kuis, jadwal webinar. (`admin/akademi` — modul, soal kuis, jadwal.)
- ✅ **Moderasi ulasan & laporan** — *Pain:* ulasan kasar/spam → *Fungsi:* sembunyikan ulasan, tangani laporan. (`admin/moderasi/ulasan` — sembunyikan ulasan, rating dihitung ulang.)

### D. Transaksi & Keuangan

- ✅ **Pencairan dana ke toko** — *Pain:* penjual menunggu uang → *Fungsi:* cairkan, tandai selesai/gagal dengan bukti. (`admin/Keuangan.php`, `Pendapatan_lib`)
- ✅ **Komisi platform terkunci per pesanan** — *Pain:* perubahan komisi merusak catatan lama → *Fungsi:* komisi dihitung & disimpan saat lunas, dibatasi 0–50%. (`Pendapatan_lib::catat`)
- ✅ **Proses refund** — *Pain:* gateway tidak punya API refund → *Fungsi:* admin menandai refund yang disetujui penjual sebagai selesai. (`Keuangan::refund`, `refund_selesai`)
- ✅ **Rekonsiliasi pembayaran otomatis** — *Pain:* pesanan menggantung/tidak terbayar → *Fungsi:* callback Duitku tercatat, sapu otomatis pesanan kedaluwarsa. (`Payment::callback/sweep`, `Order_model::log_callback`, `tandai_kedaluwarsa`)
- ✅ **Pusat sengketa (dispute)** — *Pain:* pembeli dan penjual tidak sepakat soal refund → *Fungsi:* admin menengahi dengan bukti chat & foto. (`admin/sengketa` — percakapan 3 pihak + keputusan yang membuat refund.)
- ✅ **Voucher & subsidi ongkir platform** — *Pain:* butuh program akuisisi pembeli → *Fungsi:* buat voucher platform dengan kuota & anggaran. (`admin/voucher` — kuota & anggaran; subsidi ongkir di Pengaturan.)
- ✅ **Pengaturan sistem lewat UI** — *Pain:* komisi, kunci API, kontak WA admin diubah lewat file config → *Fungsi:* halaman pengaturan. (`admin/pengaturan` — komisi, WA, email, ekspedisi, promo, stok.)

### E. Komunikasi & Keamanan

- ✅ **Broadcast pengumuman ke penjual (web + WA)** — *Pain:* info pelatihan/kebijakan tidak sampai → *Fungsi:* kirim pengumuman tersegmentasi per wilayah/kategori. (`admin/broadcast` — per wilayah/kategori, banner di panel penjual + WA.)
- ✅ **Log audit aktivitas admin** — *Pain:* tidak bisa melacak siapa mengubah apa → *Fungsi:* riwayat aksi admin. (`Audit_lib` + `admin/pengaturan/audit`.)
- ✅ **Halaman galat ramah pengguna** — *Pain:* halaman error teknis membingungkan & membocorkan info → *Fungsi:* 404/galat dalam kerangka situs. (`Galat.php`)

---

## Catatan Pemasangan

1. Jalankan migrasi `database/24_*.sql` sampai `database/30_*.sql` berurutan (aman diulang; cadangkan dulu).
2. Isi kunci di **Admin > Pengaturan**: gerbang WhatsApp (mis. Fonnte), email, dan agregator ekspedisi (mis. Biteship). Tanpa kunci, fitur tetap jalan dengan cara manual (tautan WA, tautan lacak resmi kurir).
3. Dokumen legalitas penjual disimpan di `application/dokumen_toko/` (tidak bisa diakses publik). Pastikan server web menolak akses langsung ke `/application/`.
4. Yang masih bisa dikembangkan: tarif ongkir otomatis dari kurir saat checkout pembeli (kini tarif tetap per wilayah yang diatur penjual; perbandingan tarif kurir tersedia untuk penjual).
