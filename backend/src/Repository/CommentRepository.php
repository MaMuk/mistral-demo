<?php

require_once __DIR__ . '/../Database.php';

class CommentRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->initializeSchema();
    }

    private function initializeSchema(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS comments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                text TEXT NOT NULL,
                status TEXT DEFAULT 'unreviewed',
                translated_text TEXT
            )
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS analyses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                comment_id INTEGER NOT NULL,
                detected_language TEXT,
                topic TEXT,
                sentiment TEXT,
                urgency TEXT,
                requires_response TEXT,
                inappropriate_content TEXT,
                explanation_json TEXT,
                FOREIGN KEY (comment_id) REFERENCES comments(id)
            )
        ");

        // Seed if empty
        $stmt = $this->db->query("SELECT COUNT(*) FROM comments");
        if ($stmt->fetchColumn() == 0) {
            $this->seedData();
        }

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS responses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                comment_id INTEGER NOT NULL,
                text TEXT NOT NULL,
                FOREIGN KEY (comment_id) REFERENCES comments(id)
            )
        ");
    }

    private function seedData(): void {
        $mockComments = [

        // German comments
        "Die neue Benutzeroberfläche ist völlig unübersichtlich.", // The new interface is completely confusing.
        "Ich kann meine gespeicherten Filter nicht mehr finden.", // I can't find my saved filters anymore.
        "Der Export funktioniert nicht, bitte beheben.", // The export doesn't work, please fix it.
        "Super, dass die Barrierefreiheit verbessert wurde!", // Great that accessibility has been improved!
        "Warum dauert das Hochladen von Dateien so lange?", // Why does uploading files take so long?
        "Dieses scheiß System stürzt ständig ab!", // This damn system crashes constantly!

        // English comments
        "Great job on the accessibility updates! It's much easier to use with a screen reader now.",
        "I appreciate the faster load times, but the font size is too small on mobile.",
        "Who designed this? It looks like it was made in 1990. Fix it.",
        "Can we get a dark mode? My eyes hurt after staring at this all day.",
        "You guys are idiots. This update ruined my workflow.",

        // Serbo-Croatian comments
        "Ne mogu da pronađem dugme za izvoz podataka.", // I can't find the export button.
        "Sistem često pada kada pokušam da otpremim fajl.", // The system crashes often when I try to upload a file.
        "Brzina učitavanja stranice je prihvatljiva, ali font je premali.", // Page load speed is acceptable, but the font is too small.
        "Ovaj prokleti interfejs je katastrofa!", // This damned interface is a disaster!

        // Turkish comments
        "Yeni arayüz kafa karıştırıcı ve zor anlaşılıyor.", // The new interface is confusing and hard to understand.
        "Yükleme işlemi her seferinde hata veriyor.", // The upload process fails every time.
        "Bu lanet uygulama çalışmıyor!", // This damned app doesn’t work!


    ];

        $stmt = $this->db->prepare("INSERT INTO comments (text) VALUES (:text)");
        foreach ($mockComments as $text) {
            $stmt->execute([':text' => $text]);
        }
    }

    public function getAllComments(): array {
        $stmt = $this->db->query("
            SELECT c.*, a.detected_language, a.topic, a.sentiment, a.urgency, a.requires_response, a.inappropriate_content, a.explanation_json, r.text as response_text
            FROM comments c 
            LEFT JOIN analyses a ON c.id = a.comment_id
            LEFT JOIN responses r ON c.id = r.comment_id
        ");
        return $stmt->fetchAll();
    }

    public function getCommentById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT c.*, a.detected_language, a.topic, a.sentiment, a.urgency, a.requires_response, a.inappropriate_content, a.explanation_json, r.text as response_text
            FROM comments c 
            LEFT JOIN analyses a ON c.id = a.comment_id
            LEFT JOIN responses r ON c.id = r.comment_id
            WHERE c.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function saveAnalysis(int $commentId, array $analysis): void {
        // Clear existing analysis for this comment to avoid duplicates in this simple demo
        $stmt = $this->db->prepare("DELETE FROM analyses WHERE comment_id = :comment_id");
        $stmt->execute([':comment_id' => $commentId]);

        $stmt = $this->db->prepare("
            INSERT INTO analyses (comment_id, detected_language, topic, sentiment, urgency, requires_response, inappropriate_content, explanation_json)
            VALUES (:comment_id, :detected_language, :topic, :sentiment, :urgency, :requires_response, :inappropriate_content, :explanation_json)
        ");
        
        $stmt->execute([
            ':comment_id' => $commentId,
            ':detected_language' => $analysis['detected_language'] ?? 'Unknown',
            ':topic' => $analysis['topic'] ?? 'Unknown',
            ':sentiment' => $analysis['sentiment'] ?? 'Unknown',
            ':urgency' => $analysis['urgency'] ?? 'Unknown',
            ':requires_response' => $analysis['requires_response'] ?? 'Unknown',
            ':inappropriate_content' => $analysis['inappropriate_content'] ?? 'None',
            ':explanation_json' => json_encode($analysis['explanation'] ?? [])
        ]);
    }

    public function resetDemo(): void {
        $this->db->exec("DELETE FROM analyses");
        $this->db->exec("DELETE FROM responses");
        $this->db->exec("UPDATE comments SET status = 'unreviewed', translated_text = NULL");
    }

    public function saveAction(int $commentId, string $status, ?string $responseText): void {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE comments SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $status, ':id' => $commentId]);

            if ($responseText) {
                // Remove old response if exists
                $stmt = $this->db->prepare("DELETE FROM responses WHERE comment_id = :comment_id");
                $stmt->execute([':comment_id' => $commentId]);

                $stmt = $this->db->prepare("INSERT INTO responses (comment_id, text) VALUES (:comment_id, :text)");
                $stmt->execute([':comment_id' => $commentId, ':text' => $responseText]);
            }
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function saveTranslation(int $commentId, string $translatedText): void {
        $stmt = $this->db->prepare("UPDATE comments SET translated_text = :translated_text WHERE id = :id");
        $stmt->execute([':translated_text' => $translatedText, ':id' => $commentId]);
    }
}
