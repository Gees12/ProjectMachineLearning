"""Test kontrak API ML service.

Menjalankan (dari folder ml-service, setelah model dilatih):
    pytest -q
"""
import os

os.environ["ML_API_KEY"] = "test-key"

import pytest  # noqa: E402
from fastapi.testclient import TestClient  # noqa: E402

from app import settings  # noqa: E402

settings.ML_API_KEY = "test-key"

from app.main import app  # noqa: E402

H = {"X-API-Key": "test-key"}
FEAT = {
    "F_FAS": 3.5, "F_KEB": 4.0, "F_MAK": 2.0, "F_BIA": 3.0, "F_PER": 2.5,
    "F_PRI": 3.0, "F_KEA": 4.5, "F_SOS": 4.0, "F_PEL": 3.5, "F_BEL": 2.5,
    "ANGKATAN": "2025", "LAMA_TINGGAL": "1_2",
}


@pytest.fixture(scope="module")
def client():
    if not settings.MODEL_PATH.exists():
        pytest.skip("Model belum dilatih: jalankan python -m src.train terlebih dahulu")
    with TestClient(app) as c:
        yield c


def batch(n=3):
    return {"records": [{"id": f"r{i}", "features": {**FEAT, "F_MAK": 1.0 + i % 5}} for i in range(n)]}


def test_health_tanpa_api_key(client):
    r = client.get("/health")
    assert r.status_code == 200
    assert r.json() == {"status": "ok", "model_loaded": True}


def test_api_key_salah_ditolak(client):
    assert client.get("/model/info").status_code == 401
    assert client.get("/model/info", headers={"X-API-Key": "salah"}).status_code == 401


def test_model_info(client):
    body = client.get("/model/info", headers=H).json()
    assert body["features"] == list(FEAT)
    assert body["target"] == "INGIN_KELUAR"
    assert "f1_keluar" in body["metrics"]
    assert body["data_source"] in ("dummy", "real")
    assert {s["code"] for s in body["shap_stability"]} == set(FEAT)


def test_predict_batch(client):
    body = client.post("/predict/batch", json=batch(5), headers=H).json()
    assert [p["id"] for p in body["predictions"]] == [f"r{i}" for i in range(5)]
    for p in body["predictions"]:
        assert 0 <= p["probability"] <= 1
        assert p["risk_level"] in ("rendah", "sedang", "tinggi")


def test_predict_validasi_rentang(client):
    bad = {"records": [{"id": "x", "features": {**FEAT, "F_MAK": 6}}]}
    assert client.post("/predict/batch", json=bad, headers=H).status_code == 422


def test_predict_fitur_tidak_dikenal(client):
    bad = {"records": [{"id": "x", "features": {**FEAT, "KEP_UMUM": 4}}]}
    assert client.post("/predict/batch", json=bad, headers=H).status_code == 422


def test_batch_terlalu_besar(client):
    original = settings.MAX_RECORDS
    settings.MAX_RECORDS = 2
    try:
        assert client.post("/predict/batch", json=batch(3), headers=H).status_code == 413
    finally:
        settings.MAX_RECORDS = original


def test_explain_global(client):
    body = client.post("/explain/global", json=batch(20), headers=H).json()
    codes = [f["code"] for f in body["features"]]
    assert sorted(codes) == sorted(FEAT)
    assert [f["rank"] for f in body["features"]] == list(range(1, 13))
    assert body["n_records"] == 20


def test_simulate_makanan_naik_menurunkan_risiko(client):
    scenario = {**FEAT, "F_MAK": 4.5}
    body = client.post("/simulate", json={"baseline": FEAT, "scenario": scenario}, headers=H).json()
    assert body["delta"] == pytest.approx(body["scenario_probability"] - body["baseline_probability"], abs=1e-3)
    assert body["delta"] < 0
