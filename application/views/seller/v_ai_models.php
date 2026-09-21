<!-- application/views/seller/v_ai_models.php -->
<div class="panel-judul">
    <div>
        <h1>Model AI yang tersedia</h1>
        <p class="hint">Daftar ini datang langsung dari penyedia, sesuai kunci API kamu.</p>
    </div>
    <a href="<?= site_url('seller/ai_models?reset=1') ?>" class="btn btn-black-hover-outline">
        Lupakan ingatan model
    </a>
</div>

<?php if ($galat): ?>
    <div class="alert-box alert-error mb-4"><?= html_escape($galat) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="form-card">
            <h3 class="form-card-title">Tersedia sekarang (<?= count($models) ?>)</h3>

            <?php if (! $models): ?>
                <p class="hint">Tidak ada data. Periksa kunci API di config/ai.php.</p>
            <?php else: ?>
                <ul class="model-list">
                    <?php foreach ($models as $m): ?>
                        <?php
                        $terpakai = in_array($m, $dipakai_teks, TRUE)
                            || in_array($m, $dipakai_vision, TRUE);
                        ?>
                        <li class="<?= $terpakai ? 'is-terpakai' : '' ?>">
                            <code><?= html_escape($m) ?></code>
                            <?php if ($terpakai): ?><span class="model-tag">dipakai</span><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="form-card">
            <h3 class="form-card-title">Yang tertulis di config</h3>

            <p class="hint mb-2">Model teks &mdash; dicoba dari atas:</p>
            <ul class="model-list mb-4">
                <?php foreach ($dipakai_teks as $m): ?>
                    <li class="<?= in_array($m, $models, TRUE) ? 'is-hidup' : 'is-mati' ?>">
                        <code><?= html_escape($m) ?></code>
                        <span class="model-tag"><?= in_array($m, $models, TRUE) ? 'hidup' : 'tidak ada' ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <p class="hint mb-2">Model gambar &mdash; dicoba dari atas:</p>
            <ul class="model-list">
                <?php foreach ($dipakai_vision as $m): ?>
                    <li class="<?= in_array($m, $models, TRUE) ? 'is-hidup' : 'is-mati' ?>">
                        <code><?= html_escape($m) ?></code>
                        <span class="model-tag"><?= in_array($m, $models, TRUE) ? 'hidup' : 'tidak ada' ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <p class="hint mt-3">
                Selama masih ada satu yang hidup di tiap daftar, fiturnya jalan.
                Kalau semua model gambar mati, deskripsi tetap dibuat dari nama dan
                kategori saja.
            </p>
        </div>
    </div>
</div>