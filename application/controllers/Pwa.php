<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Pwa - manifest aplikasi & halaman offline.
 *
 *   GET manifest.webmanifest   (routes.php)
 *   GET offline                halaman yang tampil saat sinyal hilang
 *
 * Service worker-nya berkas statis /sw.js di akar situs - harus di akar
 * supaya cakupannya seluruh situs.
 */
class Pwa extends CI_Controller
{
    public function manifest()
    {
        $nama = $this->config->item('nama_brand') ?: 'Sapa UMKM';
        $this->output->set_content_type('application/manifest+json')
            ->set_header('Cache-Control: public, max-age=3600')
            ->set_output(json_encode(array(
                'name'             => $nama,
                'short_name'       => mb_substr($nama, 0, 12),
                'description'      => $this->config->item('slogan'),
                'lang'             => 'id',
                'start_url'        => site_url('?sumber=pwa'),
                'scope'            => base_url(),
                'display'          => 'standalone',
                'background_color' => '#FFFFFF',
                'theme_color'      => '#22466C',
                'icons'            => array(
                    array('src' => base_url('assets/images/pwa-192.png'), 'sizes' => '192x192', 'type' => 'image/png'),
                    array('src' => base_url('assets/images/pwa-512.png'), 'sizes' => '512x512', 'type' => 'image/png'),
                    array('src' => base_url('assets/images/pwa-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'),
                ),
                'shortcuts'        => array(
                    array('name' => 'Katalog', 'url' => site_url('shop')),
                    array('name' => 'Pesanan saya', 'url' => site_url('akun/pesanan')),
                    array('name' => 'Toko saya', 'url' => site_url('seller')),
                ),
            ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public function offline()
    {
        $this->load->view('v_offline', array('brand' => $this->config->item('nama_brand') ?: 'Sapa UMKM'));
    }
}
