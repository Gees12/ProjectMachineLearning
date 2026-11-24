"""Exploratory Data Analysis (EDA) untuk data survei DormInsight.

Menghasilkan ringkasan dan grafik di reports/eda/.

Pemakaian:
    python -m src.eda --data data/dummy/dummy_survey.csv
"""
from __future__ import annotations

import argparse
import json

import matplotlib
import pandas as pd

from src import config
from src.features import build_features, item_correlations, load_export

matplotlib.use("Agg")
import matplotlib.pyplot as plt  # noqa: E402
import seaborn as sns  # noqa: E402


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--data", required=True)
    args = parser.parse_args()

    out_dir = config.REPORTS_DIR / "eda"
    out_dir.mkdir(parents=True, exist_ok=True)

    raw = load_export(args.data)
    X, y = build_features(raw, only_valid=True)
    df = X.assign(**{config.TARGET: y})

    summary = {
        "n_rows": int(len(raw)),
        "n_valid": int(len(df)),
        "n_invalid": int(len(raw) - len(df)),
        "missing_values": int(raw[[c for c in raw.columns if c != "SARAN"]].isna().sum().sum()),
        "target_counts": {str(k): int(v) for k, v in y.value_counts().sort_index().items()},
        "positive_rate": round(float(y.mean()), 4),
        "segment_counts": {
            "ANGKATAN": X["ANGKATAN"].value_counts().to_dict(),
            "LAMA_TINGGAL": X["LAMA_TINGGAL"].value_counts().to_dict(),
        },
        "small_segments_below_5": [
            f"{col}={val}" for col in ("ANGKATAN", "LAMA_TINGGAL")
            for val, cnt in X[col].value_counts().items() if cnt < config.MIN_SEGMENT_SIZE
        ],
    }
    (out_dir / "summary.json").write_text(json.dumps(summary, indent=2, ensure_ascii=False))

    desc = df[config.NUMERIC_FEATURES].describe().T.round(2)
    desc["mean_tidak_keluar"] = df[df[config.TARGET] == 0][config.NUMERIC_FEATURES].mean().round(2)
    desc["mean_ingin_keluar"] = df[df[config.TARGET] == 1][config.NUMERIC_FEATURES].mean().round(2)
    desc["selisih"] = (desc["mean_tidak_keluar"] - desc["mean_ingin_keluar"]).round(2)
    desc.to_csv(out_dir / "factor_statistics.csv")

    item_correlations(raw[raw.get("is_valid", 1) == 1]).to_csv(out_dir / "item_correlations.csv", index=False)

    # Grafik 1: distribusi target
    fig, ax = plt.subplots(figsize=(4, 3.2))
    y.map({0: "Tidak", 1: "Ya"}).value_counts().reindex(["Tidak", "Ya"]).plot.bar(ax=ax, color=["#198754", "#DC3545"])
    ax.set_title("Ingin keluar asrama?")
    ax.set_ylabel("Jumlah responden")
    ax.tick_params(axis="x", rotation=0)
    fig.tight_layout()
    fig.savefig(out_dir / "target_distribution.png", dpi=150)
    plt.close(fig)

    # Grafik 2: skor faktor berdasarkan target
    long = df.melt(id_vars=config.TARGET, value_vars=config.NUMERIC_FEATURES, var_name="faktor", value_name="skor")
    long[config.TARGET] = long[config.TARGET].map({0: "Tidak", 1: "Ya"})
    fig, ax = plt.subplots(figsize=(10, 4))
    sns.boxplot(data=long, x="faktor", y="skor", hue=config.TARGET, palette={"Tidak": "#198754", "Ya": "#DC3545"}, ax=ax)
    ax.set_title("Skor faktor menurut keinginan keluar")
    ax.legend(title="Ingin keluar")
    fig.tight_layout()
    fig.savefig(out_dir / "factor_by_target.png", dpi=150)
    plt.close(fig)

    # Grafik 3: korelasi antarfaktor dan target
    fig, ax = plt.subplots(figsize=(8, 6.5))
    sns.heatmap(df[config.NUMERIC_FEATURES + [config.TARGET]].corr(method="spearman"), annot=True, fmt=".2f",
                cmap="RdBu_r", center=0, ax=ax, annot_kws={"size": 7})
    ax.set_title("Korelasi Spearman")
    fig.tight_layout()
    fig.savefig(out_dir / "correlation_heatmap.png", dpi=150)
    plt.close(fig)

    print(json.dumps(summary, indent=2, ensure_ascii=False))
    print(f"Hasil EDA disimpan di {out_dir}")


if __name__ == "__main__":
    main()
