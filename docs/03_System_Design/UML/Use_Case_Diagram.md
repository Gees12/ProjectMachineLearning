# Use Case Diagram

> Proyek: DormInsight | Fase 3 | Versi 0.1 | Penyusun: Prayshe

Mermaid belum memiliki notasi use case resmi, sehingga diagram digambarkan dengan flowchart: aktor di sisi kiri/kanan, use case berbentuk elips (stadium) di dalam batas sistem. Spesifikasi lengkap tiap use case ada di [Use_Case_Specification.md](../../02_Requirements/Use_Case_Specification.md).

```mermaid
flowchart LR
    MHS(["👤 Mahasiswa"])
    PGL(["👤 Pengelola"])
    ADM(["👤 Admin"])
    MLS(["⚙️ ML Service"])

    subgraph SYS["Sistem DormInsight"]
        UC01([UC-01 Login])
        UC04([UC-04 Mengisi Survei])
        UC07([UC-07 Melihat Dashboard])
        UC08([UC-08 Simulasi Skenario])
        UC09([UC-09 Mengunduh Laporan])
        UC02([UC-02 Mengelola Akun])
        UC03([UC-03 Mengelola Survei])
        UC05([UC-05 Memantau dan Ekspor Respons])
        UC06([UC-06 Menjalankan Analisis ML])
    end

    MHS --- UC01
    MHS --- UC04
    PGL --- UC01
    PGL --- UC07
    PGL --- UC08
    PGL --- UC09
    ADM --- UC01
    ADM --- UC02
    ADM --- UC03
    ADM --- UC05
    ADM --- UC06
    ADM --- UC07

    UC06 --- MLS
    UC08 --- MLS

    UC04 -. "include" .-> UC01
    UC07 -. "include" .-> UC01
    UC06 -. "include" .-> UC01
```

## Keterangan

| Notasi | Arti |
|---|---|
| Garis tegas | Aktor berinteraksi dengan use case |
| Garis putus-putus `include` | Use case membutuhkan use case lain (harus login terlebih dahulu) |
| ML Service | Aktor sekunder (sistem eksternal) |

Admin juga dapat melihat dashboard karena memiliki seluruh hak akses Pengelola.

> Untuk laporan formal, diagram ini dapat digambar ulang dengan notasi UML standar di draw.io atau StarUML berdasarkan struktur yang sama.
