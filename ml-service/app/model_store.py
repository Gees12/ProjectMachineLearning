"""Memuat model sekali saat startup dan menyediakan fungsi prediksi."""
from __future__ import annotations

import joblib
import pandas as pd

from app import settings
from src import config
from src.explain import TREE_MODELS, global_importance


class ModelStore:
    def __init__(self) -> None:
        self.pipeline = None
        self.background = None
        self.meta: dict = {}

    @property
    def loaded(self) -> bool:
        return self.pipeline is not None

    def load(self, path=settings.MODEL_PATH) -> None:
        if not path.exists():
            self.pipeline, self.meta = None, {}
            return
        bundle = joblib.load(path)
        self.pipeline = bundle["pipeline"]
        self.background = bundle.get("background")
        self.meta = bundle["meta"]

    @property
    def version(self) -> str:
        return self.meta.get("model_version", "unknown")

    @staticmethod
    def to_frame(features_list) -> pd.DataFrame:
        return pd.DataFrame([f.model_dump() for f in features_list])[config.FEATURES]

    def predict_proba(self, features_list) -> list[float]:
        X = self.to_frame(features_list)
        return [float(p) for p in self.pipeline.predict_proba(X)[:, 1]]

    def explain(self, features_list) -> tuple[str, list[dict]]:
        X = self.to_frame(features_list)
        method = "shap.TreeExplainer" if self.meta.get("algorithm") in TREE_MODELS else "shap.LinearExplainer"
        return method, global_importance(self.pipeline, X, self.background)


store = ModelStore()
