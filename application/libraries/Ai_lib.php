<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Ai_lib - membuat deskripsi produk dari nama + kategori.
 *
 * Simpan di: application/libraries/Ai_lib.php
 * Pakai    : $this->load->library('ai_lib');
 *
 * Memakai format OpenAI (/chat/completions) yang didukung hampir semua
 * penyedia - Groq, OpenRouter, Cloudflare, Together, juga Ollama kalau
 * nanti dijalankan sendiri. Pindah penyedia cukup mengubah config.
 *
 * KUNCI API TIDAK PERNAH SAMPAI KE BROWSER. Semua panggilan lewat server,
 * sama seperti Duitku. Kalau kunci masuk JavaScript, siapa pun bisa
 * mengambilnya dari view-source dan memakainya atas nama akunmu.
 */
class Ai_lib
{

    protected $CI;
    protected $cfg;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('session');
        // Cache dipakai mengingat model mana yang masih hidup, supaya
        // tidak mencoba model mati berulang kali di tiap permintaan.
        $this->CI->load->driver('cache', array('adapter' => 'file', 'backup' => 'dummy'));
        /* Parameter ketiga TRUE: kalau config/ai.php tidak ada, JANGAN
           hentikan halaman. Berkas itu berisi kunci API, jadi wajar tidak
           ikut di repo - tanpa ini, formulir produk penjual gagal dibuka
           total hanya karena fitur AI belum disiapkan. */
        $this->CI->config->load('ai', TRUE, TRUE);
        $cfg = $this->CI->config->item('ai', 'ai');
        $this->cfg = is_array($cfg) ? $cfg : array();
    }

    public function aktif()
    {
        return ! empty($this->cfg['enabled'])
            && ! empty($this->cfg['api_key'])
            && $this->cfg['api_key'] !== 'ISI_KUNCI_ANDA';
    }

    /**
     * @param  string $nama     nama produk
     * @param  string $kategori nama kategori
     * @param  string $toko     nama toko, untuk nuansa lokal
     * @return array  ['ok' => bool, 'teks' => string, 'pesan' => string]
     */
    /**
     * @param  string      $nama     nama produk
     * @param  string      $kategori nama kategori
     * @param  string      $toko     nama toko
     * @param  string|NULL $gambar   path berkas foto produk (opsional)
     * @return array ['ok','teks','pesan','kuota']
     */
    public function deskripsi_produk($nama, $kategori, $toko = '', $gambar = NULL)
    {
        if (! $this->aktif()) {
            return $this->gagal('Fitur AI belum diaktifkan. Isi kunci API di config/ai.php.');
        }
        if (trim($nama) === '') {
            return $this->gagal('Isi nama produk dulu.');
        }
        if (! $this->boleh_pakai()) {
            return $this->gagal('Sudah terlalu sering. Coba lagi beberapa menit lagi.');
        }

        $kuota = $this->kuota();
        if ($kuota['habis']) {
            return $this->gagal(
                'Kuota AI hari ini sudah habis. Terisi lagi pukul '
                    . $kuota['reset_lokal'] . '. Silakan tulis deskripsinya manual dulu.'
            );
        }

        $data_gambar = $gambar ? $this->siapkan_gambar($gambar) : NULL;

        /* Instruksi versi lama menyuruh "jelaskan kesan dan momen, bukan
           spesifikasi". Itu justru mengundang kalimat melayang seperti
           "menghadirkan keindahan alam dengan warna-warna cerah".
           Sekarang dibalik: WAJIB menyebut hal yang benar-benar terlihat,
           dan frasa klise yang sering muncul dilarang satu per satu. */
        $sistem = "Kamu menulis deskripsi produk untuk toko bunga di Indonesia.\n\n"
            . "ATURAN ISI:\n"
            . "- Kalimat pertama WAJIB menyebut hal yang konkret: jenis bunga, warna\n"
            . "  yang terlihat, dan cara dirangkai atau dibungkus.\n"
            . "- Kalimat kedua boleh menyebut satu momen yang cocok, sebut yang\n"
            . "  spesifik (wisuda, ulang tahun ibu, pembukaan toko), bukan\n"
            . "  \"momen spesial\".\n"
            . "- Kalau ada foto, JELASKAN APA YANG KAMU LIHAT di foto itu.\n"
            . "  Jangan menebak dari nama produk kalau foto tersedia.\n\n"
            . "DILARANG memakai frasa kosong berikut:\n"
            . "\"keindahan alam\", \"warna-warna cerah\", \"momen spesial\",\n"
            . "\"kasih sayang dan kebahagiaan\", \"sentuhan alami\", \"sempurna untuk\",\n"
            . "\"tak terlupakan\", \"menghadirkan\", \"mempercantik hari\".\n\n"
            . "DILARANG menyebut harga, angka, diskon, atau jumlah tangkai.\n"
            . "DILARANG memakai emoji, tanda bintang, judul, atau tanda kutip.\n"
            . "Balas HANYA deskripsinya. Bahasa Indonesia, 2 kalimat, maksimal 45 kata.\n\n"
            . "CONTOH BURUK (jangan tiru):\n"
            . "Menghadirkan keindahan alam dengan warna-warna cerah, buket ini cocok\n"
            . "untuk momen spesial.\n\n"
            . "CONTOH BAIK:\n"
            . "Mawar merah tua dipadu baby breath putih, dibungkus kertas kraft cokelat\n"
            . "dengan pita satin. Pilihan yang tenang untuk anniversary atau permintaan maaf.";

        $pengguna = "Nama produk: " . trim($nama) . "\n"
            . "Kategori: " . trim($kategori);
        if (trim($toko) !== '') {
            $pengguna .= "\nToko: " . trim($toko);
        }
        if ($data_gambar) {
            $pengguna .= "\n\nLihat foto produknya, lalu sebutkan bunga dan warna "
                . "yang benar-benar terlihat di sana.";
        }

        // Isi pesan berbentuk array kalau ada gambar - format multimodal OpenAI.
        if ($data_gambar) {
            $isi = array(
                array('type' => 'text', 'text' => $pengguna),
                array(
                    'type'      => 'image_url',
                    'image_url' => array('url' => $data_gambar),
                ),
            );
        } else {
            $isi = $pengguna;
        }

        $params = array(
            'max_tokens'  => (int) $this->cfg['max_tokens'],
            'temperature' => (float) $this->cfg['temperature'],
            'messages'    => array(
                array('role' => 'system', 'content' => $sistem),
                array('role' => 'user',   'content' => $isi),
            ),
        );

        // Model vision hanya dipakai kalau memang ada foto - model teks
        // biasa lebih hemat token untuk produk tanpa gambar.
        $res = $this->panggil_dengan_kandidat($params, $data_gambar ? 'vision' : 'teks');

        /* Semua model vision mati -> ulangi TANPA gambar memakai model teks.
           Deskripsi yang agak umum masih jauh lebih berguna bagi penjual
           daripada tombol yang gagal tanpa hasil apa pun. */
        if (isset($res['error']) && $data_gambar && ! empty($res['model_habis'])) {
            log_message('error', 'AI: semua model vision gagal, mundur ke model teks.');

            $params['messages'][1]['content'] = $pengguna;
            $res = $this->panggil_dengan_kandidat($params, 'teks');
            $data_gambar = NULL;
        }

        if (isset($res['error'])) {
            return $this->gagal($res['error']);
        }

        $teks = $this->rapikan($res['teks']);

        if ($teks === '') {
            return $this->gagal(
                'Jawaban AI terpotong sebelum selesai. Coba lagi, atau naikkan '
                    . 'max_tokens di config/ai.php.'
            );
        }

        $this->catat_pemakaian();
        $res['headers']['__model'] = isset($res['model']) ? $res['model'] : NULL;
        $this->rekam_token($res['usage'], $res['headers']);

        return array(
            'ok'      => TRUE,
            'teks'    => $teks,
            'pesan'   => '',
            'kuota'   => $this->kuota(),
            'pakai_gambar' => (bool) $data_gambar,
        );
    }

    /**
     * Baca foto, kecilkan, ubah jadi data URI base64.
     *
     * Pengecilan bukan sekadar optimasi: foto 3000px dari kamera HP menjadi
     * base64 raksasa yang memakan ribuan token dan bisa menembus batas
     * ukuran permintaan. Untuk mengenali jenis bunga dan warnanya,
     * 768px sudah lebih dari cukup.
     *
     * @return string|NULL data URI, atau NULL kalau gagal
     */
    protected function siapkan_gambar($path)
    {
        if (! is_file($path) || ! is_readable($path)) {
            return NULL;
        }

        $info = @getimagesize($path);
        if ($info === FALSE) {
            return NULL;
        }

        // Tanpa GD, kirim apa adanya asal ukurannya masih wajar.
        if (! function_exists('imagecreatefromstring')) {
            if (filesize($path) > 1200000) {
                return NULL;
            }
            return 'data:' . $info['mime'] . ';base64,' . base64_encode(file_get_contents($path));
        }

        $asli = @imagecreatefromstring(file_get_contents($path));
        if ($asli === FALSE) {
            return NULL;
        }

        $lebar_maks = (int) $this->cfg['lebar_gambar_maks'];
        $w = imagesx($asli);
        $h = imagesy($asli);

        if ($w > $lebar_maks || $h > $lebar_maks) {
            $rasio = min($lebar_maks / $w, $lebar_maks / $h);
            $w_baru = max(1, (int) round($w * $rasio));
            $h_baru = max(1, (int) round($h * $rasio));

            $kecil = imagecreatetruecolor($w_baru, $h_baru);

            /* PNG dan WEBP bisa transparan. Tanpa latar putih, area
               transparan menjadi hitam setelah dijadikan JPEG - dan model
               akan melaporkan warna yang tidak pernah ada di produknya. */
            $putih = imagecolorallocate($kecil, 255, 255, 255);
            imagefilledrectangle($kecil, 0, 0, $w_baru, $h_baru, $putih);

            imagecopyresampled($kecil, $asli, 0, 0, 0, 0, $w_baru, $h_baru, $w, $h);
            imagedestroy($asli);
            $asli = $kecil;
        }

        ob_start();
        imagejpeg($asli, NULL, 82);
        $biner = ob_get_clean();
        imagedestroy($asli);

        if ($biner === '' || $biner === FALSE) {
            return NULL;
        }

        return 'data:image/jpeg;base64,' . base64_encode($biner);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Coba tiap model dari daftar kandidat sampai ada yang berhasil.
     *
     * Model yang berhasil DIINGAT selama 6 jam, jadi permintaan berikutnya
     * langsung memakainya - tanpa ini, tiap panggilan akan menabrak model
     * mati lebih dulu dan menambah satu perjalanan bolak-balik.
     *
     * @param  string $jenis 'teks' atau 'vision'
     */
    protected function panggil_dengan_kandidat(array $params, $jenis)
    {
        $daftar = ($jenis === 'vision') ? $this->cfg['model_vision'] : $this->cfg['model'];
        $daftar = is_array($daftar) ? array_values($daftar) : array($daftar);

        $kunci_cache = 'ai_model_' . $jenis;
        $diingat = $this->CI->cache->get($kunci_cache);

        // Yang diingat dicoba lebih dulu, sisanya tetap jadi cadangan.
        if ($diingat && in_array($diingat, $daftar, TRUE)) {
            $daftar = array_merge(
                array($diingat),
                array_values(array_diff($daftar, array($diingat)))
            );
        }

        $galat_terakhir = NULL;

        foreach ($daftar as $model) {
            $params['model'] = $model;
            $res = $this->panggil($this->plus_reasoning($params, $model));

            // Parameter reasoning ditolak -> coba sekali lagi tanpa itu.
            if (isset($res['error']) && ! empty($res['soal_reasoning'])) {
                log_message('error', 'AI: ' . $model . ' menolak parameter reasoning, ulangi tanpa itu.');
                $res = $this->panggil($params);
            }

            if (! isset($res['error'])) {
                $this->CI->cache->save($kunci_cache, $model, 21600);   // 6 jam
                $res['model'] = $model;
                return $res;
            }

            $galat_terakhir = $res['error'];

            /* Hanya lanjut ke kandidat berikutnya kalau masalahnya memang
               modelnya. Untuk kunci ditolak atau kuota habis, mencoba model
               lain sia-sia - dan malah menghabiskan sisa kuota. */
            if (empty($res['model_tidak_ada'])) {
                return $res;
            }

            log_message('error', 'AI: model ' . $model . ' tidak tersedia, coba berikutnya.');

            // Ingatan sudah basi kalau yang tersimpan justru yang gagal.
            if ($diingat === $model) {
                $this->CI->cache->delete($kunci_cache);
            }
        }

        return array(
            'error'       => 'Tidak ada model yang tersedia. Buka /seller/ai_models '
                . 'untuk melihat daftar model akunmu, lalu perbarui config/ai.php. '
                . '(' . $galat_terakhir . ')',
            'model_habis' => TRUE,
        );
    }

    /**
     * Tambahkan parameter penekan "berpikir" sesuai keluarga model.
     *
     * gpt-oss dan qwen3.6 adalah model reasoning: mereka menuliskan proses
     * berpikir dulu, dan tanpa penjagaan proses itu ikut masuk ke jawaban -
     * pengguna melihat "<think>Here's a thinking process..." di kolom
     * deskripsi. Cara mematikannya BERBEDA per keluarga:
     *
     *   qwen3   -> reasoning_effort 'none' benar-benar mematikan berpikir.
     *              Ini yang paling hemat: tidak ada token terbuang.
     *   gpt-oss -> tidak bisa dimatikan sama sekali, hanya bisa dikecilkan
     *              ke 'low'. Parameter reasoning_format TIDAK didukung di
     *              keluarga ini - jejaknya sudah dipisah ke field tersendiri.
     *   lainnya -> reasoning_format 'hidden'.
     *
     * Menulis deskripsi produk dua kalimat memang tidak butuh penalaran
     * berlapis, jadi tidak ada yang hilang dari mematikannya.
     */
    protected function plus_reasoning(array $params, $model)
    {
        $m = strtolower($model);

        if (strpos($m, 'qwen') !== FALSE) {
            $params['reasoning_effort'] = 'none';
        } elseif (strpos($m, 'gpt-oss') !== FALSE) {
            $params['reasoning_effort'] = 'low';
        } else {
            $params['reasoning_format'] = 'hidden';
        }
        return $params;
    }

    protected function panggil(array $params)
    {
        $url = rtrim($this->cfg['base_url'], '/') . '/chat/completions';

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_POST           => TRUE,
            CURLOPT_POSTFIELDS     => json_encode($params),
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->cfg['api_key'],
            ),
            CURLOPT_TIMEOUT        => (int) $this->cfg['timeout'],
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => TRUE,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HEADER         => TRUE,   // header ikut dibaca
        ));

        $raw_penuh = curl_exec($ch);
        $err       = curl_error($ch);
        $kode      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $panjang   = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $raw     = ($raw_penuh === FALSE) ? FALSE : substr($raw_penuh, $panjang);
        $headers = ($raw_penuh === FALSE) ? array()
            : $this->baca_header(substr($raw_penuh, 0, $panjang));

        if ($raw === FALSE) {
            log_message('error', 'AI cURL: ' . $err);
            return array('error' => 'Tidak bisa menghubungi layanan AI.');
        }

        $body = json_decode($raw, TRUE);

        if ($kode === 429) {
            // retry-after hanya dikirim saat 429. Sebutkan detiknya supaya
            // penjual tahu harus menunggu berapa lama, bukan menebak.
            $tunggu = isset($headers['retry-after']) ? (int) $headers['retry-after'] : 0;
            return array('error' => $tunggu
                ? 'Batas kecepatan tercapai. Coba lagi ' . $tunggu . ' detik lagi.'
                : 'Batas kecepatan tercapai. Coba lagi sebentar lagi.');
        }
        if ($kode === 401 || $kode === 403) {
            log_message('error', 'AI auth gagal: ' . $raw);
            return array('error' => 'Kunci API ditolak. Periksa config/ai.php.');
        }
        if ($kode !== 200) {
            $pesan = isset($body['error']['message']) ? $body['error']['message'] : ('HTTP ' . $kode);
            log_message('error', 'AI galat: ' . $raw);

            /* 404, atau 400 dengan pesan "does not exist" / "decommissioned",
               berarti nama modelnya yang salah - bukan kunci maupun kuota.
               Ditandai supaya pemanggil tahu boleh mencoba model lain. */
            $model_hilang = ($kode === 404)
                || stripos($pesan, 'does not exist') !== FALSE
                || stripos($pesan, 'decommissioned') !== FALSE
                || stripos($pesan, 'has been deprecated') !== FALSE
                || stripos($pesan, 'model_not_found') !== FALSE;

            /* Parameter reasoning ditolak model ini. Daripada gagal,
               ulangi sekali tanpa parameter itu - jejak berpikirnya nanti
               dibersihkan rapikan(). Daftar parameter Groq berubah-ubah
               per model, jadi ini pengaman yang memang diperlukan. */
            $soal_reasoning = stripos($pesan, 'reasoning') !== FALSE;

            return array(
                'error'            => 'Layanan AI menolak permintaan: ' . $pesan,
                'model_tidak_ada'  => $model_hilang,
                'soal_reasoning'   => $soal_reasoning,
            );
        }

        if (! isset($body['choices'][0]['message']['content'])) {
            log_message('error', 'AI bentuk balasan tak dikenal: ' . $raw);
            return array('error' => 'Balasan AI tidak bisa dibaca.');
        }

        return array(
            'teks'    => (string) $body['choices'][0]['message']['content'],
            'usage'   => isset($body['usage']) ? $body['usage'] : array(),
            'headers' => $headers,
        );
    }

    /**
     * Bersihkan kebiasaan model: tanda bintang, tanda kutip pembungkus,
     * kalimat pembuka, dan baris kosong berlebih.
     */
    protected function rapikan($teks)
    {
        $teks = trim($teks);

        /* Buang blok berpikir yang lolos ke jawaban. Model reasoning
           membungkusnya dengan <think>...</think> (Qwen) atau menuliskannya
           sebagai narasi "Here's a thinking process...".

           Ini jaring pengaman lapis kedua - lapis pertama ada di
           plus_reasoning(). Tetap diperlukan karena perilaku model bisa
           berubah setelah pembaruan di sisi penyedia. */
        $teks = preg_replace('/<think>.*?<\/think>/isu', '', $teks);
        $teks = preg_replace('/<reasoning>.*?<\/reasoning>/isu', '', $teks);
        $teks = preg_replace('/<\|channel\|>analysis.*?<\|message\|>/isu', '', $teks);

        /* Blok berpikir tanpa penutup berarti jawabannya terpotong di
           tengah - max_tokens habis sebelum model selesai berpikir.
           Kembalikan kosong supaya pemanggil menampilkan pesan galat,
           bukan menaruh potongan pikiran ke kolom deskripsi. */
        if (stripos($teks, '<think>') !== FALSE) {
            return '';
        }

        $teks = trim($teks);
        $teks = preg_replace('/[*_`#]+/', '', $teks);                 // markdown
        $teks = preg_replace('/^(Berikut|Ini|Deskripsi)[^:]{0,40}:\s*/iu', '', $teks);
        $teks = trim($teks, " \t\n\r\0\x0B\"'");
        $teks = preg_replace('/\s*\n\s*\n\s*/u', "\n", $teks);
        $teks = preg_replace('/[ \t]+/u', ' ', $teks);

        // Pengaman terakhir: kolom description dibatasi 2000 karakter.
        if (function_exists('mb_substr') && mb_strlen($teks) > 600) {
            $teks = mb_substr($teks, 0, 600);
        }
        return trim($teks);
    }

    /* --------------------------------------------------- batas pemakaian --- */

    protected function riwayat()
    {
        $r = $this->CI->session->userdata('ai_pakai');
        return is_array($r) ? $r : array();
    }

    protected function boleh_pakai()
    {
        $sejam = time() - 3600;
        $r = array_filter($this->riwayat(), function ($t) use ($sejam) {
            return $t > $sejam;
        });
        return count($r) < (int) $this->cfg['batas_per_jam'];
    }

    protected function catat_pemakaian()
    {
        $sejam = time() - 3600;
        $r = array_values(array_filter($this->riwayat(), function ($t) use ($sejam) {
            return $t > $sejam;
        }));
        $r[] = time();
        $this->CI->session->set_userdata('ai_pakai', $r);
    }

    /**
     * Daftar model yang benar-benar bisa dipakai kunci API ini.
     * Dipakai halaman diagnosa /seller/ai_models.
     */
    public function daftar_model()
    {
        $url = rtrim($this->cfg['base_url'], '/') . '/models';

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER     => array('Authorization: Bearer ' . $this->cfg['api_key']),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => TRUE,
            CURLOPT_SSL_VERIFYHOST => 2,
        ));
        $raw  = curl_exec($ch);
        $kode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === FALSE || $kode !== 200) {
            return array('error' => 'Gagal mengambil daftar model (HTTP ' . $kode . ').');
        }

        $body = json_decode($raw, TRUE);
        if (! isset($body['data']) || ! is_array($body['data'])) {
            return array('error' => 'Bentuk balasan tidak dikenal.');
        }

        $out = array();
        foreach ($body['data'] as $m) {
            if (isset($m['id'])) {
                $out[] = $m['id'];
            }
        }
        sort($out);
        return array('models' => $out);
    }

    /** Kosongkan ingatan model, supaya kandidat dicoba ulang dari awal. */
    public function lupakan_model()
    {
        $this->CI->cache->delete('ai_model_teks');
        $this->CI->cache->delete('ai_model_vision');
    }

    /* ---------------------------------------------------- kuota harian --- */

    /**
     * Tanggal UTC, bukan waktu lokal. Kuota Groq berganti tengah malam UTC,
     * yang di WIB jatuh pukul 07.00 pagi. Memakai tanggal lokal membuat
     * hitungan kita bergeser tujuh jam dari hitungan mereka.
     */
    protected function tanggal_utc()
    {
        return gmdate('Y-m-d');
    }

    /**
     * @return array status kuota hari ini
     */
    public function kuota()
    {
        $batas_token = (int) $this->cfg['batas_token_harian'];
        $batas_req   = (int) $this->cfg['batas_request_harian'];

        $row = $this->CI->db->where('tanggal_utc', $this->tanggal_utc())
            ->get('ai_usage')->row_array();

        $token = $row ? (int) $row['tokens_total'] : 0;
        $req   = $row ? (int) $row['requests'] : 0;

        $rasio = $batas_token > 0 ? $token / $batas_token : 0;
        if ($batas_req > 0) {
            $rasio = max($rasio, $req / $batas_req);
        }

        return array(
            'token'        => $token,
            'batas_token'  => $batas_token,
            'request'      => $req,
            'batas_request' => $batas_req,
            'persen'       => min(100, (int) round($rasio * 100)),
            'habis'        => ($token >= $batas_token) || ($req >= $batas_req),
            'peringatan'   => $rasio >= (float) $this->cfg['ambang_peringatan'],
            // Tengah malam UTC ditampilkan dalam waktu server, supaya
            // penjual tidak perlu menghitung selisih zona waktu sendiri.
            'reset_lokal'  => date('H:i', strtotime(gmdate('Y-m-d') . ' 23:59:59 UTC') + 1),
        );
    }

    protected function rekam_token($usage, $headers)
    {
        $prompt = isset($usage['prompt_tokens'])     ? (int) $usage['prompt_tokens']     : 0;
        $keluar = isset($usage['completion_tokens']) ? (int) $usage['completion_tokens'] : 0;
        $total  = isset($usage['total_tokens'])      ? (int) $usage['total_tokens']
            : ($prompt + $keluar);

        $tgl = $this->tanggal_utc();

        /* INSERT ... ON DUPLICATE KEY UPDATE, bukan SELECT lalu UPDATE.
           Dua penjual yang menekan tombol bersamaan bisa membaca angka yang
           sama lalu saling menimpa - satu panggilan jadi tidak terhitung.
           Cara ini menyerahkan penjumlahannya ke database. */
        $sql = "INSERT INTO ai_usage
                    (tanggal_utc, requests, tokens_prompt, tokens_completion, tokens_total)
                VALUES (?, 1, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    requests          = requests + 1,
                    tokens_prompt     = tokens_prompt + VALUES(tokens_prompt),
                    tokens_completion = tokens_completion + VALUES(tokens_completion),
                    tokens_total      = tokens_total + VALUES(tokens_total)";

        $this->CI->db->query($sql, array($tgl, $prompt, $keluar, $total));

        $this->CI->db->insert('ai_log', array(
            'store_id'     => $this->store_id(),
            'model'        => isset($headers['__model']) ? $headers['__model'] : NULL,
            'tokens_total' => $total,
            'berhasil'     => 1,
            'catatan'      => isset($headers['x-ratelimit-remaining-tokens'])
                ? 'sisa TPM: ' . $headers['x-ratelimit-remaining-tokens']
                : NULL,
        ));
    }

    protected function store_id()
    {
        $u = $this->CI->session->userdata('auth_user');
        if (! $u) {
            return NULL;
        }
        $row = $this->CI->db->select('id')->where('user_id', (int) $u['id'])
            ->get('stores')->row_array();
        return $row ? (int) $row['id'] : NULL;
    }

    /** Ubah blok header mentah jadi array, nama kunci huruf kecil semua. */
    protected function baca_header($blok)
    {
        $out = array();
        foreach (explode("\r\n", $blok) as $baris) {
            $p = strpos($baris, ':');
            if ($p === FALSE) {
                continue;
            }
            $out[strtolower(trim(substr($baris, 0, $p)))] = trim(substr($baris, $p + 1));
        }
        return $out;
    }

    protected function gagal($pesan)
    {
        return array('ok' => FALSE, 'teks' => '', 'pesan' => $pesan);
    }
}
