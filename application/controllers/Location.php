<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Location - menyimpan & menghapus pilihan wilayah pengunjung.
 * Simpan di application/controllers/Location.php
 *
 *   POST location/set     district_id
 *   GET  location/clear
 */
class Location extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('location_lib', 'session'));
        $this->load->helper('url');
    }

    public function set()
    {
        $ok = $this->location_lib->set($this->input->post('district_id'));

        if ($this->input->is_ajax_request()) {
            return $this
                ->output
                ->set_status_header($ok ? 200 : 422)
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'ok' => $ok,
                    'label' => $ok ? $this->location_lib->label() : NULL,
                    'message' => $ok ? NULL : 'Kecamatan tidak ditemukan.',
                    'csrf_name' => $this->security->get_csrf_token_name(),
                    'csrf_hash' => $this->security->get_csrf_hash(),
                )));
        }

        if (!$ok) {
            $this->session->set_flashdata('error', 'Kecamatan tidak ditemukan.');
        }
        return redirect($this->input->post('kembali') ?: 'shop');
    }

    public function clear()
    {
        $this->location_lib->clear();
        return redirect('shop');
    }

    public function titik()
    {
        $lat = $this->input->post('lat');
        $lng = $this->input->post('lng');

        // Titik di luar Indonesia hampir pasti salah - biasanya karena
        // lat dan lng tertukar saat dikirim.
        if (!is_numeric($lat) ||
                !is_numeric($lng) ||
                $lat < -11 ||
                $lat > 6 ||
                $lng < 95 ||
                $lng > 141) {
            return $this
                ->output
                ->set_status_header(422)
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'ok' => FALSE,
                    'pesan' => 'Lokasi tidak terbaca dengan benar.',
                )));
        }

        /* Titik ini adalah TUJUAN PENGIRIMAN, bukan sekadar posisi pembeli.
           Keduanya sering sama (beli untuk diri sendiri), tapi tidak selalu:
           kado ke kota lain dikirim ke sana, dan toko dekat tujuan yang
           lebih masuk akal - bukan toko dekat pembelinya. */
        $this->session->set_userdata('dekat', array(
            'lat'   => (float) $lat,
            'lng'   => (float) $lng,
            'label' => 'Lokasi saya',
            'dari'  => 'gps',
        ));

        /* Titik GPS sekaligus mengisi "Kirim ke": titik diterjemahkan ke
           kecamatan lewat OpenStreetMap, lalu dicocokkan dengan tabel
           wilayah. Gagal (tanpa internet, wilayah tak dikenal) tidak apa-
           apa - pengurutan toko terdekat tetap jalan dari koordinatnya. */
        /* Nama wilayah dari browser (gps.js menerjemahkan titik lewat
           Nominatim langsung dari browser) dipakai lebih dulu: server di
           Windows/XAMPP sering tidak bisa memanggil HTTPS karena sertifikat
           CA PHP belum diatur. Nama itu hanya dicocokkan ke tabel wilayah,
           jadi tidak ada yang bisa disusupkan lewat situ. */
        $pisah = function ($v) {
            $out = array();
            foreach (explode('|', (string) $v) as $x) {
                $x = trim($x);
                if ($x !== '') {
                    $out[] = mb_substr($x, 0, 100);
                }
            }
            return array_slice($out, 0, 6);
        };
        $wilayah = NULL;
        if (trim((string) $this->input->post('kecamatan', TRUE)) !== '' || trim((string) $this->input->post('kabupaten', TRUE)) !== '') {
            $this->load->model('region_model');
            $wilayah = $this->region_model->cocokkan(
                $pisah($this->input->post('provinsi', TRUE)),
                $pisah($this->input->post('kabupaten', TRUE)),
                $pisah($this->input->post('kecamatan', TRUE))
            );
        }
        if ( ! $wilayah || ! $wilayah['district_id']) {
            $wilayah = $this->wilayah_dari_titik((float) $lat, (float) $lng);
        }
        if ($wilayah && $wilayah['district_id']) {
            $this->location_lib->set($wilayah['district_id']);
        }

        return $this
            ->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'ok' => TRUE,
                'label' => ($wilayah && $wilayah['district_id']) ? $this->location_lib->label() : NULL,
                'csrf_name' => $this->security->get_csrf_token_name(),
                'csrf_hash' => $this->security->get_csrf_hash(),
            )));
    }

    /**
     * Koordinat -> kecamatan di database, lewat reverse geocoding Nominatim
     * (OpenStreetMap). Hasilnya di-cache per ~100 m supaya tidak memanggil
     * Nominatim berulang (kebijakannya: maksimal 1 permintaan/detik).
     *
     * @return array|NULL hasil Region_model::cocokkan()
     */
    protected function wilayah_dari_titik($lat, $lng)
    {
        if ( ! function_exists('curl_init')) {
            return NULL;
        }
        $this->load->driver('cache', array('adapter' => 'file'));
        $kunci = 'wilayah_' . round($lat, 3) . '_' . round($lng, 3);
        $a = $this->cache->get($kunci);

        if ($a === FALSE) {
            $url = rtrim((string) ($this->config->item('nominatim_url') ?: 'https://nominatim.openstreetmap.org'), '/')
                 . '/reverse?format=jsonv2&zoom=14&addressdetails=1&accept-language=id'
                 . '&lat=' . rawurlencode((string) $lat) . '&lon=' . rawurlencode((string) $lng);
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT        => 5,
                // Nominatim menolak permintaan tanpa User-Agent yang jelas.
                CURLOPT_USERAGENT      => $this->config->item('nama_brand') . ' (' . base_url() . ')',
            ));
            $balas = curl_exec($ch);
            $kode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $galat = curl_error($ch);
            curl_close($ch);
            if ($balas === FALSE || $kode !== 200) {
                // Mis. "SSL certificate problem" = curl.cainfo di php.ini belum diisi.
                log_message('error', 'Location::titik Nominatim gagal: HTTP ' . $kode . ' ' . $galat);
            }

            $j = ($balas !== FALSE && $kode === 200) ? json_decode($balas, TRUE) : NULL;
            $a = (is_array($j) && ! empty($j['address'])) ? $j['address'] : array();
            $this->cache->save($kunci, $a, $a ? 2592000 : 600);   // 30 hari; gagal: 10 menit
        }
        if ( ! $a) {
            return NULL;
        }

        /* OSM menaruh tiap tingkat wilayah di kolom yang berbeda-beda
           tergantung daerahnya - semua kemungkinan dikirim sebagai kandidat,
           sama seperti peta alamat (assets/js/peta-alamat.js). */
        $ambil = function (array $kunci) use ($a) {
            $out = array();
            foreach ($kunci as $k) {
                if ( ! empty($a[$k])) {
                    $out[] = (string) $a[$k];
                }
            }
            return $out;
        };
        $this->load->model('region_model');
        return $this->region_model->cocokkan(
            $ambil(array('state', 'region')),
            $ambil(array('city', 'county', 'municipality', 'town', 'state_district')),
            $ambil(array('city_district', 'district', 'subdistrict', 'municipality', 'suburb', 'village'))
        );
    }

    /**
     * Pilih tujuan pengiriman dari buku alamat. POST location/tujuan/{id}
     *
     * Alamat tersimpan yang punya koordinat dipakai langsung. Yang belum
     * punya koordinat tetap diterima - kecamatannya saja sudah cukup untuk
     * menghitung ongkir, hanya pengurutan jaraknya yang tidak seakurat
     * titik peta.
     */
    public function tujuan($id = NULL)
    {
        $this->load->library('auth_lib');
        $uid = $this->auth_lib->id();

        if ( ! $uid) {
            show_404();
        }

        $this->load->model('address_model');
        $a = $this->address_model->milik($id, $uid);

        if ( ! $a) {
            show_404();
        }

        $this->session->set_userdata('dekat', array(
            'lat'         => $a['latitude'] !== NULL ? (float) $a['latitude'] : NULL,
            'lng'         => $a['longitude'] !== NULL ? (float) $a['longitude'] : NULL,
            'label'       => $a['label'],
            'dari'        => 'alamat',
            'alamat_id'   => (int) $a['id'],
            'district_id' => (int) $a['district_id'],
            'wilayah'     => trim($a['district_name'] . ', ' . $a['regency_name'], ', '),
        ));

        return redirect($this->input->post('balik') ?: 'shop');
    }

    /**
     * Hapus titik lokasi. GET location/lupakan_titik
     */
    public function lupakan_titik()
    {
        $this->session->unset_userdata('dekat');
        return redirect('shop');
    }
}
