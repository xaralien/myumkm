<!-- application/views/admin/v_admin_footer.php -->
</div>
<script src="<?= aset('assets/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= aset('assets/js/panel-menu.js') ?>"></script>
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
    window.CHAT_TEMPLATE = <?= json_encode($CI_n->db->table_exists('chat_templates')
        ? $CI_n->db->select('judul, isi')->where('store_id', (int) $CI_n->auth_lib->store()['id'])
              ->order_by('urutan', 'ASC')->order_by('id', 'ASC')->get('chat_templates')->result_array()
        : array()) ?>;
  </script>
  <script src="<?= aset('assets/js/panel-notif.js') ?>"></script>
  <script src="<?= aset('assets/js/template-chat.js') ?>"></script>
<?php endif; ?>
</body>
</html>
