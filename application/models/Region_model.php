<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Region_model - simpan di application/models/Region_model.php
 */
class Region_model extends CI_Model {

    public function provinces()
    {
        return $this->db->order_by('name', 'ASC')->get('provinces')->result_array();
    }

    public function regencies($province_id)
    {
        return $this->db->where('province_id', (int) $province_id)
                        ->order_by('name', 'ASC')
                        ->get('regencies')->result_array();
    }

    public function districts($regency_id)
    {
        return $this->db->where('regency_id', (int) $regency_id)
                        ->order_by('name', 'ASC')
                        ->get('districts')->result_array();
    }

    /**
     * Satu kecamatan lengkap dengan kabupaten dan provinsinya.
     * Dipakai untuk memvalidasi id yang datang dari cookie atau form -
     * sekaligus jadi bukti bahwa kecamatan itu memang ada.
     */
    public function district_full($district_id)
    {
        $row = $this->db
            ->select('d.id AS district_id, d.name AS district_name,
                      r.id AS regency_id,  r.name AS regency_name,
                      p.id AS province_id, p.name AS province_name', FALSE)
            ->from('districts d')
            ->join('regencies r', 'r.id = d.regency_id')
            ->join('provinces p', 'p.id = r.province_id')
            ->where('d.id', (int) $district_id)
            ->get()->row_array();

        return $row ?: NULL;
    }

    /** Cari kecamatan lintas wilayah, untuk kolom pencarian cepat di modal. */
    public function search_districts($q, $limit = 15)
    {
        return $this->db
            ->select('d.id, d.name AS district_name, r.name AS regency_name, p.name AS province_name', FALSE)
            ->from('districts d')
            ->join('regencies r', 'r.id = d.regency_id')
            ->join('provinces p', 'p.id = r.province_id')
            ->group_start()->like('d.name', $q)->or_like('r.name', $q)->group_end()
            ->order_by('d.name', 'ASC')
            ->limit((int) $limit)
            ->get()->result_array();
    }

    /* =====================================================================
       PENCOCOKAN NAMA WILAYAH DARI PETA

       OpenStreetMap mengembalikan nama, bukan id. Nama itu ditulis berbeda-
       beda: "Kota Batam" vs "Batam", "Kecamatan Lubuk Baja" vs "Lubuk Baja",
       "KAB. BINTAN". Fungsi di bawah menyamakan bentuknya dulu, baru
       dicocokkan ke daftar wilayah yang ada di database.
       ===================================================================== */

    /**
     * Samakan bentuk nama supaya bisa dibandingkan.
     * "KAB. Bintan" dan "Kabupaten  Bintan" sama-sama menjadi "bintan".
     */
    protected function samakan($nama)
    {
        $n = strtolower(trim((string) $nama));

        // Awalan jenis wilayah dibuang - OSM dan database tidak selalu sepakat
        // memakainya, dan yang membedakan wilayah adalah nama setelahnya.
        $n = preg_replace('/^(kabupaten administrasi|kota administrasi|kabupaten|kota|kab\.?|kec\.?|kecamatan|distrik|daerah)\s+/u', '', $n);

        // Tanda baca dan spasi ganda dibuang: "Batu  Aji," -> "batu aji"
        $n = preg_replace('/[^a-z0-9\s]/u', ' ', $n);
        $n = trim(preg_replace('/\s+/u', ' ', $n));

        return $n;
    }

    /** Cari satu baris yang namanya cocok, dari daftar kandidat. */
    protected function cari_cocok(array $baris, array $kandidat)
    {
        $rapi = array();
        foreach ($baris as $b) {
            $rapi[$this->samakan($b['name'])] = $b;
        }

        // Tahap 1: sama persis setelah disamakan bentuknya.
        foreach ($kandidat as $k) {
            $k = $this->samakan($k);
            if ($k !== '' && isset($rapi[$k])) {
                return $rapi[$k];
            }
        }

        /* Tahap 2: salah satu memuat yang lain - untuk kasus seperti
           "Batam Kota" (OSM) vs "Batamkota", atau nama OSM yang membawa
           embel-embel tambahan. Dibatasi minimal 4 huruf supaya potongan
           pendek seperti "kota" tidak cocok ke mana-mana. */
        foreach ($kandidat as $k) {
            $k = $this->samakan($k);
            if (strlen($k) < 4) {
                continue;
            }
            foreach ($rapi as $nama => $b) {
                if (strlen($nama) >= 4 && (strpos($nama, $k) !== FALSE || strpos($k, $nama) !== FALSE)) {
                    return $b;
                }
            }
        }

        return NULL;
    }

    /**
     * Cocokkan nama-nama dari peta ke id wilayah di database.
     *
     * Tiap tingkat menerima BEBERAPA kandidat, karena OSM menaruh kecamatan
     * di kolom yang berbeda-beda tergantung daerahnya (city_district,
     * suburb, municipality, village).
     *
     * Pencocokan dilakukan dari atas ke bawah dan dipersempit: kecamatan
     * hanya dicari di dalam kabupaten yang sudah ketemu. Tanpa itu,
     * kecamatan bernama sama di dua kabupaten bisa tertukar.
     *
     * @return array selengkap yang bisa ditemukan; kunci yang gagal bernilai NULL
     */
    public function cocokkan(array $provinsi, array $kabupaten, array $kecamatan)
    {
        $out = array(
            'province_id' => NULL, 'province_name' => NULL,
            'regency_id'  => NULL, 'regency_name'  => NULL,
            'district_id' => NULL, 'district_name' => NULL,
        );

        $p = $this->cari_cocok($this->provinces(), $provinsi);
        if ( ! $p) {
            return $out;
        }
        $out['province_id']   = (int) $p['id'];
        $out['province_name'] = $p['name'];

        $r = $this->cari_cocok($this->regencies($p['id']), $kabupaten);
        if ( ! $r) {
            return $out;
        }
        $out['regency_id']   = (int) $r['id'];
        $out['regency_name'] = $r['name'];

        $d = $this->cari_cocok($this->districts($r['id']), $kecamatan);
        if ($d) {
            $out['district_id']   = (int) $d['id'];
            $out['district_name'] = $d['name'];
        }

        return $out;
    }
}
