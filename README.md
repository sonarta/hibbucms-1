# HibbuCMS

<div align="center">

![HibbuCMS Logo](/public/logo.png)

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Laravel](https://img.shields.io/badge/Laravel-12.0-FF2D20?logo=laravel)](https://laravel.com)
[![React](https://img.shields.io/badge/React-19.0-61DAFB?logo=react)](https://reactjs.org)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.7-3178C6?logo=typescript)](https://www.typescriptlang.org)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](http://makeapullrequest.com)

HibbuCMS is a modern and open-source Content Management System (CMS) built with Laravel 12 and React. It is designed to provide an intuitive, flexible, and powerful content management experience.

[Demo](https://cms.hibbuproject.com/demo) • [Dokumentasi](https://cms.hibbuproject.com/docs) • [Roadmap](https://github.com/Hibbu-Creative-Project/hibbucms/projects) • [Contribute](#-Contribute) 

</div>

## ✨ Highlights

- 🚀 **Modern Stack** - Laravel 12 + React + TypeScript
- 🎨 **Elegant UI/UX** - With Radix UI, Shadcn UI, and TailwindCSS
- 📱 **Fully Responsive** - Perfect display on all devices
- 🔒 **Secure by Default** - Best security practices
- 🌐 **SEO Friendly** - Built-in search engine optimization
- 🔌 **Extensible** - Modular plugin and theme system

## 🎯 Main Features

- 📝 **Content Management**
  - Post and Page Management with draft/publish system
  - WYSIWYG Editor with TinyMCE and TipTap
  - Categories and Tags for content organization
  - Media Manager for file and image management

- 🎨 **Theme System**
  - Flexible theme system
  - Support for multiple themes
  - Ability to upload and activate themes
  - Customizable layouts

- 👥 **User Management**
  - Role-Based Access Control (RBAC)
  - User authentication and authorization
  - Email verification
  - Permission management

[See all features in the documentation](https://cms.hibbuproject.com/docs/features)

## 🛠️ Tech Stack

- **Backend:**
  - Laravel 12
  - PHP 8.2+
  - MySQL/PostgreSQL

- **Frontend:**
  - React with TypeScript
  - Inertia.js
  - TailwindCSS
  - Shadcn UI Components
  - Radix UI Components

[Complete tech stack details](https://cms.hibbuproject.com/docs/tech-stack)

## 🚀 Quick Start

```bash
# Clone repository
git clone https://github.com/Hibbu-Creative-Project/hibbucms.git

# Install dependency
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Migrate database
php artisan migrate --seed

# Build & running
npm run build
php artisan serve
```

[Panduan instalasi lengkap](https://cms.hibbuproject.com/docs/installation)

## Production: task scheduler (scheduled posts)

HibbuCMS mendaftarkan perintah `posts:publish-scheduled` di [`routes/console.php`](routes/console.php) agar post berstatus **scheduled** otomatis menjadi **published** pada waktu `published_at`. Laravel hanya menjalankan jadwal ini jika **cron** di server memanggil scheduler.

**Wajib di production:** tambahkan entri cron (sesuaikan path project dan user):

```bash
* * * * * cd /path/to/hibbucms-1 && php artisan schedule:run >> /dev/null 2>&1
```

Tanpa baris di atas, post terjadwal tidak akan pernah terbit sampai Anda menjalankan `php artisan posts:publish-scheduled` secara manual. Lihat juga [Laravel — task scheduling](https://laravel.com/docs/scheduling).

**Lokal:** saat development, jalankan `php artisan schedule:work` di terminal terpisah (atau cron di WSL) agar jadwal diuji tanpa deploy.

## 📷 Media: image variants & S3 / CDN

- **Varian gambar:** untuk gambar raster (bukan SVG/GIF), setelah unggah HibbuCMS membuat preset `thumb`, `medium`, dan `large` (lebar maks. 300 / 768 / 1920 px) memakai [Intervention Image](https://image.intervention.io/). Metadata disimpan di kolom `variants`; URL lengkap tersedia di `variant_urls` pada API media admin. Matikan dengan `MEDIA_IMAGE_VARIANTS=false` atau sesuaikan preset di `config/media.php`.
- **Disk penyimpanan:** set `MEDIA_DISK=public` (default, `storage/app/public` + symlink `public/storage`) atau `MEDIA_DISK=s3` setelah mengisi `AWS_*` di `.env` dan memastikan bucket serta IAM sudah benar.
- **CDN / URL publik:** untuk disk `public`, set `MEDIA_URL` ke basis URL aset Anda (mis. `https://cdn.example.com/storage`) agar `Storage::url()` mengarah ke CDN. Untuk S3, gunakan `AWS_URL` (mis. CloudFront) sesuai [dokumentasi Laravel filesystem](https://laravel.com/docs/filesystem).

## 🤝 Contribute

We greatly appreciate contributions from the community! HibbuCMS is an open-source project, and we welcome contributions in various forms:

- 🐛 Reporting bugs
- 💡 Suggesting new features
- 📝 Improving documentation
- 💻 Submitting pull requests

Before contributing, please read our [Contribution Guidelines](CONTRIBUTING.md).

### Contributors

<a href="https://github.com/Hibbu-Creative-Project/hibbucms/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=Hibbu-Creative-Project/hibbucms" />
</a>

## 📊 Roadmap

See our [Project Board](https://github.com/Hibbu-Creative-Project/hibbucms/projects) for upcoming development plans.

## 📜 License

HibbuCMS is licensed under the [MIT License](LICENSE).

## 💬 Community

- [GitHub Discussions](https://github.com/Hibbu-Creative-Project/hibbucms/discussions)

## 🌟 Sponsors

If you like HibbuCMS and want to support its development:

[![Sponsor on GitHub](https://img.shields.io/badge/Sponsor-GitHub-ea4aaa?logo=github-sponsors)](https://github.com/sponsors/Hibbu-Creative-Project)
[![Sponsor on OpenCollective](https://img.shields.io/badge/Sponsor-OpenCollective-7FADF2?logo=open-collective)](https://opencollective.com/hibbucms)

## 🙏 Special Thanks

- [Laravel](https://laravel.com)
- [React](https://reactjs.org)
- [TailwindCSS](https://tailwindcss.com)
- [Shadcn UI](https://ui.shadcn.com/)
- [Radix UI](https://www.radix-ui.com)
- [And all contributors](https://github.com/Hibbu-Creative-Project/hibbucms/graphs/contributors)
