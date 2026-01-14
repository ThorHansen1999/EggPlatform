<?php

return [
    'slack_webhook_url' => env('EGG_SLACK_WEBHOOK_URL'),

    // Default to Ollama and deepseek-r1:8b if not provided via env
    'ai_provider' => env('EGG_AI_PROVIDER', 'ollama'),
    'ai_model' => env('EGG_AI_MODEL', 'deepseek-r1:8b'),

    // Base URL used by Prism Ollama provider; default to docker service name if running in containers
    'ollama_base_url' => env('EGG_AI_BASE_URL', env('PRISM_OLLAMA_BASE_URL', 'http://ollama:11434')),

];
