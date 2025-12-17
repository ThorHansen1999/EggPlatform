#!/bin/sh
set -e

# Load Laravel .env variables
if [ -f /root/.env ]; then
  set -a
  . /root/.env
  set +a
fi

MODEL="${EGG_AI_MODEL:-deepseek-r1:8b}"
BASE_URL="http://localhost:11434"

echo "Starting Ollama server..."
/usr/bin/ollama serve &
OLLAMA_PID=$!

echo "Waiting for Ollama API..."
until curl -s $BASE_URL/api/models/$MODEL >/dev/null 2>&1; do
  echo "Waiting for Ollama to load model..."
  sleep 2
done

echo "Preloading model $MODEL..."
curl -s -X POST $BASE_URL/api/generate \
     -H "Content-Type: application/json" \
     -d "{\"model\":\"$MODEL\",\"prompt\":\"\"}" >/dev/null

echo "Model $MODEL preloaded. Ollama server is ready."
wait $OLLAMA_PID
