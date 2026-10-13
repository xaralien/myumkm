<?php
/* -----------------------------------------------------------------------------
   surat/reset.php - email "ganti sandi"

   Variabel: $nama, $tautan, $jam
   -------------------------------------------------------------------------- */

$tombol = '-apple-system,\'Segoe UI\',Arial,sans-serif';

ob_start();
?>
<p style="margin:0 0 14px;">Halo <?= html_escape($nama) ?>,</p>

<p style="margin:0 0 20px;">
  Ada yang meminta penggantian sandi untuk akun ini. Kalau itu kamu, tekan
  tombol di bawah. Tautannya berlaku <?= (int) $jam ?> jam dan hanya bisa
  dipakai sekali.
</p>

<!-- Tombol dibuat dari tabel, bukan <a> yang di-padding.
     Outlook mengabaikan padding pada <a>, jadi "tombolnya" jadi tulisan biru
     biasa tanpa kotak. Dengan sel tabel berwarna, kotaknya tetap ada. -->
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px;">
  <tr>
    <td align="center" bgcolor="#2F6A8A" style="border-radius:10px;">
      <a href="<?= html_escape($tautan) ?>"
         style="display:inline-block; padding:13px 26px; font-family:<?= $tombol ?>;
                font-size:15px; font-weight:700; color:#FFFFFF; text-decoration:none;
                border-radius:10px;">Pasang sandi baru</a>
    </td>
  </tr>
</table>

<p style="margin:0 0 8px; font-size:13px; color:#5B7288;">
  Tombolnya tidak bisa ditekan? Salin alamat ini ke browser:
</p>

<!-- word-break, kalau tidak alamat sepanjang ini melebarkan seluruh email
     dan di HP harus digeser ke samping untuk membacanya. -->
<p style="margin:0 0 20px; font-size:12px; color:#2F6A8A; word-break:break-all;">
  <?= html_escape($tautan) ?>
</p>

<p style="margin:0; font-size:13px; color:#5B7288;">
  Kalau bukan kamu yang meminta, abaikan email ini. Sandimu tetap seperti
  sekarang selama tautan di atas tidak dibuka.
</p>
<?php
$isi = ob_get_clean();

/* return = TRUE lalu di-echo, bukan dibiarkan view ini mencetak
   sendiri: berkas ini dimuat dengan return=TRUE oleh Surat_lib, dan
   penyangga keluaran CodeIgniter bersarang dua tingkat mudah membuat isinya
   hilang. Dikembalikan sebagai string, tidak ada yang bisa salah. */
echo $this->load->view('surat/kerangka', array(
    'judul'     => 'Ganti sandi',
    'pratinjau' => 'Tautan ganti sandi, berlaku ' . (int) $jam . ' jam.',
    'isi'       => $isi,
), TRUE);
