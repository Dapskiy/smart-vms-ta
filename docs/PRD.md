# 📋 Product Requirements Document (PRD)

## Smart VMS — Visitor Management System

**Project:** Tugas Akhir — Smart Visitor Management System  
**Version:** 1.0.0  
**Last Updated:** 2026-07-23  
**Status:** In Development

---

## 1. Ringkasan Proyek

Smart VMS adalah sistem manajemen pengunjung berbasis web yang dirancang untuk mendigitalisasi proses penerimaan tamu di lingkungan perkantoran atau institusi. Sistem ini mengintegrasikan **AI chatbot**, **verifikasi wajah (face recognition)**, **kiosk self-service**, dan **admin dashboard** untuk menciptakan pengalaman kunjungan yang modern, aman, dan efisien.

### Problem Statement

Sistem penerimaan tamu konvensional masih mengandalkan buku tamu manual yang menimbulkan masalah:
- Tidak ada verifikasi identitas yang andal
- Data kunjungan sulit dilacak dan dianalisis
- Proses check-in/check-out lambat dan rentan antrian
- Tidak ada mekanisme persetujuan digital untuk walk-in visitor
- Tidak ada pencatatan kehadiran PIC (Person in Charge) secara otomatis

### Solusi

Smart VMS menyediakan platform terpadu yang mencakup:
- **Kiosk publik** dengan form walk-in dan face recognition check-in/check-out
- **Admin dashboard** (Filament v4) untuk pengelolaan tamu, PIC, ruang meeting, dan pelaporan
- **AI assistant** berbasis OpenAI GPT-4o-mini untuk membantu admin query data real-time
- **Alur approval via email** untuk kunjungan walk-in
- **Biometric verification** dengan face descriptor (Euclidean Distance)

---

## 2. Scope Project

### 2.1 In Scope (Termasuk)

| Area | Fitur |
|------|-------|
| **Visitor Management** | Registrasi visitor, data identitas, blacklist management |
| **Appointment System** | Penjadwalan kunjungan (appointment), walk-in registration, approval flow |
| **Kiosk Self-Service** | Form walk-in, face check-in, face check-out, QR check-in, returning visitor detection |
| **Face Recognition** | Registrasi wajah, verifikasi check-in/check-out via Euclidean Distance, encrypted face data |
| **PIC Management** | CRUD PIC, departemen, availability toggle, face-based attendance |
| **Room Management** | CRUD ruang meeting dengan lokasi dan deskripsi |
| **Dashboard & Analytics** | Statistik harian, tren kunjungan, chart tujuan kunjungan, akurasi biometrik |
| **AI Chatbot** | Admin AI assistant (OpenAI) dengan konteks real-time dari database |
| **RBAC** | Role-based access control dengan Spatie Permission + Filament Shield |
| **Email Notification** | Notifikasi approval/rejection ke PIC via email |
| **Data Export** | Export data ke Excel via Filament Excel |

### 2.2 Out of Scope (Tidak Termasuk — untuk saat ini)

- Mobile application (Android/iOS)
- Video surveillance / CCTV integration
- Multi-tenant / multi-building support
- Payment / billing system
- Visitor badge printing
- SMS notification
- Real-time WebSocket push notification (saat ini menggunakan polling)

---

## 3. MVP (Minimum Viable Product)

### Phase 1: Core Foundation ✅
- [x] Setup Laravel 12 + Filament v4 + PostgreSQL
- [x] User authentication dengan RBAC (super_admin, admin, security)
- [x] CRUD: Visitors, Appointments, PICs, Departments, Rooms
- [x] Dashboard dengan statistik overview (tamu hari ini, aktif, pending, selesai)

### Phase 2: Kiosk & Check-in/Check-out ✅
- [x] Kiosk walk-in form (Livewire) — registrasi tamu langsung
- [x] QR-based check-in (visitor menunjukkan QR code dari invitation)
- [x] Face registration saat pertama kali check-in
- [x] Face-based check-in dan check-out mandiri
- [x] Returning visitor detection (auto-fill data dari face recognition)
- [x] Kiosk security PIN protection

### Phase 3: Walk-in Approval Flow ✅
- [x] Walk-in appointment → kirim email approval ke PIC
- [x] PIC approve/reject via link email (tanpa login)
- [x] Kiosk real-time polling status approval
- [x] Standarisasi status enum: pending → active → completed/rejected/cancelled

### Phase 4: AI & Analytics ✅
- [x] Admin AI chatbot (OpenAI GPT-4o-mini)
- [x] Intent detection — query PIC, visitor, appointment, checkout, blacklist
- [x] Dashboard widgets: tren kunjungan, distribusi tujuan, latest guests
- [x] Face verification logs + akurasi biometrik metrics

### Phase 5: PIC Attendance & Biometric Logging ✅
- [x] PIC face-based attendance (check-in/check-out kehadiran PIC)
- [x] Face verification logs (Euclidean Distance, success/fail, IP address)
- [x] PIC availability auto-reset harian (00:00)
- [x] PIC current location tracking

---

## 4. Goals

### 4.1 Business Goals

| Goal | Metric Target |
|------|---------------|
| Digitalisasi proses penerimaan tamu | 100% kunjungan tercatat digital |
| Mempercepat proses check-in | Check-in < 60 detik (face) |
| Meningkatkan keamanan identifikasi | Akurasi face recognition ≥ 90% |
| Memberikan visibility kepada management | Dashboard real-time accessible |
| Membantu admin dengan AI | Admin bisa query data via natural language |

### 4.2 Technical Goals

