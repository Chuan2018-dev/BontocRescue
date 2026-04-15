FROM python:3.13-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PYTHONPATH=/app

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends libgomp1 libjpeg62-turbo libpng16-16 \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt ./

RUN pip install --upgrade pip \
    && pip install --no-cache-dir -r requirements.txt

COPY . .

EXPOSE 10000

CMD ["python", "serve_api.py"]
