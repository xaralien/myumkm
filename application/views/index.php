<?php
/* =============================================================================
   Layout halaman publik.

   Navbar dan footer memakai v_navbar & v_footer (tema Nusantara).
   Sebelumnya berkas ini masih memuat parts/navbar.php dan
   parts/footer.php - navbar dan footer Furni lama - sehingga tema baru
   tidak pernah terlihat walau berkasnya sudah terpasang.
   ========================================================================== */

$this->load->view('parts/header');   // <head>, CSS, <body>
$this->load->view('v_navbar');
$this->load->view($pages);
$this->load->view('parts/footer');   // v_footer, script, </body></html>
