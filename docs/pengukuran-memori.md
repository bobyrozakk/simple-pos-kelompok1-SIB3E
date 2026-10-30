# Pengukuran Memori: get() vs lazyById()

**Dataset: sekitar 52.500 row pada tabel `transactions`.**

| Cara baca data            | Peak memory (MB) |
| ------------------------- | ---------------- |
| `Transaction::lazyById()` | 38,7             |
| `Transaction::get()`      | 97,1             |

**Kesimpulan kelompok:**

`get()` memuat seluruh row ke memori sekaligus, sehingga peak memory-nya mencapai 97,1 MB, sekitar 2,5 kali lipat dibanding `lazyById()` yang hanya 38,7 MB karena membaca data per chunk kecil. Selisih ini akan makin lebar seiring bertambahnya data, dan `get()` berisiko menabrak `memory_limit` PHP. Karena itu, untuk export data besar (seperti CSV) kami memakai `lazyById()`.
