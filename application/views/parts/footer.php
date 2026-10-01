<?php
/* =============================================================================
   Penutup halaman publik: footer tema, script bersama, lalu </body>.

   Footer Furni lama (gambar sofa, kredit Untree.co, tautan Terms & Privacy
   yang tidak menuju ke mana pun) diganti v_footer.php.
   ========================================================================== */
$this->load->view('v_footer');
?>

<?php $this->load->view('parts/chat_inbox'); ?>

<?php $CI = &get_instance();
$CI->load->library('tampilan_lib'); ?>


<script>
  window.TAMPILAN = {
    mode: <?= json_encode($CI->tampilan_lib->mode()) ?>,
    // Pilihan manual tidak boleh dikoreksi otomatis.
    manual: <?= $CI->tampilan_lib->manual() ? 'true' : 'false' ?>
  };
</script>
<script src="<?= aset('assets/js/tampilan.js') ?>"></script>
<script src="<?= aset('assets/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= aset('assets/js/tiny-slider.js') ?>"></script>
<script src="<?= aset('assets/js/custom.js') ?>"></script>
</body>

</html>