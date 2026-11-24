"""Training, perbandingan model, evaluasi, dan penyimpanan model DormInsight.

Alur:
1. Muat CSV ekspor survei, validasi, ambil respons valid, bangun fitur.
2. Bandingkan baseline, Logistic Regression, Decision Tree, Random Forest
   dengan nested cross-validation (outer stratified 5-fold untuk evaluasi,
   inner 3-fold untuk tuning hyperparameter) agar skor tidak bias optimis.
3. Pilih model dengan F1 kelas "ingin keluar" tertinggi. Jika selisih F1
   kurang dari 0.02, pilih model yang lebih sederhana.
4. Latih ulang model terpilih pada seluruh data, simpan model + metadata,
   buat laporan (metrik, confusion matrix, SHAP) dan catat experiment log.

Pemakaian:
    python -m src.train --data data/dummy/dummy_survey.csv --data-source dummy
    python -m src.train --data data/raw/survey_export.csv --data-source real --version 1.0.0
"""
from __future__ import annotations

import argparse
import json
import warnings
from datetime import datetime, timezone, timedelta

import joblib
import matplotlib
import numpy as np
import pandas as pd
import sklearn
from sklearn.base import clone
from sklearn.metrics import ConfusionMatrixDisplay, classification_report, confusion_matrix
from sklearn.model_selection import GridSearchCV, StratifiedKFold, cross_val_predict, cross_validate

from src import config
from src.explain import global_importance
from src.features import build_features, load_export
from src.modeling import BASELINES, CANDIDATES, make_pipeline

matplotlib.use("Agg")
import matplotlib.pyplot as plt  # noqa: E402
from sklearn.exceptions import UndefinedMetricWarning  # noqa: E402

# Baseline "selalu tebak mayoritas" memang tidak pernah memprediksi kelas 1,
# sehingga precision-nya tidak terdefinisi. Peringatan ini wajar dan disembunyikan.
warnings.filterwarnings("ignore", category=UndefinedMetricWarning)

WITA = timezone(timedelta(hours=8))
SCORING = {
    "accuracy": "accuracy",
    "balanced_accuracy": "balanced_accuracy",
    "precision": "precision",
    "recall": "recall",
    "f1": "f1",
    "roc_auc": "roc_auc",
}
TIE_MARGIN = 0.02


def _summarize(cv_result: dict) -> dict:
    out = {}
    for key in SCORING:
        vals = cv_result[f"test_{key}"]
        out[key] = round(float(np.nanmean(vals)), 4)
        out[f"{key}_std"] = round(float(np.nanstd(vals)), 4)
    return out


def evaluate_candidates(X, y, outer, inner) -> tuple[pd.DataFrame, dict]:
    rows, estimators = [], {}
    for name, base in BASELINES.items():
        res = cross_validate(make_pipeline(clone(base)), X, y, cv=outer, scoring=SCORING, error_score=np.nan)
        rows.append({"model": name, "algorithm": type(base).__name__, **_summarize(res)})

    for name, spec in CANDIDATES.items():
        search = GridSearchCV(make_pipeline(clone(spec["estimator"])), spec["grid"], scoring="f1", cv=inner, n_jobs=-1)
        res = cross_validate(search, X, y, cv=outer, scoring=SCORING, error_score=np.nan)
        rows.append({"model": name, "algorithm": spec["algorithm"], **_summarize(res)})
        estimators[name] = search
        print(f"  {name:22s} F1 = {rows[-1]['f1']:.3f} ± {rows[-1]['f1_std']:.3f}")
    return pd.DataFrame(rows), estimators


def select_model(results: pd.DataFrame) -> str:
    cands = results[results["model"].isin(CANDIDATES)].copy()
    best_f1 = cands["f1"].max()
    order = list(CANDIDATES)  # urutan kesederhanaan
    close = cands[cands["f1"] >= best_f1 - TIE_MARGIN]
    return min(close["model"], key=order.index)


def plot_confusion(y, y_pred, path) -> None:
    fig, ax = plt.subplots(figsize=(4.5, 4))
    ConfusionMatrixDisplay(confusion_matrix(y, y_pred), display_labels=["Tidak keluar", "Ingin keluar"]).plot(
        ax=ax, cmap="Blues", colorbar=False)
    ax.set_title("Confusion matrix (out-of-fold)")
    fig.tight_layout()
    fig.savefig(path, dpi=150)
    plt.close(fig)


