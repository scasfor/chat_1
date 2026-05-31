# General Conversation Integration Guide

# Objective

Implement a general conversation layer for the chatbot system.

The chatbot currently supports:
- categories
- intents
- keyword matching
- decision-tree workflows
- AI fallback integration

This guide explains how to integrate conversational interactions such as:
- greetings
- thanks
- goodbyes
- confirmations
- help requests
- fallback responses

without breaking the structured business workflow.

---

# Why General Conversation Matters

Without conversational handling:

User:
```text
hello
```

Bot:
```text
I couldn't find a matching topic.
```

This creates a poor user experience.

General conversation support makes the chatbot feel:
- more natural
- more interactive
- more human-like
- easier to use

even without full AI conversation capabilities.

---

# Recommended Architecture

```text
User Message
    ↓
General Conversation Detection
    ↓
Business Intent Detection
    ↓
Fallback Response
```

---

# Recommended Intent Categories

Create a dedicated category:

```text
General Conversation
```

This category should contain all conversational intents.

---

# Recommended General Conversation Intents

| Intent Key | Purpose |
|---|---|
| greeting | hello / hi |
| goodbye | bye / see you |
| thanks | thank you |
| help | user asking for help |
| yes_confirmation | yes / okay |
| no_confirmation | no / cancel |
| fallback | unmatched input |

---

# Example Intent Structure

## Greeting Intent

### intents table

| field | value |
|---|---|
| intent_key | greeting |
| title | Greeting |
| category | General Conversation |

---

### intent_phrases

```text
hello
hi
hey
good morning
good evening
hola
yo
```

---

### response

```json
{
  "text": "Hello 👋 How can I help you today?",
  "suggestions": [
    "Create Account",
    "Login Help",
    "Reset Password"
  ]
}
```

---

# Goodbye Intent Example

## intent_key

```text
goodbye
```

---

## phrases

```text
bye
goodbye
see you
talk later
have a nice day
```

---

## response

```json
{
  "text": "Goodbye 👋 Feel free to return anytime if you need help."
}
```

---

# Thanks Intent Example

## phrases

```text
thanks
thank you
appreciate it
thanks a lot
```

---

## response

```json
{
  "text": "You're welcome 😊"
}
```

---

# Help Intent Example

## phrases

```text
help
i need help
support
assist me
can you help me
```

---

## response

```json
{
  "text": "I can help you with APPUI support topics.",
  "suggestions": [
    "Access Platform",
    "Create Account",
    "Login Issues",
    "Reset Password"
  ]
}
```

---

# Recommended Processing Priority

General conversation intents should run BEFORE business intent matching.

Recommended flow:

```text
1. Normalize message
2. Check general conversation intents
3. Check business intents
4. AI fallback
5. Default fallback
```

---

# Why Priority Matters

Example:

```text
hi i cant login
```

The chatbot should:
- detect greeting
- continue detecting login issue

NOT stop at greeting only.

---

# Recommended Hybrid Handling

If greeting + business intent exist:

## Example

User:
```text
hello i need login help
```

Bot:
```text
Hello 👋 I can help with login issues.

[ Forgot Password ]
[ Access Platform ]
```

---

# Suggested Database Structure

Use the same intents system.

DO NOT create separate hardcoded logic.

All conversational intents should live inside:
- intents table
- intent_phrases table
- followup_intents table

---

# Recommended Intent Priority Column

Add:

```sql
ALTER TABLE intents
ADD COLUMN priority INT DEFAULT 1;
```

---

# Suggested Priority Values

| Type | Priority |
|---|---|
| greeting | 100 |
| thanks | 100 |
| goodbye | 100 |
| business intents | 50 |
| fallback | 1 |

Higher priority runs first.

---

# Recommended Fallback Strategy

Never respond with:

```text
I don't understand.
```

Instead:

```json
{
  "text": "I'm here to help with APPUI support topics.",
  "suggestions": [
    "Create Account",
    "Access Platform",
    "Reset Password",
    "Contact Support"
  ]
}
```

---

# Recommended Clarification Flow

If the message is ambiguous:

Example:

```text
account issue
```

Bot:

```json
{
  "type": "clarification",
  "message": "What kind of account issue are you facing?",
  "options": [
    "Create Account",
    "Login Problem",
    "Reset Password"
  ]
}
```

---

# Conversation State Support

The chatbot should store:
- current intent
- current flow
- previous message
- session id

This improves conversational continuity.

---

# Example Conversation

## Greeting

User:
```text
hello
```

Bot:
```text
Hello 👋 How can I help you today?
```

---

## Mixed Intent

User:
```text
hi i forgot my password
```

Bot:
```text
Hello 👋 I can help you reset your password.

[ Reset Password ]
[ Login Issues ]
```

---

## Goodbye

User:
```text
bye
```

Bot:
```text
Goodbye 👋 Have a great day.
```

---

# Recommended API Response Structure

```json
{
  "intent": "greeting",
  "confidence": 98,
  "response": {
    "text": "Hello 👋 How can I help you today?"
  },
  "suggestions": [
    "Create Account",
    "Login Help"
  ]
}
```

---

# Recommended Engineering Rules

## DO NOT

### ❌ Hardcode greetings in frontend

### ❌ Use giant if/else blocks

### ❌ Mix conversation logic with controllers

---

# Recommended Architecture

```text
app/
├── Services/
│   ├── IntentMatcher.php
│   ├── ConversationEngine.php
│   ├── AIIntentAgent.php
│   └── ConversationLogger.php
```

---

# Recommended ConversationEngine Responsibilities

The ConversationEngine should:
- detect conversational intents
- manage greetings
- manage confirmations
- handle session flow
- merge conversation + business intents

---

# AI Integration Recommendation

General conversation can optionally use AI for:
- paraphrase understanding
- typo correction
- multilingual greetings

BUT:
- final responses should still come from database intents

---

# Recommended Future Enhancements

## Phase 1
- greetings
- thanks
- goodbye
- help

---

## Phase 2
- confirmations
- contextual replies
- conversation memory

---

## Phase 3
- multilingual support
- emotional tone detection
- smart conversational routing

---

# Final Recommendation

The chatbot should behave like:

```text
Structured Support Bot
+
Light Conversational Layer
```

NOT:

```text
Fully Open AI Chatbot
```

This provides:
- predictable behavior
- professional support flow
- lower AI costs
- maintainable architecture
- better user experience

---

# Expected Final Result

The chatbot should:
- greet users naturally
- handle basic conversation smoothly
- continue structured support flows
- provide intelligent fallback responses
- maintain business workflow control
