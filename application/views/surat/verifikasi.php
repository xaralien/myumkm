<?php
/* -----------------------------------------------------------------------------
   surat/verifikasi.php - email "verifikasi alamat email"

   Variabel: $nama, $tautan
   -------------------------------------------------------------------------- */

$tombol = '-apple-system,\'Segoe UI\',Arial,sans-serif';

ob_start();
?>
<p style="margin:0 0 14px;">Halo <?= html_escape($nama) ?>, terima kasih sudah mendaftar.</p>

<p style="margin:0 0 20px;">
  Tekan tombol di bawah untuk memastikan alamat email ini benar milikmu.
  Akunmu sudah aktif - verifikasi ini supaya kami bisa mengirim tautan sandi
  baru kalau kamu lupa, dan diperlukan kalau nanti kamu mau membuka toko.
</p>

<!-- Alasan tombol dibuat dari tabel sama dengan di surat/reset.php: Outlook
     mengabaikan padding pada <a>. -->
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px;">
  <tr>
    <td align="center" bgcolor="#2F6A8A" style="border-radius:10px;">
      <a href="<?= html_escape($tautan) ?>"
         style="display:inline-block; padding:13px 26px; font-family:<?= $tombol ?>;
                font-size:15px; font-weight:700; color:#FFFFFF; text-decoration:none;
                border-radius:10px;">Verifikasi email</a>
    </td>
  </tr>
</table>

<p style="margin:0 0 8px; font-size:13px; color:#5B7288;">
  Tombolnya tidak bisa ditekan? Salin alamat ini ke browser:
</p>

<p style="margin:0 0 20px; font-size:12px; color:#2F6A8A; word-break:break-all;">
  <?= html_escape($tautan) ?>
</p>

<p style="margin:0; font-size:13px; color:#5B7288;">
  Tautannya berlaku 2 hari. Kalau lewat, kirim ulang dari halaman profilmu.
</p>
<?php
$isi = ob_get_clean();

/* return = TRUE lalu di-echo, bukan dibiarkan view ini mencetak
   sendiri: berkas ini dimuat dengan return=TRUE oleh Surat_lib, dan
   penyangga keluaran CodeIgniter bersarang dua tingkat mudah membuat isinya
   hilang. Dikembalikan sebagai string, tidak ada yang bisa salah. */
echo $this->load->view('surat/kerangka', array(
    'judul'     => 'Verifikasi email',
    'pratinjau' => 'Satu tekan untuk memastikan email ini milikmu.',
    'isi'       => $isi,
), TRUE);
