# Comment Triage Demo

This project demonstrates how to use a local Large Language Model (LLM) to assist institutional CMS workflows. It focuses on clarity, auditability, and explainability.

![Frontend Screenshot](Frontend_Screenshot.png)

## What this demo shows
- **Automated Triage**: Using an LLM to categorize and analyze feedback comments.
- **Privacy-First**: All data stays local; no external APIs are used.
- **Human-in-the-Loop**: The LLM acts as an assistant, providing analysis and explanations, not automated decisions.
- **Translating**: The LLM can translate comments to German.
- **Response**: The LLM can generate responses to comments.

## Why LLMs?
LLMs can process unstructured text data (like feedback comments) at scale, identifying themes, sentiment, and potential issues faster than manual review.

## Safety for Public Sector
- **Local Execution**: We use Ollama running locally, ensuring no data leaves the infrastructure.
- **Explainability**: The system requires the LLM to explain its reasoning for every analysis.
- **No Automated Actions**: The system only flags and categorizes; it does not delete or hide content automatically.

## How to Run Locally

### Prerequisites
- PHP 8.0+
- Node.js & npm
- [Ollama](https://ollama.com/) installed and running
- `mistral` model pulled (`ollama pull mistral`)

### Backend
1. Navigate to `backend`:
   ```bash
   cd backend
   ```
2. Start the built-in PHP server:
   ```bash
   php -S localhost:8000 -t public
   ```

### Frontend
1. Navigate to `frontend`:
   ```bash
   cd frontend
   ```
2. Install dependencies:
   ```bash
   npm install
   ```
3. Start the development server:
   ```bash
   npm run dev
   ```

### Usage
Open the frontend URL (usually `http://localhost:5173`) in your browser.

## License

This project is licensed under the ISC License - see the [LICENSE](LICENSE) file for details.