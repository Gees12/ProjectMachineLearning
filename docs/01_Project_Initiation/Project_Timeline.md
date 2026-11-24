# Project Timeline

> Proyek: DormInsight
> Dokumen: Fase 1 - Project Initiation
> Versi: 0.2 (Draft)
> Terakhir diperbarui: 23 November 2026

## 1. Asumsi Jadwal

- Metode: Agile/Scrum yang disederhanakan.
- Jumlah sprint: 6, masing-masing 2 minggu (total 12 minggu).
- Tanggal mulai: 9 November 2026.
- Periode Fase 1 (Project Initiation): 9 sampai 23 November 2026.
- Tanggal sprint berikutnya dan tanggal selesai proyek: [TBD], menyusul jadwal proyek penuh.
- Setiap sprint diakhiri sprint review yang menghasilkan deliverable yang dapat diperiksa.

## 2. Jadwal Sprint

| Sprint | Minggu | Tanggal | Fokus | Deliverable |
|---|---|---|---|---|
| Sprint 1 | 1 sampai 2 | 9 sampai 23 Nov 2026 | Project initiation | Project Charter, Problem Statement, Project Scope, Feasibility Study, Project Timeline, pengajuan izin survei |
| Sprint 2 | 3 sampai 4 | [TBD] | Requirements, system design, UI/UX | PRD, SRS, User Stories, Use Case, Architecture, ERD, UML, Data Dictionary, prototype Figma, instrumen survei final |
| Sprint 3 | 5 sampai 6 | [TBD] | Database, authentication, survey | Migrasi database, login dan role, modul survei; **survei mulai disebar** |
| Sprint 4 | 7 sampai 8 | [TBD] | ML development dan evaluation | Dataset Documentation, EDA, Experiment Log, Model Evaluation, Model Card |
| Sprint 5 | 9 sampai 10 | [TBD] | Dashboard dan ML integration | FastAPI ML service, integrasi Laravel, dashboard, laporan PDF/Excel |
| Sprint 6 | 11 sampai 12 | [TBD] | Testing, UAT, deployment | Test Report, UAT, Deployment Guide, User Manual, Release Notes |

## 3. Jalur Paralel

| Minggu | Jalur Web | Jalur ML |
|---|---|---|
| 5 sampai 6 | Database, autentikasi, modul survei | Penyebaran survei dan monitoring jumlah respons |
| 7 sampai 8 | Manajemen data, kerangka dashboard | Preprocessing, EDA, training, evaluasi |
| 9 sampai 10 | Integrasi dengan ML service | Pembuatan endpoint FastAPI dan SHAP |

## 4. Milestone

| Kode | Milestone | Target |
|---|---|---|
| M1 | Dokumen Fase 1 selesai | 23 November 2026 |
| M2 | Requirements, desain sistem, dan instrumen survei final | Akhir Sprint 2 |
| M3 | Survei online dan mulai mengumpulkan data | Pertengahan Sprint 3 |
| M4 | Target minimum 100 respons valid tercapai | Akhir Sprint 3 |
| M5 | Model terpilih dan terdokumentasi | Akhir Sprint 4 |
| M6 | Aplikasi terintegrasi penuh | Akhir Sprint 5 |
| M7 | UAT lulus dan rilis v1.0 | Akhir Sprint 6 |

## 5. Penanggung Jawab

| Pekerjaan | Penanggung Jawab |
|---|---|
| Timeline, koordinasi, izin survei | Prayshe |
| Requirements, PRD, SRS, UML | Prayshe |
| Prototype UI/UX | Cheryl |
| Laravel, database, API | Geraldo |
| Tampilan survei dan dashboard | Cheryl |
| Instrumen survei, data, dan model ML | Gerald (instrumen bersama Prayshe) |
| FastAPI ML service | Gerald |
| Integrasi Laravel dengan ML service | Geraldo dan Gerald |

## 6. Manajemen Tugas

Tugas dikelola di GitHub Projects dengan alur status:

`Backlog → To Do → In Progress → Code Review → Testing → Done`
