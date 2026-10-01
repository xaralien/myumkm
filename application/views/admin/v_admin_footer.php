<!-- application/views/admin/v_admin_footer.php -->
</div>
<script src="<?= aset('assets/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= aset('assets/js/summernote-bs5.js') ?>"></script>

<?php
/* Penjual bisa membalas pembeli dari halaman panel mana pun - tidak perlu
     membuka daftar pesanan lebih dulu. */
$this->load->view('parts/chat_inbox', array('sisi_paksa' => 'seller'));
?>
</body>

</html>