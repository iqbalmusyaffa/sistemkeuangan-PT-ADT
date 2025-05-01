# FinanceHub - Aplikasi Manajemen Keuangan

FinanceHub adalah aplikasi web berbasis Laravel yang dirancang untuk membantu pengguna dalam mengelola keuangan pribadi atau bisnis mereka. Aplikasi ini menyediakan fitur-fitur untuk melacak pendapatan, pengeluaran, dan memberikan analisis keuangan yang komprehensif.

## Fitur Utama

- Manajemen transaksi keuangan
- Kategorisasi pendapatan dan pengeluaran
- Laporan keuangan dan analisis
- Dashboard interaktif
- Manajemen anggaran
- Export data ke format Excel

## Persyaratan Sistem

- PHP >= 8.2
- Composer
- Node.js & NPM
- Database (MySQL/PostgreSQL/SQLite)
- Web Server (Apache/Nginx)

## 🛠️ Tech Stack & Dependencies

### 📦 Frontend
- **Vue.js 3.5** – JavaScript framework (Composition API)
- **CoreUI Vue** – UI components & layout
- **Vue Router 4.5** – Routing
- **Pinia** – State management
- **Tailwind CSS** – Utility-first CSS framework
- **Sass & PostCSS** – CSS preprocessing
- **Bootstrap 5.3 + Bootstrap Vue** – Additional UI styling
- **SweetAlert2** – Beautiful alerts and modals
- **Chart.js + CoreUI Chart.js** – Data visualization
- **Simplebar Vue** – Custom scrollbars
- **Day.js / Moment.js** – Date handling

### 📊 Table/Data Display
- **DataTables (Vue 3 integration)** – Interactive data tables
- `datatables.net`, `datatables.net-vue3`, `datatables.net-buttons-dt`, `datatables.net-responsive`

### 🔧 Build Tools & Utilities
- **Vite 6** – Next-gen frontend tooling
- **Laravel Vite Plugin** – For Laravel integration
- **ESLint + eslint-plugin-vue** – Linting and code quality
- **Autoprefixer & PostCSS** – CSS tooling
- **Concurrently** – Run multiple npm scripts simultaneously

### 🌐 Networking
- **Axios** – HTTP client for API communication

### 🗂️ Backend
- **Laravel 11** – Backend framework
- **Sanctum** – Authentication
- **MySQL** – Database

### Tools & Deployment
- 🧰 Git & GitHub
- 🧪 Postman (API Testing)
- 🐧 Linux/Ubuntu Server
- 🌐 Domain: `#`

## Instalasi

1. Clone repository:
```bash
git clone https://github.com/iqbalmusyaffa/sistemkeuangan-PT-ADT.git
cd codeta-finacehub
```

2. Install dependencies PHP:
```bash
composer install
```

3. Install dependencies JavaScript:
```bash
npm install
```

4. Salin file .env:
```bash
cp .env.example .env
```

5. Generate application key:
```bash
php artisan key:generate
```

6. Konfigurasi database di file .env:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=financehub
DB_USERNAME=root
DB_PASSWORD=
```

7. Jalankan migrasi database:
```bash
php artisan migrate
```

8. Jalankan seeder (opsional):
```bash
php artisan db:seed
```

9. Build assets:
```bash
npm run build
```

10. Jalankan aplikasi:
```bash
php artisan serve
```

Aplikasi akan berjalan di `http://localhost:8000`

## Pengembangan

Untuk mode pengembangan, jalankan:
```bash
npm run dev
php artisan serve
```

## Testing

Untuk menjalankan test:
```bash
php artisan test
```

## Kontribusi

Silakan buat pull request untuk kontribusi. Untuk perubahan besar, buka issue terlebih dahulu untuk mendiskusikan perubahan yang diinginkan.

## Lisensi

Aplikasi ini dilisensikan di bawah [MIT License](https://opensource.org/licenses/MIT).
