<!-- application/views/admin/v_admin_footer.php -->
</div>
<script src="<?= aset('assets/js/bootstrap.bundle.min.js') ?>"></script>
<?php
  /* Penjual bisa membalas pembeli dari halaman panel mana pun - tidak perlu
     membuka daftar pesanan lebih dulu. */
  $this->load->view('parts/chat_inbox', array('sisi_paksa' => 'seller'));

  $CI_n =& get_instance();
  $CI_n->load->library('auth_lib');
?>
<?php if ($CI_n->auth_lib->store()): ?>
  <script>
    window.PANEL_NOTIF = { url: '<?= site_url('seller/notif') ?>' };
  </script>
  <script src="<?= aset('assets/js/panel-notif.js') ?>"></script>
<?php endif; ?>
</body>
</html>
