# 📧 Email Notification System

Sistem notifikasi email yang modern dan aman dengan antarmuka yang menarik. Dibangun dengan HTML, CSS, JavaScript, dan PHP menggunakan PHPMailer untuk pengiriman email melalui SMTP.

## ✨ Fitur

- 🎨 **Desain Modern** - Antarmuka yang menarik dengan tema email yang konsisten
- 📱 **Responsive Design** - Tampilan optimal di desktop dan mobile
- 🔔 **Custom Notifications** - Toast notification yang elegan dengan animasi
- 🛡️ **Rate Limiting** - Proteksi anti-spam (maksimal 3x pengiriman per jam per email)
- 🔒 **Secure Configuration** - Kredensial SMTP tersimpan terpisah dan aman
- ✅ **Form Validation** - Validasi email di client dan server
- 🎯 **User Experience** - Loading state, disabled button, dan feedback yang jelas
- 📊 **Error Handling** - Penanganan error yang komprehensif

## 🚀 Instalasi

### Persyaratan

- PHP 7.4 atau lebih tinggi
- Web server (Apache/Nginx) atau PHP built-in server
- Akses ke SMTP server (contoh: Hostinger, Gmail, dll)
- PHPMailer (sudah termasuk dalam proyek)

### Langkah Instalasi

1. **Clone atau download proyek ini**
   ```bash
   git clone <repository-url>
   cd email_notification
   ```

2. **Buat file konfigurasi**
   ```bash
   cp config/config.example.php config/config.php
   ```

3. **Edit file konfigurasi**
   Buka `config/config.php` dan isi dengan kredensial SMTP Anda:
   ```php
   return [
       'smtp' => [
           'host'       => 'smtp. ... .com',
           'port'       => 465,
           'secure'     => 'ssl',
           'auth'       => true,
           'username'   => 'your-email@domain.com',
           'password'   => 'your-password',
           'from_email' => 'your-email@domain.com',
           'from_name'  => 'Admin Web Notifikasi',
       ],
   ];
   ```

4. **Pastikan direktori logs dapat ditulis**
   ```bash
   chmod 755 logs/
   ```

5. **Jalankan aplikasi**
   
   **Menggunakan PHP built-in server:**
   ```bash
   php -S localhost:8000
   ```
   
   **Atau menggunakan web server (Apache/Nginx)**
   - Letakkan file di direktori web server
   - Akses melalui browser

## 📁 Struktur Folder

```
email_notification/
├── config/
│   ├── config.php          # Konfigurasi SMTP (JANGAN commit ke git)
│   └── config.example.php  # Template konfigurasi
├── css/
│   └── styles.css          # Styling aplikasi
├── email/
│   ├── kirim_email.php     # Handler pengiriman email
│   └── rate_limit.php      # Sistem rate limiting
├── js/
│   └── main.js             # JavaScript untuk form handling
├── logs/
│   └── email_log.json      # Log pengiriman email (auto-generated)
├── vendor/
│   └── phpmailer/          # Library PHPMailer
├── index.html              # Halaman utama
├── .gitignore              # File yang diabaikan git
└── README.md               # Dokumentasi ini
```

## ⚙️ Konfigurasi

### Konfigurasi SMTP

Edit file `config/config.php` dengan kredensial SMTP Anda:

| Parameter | Deskripsi | Contoh |
|-----------|-----------|--------|
| `host` | Alamat SMTP server | `smtp. ... .com` |
| `port` | Port SMTP | `465` (SSL) atau `587` (TLS) |
| `secure` | Tipe enkripsi | `ssl` atau `tls` |
| `username` | Email SMTP | `your-email@domain.com` |
| `password` | Password email | `your-password` |
| `from_email` | Email pengirim | `your-email@domain.com` |
| `from_name` | Nama pengirim | `Admin Web Notifikasi` |

