<?php

namespace Aftandilmmd\WorkflowAutomation\Enums;

enum AiProvider: string
{
    case OpenAi    = 'openai';
    case Anthropic = 'anthropic';
    case Gemini    = 'gemini';
    case Groq      = 'groq';
    case Mistral   = 'mistral';
    case DeepSeek  = 'deepseek';
    case Ollama    = 'ollama';
    case XAi       = 'xai';
    case Cohere    = 'cohere';
}
