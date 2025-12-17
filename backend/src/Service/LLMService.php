<?php

/**
 * LLM Service for Comment Triage
 * 
 * This service provides AI-assisted analysis for public sector comment management.
 * All processing is done locally via Ollama to ensure data privacy.
 * The LLM acts as an assistant - all decisions remain with human operators.
 */
class LLMService {
    private string $ollamaUrl = 'http://localhost:11434/api/chat';
    private string $model = 'mistral';
    private string $TRANSLATE_TO = 'German';

    // =========================================================================
    // JSON SCHEMAS FOR STRUCTURED OUTPUTS
    // =========================================================================

    /**
     * Schema for comment analysis output.
     * Ensures consistent, parseable responses for the triage workflow.
     */
    private function getAnalysisSchema(): array {
        return [
            'type' => 'object',
            'additionalProperties' => [
                'type' => 'object',
                'properties' => [
                    'detected_language' => [
                        'type' => 'string',
                        'description' => 'ISO 639-1 language code of the comment (e.g., en, de, hr, tr, sr)'
                    ],
                    'topic' => [
                        'type' => 'string',
                        'enum' => [
                            'Service Complaint',
                            'Information Request',
                            'Praise',
                            'Policy Feedback',
                            'Accessibility Issue',
                            'Technical Problem',
                            'Suggestion',
                            'Other'
                        ]
                    ],
                    'sentiment' => [
                        'type' => 'string',
                        'enum' => ['Positive', 'Negative', 'Neutral']
                    ],
                    'urgency' => [
                        'type' => 'string',
                        'enum' => ['High', 'Medium', 'Low']
                    ],
                    'requires_response' => [
                        'type' => 'string',
                        'enum' => ['Yes', 'No', 'Maybe']
                    ],
                    'inappropriate_content' => [
                        'type' => 'string',
                        'enum' => ['None', 'Profanity', 'Hate Speech', 'Threatening', 'Personal Attack', 'Spam']
                    ],
                    'explanation' => [
                        'type' => 'string'
                    ]
                ],
                'required' => [
                    'detected_language',
                    'topic',
                    'sentiment',
                    'urgency',
                    'requires_response',
                    'inappropriate_content',
                    'explanation'
                ]
            ]
        ];
    }

