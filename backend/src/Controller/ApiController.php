<?php

require_once __DIR__ . '/../Repository/CommentRepository.php';
require_once __DIR__ . '/../Service/LLMService.php';

class ApiController {
    private CommentRepository $repository;
    private LLMService $llmService;

    public function __construct() {
        $this->repository = new CommentRepository();
        $this->llmService = new LLMService();
    }

    public function handleRequest(string $method, string $uri): void {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *'); // For demo purposes
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        if ($method === 'OPTIONS') {
            exit(0);
        }

        try {
            if ($method === 'GET' && $uri === '/api/comments') {
                $this->getComments();
            } elseif ($method === 'POST' && $uri === '/api/analyze') {
                $this->analyzeComments();
            } elseif ($method === 'POST' && $uri === '/api/reset') {
                $this->resetDataForDemo();
            } elseif ($method === 'POST' && $uri === '/api/generate-response') {
                $this->generateResponse();
            } elseif ($method === 'POST' && $uri === '/api/submit-action') {
                $this->submitAction();
            } elseif ($method === 'POST' && $uri === '/api/translate') {
                $this->translate();
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Not Found']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    private function getComments(): void {
        $comments = $this->repository->getAllComments();
        echo json_encode($comments);
    }

    private function analyzeComments(): void {
        $input = json_decode(file_get_contents('php://input'), true);
        $singleId = $input['id'] ?? null;

        if ($singleId) {
            $comment = $this->repository->getCommentById((int)$singleId);
            if (!$comment) {
                http_response_code(404);
                echo json_encode(['error' => 'Comment not found']);
                return;
            }
            $comments = [$comment];
        } else {
            $comments = $this->repository->getAllComments();
        }
        
        // Prepare comments for LLM (id and text only)
        $commentsForLlm = array_map(function($c) {
            return ['id' => $c['id'], 'text' => $c['text']];
        }, $comments);

        $analysisResults = $this->llmService->analyzeComments($commentsForLlm);

        foreach ($analysisResults as $commentId => $analysis) {
            $this->repository->saveAnalysis((int)$commentId, $analysis);
        }

        // Return updated comments
        $this->getComments();
    }

    private function resetDataForDemo(): void {
        $this->repository->resetDemo();
        $this->getComments();
    }

    private function generateResponse(): void {
        $input = json_decode(file_get_contents('php://input'), true);
        $commentId = $input['id'] ?? null;
        $responseType = $input['type'] ?? 'Custom';
        $language = $input['language'] ?? 'English';

        if (!$commentId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing comment ID']);
            return;
        }

        $comment = $this->repository->getCommentById((int)$commentId);
        if (!$comment) {
            http_response_code(404);
            echo json_encode(['error' => 'Comment not found']);
            return;
        }

        $responseText = $this->llmService->generateResponse($comment, $responseType, $language);
        echo json_encode(['response' => $responseText]);
    }

    private function submitAction(): void {
        $input = json_decode(file_get_contents('php://input'), true);
        $commentId = $input['id'] ?? null;
        $status = $input['status'] ?? null;
        $responseText = $input['response'] ?? null;

        if (!$commentId || !$status) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }

        $this->repository->saveAction((int)$commentId, $status, $responseText);
        $this->getComments();
    }

    private function translate(): void {
        $input = json_decode(file_get_contents('php://input'), true);
        $commentId = $input['id'] ?? null;

        if (!$commentId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing comment ID']);
            return;
        }

        $comment = $this->repository->getCommentById((int)$commentId);
        if (!$comment) {
            http_response_code(404);
            echo json_encode(['error' => 'Comment not found']);
            return;
        }

        $translatedText = $this->llmService->translateComment($comment['text']);
        $this->repository->saveTranslation((int)$commentId, $translatedText);
        
        $this->getComments();
    }
}
