<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/* =============================================================================
   Salin berkas ini menjadi ai.php, lalu isi api_key.
   ai.php masuk .gitignore - kunci API tidak ikut ter-commit.

   Tanpa ai.php, tombol "Buatkan dengan AI" di formulir produk
   disembunyikan otomatis. Tidak ada halaman yang rusak.
   ========================================================================== */

$config['ai']['enabled']  = TRUE;
$config['ai']['base_url'] = 'https://api.groq.com/openai/v1';
$config['ai']['api_key']  = 'ISI_KUNCI_ANDA';

/* Daftar model, dicoba dari atas. Groq merotasi model beberapa kali
   setahun - buka /seller/ai_models untuk melihat yang masih tersedia. */
$config['ai']['model'] = array(
    'openai/gpt-oss-120b',
    'openai/gpt-oss-20b',
);
$config['ai']['model_vision'] = array(
    'qwen/qwen3.6-27b',
    'meta-llama/llama-4-maverick-17b-128e-instruct',
);

$config['ai']['max_tokens']        = 500;
$config['ai']['temperature']       = 0.7;
$config['ai']['timeout']           = 20;
$config['ai']['lebar_gambar_maks'] = 768;

// Pagar pemakaian - berhenti sebelum batas tier gratis tersentuh.
$config['ai']['batas_per_jam']        = 20;
$config['ai']['batas_token_harian']   = 80000;
$config['ai']['batas_request_harian'] = 800;
$config['ai']['ambang_peringatan']    = 0.8;