def plot_comparison(results: pd.DataFrame, path) -> None:
    fig, ax = plt.subplots(figsize=(7, 3.8))
    r = results.set_index("model")
    ax.bar(r.index, r["f1"], yerr=r["f1_std"], color=["#9AA5B1"] * len(BASELINES) + ["#1F4E79"] * len(CANDIDATES),
           capsize=4)
    ax.set_ylabel("F1 kelas ingin keluar")
    ax.set_ylim(0, 1)
    ax.set_title("Perbandingan model (nested 5-fold CV)")
    plt.setp(ax.get_xticklabels(), rotation=20, ha="right")
    fig.tight_layout()
    fig.savefig(path, dpi=150)
    plt.close(fig)


def plot_shap(importance: list[dict], path) -> None:
    labels = {**config.FACTORS, "ANGKATAN": "Angkatan", "LAMA_TINGGAL": "Lama tinggal"}
    imp = list(reversed(importance))
    fig, ax = plt.subplots(figsize=(7, 4.5))
    ax.barh([labels[i["code"]] for i in imp], [i["mean_abs_shap"] for i in imp], color="#1F4E79")
    ax.set_xlabel("Rata-rata |SHAP| (besar pengaruh)")
    ax.set_title("Faktor paling berpengaruh terhadap keinginan keluar")
    fig.tight_layout()
    fig.savefig(path, dpi=150)
    plt.close(fig)


def shap_stability(best_pipeline, X, y, n_boot: int) -> pd.DataFrame:
    """Seberapa stabil peringkat SHAP jika data sedikit berubah (bootstrap).

    Dengan data kecil (ratusan respons), peringkat faktor bisa berubah-ubah.
    Laporan ini menunjukkan seberapa sering tiap fitur masuk 3 besar.
    """
    rng = np.random.default_rng(config.RANDOM_STATE)
    ranks = {f: [] for f in config.FEATURES}
    done = 0
    for _ in range(n_boot):
        idx = rng.choice(len(X), size=len(X), replace=True)
        Xb, yb = X.iloc[idx], y.iloc[idx]
        if yb.nunique() < 2:
            continue
        model = clone(best_pipeline).fit(Xb, yb)
        imp = global_importance(model, X, X.sample(min(100, len(X)), random_state=config.RANDOM_STATE))
        for r in imp:
            ranks[r["code"]].append(r["rank"])
        done += 1
    rows = [{
        "code": f,
        "top3_frequency": round(float(np.mean(np.array(r) <= 3)), 3),
        "mean_rank": round(float(np.mean(r)), 2),
    } for f, r in ranks.items()]
    out = pd.DataFrame(rows).sort_values(["top3_frequency", "mean_rank"], ascending=[False, True])
    out["n_bootstrap"] = done
    return out


