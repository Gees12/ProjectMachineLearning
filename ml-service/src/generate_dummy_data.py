"""Membuat data survei DUMMY untuk menguji pipeline sebelum data asli tersedia.

PERINGATAN: data ini sintetis. Hasil model dari data ini TIDAK boleh
dilaporkan sebagai temuan tentang Crystal Dormitory.

Pola yang ditanamkan (agar pipeline dapat diuji apakah mampu menemukannya):
makanan, biaya, dan privasi paling berpengaruh terhadap keinginan keluar.

Pemakaian:
    python -m src.generate_dummy_data --n 150 --out data/dummy/dummy_survey.csv
"""
from __future__ import annotations

import argparse
import uuid

import numpy as np
import pandas as pd

from src import config

# Bobot pengaruh tiap faktor (negatif: makin puas, makin kecil peluang keluar)
TRUE_WEIGHTS = {
    "F_MAK": -1.1, "F_BIA": -0.9, "F_PRI": -0.7, "F_PER": -0.4, "F_FAS": -0.3,
    "F_PEL": -0.3, "F_SOS": -0.2, "F_KEB": -0.15, "F_BEL": -0.15, "F_KEA": -0.05,
}
FACTOR_MEANS = {
    "F_FAS": 3.4, "F_KEB": 3.8, "F_MAK": 2.8, "F_BIA": 3.0, "F_PER": 3.0,
    "F_PRI": 3.1, "F_KEA": 4.1, "F_SOS": 3.9, "F_PEL": 3.5, "F_BEL": 3.0,
}


def generate(n: int, seed: int = config.RANDOM_STATE) -> pd.DataFrame:
    rng = np.random.default_rng(seed)

    # Kepuasan laten per faktor, sedikit berkorelasi satu sama lain
    general = rng.normal(0, 0.5, n)
    latent = {f: np.clip(FACTOR_MEANS[f] + general + rng.normal(0, 0.8, n), 1, 5) for f in config.FACTORS}

    rows = {"response_id": [str(uuid.UUID(int=int(rng.integers(0, 2**63)))) for _ in range(n)]}
    for f, items in config.FACTOR_ITEMS.items():
        for item in items:
            rows[item] = np.clip(np.rint(latent[f] + rng.normal(0, 0.5, n)), 1, 5).astype(int)

    rows["ANGKATAN"] = rng.choice(config.ANGKATAN_VALUES, n, p=[0.2, 0.25, 0.3, 0.25])
    rows["LAMA_TINGGAL"] = rng.choice(config.LAMA_TINGGAL_VALUES, n, p=[0.35, 0.4, 0.25])

    # Log-odds keinginan keluar
    logit = -1.0 + sum(w * (latent[f] - 3) for f, w in TRUE_WEIGHTS.items())
    logit += np.where(rows["LAMA_TINGGAL"] == "gt2", 0.4, 0.0)
    logit += rng.normal(0, 0.6, n)
    prob = 1 / (1 + np.exp(-logit))
    rows[config.TARGET] = (rng.random(n) < prob).astype(int)

    sat_mean = np.mean([latent[f] for f in config.FACTORS], axis=0)
    rows["KEP_UMUM"] = np.clip(np.rint(sat_mean + rng.normal(0, 0.4, n)), 1, 5).astype(int)
    rows["SARAN"] = ""
    rows["duration_seconds"] = rng.integers(150, 600, n)
    rows["is_valid"] = 1

    df = pd.DataFrame(rows)
    # Sisipkan beberapa respons tidak valid (terlalu cepat) untuk menguji filter
    bad = rng.choice(n, size=max(1, n // 30), replace=False)
    df.loc[bad, "duration_seconds"] = rng.integers(40, 110, len(bad))
    df.loc[bad, "is_valid"] = 0
    return df


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--n", type=int, default=150)
    parser.add_argument("--out", default=str(config.DUMMY_DIR / "dummy_survey.csv"))
    parser.add_argument("--seed", type=int, default=config.RANDOM_STATE)
    args = parser.parse_args()

    df = generate(args.n, args.seed)
    config.DUMMY_DIR.mkdir(parents=True, exist_ok=True)
    df.to_csv(args.out, index=False)
    print(f"Data dummy: {len(df)} baris, {df['is_valid'].sum()} valid, "
          f"proporsi ingin keluar {df[config.TARGET].mean():.1%} -> {args.out}")


if __name__ == "__main__":
    main()
