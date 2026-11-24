"""Skema request/response sesuai docs/03_System_Design/API_Documentation.md."""
from typing import Literal

from pydantic import BaseModel, ConfigDict, Field

Score = Field(ge=1.0, le=5.0)


class Features(BaseModel):
    model_config = ConfigDict(extra="forbid")

    F_FAS: float = Score
    F_KEB: float = Score
    F_MAK: float = Score
    F_BIA: float = Score
    F_PER: float = Score
    F_PRI: float = Score
    F_KEA: float = Score
    F_SOS: float = Score
    F_PEL: float = Score
    F_BEL: float = Score
    ANGKATAN: Literal["2023_atau_sebelumnya", "2024", "2025", "2026"]
    LAMA_TINGGAL: Literal["lt1", "1_2", "gt2"]


class Record(BaseModel):
    id: str = Field(min_length=1, max_length=64)
    features: Features


class BatchRequest(BaseModel):
    records: list[Record] = Field(min_length=1)


class Prediction(BaseModel):
    id: str
    probability: float
    risk_level: Literal["rendah", "sedang", "tinggi"]


class PredictResponse(BaseModel):
    model_version: str
    predictions: list[Prediction]


class FeatureImportance(BaseModel):
    code: str
    mean_abs_shap: float
    direction: Literal["negatif", "positif", "campuran"]
    rank: int


class ExplainResponse(BaseModel):
    model_version: str
    method: str
    n_records: int
    features: list[FeatureImportance]


class SimulateRequest(BaseModel):
    baseline: Features
    scenario: Features


class SimulateResponse(BaseModel):
    model_version: str
    baseline_probability: float
    scenario_probability: float
    delta: float
    note: str = "Hasil simulasi adalah perkiraan model, bukan jaminan."
