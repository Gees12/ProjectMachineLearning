"""Validasi header X-API-Key (NFR-004)."""
import secrets

from fastapi import Header, HTTPException, status

from app import settings


def require_api_key(x_api_key: str | None = Header(default=None)) -> None:
    expected = settings.ML_API_KEY
    if not expected or not x_api_key or not secrets.compare_digest(x_api_key, expected):
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Invalid API key")
