"""Explainability: nilai SHAP global per fitur asli.

Dipakai oleh script training (laporan) dan oleh FastAPI (/explain/global).
Kolom hasil one-hot (ANGKATAN_2024, ...) dijumlahkan kembali menjadi satu
fitur ANGKATAN agar mudah dibaca pengelola.
"""
from __future__ import annotations

import numpy as np
import pandas as pd
import shap
from scipy.stats import spearmanr

from src import config

TREE_MODELS = ("DecisionTreeClassifier", "RandomForestClassifier")
DIRECTION_THRESHOLD = 0.3


def _original_feature(transformed_name: str) -> str:
    # contoh: "num__F_MAK", "cat__ANGKATAN_2024", "ord__LAMA_TINGGAL"
    name = transformed_name.split("__", 1)[1]
    for feat in config.FEATURES:
        if name == feat or name.startswith(feat + "_"):
            return feat
    raise KeyError(transformed_name)


def _positive_class_shap(values) -> np.ndarray:
    """Menyeragamkan berbagai bentuk output SHAP menjadi (n_sampel, n_kolom) untuk kelas 1."""
    if isinstance(values, list):  # versi lama: [kelas0, kelas1]
        return np.asarray(values[1])
    values = np.asarray(values)
    if values.ndim == 3:  # (n, f, kelas)
        return values[:, :, 1]
    return values


def shap_matrix(pipeline, X: pd.DataFrame, background: pd.DataFrame | None = None) -> pd.DataFrame:
    """Nilai SHAP per sampel per fitur asli (log-odds/probabilitas, tergantung model)."""
    pre = pipeline.named_steps["pre"]
    clf = pipeline.named_steps["clf"]
    Xt = pre.transform(X)
    names = pre.get_feature_names_out()

    if type(clf).__name__ in TREE_MODELS:
        explainer = shap.TreeExplainer(clf)
        raw = explainer.shap_values(Xt)
    else:
        bg = pre.transform(background) if background is not None else Xt
        explainer = shap.LinearExplainer(clf, bg)
        raw = explainer.shap_values(Xt)

    values = pd.DataFrame(_positive_class_shap(raw), columns=names, index=X.index)
    grouped = values.T.groupby(_original_feature).sum().T
    return grouped[config.FEATURES]


def _direction(feature: str, X: pd.DataFrame, shap_col: pd.Series) -> str:
    if feature in config.CATEGORICAL_FEATURES or len(X) < 3:
        return "campuran"
    if feature in config.ORDINAL_FEATURES:
        values = X[feature].map({v: i for i, v in enumerate(config.LAMA_TINGGAL_VALUES)})
    else:
        values = X[feature]
    if values.nunique() < 2 or shap_col.nunique() < 2:
        return "campuran"
    rho = spearmanr(values, shap_col).statistic
    if np.isnan(rho):
        return "campuran"
    if rho <= -DIRECTION_THRESHOLD:
        return "negatif"
    if rho >= DIRECTION_THRESHOLD:
        return "positif"
    return "campuran"


def global_importance(pipeline, X: pd.DataFrame, background: pd.DataFrame | None = None) -> list[dict]:
    sv = shap_matrix(pipeline, X, background)
    rows = []
    for feat in config.FEATURES:
        rows.append({
            "code": feat,
            "mean_abs_shap": round(float(sv[feat].abs().mean()), 5),
            "direction": _direction(feat, X, sv[feat]),
        })
    rows.sort(key=lambda r: r["mean_abs_shap"], reverse=True)
    for i, r in enumerate(rows, start=1):
        r["rank"] = i
    return rows
