-- =============================================================================
--  34_chat_percakapan.sql
--
--  Memulihkan pesan chat yang tersimpan tanpa wadah percakapan.
--
--  Sebelum perbaikan Chat_model::kirim(), pesan yang dikirim pembeli dari
--  halaman pesanannya (dan catatan sistem seperti "Pesanan dikirim") hanya
--  diberi order_id, tanpa conversation_id. Kotak masuk penjual (menu Pesan)
--  membaca lewat conversation_id, jadi pesan-pesan itu tidak pernah muncul
--  di sana. Berkas ini membuatkan wadahnya bila belum ada, lalu menautkan
--  pesan-pesan itu.
--
--  Tidak ada pesan yang dihapus atau diubah isinya. Aman dijalankan berulang.
--  CADANGKAN DULU.
-- =============================================================================

SET NAMES utf8mb4;

-- Wadah untuk pesanan yang punya pesan tapi belum punya percakapan.
-- IGNORE: indeks unik conversations.order_id menolak duplikat dengan diam.
INSERT IGNORE INTO conversations (store_id, user_id, order_id, created_at, last_message_at)
SELECT o.store_id, o.user_id, o.id, MIN(m.created_at), MAX(m.created_at)
  FROM order_messages m
  JOIN orders o ON o.id = m.order_id
 WHERE m.conversation_id IS NULL
   AND o.store_id IS NOT NULL
 GROUP BY o.id, o.store_id, o.user_id;

-- Tautkan pesan ke wadah pesanannya.
UPDATE order_messages m
  JOIN conversations c ON c.order_id = m.order_id
   SET m.conversation_id = c.id
 WHERE m.conversation_id IS NULL;

-- Urutan kotak masuk memakai waktu pesan terakhir.
UPDATE conversations c
  JOIN (SELECT conversation_id, MAX(created_at) AS akhir
          FROM order_messages
         WHERE conversation_id IS NOT NULL
         GROUP BY conversation_id) x ON x.conversation_id = c.id
   SET c.last_message_at = x.akhir
 WHERE c.last_message_at IS NULL OR c.last_message_at < x.akhir;
