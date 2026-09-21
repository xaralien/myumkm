<!-- Partial: HEADER (hero) -->
<!-- <div data-include="<?= base_url() ?>parts/header.php"></div> -->
<?php $this->load->view('parts/header.php') ?>

<!-- Partial: NAVBAR -->
<!-- <div data-include="<?= base_url() ?>parts/navbar.php"></div> -->
<?php $this->load->view('parts/navbar.php') ?>


<!-- Partial: BODY (konten utama) -->
<!-- <div data-include="<?= $pages ?>"></div> -->
<?php $this->load->view($pages) ?>

<!-- Partial: FOOTER -->
<!-- <div data-include="<?= base_url() ?>parts/footer.php"></div> -->
<?php $this->load->view('parts/footer.php') ?>