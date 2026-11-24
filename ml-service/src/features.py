"""Memuat data ekspor survei, memvalidasi, dan membangun fitur.

Format input: CSV ekspor Laravel (satu baris per respons) dengan kolom item
Likert (F_FAS_1, F_FAS_2, ...), ANGKATAN, LAMA_TINGGAL, KEP_UMUM,
INGIN_KELUAR, duration_seconds, is_valid. Lihat
docs/04_Machine_Learning/Dataset_Documentation.md.

Perhitungan skor faktor di sini HARUS sama dengan FeatureBuilder di Laravel:
skor faktor = rata-rata item faktor tersebut.
"""
from __future__ import annotations

import pandas as pd

from src import config


class DataValidationError(ValueError):
    pass


def load_export(path) -> pd.DataFrame:
    df = pd.read_csv(path, dtype={"ANGKATAN": str, "LAMA_TINGGAL": str})
    validate_export(df)
    return df


def validate_export(df: pd.DataFrame) -> None:
    item_cols = [c for items in config.FACTOR_ITEMS.values() for c in items]
    required = item_cols + ["ANGKATAN", "LAMA_TINGGAL", config.TARGET]
    missing = [c for c in required if c not in df.columns]
    if missing:
        raise DataValidationError(f"Kolom wajib tidak ditemukan: {missing}")

    bad_likert = df[item_cols].apply(lambda s: ~s.between(1, 5)).any()
    if bad_likert.any():
        raise DataValidationError(f"Nilai Likert di luar 1-5 pada: {list(bad_likert[bad_likert].index)}")

    if not df[config.TARGET].isin([0, 1]).all():
        raise DataValidationError("INGIN_KELUAR harus bernilai 0 atau 1")

    for col, allowed in (("ANGKATAN", config.ANGKATAN_VALUES), ("LAMA_TINGGAL", config.LAMA_TINGGAL_VALUES)):
        bad = set(df[col].dropna().unique()) - set(allowed)
        if bad:
            raise DataValidationError(f"Nilai {col} tidak dikenal: {sorted(bad)}")


def build_features(df: pd.DataFrame, only_valid: bool = True) -> tuple[pd.DataFrame, pd.Series]:
    """Mengembalikan (X, y). X berisi kolom config.FEATURES saja."""
    data = df.copy()
    if only_valid and "is_valid" in data.columns:
        data = data[data["is_valid"] == 1]

    out = pd.DataFrame(index=data.index)
    for factor, items in config.FACTOR_ITEMS.items():
        out[factor] = data[items].mean(axis=1).round(2)
    out["ANGKATAN"] = data["ANGKATAN"].astype(str)
    out["LAMA_TINGGAL"] = data["LAMA_TINGGAL"].astype(str)

    X = out[config.FEATURES]
    y = data[config.TARGET].astype(int)
    return X, y


def item_correlations(df: pd.DataFrame) -> pd.DataFrame:
    """Korelasi Pearson antar-2 item per faktor (cek konsistensi internal)."""
    rows = []
    for factor, (a, b) in config.FACTOR_ITEMS.items():
        r = df[a].corr(df[b])
        rows.append({
            "factor": factor,
            "name": config.FACTORS[factor],
            "r_item1_item2": round(float(r), 3),
            "status": "konsisten" if r >= 0.5 else ("cukup" if r >= 0.3 else "lemah, tinjau item"),
        })
    return pd.DataFrame(rows)
