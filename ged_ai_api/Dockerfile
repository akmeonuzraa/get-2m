FROM python:3.11-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

WORKDIR /app

# copy only requirements first for better caching
COPY requirements.txt /app/requirements.txt
RUN pip install --no-cache-dir -r /app/requirements.txt

# copy rest of the package
COPY . /app

EXPOSE 8000

CMD ["uvicorn", "ged_ai_api.main:app", "--host", "0.0.0.0", "--port", "8000", "--workers", "1"]
