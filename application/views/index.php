<?php
/* =============================================================================
   Layout halaman publik - memilih tampilan mobile atau desktop.

   Di HP, KERANGKA mobile selalu dipakai (bilah atas + tab bar bawah). Yang
   jatuh ke versi desktop hanya ISI halamannya, kalau versi HP-nya belum
   dibuat. Sebelumnya seluruh kerangka ikut desktop, sehingga halaman
   seperti keranjang dan lacak kehilangan tab bar bawahnya.
   ========================================================================== */

$CI =& get_instance();
$CI->load->library('tampilan_lib');

if ($CI->tampilan_lib->is_mobile()) {

    $isi = file_exists(VIEWPATH . 'mobile/' . $pages . '.php')
        ? 'mobile/' . $pages
        : $pages;

    $this->load->view('mobile/parts/header');
    $this->load->view($isi);
    $this->load->view('mobile/parts/footer');

} else {
    $this->load->view('parts/header');   // <head>, CSS, <body>
    $this->load->view('v_navbar');
    $this->load->view($pages);
    $this->load->view('parts/footer');   // v_footer, script, </body></html>
}
