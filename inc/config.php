<?php
// inc/config.php
declare(strict_types=1);

/**
 * Put your key here OR set it in Windows env var GROQ_API_KEY
 * Safer: env var
 */
const GROQ_API_KEY = "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"; // optional fallback, can leave empty

const GROQ_API_URL = "https://api.groq.com/openai/v1/chat/completions";
const GROQ_MODEL   = "llama-3.3-70b-versatile";
