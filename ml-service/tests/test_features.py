import pandas as pd
import pytest

from src import config
from src.features import DataValidationError, build_features, validate_export
from src.generate_dummy_data import generate


def test_skor_faktor_adalah_rata_rata_item():
    df = generate(20)
    X, _ = build_features(df, only_valid=False)
    expected = df[["F_MAK_1", "F_MAK_2"]].mean(axis=1).round(2)
    pd.testing.assert_series_equal(X["F_MAK"], expected, check_names=False)


def test_respons_tidak_valid_dibuang():
    df = generate(60)
    X, y = build_features(df, only_valid=True)
    assert len(X) == int(df["is_valid"].sum())


def test_kolom_terlarang_bukan_fitur():
    X, _ = build_features(generate(10))
    assert list(X.columns) == config.FEATURES
    for col in config.EXCLUDED_COLUMNS:
        assert col not in X.columns


def test_validasi_likert_di_luar_rentang():
    df = generate(10)
    df.loc[0, "F_FAS_1"] = 7
    with pytest.raises(DataValidationError):
        validate_export(df)


def test_validasi_kategori_tidak_dikenal():
    df = generate(10)
    df.loc[0, "LAMA_TINGGAL"] = "5_tahun"
    with pytest.raises(DataValidationError):
        validate_export(df)
