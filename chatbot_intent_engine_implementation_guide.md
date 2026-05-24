# Chatbot Intent Engine Implementation Guide

## Objective

Implement the chatbot intent matching engine for the rule-based decision tree chatbot system.

The system must:
- accept free user input
- normalize user messages
- detect intents using phrases and keywords
- return the most relevant chatbot response
- provide confidence scoring
- support follow-up suggestions
- store unmatched messages for future improvements

This implementation must NOT use AI or ML models.

---

# System Architecture

```text
User Message
    ↓
Normalize Text
    ↓
Exact Phrase Matching
    ↓
Keyword Scoring
    ↓
Synonym Expansion
    ↓
Confidence Calculation
    ↓
Best Intent Selection
    ↓
Response + Suggestions
```

---

# Existing Database Tables

The following tables already exist:

- categories
- intents
- intent_phrases
- intent_keywords
- followup_intents
- chatbot_conversations
- unmatched_questions

Use these tables dynamically. Do NOT hardcode chatbot logic.

---

# Required Features

## 1. Text Normalization

Create a reusable normalization function.

### Requirements

Normalize incoming user messages by:

- converting to lowercase
- removing punctuation
- removing duplicate spaces
- trimming spaces

### Example

Input:

```text
"How do I Login to APPUI??"
```

Output:

```text
"how do i login to appui"
```

### Laravel Example

```php
public function normalizeText(string $text): string
{
    $text = strtolower($text);

    $text = preg_replace('/[^a-z0-9\s]/', '', $text);

    $text = preg_replace('/\s+/', ' ', $text);

    return trim($text);
}
```

---

# 2. Exact Phrase Matching

Before keyword scoring, check whether the normalized user message exactly matches a phrase in the database.

### Flow

```text
normalized_message
    ↓
search intent_phrases.normalized_phrase
```

If found:
- immediately return the associated intent
- set confidence to 100

### Example SQL

```sql
SELECT *
FROM intent_phrases
WHERE normalized_phrase = ?
LIMIT 1
```

---

# 3. Keyword Scoring Engine

If no exact match is found:

- tokenize the message
- compare against intent keywords
- calculate scores

Each keyword has a weight.

### Example

User message:

```text
i cannot login to appui
```

Detected keywords:

```text
login
appui
```

Intent scores:

| Intent | Score |
|---|---|
| access_platform | 15 |
| reset_password | 4 |

Highest score wins.

---

# 4. Scoring Rules

## Keyword Match

If message contains keyword:

```php
$score += $keyword->weight;
```

## Multiple Matches

Accumulate scores.

Example:

```text
login = 5
appui = 10
```

Total:

```text
15
```

---

# 5. Confidence Calculation

Calculate confidence score based on:

```text
winning_score / total_possible_score
```

### Example

| Intent | Score |
|---|---|
| access_platform | 15 |
| reset_password | 6 |

Confidence:

```text
15 / (15 + 6) = 71%
```

---

# 6. Ambiguous Intent Handling

If multiple intents have similar scores:

Do NOT automatically choose one.

Instead return clarification options.

### Example

```json
{
  "type": "clarification",
  "message": "I found multiple related topics. Which one do you mean?",
  "options": [
    "Create account",
    "Reset password"
  ]
}
```

### Threshold Recommendation

If:

```text
difference between top 2 scores < 20%
```

trigger clarification flow.

---

# 7. Follow-up Suggestions

After returning a response:

- load followup intents
- include suggestion buttons

### Example Response

```json
{
  "text": "You can access APPUI from...",
  "suggestions": [
    "Create account",
    "Forgot password",
    "Login issues"
  ]
}
```

---

# 8. Unmatched Message Handling

If no intent reaches minimum confidence:

- store message in unmatched_questions
- return fallback response

### Example

```json
{
  "type": "fallback",
  "message": "I couldn't find a matching topic.",
  "suggestions": [
    "Create account",
    "Login help",
    "Contact support"
  ]
}
```

---

# 9. Conversation Logging

Store all chatbot interactions.

### Required Data

- session_id
- original_message
- normalized_message
- matched_intent_id
- confidence_score
- bot_response
- timestamp

This data will be used for:
- analytics
- chatbot improvements
- failed intent detection

---

# 10. Synonym Support

Implement synonym expansion.

### Example Table

| word | synonym |
|---|---|
| login | signin |
| login | access |
| create | register |

### Flow

Before scoring:
- expand synonyms
- include synonym matches

Example:

```text
signin
```

should behave like:

```text
login
```

---

# 11. API Endpoint

Create chatbot API endpoint.

## Endpoint

```http
POST /api/chatbot/message
```

## Request

```json
{
  "message": "How do I login to APPUI?"
}
```

## Success Response

```json
{
  "intent": "access_platform",
  "confidence": 92,
  "response": {
    "text": "You can access APPUI anytime from..."
  },
  "suggestions": [
    "Create account",
    "Forgot password"
  ]
}
```

---

# 12. Service Architecture

Implement the matching logic inside:

```text
app/Services/IntentMatcher.php
```

Do NOT place matching logic directly inside controllers.

---

# Recommended Laravel Structure

```text
app/
├── Http/
│   └── Controllers/
│       └── ChatbotController.php
│
├── Services/
│   └── IntentMatcher.php
│
├── Models/
│   ├── Intent.php
│   ├── IntentPhrase.php
│   ├── IntentKeyword.php
│   └── FollowupIntent.php
```

---

# Important Requirements

## DO NOT

### ❌ Hardcode chatbot responses

All responses must come from database.

---

### ❌ Use giant if/else blocks

Bad:

```php
if(str_contains($message, 'login'))
```

---

### ❌ Use only SQL LIKE queries

Use weighted scoring.

---

# Recommended Enhancements (Phase 2)

After MVP is complete:

- fuzzy search
- typo tolerance
- multilingual support
- contextual conversation state
- search engine integration
- WhatsApp integration

---

# MVP Completion Criteria

The implementation is complete when:

- normalization works
- exact phrase matching works
- keyword scoring works
- confidence scores work
- unmatched flow works
- suggestions work
- API endpoint works
- conversation logging works

---

# Expected Result

The chatbot should be able to:

- understand multiple variations of user questions
- return accurate predefined responses
- suggest related topics
- continuously improve from unmatched questions
- operate entirely without AI/ML services
