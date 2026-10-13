<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sedang offline &middot; <?= html_escape($brand) ?></title>
<meta name="theme-color" content="#22466C">
<style>
  body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
         background: #F2F7FA; color: #22466C; text-align: center; padding: 24px; box-sizing: border-box; }
  .kotak { max-width: 380px; background: #fff; border-radius: 22px; padding: 32px 24px; border: 1px solid #DCE7EE; }
  h1 { font-size: 24px; margin: 12px 0 8px; } p { color: #3E566B; line-height: 1.6; }
  button { margin-top: 12px; background: #22466C; color: #fff; border: 0; border-radius: 14px; padding: 14px 22px; font-size: 17px; font-weight: 700; }
</style></head>
<body><div class="kotak">
  <div style="font-size:48px">📶</div>
  <h1>Sinyal sedang hilang</h1>
  <p>Halaman yang pernah kamu buka tetap bisa dilihat. Pesanan dan pembayaran perlu internet &mdash; coba lagi begitu sinyal kembali.</p>
  <button type="button" onclick="location.reload()">Coba lagi</button>
</div></body></html>
