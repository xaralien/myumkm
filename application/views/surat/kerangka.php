<?php
/* -----------------------------------------------------------------------------
   surat/kerangka.php - bingkai HTML untuk semua email

   Dipanggil dari templat surat lain:
       $this->load->view('surat/kerangka', array('judul' => ..., 'isi' => ...));

   KENAPA TABEL DAN GAYA SEBARIS, BUKAN CSS RAPI

   Email bukan halaman web. Outlook di Windows merender lewat mesin Word,
   Gmail membuang seluruh <style> di <head>, dan banyak klien tidak mengenal
   flexbox maupun grid. Yang bisa diandalkan di semua tempat tinggal dua:
   tabel untuk tata letak, dan atribut style= pada tiap elemen.

   Jadi berkas ini memang terlihat seperti HTML tahun 2003. Itu disengaja -
   bukan karena belum dirapikan.

   Warnanya ditulis sebagai kode hex langsung, bukan var(--wn-biru): email
   tidak punya :root untuk dibaca. Nilainya disalin dari tema-nusantara.css,
   jadi kalau tema situsnya berubah, berkas ini perlu diubah juga.
   -------------------------------------------------------------------------- */

$situs = 'Sapa UMKM';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= html_escape($judul) ?></title>
</head>
<body style="margin:0; padding:0; background:#F2F7FA;">

<!-- Pratinjau yang muncul di daftar kotak masuk, di samping subjeknya.
     Disembunyikan dari isi email. Tanpa ini, yang tampil di sana adalah
     potongan pertama isi email - biasanya "Halo Budi," yang tidak
     memberi tahu apa pun. -->
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">
  <?= html_escape($pratinjau ?? $judul) ?>
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#F2F7FA; padding:28px 12px;">
  <tr>
    <td align="center">

      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
             style="max-width:520px; background:#FFFFFF; border:1px solid #DCE7EE;
                    border-radius:14px; overflow:hidden;">

        <!-- Kepala. Gradien ditulis sebagai background-image DI ATAS
             background-color: klien yang tidak mengerti gradien jatuh ke
             warna biru polos, bukan ke latar putih dengan tulisan putih. -->
        <tr>
          <td style="background-color:#22466C;
                     background-image:linear-gradient(135deg,#2F6A8A 0%,#22466C 56.5%);
                     padding:22px 28px;">
            <p style="margin:0; font-family:Georgia,'Times New Roman',serif;
                      font-size:20px; font-weight:700; color:#FFFFFF;
                      letter-spacing:.2px;"><?= html_escape($situs) ?></p>
          </td>
        </tr>

        <!-- Garis emas tipis di bawah kepala - satu-satunya tempat warna
             kedua tema muncul di email ini. Tinggi dipaksa lewat atribut
             height DAN font-size/line-height: Outlook memberi tinggi minimum
             pada sel kosong sesuai ukuran fontnya, jadi tanpa ini garisnya
             jadi pita setebal 15px. -->
        <tr>
          <td height="4" bgcolor="#CBA02D"
              style="height:4px; line-height:4px; font-size:4px;
                     background-color:#CBA02D;
                     background-image:linear-gradient(90deg,#A07B16 0%,#CBA02D 45%,#FDD977 100%);">&nbsp;</td>
        </tr>

        <tr>
          <td style="padding:28px;
                     font-family:-apple-system,'Segoe UI',Arial,sans-serif;
                     font-size:15px; line-height:1.6; color:#3E566B;">
            <?= $isi ?>
          </td>
        </tr>

        <tr>
          <td style="padding:18px 28px; background:#F7FAFC; border-top:1px solid #DCE7EE;
                     font-family:-apple-system,'Segoe UI',Arial,sans-serif;
                     font-size:12px; line-height:1.6; color:#5B7288;">
            Email ini dikirim otomatis oleh <?= html_escape($situs) ?>.
            Kalau kamu tidak merasa memintanya, abaikan saja - tidak ada yang
            berubah pada akunmu.
          </td>
        </tr>

      </table>

    </td>
  </tr>
</table>

</body>
</html>
