<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## Analisis Keamanan: Apakah Validasi `numeric` Cukup Mencegah Manipulasi Total Transaksi?

### **Pertanyaan:**
*Kalau field `total` tetap dikirim dari form HTML dan divalidasi dengan aturan `numeric`, apakah itu cukup mencegah manipulasi total? Kenapa atau kenapa tidak?*

### **Jawaban:**
**TIDAK CUKUP (SANGAT TIDAK AMAN).**

### **Alasan dan Analisis Keamanan:**

1. **Rule `numeric` Hanya Memvalidasi Tipe Data, Bukan Kebenaran Nilai (Business Logic Verification):**
   - Aturan validasi `'total' => 'required|numeric'` di Laravel hanya bertugas memastikan bahwa input yang diterima bertipe angka (misalnya: `100`, `1000`, atau `0.5`).
   - Aturan ini **TIDAK** mengecek apakah nilai angka yang dikirimkan cocok/sesuai dengan akumulasi perkalian harga resmi produk di database (`harga * qty`).

2. **Potensi Manipulasi Sisi Klien (Client-Side Tampering / Parameter Tampering):**
   - Input HTML apa pun yang dikirim dari browser (termasuk `<input type="hidden" name="total">`) berada di bawah kendali penuh pengguna.
   - Pembeli/penyerang dapat dengan mudah mengubah nilai `total` sebelum dikirim ke server melalui:
     - *Inspect Element* / Browser Developer Tools pada HTML form.
     - HTTP Interceptor seperti Burp Suite, OWASP ZAP, atau via cURL / Postman.
   - **Skenario Serangan:**
     - Pengguna membeli barang dengan total harga asli **Rp 1.000.000**.
     - Penyerang mengubah nilai `total` pada form/request menjadi **`1000`** (Rp 1.000).
     - Karena angka `1000` adalah nilai numerik yang sah, validasi `numeric` akan **LOLOS**, dan sistem akan menyimpan transaksi Rp 1.000 untuk barang seharga Rp 1.000.000. Ini mengakibatkan kerugian finansial pada sistem POS.

3. **Prinsip Keamanan Dasar Web (*Never Trust Client Input*):**
   - Aturan emas dalam pengembangan aplikasi web adalah **jangan pernah mempercayai kalkulasi harga dari input klien**.
   - Solusi yang tepat dan aman (seperti yang telah diterapkan pada `TransactionController@store`):
     - Server **TIDAK BOLEH** menerima input `total` dari form HTML klien.
     - Server **WAJIB** menghitung ulang total secara mandiri di backend dengan mengambil harga terpercaya langsung dari tabel `products` di database:
       ```php
       $product = Product::findOrFail($item['product_id']);
       $subtotal = $product->price * $item['qty'];
       $total += $subtotal;
       ```

---

## Skenario Uji Manual RBAC & Hak Akses (Increment 7)

Berikut adalah skenario pengujian manual untuk setiap peran pada aplikasi Simple POS:

### 1. Tamu / Guest (Belum Login)
* **Langkah Uji:**
  1. Akses halaman `/info` di browser saat belum login.
  2. Coba akses halaman terproteksi seperti `/pos` atau `/products` langsung dari address bar.
  3. Login terlebih dahulu, kemudian coba akses kembali halaman `/info` atau `/login`.
* **Hasil yang Diharapkan:**
  1. Halaman `/info` dapat dibuka dengan normal bagi tamu/pengunjung.
  2. Akses ke `/pos` atau `/products` tanpa login akan otomatis dialihkan (*redirect*) ke halaman `/login`.
  3. Pengguna yang sudah login akan otomatis dialihkan ke halaman `/pos` jika mencoba membuka `/info` atau `/login`.

### 2. Peran: Kasir (`kasir@pos.test`)
* **Langkah Uji:**
  1. Login dengan kredensial `kasir@pos.test` dan kata sandi `password`.
  2. Buka halaman `/pos` dan `/transactions`.
  3. Coba buka halaman kelola produk (`/products`) atau kategori (`/categories`) langsung dari address bar.
* **Hasil yang Diharapkan:**
  1. Halaman `/pos` dan `/transactions` tampil dengan normal. Menu Produk dan Kategori disembunyikan dari navigasi atas.
  2. Saat membuka `/products` atau `/categories`, sistem menolak akses dengan menampilkan halaman **Error 403 Forbidden** ("Anda tidak memiliki akses untuk halaman ini.").

### 3. Peran: Manager (`manager@pos.test`)
* **Langkah Uji:**
  1. Login dengan akun berperan `manager`.
  2. Akses halaman `/transactions` untuk melihat riwayat transaksi.
  3. Coba akses halaman kelola produk (`/products`).
* **Hasil yang Diharapkan:**
  1. Halaman `/transactions` dapat dibuka dan diakses dengan normal.
  2. Akses ke halaman `/products` ditolak dengan response **Error 403 Forbidden**.

### 4. Peran: Admin (`admin@pos.test`)
* **Langkah Uji:**
  1. Login dengan kredensial `admin@pos.test` dan kata sandi `password`.
  2. Periksa menu navigasi di bagian atas.
  3. Akses halaman `/products` dan `/categories`.
* **Hasil yang Diharapkan:**
  1. Menu navigasi menampilkan secara lengkap menu Kasir, Transaksi, Produk, dan Kategori.
  2. Semua halaman (`/pos`, `/transactions`, `/products`, `/categories`) dapat dibuka dan dikelola dengan normal tanpa hambatan.


