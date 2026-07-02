# COI-Bot Architecture & Deployment Guide

This document provides a comprehensive overview of the COI-Bot architecture, focusing on the Intent Matching Engine, and includes a complete deployment guide for both the backend administrative panel and the frontend chatbot widget.

---

## 1. Code & Intent Matching Engine Architecture

The Intent Matching Engine is the core of COI-Bot, responsible for understanding user queries and mapping them to predefined conversational or business intents. The system is built using a hybrid rule-based and keyword-scoring approach, enhanced by AI-generated intent data.

### 1.1 Core Components

- **`IntentMatcher` (`app/Services/IntentMatcher.php`)**: The primary orchestrator for processing user messages.
- **`ConversationEngine` (`app/Services/ConversationEngine.php`)**: Handles general conversational layers (e.g., greetings, goodbyes, confirmations) and hybrid responses.
- **`GeminiService` (`app/Services/GeminiService.php`)**: Integrates with the Gemini 2.5 Flash API to automatically generate training phrases and weighted keywords for business intents.
- **`ChatbotController` (`app/Http/Controllers/ChatbotController.php`)**: The API endpoint that receives messages from the frontend widget and returns the matched response.

### 1.2 The Matching Pipeline (`IntentMatcher::match`)

When a user sends a message, the text undergoes a multi-step evaluation pipeline:

#### Step 1: Text Normalization
The input string is normalized using `IntentPhrase::normalize($text)`, which handles lowercase conversion, punctuation stripping, and basic sanitization to ensure consistent matching.

#### Step 2: Exact Match (High Priority)
The engine first checks for an exact match against predefined phrases or intent titles. 
- If an exact match is found, the system immediately returns a 100% confidence response.
- Only active intents are considered, and they are ordered by a `priority` flag.

#### Step 3: Tokenization & Synonym Expansion
If no exact match is found:
- The normalized message is split into tokens (words longer than 1 character).
- **Synonym Expansion**: Tokens are mapped against the `Synonym` database model. If a token matches a known synonym, the base word is appended to the token array, increasing the chances of a keyword match.

#### Step 4: Conversational Intent Detection
The `ConversationEngine` analyzes the tokens to detect general conversational intents (e.g., `greeting`, `help`, `thanks`). 
- This ensures the bot can respond to natural dialogue gracefully without forcing a business logic fallback.

#### Step 5: Business Keyword Scoring
Tokens are evaluated against all active business intents using a weighted keyword scoring algorithm:
- Each intent has associated keywords, with weights ranging from 1 to 10.
- If a token matches an intent's keyword, its weight is added to that intent's score.
- **Confidence Calculation**: The winning intent's confidence is calculated as `(Winning Score / Total Score of all matched intents) * 100`.
- **Ambiguity Check**: If the confidence is >= 40% (Threshold), the system checks the gap between the top two scoring intents. If the difference is less than 20%, it triggers a **Clarification State**, asking the user to choose between the ambiguous topics (e.g., "I found multiple related topics. Which one do you mean?").

#### Step 6: Decision Tree & Response Construction
Based on the detected intents, the engine makes a final routing decision:
1. **Hybrid Response**: If the user sends a conversational phrase alongside a business query (e.g., "Hello, how do I reset my password?"), the engine combines the responses (e.g., "Hello! 👋 Here is how to reset your password...").
2. **Pure Conversational**: Matches only a conversational intent (100% confidence).
3. **Pure Business**: Matches a business intent above the 40% threshold.
4. **Fallback**: If no intents match or confidence is below 40%, the message is logged as an `UnmatchedQuestion` for future administrator review, and a fallback response with high-priority business suggestions is returned.

### 1.3 AI-Powered Intent Generation (`GeminiService`)
To simplify bot configuration, administrators can input a Question and Response. The `GeminiService` then prompts the `gemini-2.5-flash` model to automatically generate:
- 5 to 8 natural language phrase variations.
- 5 to 10 weighted keywords (1-10 scale) tailored to the intent.
This data directly feeds into the matching pipeline, significantly reducing manual data entry and drastically improving the matching scope without requiring exhaustive human configuration.

---

## 2. Deployment Guide

The COI-Bot project consists of two separate applications:
1. **`bot-admin`**: A Laravel (v13+) API backend and Filament-powered administrative panel.
2. **`chat-bot`**: An embeddable React chatbot component library built with Vite.

### 2.1 Backend Deployment (`bot-admin`)

#### Prerequisites
- PHP 8.3 or higher
- Composer
- Node.js (v18+) and npm
- A relational database (MySQL/PostgreSQL/SQLite)

#### Installation Steps
1. **Navigate to the backend directory:**
   ```bash
   cd bot-admin
   ```
2. **Install PHP dependencies:**
   ```bash
   composer install --optimize-autoloader --no-dev
   ```
3. **Environment Setup:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Edit the `.env` file with your production database credentials and application URL.

4. **Database Migrations:**
   Run the database migrations and seeders to set up the schema and default categories/intents.
   ```bash
   php artisan migrate --force --seed
   ```
   *(If you have a seeder configured for defaults, you can append `--seed`)*

5. **Install Frontend Assets (Filament & Vite):**
   ```bash
   npm install --ignore-scripts
   npm run build
   ```
6. **Configure Gemini API:**
   Ensure you set your Google Gemini API key in the Filament Settings panel (this uses the `Setting` model in the database) to enable automated phrase and keyword generation.

### 2.2 Frontend Deployment (`chat-bot` Component Library)

The frontend is a reusable React component library that can be integrated into any existing React application.

#### Prerequisites
- Node.js 18 or higher
- npm 9 or higher

#### Building the Library
1. **Navigate to the frontend directory:**
   ```bash
   cd chat-bot
   ```
2. **Install dependencies:**
   ```bash
   npm install
   ```
3. **Build the distributable files:**
   ```bash
   npm run build
   ```
   This generates the ES Module (`dist/index.es.js`), CommonJS (`dist/index.cjs.js`), and TypeScript type declarations (`dist/index.d.ts`).

#### Integrating into a Host Application
To use the widget in a production React application:

1. Copy the built package or publish it to a private npm registry.
2. Install it in your target React app.
3. Import and configure the component:

```tsx
import { ChatBot } from 'coi-chatbot';
import type { ChatConfig } from 'coi-chatbot';

const config: ChatConfig = {
  // Point to your deployed Laravel API endpoint
  apiUrl: 'https://admin.yourdomain.com/api/chatbot/message',
  botName: 'Support Assistant',
  inputPlaceholder: 'Ask a question...',
  welcomeMessage: 'Hello! How can I help you today?',
};

export default function App() {
  return <ChatBot config={config} />;
}
```

> **Note**: `react` and `react-dom` are peer dependencies and must be provided by the host application.

### 2.3 Local Development Demo
To test the widget against your local backend:
1. Ensure the Laravel dev server is running (`php artisan serve` on port 8000).
2. Inside the `chat-bot` directory, run:
   ```bash
   npm run dev
   ```
3. This spins up a Vite dev server at `http://localhost:5173` with a playground rendering the chatbot connected to `http://localhost:8000/api/chatbot/message`.
