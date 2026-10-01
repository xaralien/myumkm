<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ulasan - menulis dan membaca ulasan produk.
 * Simpan di: application/controllers/Ulasan.php
 *
 *   GET  ulasan/produk/{toko}/{slug}      semua ulasan produk + tab toko
 *   GET  ulasan/tulis/{nomor}/{token}     formulir ulasan untuk satu pesanan
 *   POST ulasan/kirim/{nomor}/{token}     simpan ulasan
 */
class Ulasan extends CI_Controller {

    /** Batas unggahan. Video dibatasi ketat karena berkasnya jauh lebih berat. */
    const MAKS_FOTO   = 5;
    const MAKS_VIDEO  = 1;
    const UKURAN_FOTO = 5242880;    // 5 MB
    const UKURAN_VIDEO = 31457280;  // 30 MB

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('review_model', 'product_model'));
        $this->load->helper(array('url', 'form', 'money'));
        $this->load->library('form_validation');
    }

    /* =====================================================================
       MEMBACA
       ===================================================================== */

    /** Halaman semua ulasan: tab Produk ini | Semua produk toko. */
    public function produk($toko = NULL, $slug = NULL)
    {
        $p = $this->product_model->detail($toko, $slug);
        if ( ! $p) {
            show_404();
        }

        // 'toko' = ulasan seluruh produk toko ini, seperti tab kedua di
        // aplikasi marketplace pada umumnya.
        $tab = ($this->input->get('tab') === 'toko') ? 'toko' : 'produk';
        $id  = ($tab === 'toko') ? $p['store_id'] : $p['id'];

        $opsi = array(
            'rating' => (int) $this->input->get('bintang') ?: NULL,
            'media'  => $this->input->get('media') === '1',
            'limit'  => 10,
        );

        $hal = max(1, (int) $this->input->get('page'));
        $opsi['offset'] = ($hal - 1) * $opsi['limit'];

        $total = $this->review_model->hitung($tab, $id, $opsi);

        $data = array(
            'p'         => $p,
            'tab'       => $tab,
            'ringkasan' => $this->review_model->ringkasan($tab, $id),
            'ulasan'    => $this->review_model->daftar($tab, $id, $opsi),
            'media'     => $this->review_model->media($tab, $id, 6),
            'filter'    => $opsi,
            'total'     => $total,
            'hal'       => $hal,
            'hal_total' => max(1, (int) ceil($total / $opsi['limit'])),
            'pages'     => 'v_ulasan',
        );

        $this->load->view('index', $data);
    }

    /* =====================================================================
       MENULIS
       ===================================================================== */

    public function tulis($nomor = NULL, $token = NULL)
    {
        $order = $this->pesanan($nomor, $token);

        $data = array(
            'order'  => $order,
            'items'  => $this->review_model->bisa_diulas($order['id']),
            'sudah'  => $this->review_model->milik_pesanan($order['id']),
            'pages'  => 'v_ulasan_tulis',
        );

        $this->load->view('index', $data);
    }

    public function kirim($nomor = NULL, $token = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $order = $this->pesanan($nomor, $token);
        $this->load->library('auth_lib');

        $hasil = $this->review_model->simpan(
            $order,
            $this->input->post('item_id'),
            $this->input->post('rating'),
            $this->input->post('isi', TRUE),
            $this->input->post('nama', TRUE) ?: $order['customer_name'],
            $this->auth_lib->id()
        );

        if ( ! $hasil['ok']) {
            $this->session->set_flashdata('error', $hasil['pesan']);
            return redirect('ulasan/tulis/' . $nomor . '/' . $token);
        }

        $jml = $this->simpan_media($hasil['id']);

        $this->session->set_flashdata('sukses',
            $hasil['pesan'] . ($jml ? ' ' . $jml . ' media ikut terkirim.' : ''));

        return redirect('ulasan/tulis/' . $nomor . '/' . $token);
    }

    /* =====================================================================
       BANTU
       ===================================================================== */

    /**
     * Pesanan yang cocok nomor DAN tokennya, atau 404.
     *
     * Token-nya yang membuktikan hak - jadi pemesan tamu pun bisa menulis
     * ulasan lewat tautan pesanannya, tanpa harus membuat akun.
     */
    protected function pesanan($nomor, $token)
    {
        $o = $this->db->where('order_number', $nomor)
                      ->where('access_token', $token)
                      ->get('orders')->row_array();

        if ( ! $o) {
            show_404();
        }
        return $o;
    }

    /**
     * Simpan foto & video ulasan.
     *
     * Jenis berkas ditentukan dari ISI berkasnya, bukan dari nama atau
     * header yang dikirim browser - keduanya bisa dipalsukan, dan berkas
     * .php yang diberi nama .jpg akan tersimpan di folder yang dilayani
     * web server.
     */
    protected function simpan_media($review_id)
    {
        if (empty($_FILES['media']['name'][0])) {
            return 0;
        }

        $folder = FCPATH . 'upload/ulasan';
        if ( ! is_dir($folder)) {
            @mkdir($folder, 0755, TRUE);
        }

        $gambar = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp');
        $video  = array('video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mp4');

        $n_foto = $n_video = $simpan = 0;

        foreach ($_FILES['media']['name'] as $i => $nama_asli) {
            if ($_FILES['media']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $tmp   = $_FILES['media']['tmp_name'][$i];
            $besar = (int) $_FILES['media']['size'][$i];

            $info = @getimagesize($tmp);

            if ($info !== FALSE && isset($gambar[$info[2]])) {
                if ($n_foto >= self::MAKS_FOTO || $besar > self::UKURAN_FOTO) {
                    continue;
                }
                $ext  = $gambar[$info[2]];
                $tipe = 'foto';
                $n_foto++;
            } else {
                $mime = function_exists('mime_content_type') ? mime_content_type($tmp) : '';
                if ( ! isset($video[$mime]) || $n_video >= self::MAKS_VIDEO || $besar > self::UKURAN_VIDEO) {
                    continue;
                }
                $ext  = $video[$mime];
                $tipe = 'video';
                $n_video++;
            }

            $nama = bin2hex(random_bytes(16)) . '.' . $ext;

            if ( ! move_uploaded_file($tmp, $folder . DIRECTORY_SEPARATOR . $nama)) {
                continue;
            }
            @chmod($folder . DIRECTORY_SEPARATOR . $nama, 0644);

            // Foto diseragamkan ukurannya; video dibiarkan apa adanya.
            if ($tipe === 'foto') {
                rapikan_gambar($folder . DIRECTORY_SEPARATOR . $nama, 1000);
            }

            $this->review_model->tambah_media($review_id, $nama, $tipe, $simpan);
            $simpan++;
        }

        return $simpan;
    }
}