- Sistem monolitik yang stabil (single Laravel application)
- Database relasional yang konsisten dengan proper constraints & indexes
- Enkripsi data biometrik at-rest (face_photo + face_features)
- Responsive UI yang bisa diakses dari kiosk touchscreen dan desktop browser
- Clean code dengan separation of concerns (Controllers, Services, Models, Policies)

### 4.3 User Experience Goals

- Kiosk interface yang intuitif untuk visitor tanpa training
- Admin dashboard yang informatif dan actionable
- Proses approval yang seamless (PIC cukup klik link di email)
- AI chatbot yang memahami konteks bahasa Indonesia dan Inggris

---

## 5. Technical Requirements

### 5.1 Runtime Environment

| Komponen | Requirement |
|----------|-------------|
| PHP | ≥ 8.2 |
| Database | PostgreSQL (primary) |
| Node.js | LTS (untuk Vite build) |
| Web Server | PHP built-in / Nginx / Apache |
| OS | Cross-platform (developed on Windows) |

### 5.2 Framework & Library

| Layer | Technology | Version |
|-------|-----------|---------|
| Backend Framework | Laravel | ^12.0 |
| Admin Panel | Filament | ^4.8.5 |
| Frontend Build | Vite | ^7.0.7 |
| CSS Framework | TailwindCSS | ^4.0.0 |
| Real-time Components | Livewire | (bundled in Filament v4) |
| RBAC | Spatie Permission | ^7.3 |
| Shield (Filament RBAC) | Filament Shield | ^4.2 |
| AI Integration | OpenAI API | GPT-4o-mini |
| Date Filter | Filament DateRangePicker | ^5.0 |
| Excel Export | Filament Excel (pxlrbt) | ^3.6 |

### 5.3 Security Requirements

- **Authentication**: Laravel built-in auth + Filament panel auth
- **Authorization**: Spatie Permission + Filament Shield + Policy classes
- **Biometric Encryption**: AES-256-CBC via Laravel `Crypt` facade (face_photo & face_features)
- **File Storage**: Encrypted face photos stored as `.enc` files in private `storage/app/private/`
- **Kiosk Security**: PIN-protected access + IP whitelist (`KIOSK_ALLOWED_IPS`)
- **Token Security**: Unique random tokens untuk appointment invitation & approval
- **CSRF Protection**: Laravel default middleware
- **Gate**: `super_admin` role bypasses all permission checks

### 5.4 Performance Requirements

- Composite database index pada `appointments(status, visit_date)` — query paling umum
- Foreign key indexes untuk PostgreSQL (tidak auto-index FK)
- Cache 2 menit pada dashboard statistik (`dashboard_guest_stats_*`)
- Face photo disimpan sebagai file terenkripsi (bukan blob di DB) untuk menghindari overhead

---

## 6. Success Metrics

### 6.1 Functional Metrics

| Metric | Target | Cara Ukur |
|--------|--------|-----------|
| Akurasi Face Recognition | ≥ 90% | `face_verification_logs.is_success` rate |
| Rata-rata Euclidean Distance (match) | ≤ 0.50 | `face_verification_logs.euclidean_distance` avg |
| Waktu Check-in (face) | < 60 detik | Timestamp delta (start scan → checkin_time) |
| Approval Response Time | < 10 menit | Delta `created_at` → `approved_at` pada walk-in |
| Data Completeness | 100% | Semua kunjungan memiliki visitor_id + visit_id |

### 6.2 Technical Metrics

| Metric | Target | Cara Ukur |
|--------|--------|-----------|
| Dashboard Load Time | < 2 detik | Browser DevTools / Lighthouse |
| Database Query Count (dashboard) | ≤ 5 queries | Laravel Debugbar / `DB::getQueryLog()` |
| Zero Data Leak | 0 incident | Biometric data selalu terenkripsi at-rest |
| Uptime Kiosk | ≥ 99% selama demo | Monitoring manual |
| Migration Rollback Safety | 100% reversible | Semua migration memiliki `down()` |

### 6.3 User Satisfaction Metrics

| Metric | Target | Cara Ukur |
|--------|--------|-----------|
| Visitor bisa self-check-in tanpa bantuan | ≥ 80% | Observasi saat UAT |
| Admin bisa query data via AI chatbot | ≥ 5 intent tercover | Test scenario saat demo |
| PIC bisa approve/reject dari email | 100% | End-to-end testing |

---

## 7. Stakeholders & Roles

| Role | Deskripsi | Akses |
|------|-----------|-------|
| **super_admin** | Full access — bypass semua permission | Filament Dashboard + semua CRUD |
| **admin** | Mengelola data tamu, PIC, ruangan, melihat report | Filament Dashboard (sesuai Shield permission) |
| **security** | Monitoring check-in/check-out, manual checkout | Filament Dashboard (limited) |
| **PIC** | Approve/reject walk-in via email, attendance | Link approval email + Kiosk attendance |
| **Visitor** | Self-service check-in/check-out | Kiosk interface + invitation link |

---

## 8. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|--------|--------|---------|
| Face recognition akurasi rendah | Visitor gagal check-in | Threshold adjustable (default 0.50), fallback manual check-in |
| OpenAI API downtime | AI chatbot tidak responsif | Graceful error handling, dashboard tetap fungsional tanpa AI |
| Data biometrik bocor | Pelanggaran privasi | Enkripsi AES-256 at-rest, file storage private, no plain-text |
| Kiosk diakses unauthorized | Manipulasi data | PIN protection + IP whitelist |
| Database corruption | Data hilang | Regular backup PostgreSQL, migration rollback |
