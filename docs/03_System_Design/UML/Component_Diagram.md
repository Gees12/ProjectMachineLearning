# Component Diagram

> Proyek: DormInsight | Fase 3 | Versi 0.1 | Penyusun: Geraldo, Gerald

```mermaid
flowchart TB
    subgraph Browser
        UI[Blade Pages + Bootstrap 5 + Chart.js]
    end

    subgraph Laravel["Laravel Web App"]
        direction TB
        MW[Middleware<br/>auth, role, throttle, csrf]
        subgraph Ctrl["Controllers"]
            AUTH[AuthController]
            USR[UserController]
            SVY[Survey, Factor, QuestionController]
            SUB[SubmissionController]
            RES[ResponseController]
            ANL[AnalysisController]
            DSH[Dashboard, SimulationController]
            RPT[ReportController]
        end
        subgraph Svc["Services"]
            SS[SubmissionService]
            FB[FeatureBuilder]
            RV[ResponseValidator]
            AS[AnalysisService]
            MC[MlClient]
            RS[ReportService<br/>DomPDF, Laravel Excel]
        end
        ORM[Eloquent Models]
    end

    DB[(MySQL)]

    subgraph FastAPI["ML Service"]
        direction TB
        SEC[API Key Guard]
        R1["/health, /model/info"]
        R2["/predict/batch, /simulate"]
        R3["/explain/global"]
        PIPE[sklearn Pipeline<br/>preprocessing + classifier]
        SHAPX[SHAP Explainer]
        FILES[(model.joblib<br/>model_meta.json)]
    end

    subgraph Offline["Training Offline (Fase 4)"]
        NB[Notebooks EDA]
        TR[src/train.py, evaluate.py]
        CSV[(Export CSV anonim)]
    end

    UI --> MW --> Ctrl
    SUB --> SS --> FB
    SS --> RV
    ANL --> AS --> MC
    DSH --> MC
    RPT --> RS
    Ctrl --> ORM
    Svc --> ORM
    ORM --> DB

    MC -->|HTTP JSON| SEC
    SEC --> R1 & R2 & R3
    R2 --> PIPE
    R3 --> SHAPX --> PIPE
    PIPE --> FILES

    RES -->|ekspor| CSV --> NB --> TR -->|menghasilkan| FILES
```

## Keterangan Antarmuka

| Dari | Ke | Antarmuka |
|---|---|---|
| Browser | Laravel | HTTPS, form HTML, session cookie |
| Laravel | MySQL | PDO via Eloquent |
| Laravel (MlClient) | ML Service | REST JSON + header `X-API-Key` (lihat API_Documentation.md) |
| Training offline | ML Service | File `model.joblib` dan `model_meta.json` disalin ke `ml-service/models/` |
| Laravel | Training offline | Ekspor CSV respons anonim |