def append_experiment_log(row: dict) -> None:
    path = config.REPORTS_DIR / "experiment_log.csv"
    df = pd.DataFrame([row])
    df.to_csv(path, mode="a", header=not path.exists(), index=False)


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--data", required=True, help="CSV ekspor survei")
    parser.add_argument("--data-source", choices=["dummy", "real"], required=True)
    parser.add_argument("--version", default="0.1.0")
    parser.add_argument("--bootstrap", type=int, default=30, help="Jumlah bootstrap untuk uji stabilitas SHAP")
    args = parser.parse_args()

    config.MODELS_DIR.mkdir(exist_ok=True)
    config.FIGURES_DIR.mkdir(parents=True, exist_ok=True)
    now = datetime.now(WITA)

    raw = load_export(args.data)
    X, y = build_features(raw, only_valid=True)
    n, pos = len(y), int(y.sum())
    print(f"Data: {len(raw)} baris, {n} valid, ingin keluar = {pos} ({pos / n:.1%})")

    data_sufficient = n >= config.MIN_VALID_RESPONSES and min(pos, n - pos) >= 2 * 5
    if not data_sufficient:
        print(f"PERINGATAN: data belum memenuhi batas minimum ({config.MIN_VALID_RESPONSES} respons valid "
              f"dan cukup sampel tiap kelas). Hasil hanya indikatif.")

    outer = StratifiedKFold(n_splits=5, shuffle=True, random_state=config.RANDOM_STATE)
    inner = StratifiedKFold(n_splits=3, shuffle=True, random_state=config.RANDOM_STATE)

    print("Nested cross-validation:")
    results, searches = evaluate_candidates(X, y, outer, inner)
    chosen = select_model(results)
    spec = CANDIDATES[chosen]
    chosen_row = results.set_index("model").loc[chosen]
    baseline_f1 = float(results.set_index("model").loc["baseline_mayoritas", "f1"])
    print(f"Model terpilih: {chosen}")

    # Prediksi out-of-fold model terpilih untuk confusion matrix dan classification report
    y_oof = cross_val_predict(clone(searches[chosen]), X, y, cv=outer)
    report = classification_report(y, y_oof, target_names=["tidak_keluar", "ingin_keluar"], output_dict=True,
                                   zero_division=0)

    # Latih ulang pada seluruh data
    final_search = GridSearchCV(make_pipeline(clone(spec["estimator"])), spec["grid"], scoring="f1",
                                cv=StratifiedKFold(5, shuffle=True, random_state=config.RANDOM_STATE), n_jobs=-1)
    final_search.fit(X, y)
    pipeline = final_search.best_estimator_
    background = X.sample(min(100, len(X)), random_state=config.RANDOM_STATE)
    importance = global_importance(pipeline, X, background)
    print(f"Uji stabilitas SHAP ({args.bootstrap} bootstrap)...")
    stability = shap_stability(pipeline, X, y, args.bootstrap)
    stab_map = stability.set_index("code").to_dict("index")
    for item in importance:
        item["top3_frequency"] = stab_map[item["code"]]["top3_frequency"]

    suffix = "-dummy" if args.data_source == "dummy" else ""
    model_version = f"{spec['abbr']}-{args.version}{suffix}"

    metrics = {
        "cv_folds": 5,
        "cv_method": "nested stratified 5-fold (inner 3-fold grid search)",
        "n_train": n,
        "positive_rate": round(pos / n, 4),
        "accuracy": chosen_row["accuracy"],
        "balanced_accuracy": chosen_row["balanced_accuracy"],
        "precision_keluar": chosen_row["precision"],
        "recall_keluar": chosen_row["recall"],
        "f1_keluar": chosen_row["f1"],
        "f1_keluar_std": chosen_row["f1_std"],
        "roc_auc": chosen_row["roc_auc"],
        "baseline_f1_keluar": round(baseline_f1, 4),
    }
    meta = {
        "model_version": model_version,
        "algorithm": spec["algorithm"],
        "trained_at": now.isoformat(timespec="seconds"),
        "data_source": args.data_source,
        "data_file": str(args.data),
        "data_sufficient": bool(data_sufficient),
        "features": config.FEATURES,
        "target": config.TARGET,
        "risk_thresholds": config.RISK_THRESHOLDS,
        "best_params": {k.replace("clf__", ""): v for k, v in final_search.best_params_.items()},
        "metrics": metrics,
        "global_shap": importance,
        "sklearn_version": sklearn.__version__,
    }

    joblib.dump({"pipeline": pipeline, "background": background, "meta": meta}, config.MODELS_DIR / "model.joblib")
    (config.MODELS_DIR / "model_meta.json").write_text(json.dumps(meta, indent=2, ensure_ascii=False))

    results.to_csv(config.REPORTS_DIR / "model_comparison.csv", index=False)
    stability.to_csv(config.REPORTS_DIR / "shap_stability.csv", index=False)
    (config.REPORTS_DIR / "classification_report.json").write_text(json.dumps(report, indent=2))
    plot_confusion(y, y_oof, config.FIGURES_DIR / "confusion_matrix.png")
    plot_comparison(results, config.FIGURES_DIR / "model_comparison.png")
    plot_shap(importance, config.FIGURES_DIR / "shap_global.png")

    append_experiment_log({
        "timestamp": now.isoformat(timespec="seconds"),
        "model_version": model_version,
        "data_source": args.data_source,
        "data_file": args.data,
        "n_valid": n,
        "positive_rate": round(pos / n, 4),
        **{f"f1_{r['model']}": r["f1"] for _, r in results.iterrows()},
        "chosen": chosen,
        "best_params": json.dumps(meta["best_params"]),
        "top3_shap": ", ".join(i["code"] for i in importance[:3]),
    })

    print(f"F1 kelas ingin keluar: {metrics['f1_keluar']:.3f} (baseline {baseline_f1:.3f})")
    print("3 faktor teratas (SHAP):", ", ".join(
        f"{i['code']} ({i['direction']}, masuk 3 besar {i['top3_frequency']:.0%})" for i in importance[:3]))
    print(f"Model disimpan: models/model.joblib, versi {model_version}")


if __name__ == "__main__":
    main()
