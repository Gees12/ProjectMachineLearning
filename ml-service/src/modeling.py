"""Definisi preprocessing dan kandidat model.

Model disimpan sebagai satu sklearn Pipeline (preprocessing + classifier)
agar preprocessing saat training dan saat prediksi selalu sama (ADR-03).
"""
from __future__ import annotations

from sklearn.compose import ColumnTransformer
from sklearn.dummy import DummyClassifier
from sklearn.ensemble import RandomForestClassifier
from sklearn.linear_model import LogisticRegression
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder, OrdinalEncoder, StandardScaler
from sklearn.tree import DecisionTreeClassifier

from src import config


def build_preprocessor() -> ColumnTransformer:
    return ColumnTransformer(
        transformers=[
            ("num", StandardScaler(), config.NUMERIC_FEATURES),
            ("cat", OneHotEncoder(categories=[config.ANGKATAN_VALUES], handle_unknown="ignore"),
             config.CATEGORICAL_FEATURES),
            ("ord", OrdinalEncoder(categories=[config.LAMA_TINGGAL_VALUES]), config.ORDINAL_FEATURES),
        ],
        verbose_feature_names_out=True,
    )


def make_pipeline(classifier) -> Pipeline:
    return Pipeline([("pre", build_preprocessor()), ("clf", classifier)])


# Urutan = urutan kesederhanaan; dipakai sebagai tie-breaker saat F1 hampir sama
CANDIDATES = {
    "logistic_regression": {
        "abbr": "lr",
        "algorithm": "LogisticRegression",
        "estimator": LogisticRegression(class_weight="balanced", max_iter=2000, random_state=config.RANDOM_STATE),
        "grid": {"clf__C": [0.1, 1.0, 10.0]},
    },
    "decision_tree": {
        "abbr": "dt",
        "algorithm": "DecisionTreeClassifier",
        "estimator": DecisionTreeClassifier(class_weight="balanced", random_state=config.RANDOM_STATE),
        "grid": {"clf__max_depth": [2, 3, 4, 5], "clf__min_samples_leaf": [5, 10]},
    },
    "random_forest": {
        "abbr": "rf",
        "algorithm": "RandomForestClassifier",
        "estimator": RandomForestClassifier(n_estimators=300, class_weight="balanced_subsample",
                                            random_state=config.RANDOM_STATE, n_jobs=-1),
        "grid": {"clf__max_depth": [3, 5, None], "clf__min_samples_leaf": [2, 5]},
    },
}

BASELINES = {
    "baseline_mayoritas": DummyClassifier(strategy="most_frequent"),
    "baseline_acak_stratified": DummyClassifier(strategy="stratified", random_state=config.RANDOM_STATE),
}
