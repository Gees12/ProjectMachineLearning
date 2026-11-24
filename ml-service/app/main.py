"""DormInsight ML Service (FastAPI).

Menjalankan secara lokal (dari folder ml-service):
    uvicorn app.main:app --reload --port 8001

Dokumentasi interaktif: http://localhost:8001/docs
"""
from contextlib import asynccontextmanager

from fastapi import Depends, FastAPI, HTTPException, status

from app import settings
from app.model_store import store
from app.schemas import (BatchRequest, ExplainResponse, PredictResponse, Prediction, SimulateRequest,
                         SimulateResponse)
from app.security import require_api_key
from src.config import risk_level


@asynccontextmanager
async def lifespan(_: FastAPI):
    store.load()
    yield


app = FastAPI(title="DormInsight ML Service", version="0.1.0", lifespan=lifespan)


def require_model() -> None:
    if not store.loaded:
        raise HTTPException(status_code=status.HTTP_503_SERVICE_UNAVAILABLE, detail="Model not loaded")


def check_batch_size(req: BatchRequest) -> None:
    if len(req.records) > settings.MAX_RECORDS:
        raise HTTPException(status_code=413,
                            detail=f"Too many records (max {settings.MAX_RECORDS})")


protected = [Depends(require_api_key), Depends(require_model)]


@app.get("/health")
def health():
    return {"status": "ok", "model_loaded": store.loaded}


@app.get("/model/info", dependencies=protected)
def model_info():
    m = store.meta
    return {
        "model_version": m["model_version"],
        "algorithm": m["algorithm"],
        "trained_at": m["trained_at"],
        "data_source": m["data_source"],
        "data_sufficient": m["data_sufficient"],
        "features": m["features"],
        "target": m["target"],
        "risk_thresholds": m["risk_thresholds"],
        "metrics": m["metrics"],
        "shap_stability": [{"code": i["code"], "top3_frequency": i.get("top3_frequency")}
                           for i in m.get("global_shap", [])],
    }


@app.post("/predict/batch", response_model=PredictResponse, dependencies=protected)
def predict_batch(req: BatchRequest):
    check_batch_size(req)
    probs = store.predict_proba([r.features for r in req.records])
    return PredictResponse(
        model_version=store.version,
        predictions=[Prediction(id=r.id, probability=round(p, 4), risk_level=risk_level(p))
                     for r, p in zip(req.records, probs)],
    )


@app.post("/explain/global", response_model=ExplainResponse, dependencies=protected)
def explain_global(req: BatchRequest):
    check_batch_size(req)
    method, importance = store.explain([r.features for r in req.records])
    return ExplainResponse(model_version=store.version, method=method, n_records=len(req.records),
                           features=importance)


@app.post("/simulate", response_model=SimulateResponse, dependencies=protected)
def simulate(req: SimulateRequest):
    base, scen = store.predict_proba([req.baseline, req.scenario])
    return SimulateResponse(model_version=store.version, baseline_probability=round(base, 4),
                            scenario_probability=round(scen, 4), delta=round(scen - base, 4))