```

## 🎯 Cara Penggunaan

1. **Buka halaman aplikasi** di browser
2. **Masukkan alamat email** yang akan menerima notifikasi
3. **Klik tombol "Kirim"**
4. **Tunggu proses pengiriman** (tombol akan disabled selama proses)
5. **Lihat notifikasi** sukses atau error

### Rate Limiting

Sistem membatasi pengiriman email:
- **Maksimal 3x pengiriman** per email per jam
- Jika melebihi batas, akan muncul pesan error dengan waktu tersisa
- Log pengiriman tersimpan di `logs/email_log.json`

## 🔧 Troubleshooting

### Email tidak terkirim

1. **Cek konfigurasi SMTP**
   - Pastikan host, port, username, dan password benar
   - Pastikan enkripsi sesuai (SSL/TLS)

2. **Aktifkan debug mode**
   Edit `config/config.php`:
   ```php
   'options' => [
       'debug' => 2, // 0-4, semakin tinggi semakin detail
   ]
   ```

3. **Cek log error PHP**
   - Lihat error log web server
   - Pastikan `display_errors` aktif untuk debugging

### Error "File PHPMailer tidak ditemukan"

- Pastikan folder `vendor/phpmailer/` ada
- Pastikan file `Exception.php`, `PHPMailer.php`, dan `SMTP.php` ada

### Error "File konfigurasi tidak ditemukan"

- Pastikan file `config/config.php` sudah dibuat dari `config.example.php`
- Pastikan file memiliki permission yang benar

### Rate limit tidak bekerja

- Pastikan folder `logs/` dapat ditulis (chmod 755)
- Cek file `logs/email_log.json` apakah dapat dibuat/ditulis

## 🛡️ Keamanan

### Best Practices yang Diterapkan

1. **Kredensial Terpisah**
   - File `config/config.php` tidak di-commit ke git
   - Sudah ada di `.gitignore`

2. **Input Validation**
   - Validasi email format di client dan server
   - Sanitasi input sebelum diproses

3. **Rate Limiting**
   - Mencegah spam dengan batasan pengiriman
   - Log pengiriman untuk monitoring

4. **Error Handling**
   - Tidak menampilkan error detail ke user
   - Error log untuk debugging

5. **Output Buffering**
   - Mencegah output tidak diinginkan sebelum JSON response

## 📝 Lisensi

Proyek ini bebas digunakan untuk keperluan pribadi atau komersial.

## 👨‍💻 Teknologi yang Digunakan

- **HTML5** - Struktur halaman
- **CSS3** - Styling dengan CSS Variables
- **JavaScript (ES6+)** - Interaktivitas dan AJAX
- **PHP 7.4+** - Backend processing
- **PHPMailer** - Library pengiriman email

## 🔄 Update & Maintenance

### Membersihkan Log Email

Log email otomatis dibersihkan setelah 1 jam, namun jika ingin membersihkan manual:

```bash
# Hapus file log
rm logs/email_log.json

# Atau kosongkan isinya
echo '{}' > logs/email_log.json
```

### Update PHPMailer

Jika ingin update PHPMailer ke versi terbaru, ganti folder `vendor/phpmailer/` dengan versi baru.

## 📞 Support

Jika mengalami masalah atau memiliki pertanyaan:
1. Cek bagian Troubleshooting di atas
2. Pastikan semua konfigurasi sudah benar
3. Cek log error untuk detail error

## 🎨 Customization

### Mengubah Tema Warna

Edit file `css/styles.css`, bagian CSS Variables:

```css
:root {
    --color-primary: #667eea;      /* Warna utama */
    --color-primary-dark: #764ba2;  /* Warna gelap */
    --color-accent: #f093fb;       /* Warna aksen */
    /* ... */
}
```

### Mengubah Pesan Email

Edit file `email/kirim_email.php`, bagian `$body_email`:

```php
$body_email = "
    <!DOCTYPE html>
    <html>
    <!-- Custom HTML email template -->
    </html>
";
```

## 📊 Fitur Rate Limiting

Sistem rate limiting menggunakan file JSON untuk menyimpan log pengiriman. Setiap email memiliki array timestamp pengiriman yang digunakan untuk menghitung batas.

**Konfigurasi Rate Limit:**
- File: `email/kirim_email.php`
- Fungsi: `checkRateLimit($email, 3, 3600)`
- Parameter: email, maksimal percobaan, window waktu (detik)

---

**Dibuat dengan ❤️ untuk memudahkan pengiriman notifikasi email**

