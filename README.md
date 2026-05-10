# Expensa — AI-driven expense management

A simplified expense management app:

- **OCR receipts** with Mistral's vision model (Pixtral) — extracts merchant, total, tax, currency, line items, category, payment method.
- **Policies in plain English** — write rules like _"Meals over $75 per person require manager approval"_; Mistral evaluates each expense against each policy and returns `pass` / `fail` / `needs_approval` / `not_applicable` with a reason.
- **Clean architecture** — services depend on interfaces (`OcrServiceInterface`, `PolicyEvaluatorInterface`), so swapping providers (Tesseract, OpenAI, an offline DSL) is a one-binding change in `AppServiceProvider`.

## Stack

- **Backend** — Laravel 12 (PHP 8.2), SQLite, local file storage for receipts.
- **Frontend** — Next.js 16 (App Router) + React 19 + Tailwind v4.
- **AI** — Mistral API (`pixtral-12b-2409` for OCR, `mistral-small-latest` for policy reasoning). Configurable via env.

## Layout

```
backend/
  app/
    Http/Controllers/Api/   # ReceiptController, ExpenseController, PolicyController
    Models/                 # Expense, Policy
    Repositories/           # Eloquent persistence boundary
    Services/
      Mistral/              # MistralClient (HTTP wrapper)
      Ocr/                  # OcrServiceInterface + MistralOcrService
      PolicyEngine/         # PolicyEvaluatorInterface, MistralPolicyEvaluator,
                            #   PolicyEvaluationService (runs all active policies)
  config/mistral.php
  routes/api.php
  database/migrations/      # expenses, policies
frontend/
  src/
    app/                    # /, /policies, /expenses/[id]
    components/             # nav, upload-form, expense-row, policy-manager, ...
    lib/                    # api client, types, formatters
```

## Setup

### 1. Backend

```powershell
cd backend
# .env was created during scaffold; just add your key:
#   MISTRAL_API_KEY=sk-...
php artisan migrate           # already run during scaffold; re-run if you reset
php artisan storage:link      # already run during scaffold
php artisan serve             # http://127.0.0.1:8000
```

Required env vars (in `backend/.env`):

```
MISTRAL_API_KEY=             # required for OCR + policy evaluation
MISTRAL_BASE_URL=https://api.mistral.ai/v1
MISTRAL_VISION_MODEL=pixtral-12b-2409
MISTRAL_CHAT_MODEL=mistral-small-latest
FRONTEND_URL=http://localhost:3000   # used by CORS
```

### 2. Frontend

```powershell
cd frontend
# .env.local already points to http://localhost:8000
npm run dev                   # http://localhost:3000
```

## API

| Method | Path                              | Purpose                                                       |
| ------ | --------------------------------- | ------------------------------------------------------------- |
| POST   | `/api/receipts`                   | multipart `receipt=@file.jpg` → extract, persist, evaluate    |
| GET    | `/api/expenses`                   | list expenses (most recent first)                             |
| GET    | `/api/expenses/{id}`              | single expense incl. policy results and receipt URL           |
| PATCH  | `/api/expenses/{id}`              | edit extracted fields                                         |
| POST   | `/api/expenses/{id}/reevaluate`   | re-run all active policies                                    |
| DELETE | `/api/expenses/{id}`              | delete (also removes the stored image)                        |
| GET    | `/api/policies`                   | list policies                                                 |
| POST   | `/api/policies`                   | `{ name, rule_text, active? }`                                |
| PATCH  | `/api/policies/{id}`              | update name / rule_text / active                              |
| DELETE | `/api/policies/{id}`              | delete                                                        |

## How a receipt becomes an expense

1. User uploads an image. Laravel stores it in `storage/app/public/receipts/`.
2. `MistralOcrService` base64-encodes it, sends a Pixtral vision call with a strict JSON-only system prompt, normalizes the response.
3. The `Expense` row is created with the extracted fields plus `raw_extraction` (the full model JSON, kept for audit).
4. `PolicyEvaluationService` loops over every active `Policy` and asks `mistral-small-latest` to judge it; results are stored on the expense as `policy_results` and rolled up into `status` (`approved` / `needs_approval` / `rejected` / `pending`).
5. The expense detail page shows the original image alongside the extracted fields, line items, and per-policy verdicts.

## Swapping the AI provider

Both AI calls go through interfaces — change one binding in `app/Providers/AppServiceProvider.php` to swap implementations:

```php
$this->app->bind(OcrServiceInterface::class, TesseractOcrService::class);
$this->app->bind(PolicyEvaluatorInterface::class, RegexPolicyEvaluator::class);
```

No controller, repository, or frontend code needs to change.