    /**
     * Schema for response generation output.
     */
    private function getResponseSchema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'response_text' => [
                    'type' => 'string',
                    'description' => 'The drafted response to the user'
                ],
                'tone_used' => [
                    'type' => 'string',
                    'enum' => ['Formal', 'Friendly', 'Empathetic', 'Neutral']
                ],
                'follow_up_suggested' => [
                    'type' => 'string',
                    'enum' => ['Yes', 'No']
                ]
            ],
            'required' => ['response_text', 'tone_used', 'follow_up_suggested']
        ];
    }

    /**
     * Schema for translation output.
     */
    private function getTranslationSchema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'source_language' => [
                    'type' => 'string',
                    'description' => 'Detected source language (ISO 639-1 code)'
                ],
                'translated_text' => [
                    'type' => 'string'
                ]
            ],
            'required' => ['source_language', 'translated_text']
        ];
    }

    // =========================================================================
    // CORE API METHODS
    // =========================================================================

    /**
     * Analyze comments for triage purposes.
     * Detects language, categorizes content, flags issues, and provides explanations.
     */
    public function analyzeComments(array $comments): array {
        $prompt = $this->buildAnalysisPrompt($comments);
        
        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $this->getAnalysisSystemPrompt()],
                ['role' => 'user', 'content' => $prompt]
            ],
            'stream' => false,
            'format' => $this->getAnalysisSchema(),
            'options' => [
                'temperature' => 0.1  // Low temperature for consistent analysis
            ]
        ];

        $response = $this->callOllama($payload);
        return $this->parseJsonResponse($response);
    }

    /**
     * Generate a draft response to a user comment.
     * Staff reviews and edits before publishing.
     */
    public function generateResponse(array $comment, string $responseType, string $language = 'German'): string {
        $prompt = $this->buildResponsePrompt($comment, $responseType, $language);
        
        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $this->getResponseSystemPrompt()],
                ['role' => 'user', 'content' => $prompt]
            ],
            'stream' => false,
            'format' => $this->getResponseSchema(),
            'options' => [
                'temperature' => 0.7  // Slightly higher for natural-sounding responses
            ]
        ];

        $response = $this->callOllama($payload);
        $parsed = $this->parseJsonResponse($response);
        
        return $parsed['response_text'] ?? 'Failed to generate response.';
    }

    /**
     * Translate comment text to the configured target language.
     */
    public function translateComment(string $text): string {
        $prompt = $this->buildTranslationPrompt($text);
        
        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $this->getTranslationSystemPrompt()],
                ['role' => 'user', 'content' => $prompt]
            ],
            'stream' => false,
            'format' => $this->getTranslationSchema(),
            'options' => [
                'temperature' => 0.2
            ]
        ];

        $response = $this->callOllama($payload);
        $parsed = $this->parseJsonResponse($response);
        
        return $parsed['translated_text'] ?? 'Failed to translate.';
    }

    // =========================================================================
    // SYSTEM PROMPTS
    // =========================================================================

    private function getAnalysisSystemPrompt(): string {
        return <<<EOT
You analyze user feedback for a public institution. Detect the language first, then analyze content IN THAT LANGUAGE for inappropriate content.

Topics: Service Complaint, Information Request, Praise, Policy Feedback, Accessibility Issue, Technical Problem, Suggestion, Other
Urgency: High (safety/legal/vulnerable), Medium (needs follow-up), Low (general)
EOT;
    }

    private function getResponseSystemPrompt(): string {
        return <<<EOT
Draft professional, empathetic responses for a public institution. Be warm but formal. Staff will review before sending.
EOT;
    }

    private function getTranslationSystemPrompt(): string {
        return <<<EOT
Translate accurately, preserving tone. If content is inappropriate, translate literally.
EOT;
    }

    // =========================================================================
    // PROMPT BUILDERS
    // =========================================================================

    private function buildAnalysisPrompt(array $comments): string {
        $commentsJson = json_encode($comments, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return <<<EOT
Analyze the following user feedback comments. Each comment has an ID and text.

For each comment, provide:
- detected_language: ISO 639-1 code (e.g., "en", "de", "hr", "tr", "sr")
- topic: Category from the allowed list
- sentiment: Positive, Negative, or Neutral
- urgency: High, Medium, or Low
- requires_response: Whether this comment warrants a reply
- inappropriate_content: Type of problematic content, or "None"
- explanation: Brief reasoning for your assessment (2-3 sentences)

IMPORTANT: Detect inappropriate content in ANY language, not just English. Analyze the text in its original language before categorizing.

Comments to analyze:
$commentsJson
EOT;
    }

    private function buildResponsePrompt(array $comment, string $responseType, string $language): string {
        $analysisContext = "";
        if (isset($comment['topic'])) {
            $analysisContext = "Prior Analysis: Topic={$comment['topic']}, Sentiment={$comment['sentiment']}, Urgency=" . ($comment['urgency'] ?? 'Unknown');
        }

        return <<<EOT
Draft a response to this user comment.

Original Comment: "{$comment['text']}"
$analysisContext

Response Type: $responseType
- "Thank You": Acknowledge and express appreciation
- "Redirect": Politely direct to appropriate department/resource
- "Custom": Address the specific concern or question

Output Language: $language

Guidelines:
- Keep response concise but complete (2-4 sentences typically)
- Use appropriate formality for a public institution
- If the comment was negative, acknowledge the concern empathetically
- Do not make promises beyond providing information or escalating
EOT;
    }

    private function buildTranslationPrompt(string $text): string {
        return <<<EOT
Translate the following text to {$this->TRANSLATE_TO}.

Original text:
"$text"

Provide the translation and detect the source language.
EOT;
    }

    // =========================================================================
    // HTTP & PARSING UTILITIES
    // =========================================================================

    /**
     * Make HTTP request to Ollama API.
     */
    private function callOllama(array $payload): string {
        $ch = curl_init($this->ollamaUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);  // 5 minute timeout for slower models

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('Ollama connection error: ' . $error);
        }
        
        curl_close($ch);

        $data = json_decode($response, true);
        
        if (!isset($data['message']['content'])) {
            throw new Exception('Invalid response from Ollama: ' . substr($response, 0, 200));
        }

        return $data['message']['content'];
    }

    /**
     * Parse JSON response with fallback handling.
     */
    private function parseJsonResponse(string $jsonContent): array {
        $decoded = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Fallback: try to extract JSON if wrapped in markdown or extra text
            if (preg_match('/\{.*\}/s', $jsonContent, $matches)) {
                $decoded = json_decode($matches[0], true);
            }
        }

        if (!is_array($decoded)) {
            throw new Exception('Failed to parse LLM response as JSON: ' . substr($jsonContent, 0, 200));
        }

        return $decoded;
    }
}
