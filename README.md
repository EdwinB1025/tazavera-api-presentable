<p align="center">
  <img src="https://raw.githubusercontent.com/EdwinB1025/tazzavera-api/PROD/public/scribeIcon.svg" width="120">
</p>


REST backend for **Tazavera**, the specialty coffee verification platform. This API serves the data model, OAuth2/PKCE authentication, and the **consensus** engine that averages and cross-checks specialist evaluations of each coffee a specialty coffee shop offers.

> This repository is **the API only**. The front end (Livewire, D3, Leaflet, flavor wheel) lives in a separate repository.

## 🕵️ The problem it tackles

There's a lot of coffee labeled "specialty" that really isn't, and the everyday consumer has no way to tell — grading stays in the hands of a few, and that skews the market. Tazavera doesn't settle for yet another rating: it **verifies** what a coffee shop claims to sell. If it declares "fruity notes, high acidity, natural process," several specialists cup the actual product and the system derives a consensus that confirms or refutes that claim.

The evaluation system is based on the **SCA Coffee Value Assessment (CVA v2, provisional standard 2024–2025)**, adapting the formal cupping method to real coffee-shop conditions (coffee already brewed, without strict replication of the physical-cups protocol).

## 🧪 Evaluation model (overview)

- 🔬 **Descriptive (specialist)** — objective intensity record. Seven axes (fragrance, aroma, flavor, aftertaste, acidity, sweetness, mouthfeel) on a 0–15 scale, plus CATA descriptors from a hierarchical taxonomy and Main Tastes.
- ⭐ **Affective (specialist)** — the quality verdict, eight axes (the seven + overall) on a 1–9 scale. Derives a **cupping score 0–100** per evaluation and feeds the **aggregate consensus** at the offering level.
- 👤 **Consumer** — simple reaction mapped to the 1–9 scale. Role foreseen in the ENUM; its own flow remains in the backlog.

Specialist and consumer are **not averaged against each other** — they are cross-checked attribute by attribute; that's where the market-intelligence value lives.

### The consensus (this API's engine)

When an offering reaches **≥5 closed specialist evaluations**, closing an evaluation triggers — via event and a **queue worker** — the recalculation of the consensus (`OfferingConsensusService::recompute`):

- **Per-axis averages** (`*_avg`) and **aggregate cupping score** (CVA formula `0.65625·Σ + 52.75 − 2u − 4d`, rounded to 0.25).
- **Inter-specialist concordance** for the descriptive/affective parts (normalized dispersion index, `1 − σ/σ_max`), with per-axis detail in `axis_concordances`.
- **Consensus flavor tree** (`offering_tastes`): grouped descriptors, each with how many distinct specialists marked it.
- **`verification_status`**: `provisional` → `verified` once the threshold is reached.

The detail of the formulas, the threshold, and the reinterpretation of the deductions `u` (non-uniformity, derived from inter-specialist dispersion) and `d` (defects) is in the design documentation.

## 📚 Documentation

- 📖 **API docs:** [View documentation](https://edwinb1025.github.io/tazzavera-api-presetnable/public/docs/index.html)

The page includes example requests (bash, JavaScript), a Postman collection (`/docs/collection.json`), and an OpenAPI spec (`/docs/openapi.yaml`).

> 🔄 In case of a discrepancy between docs and code, **the code is the source of truth**.

## 🛠️ Stack

- 🐘 **Framework:** Laravel 13, PHP 8.5
- 🔐 **Auth:** Laravel Passport 13 (OAuth2 + PKCE) + Fortify (web session for the authorization flow)
- 🛡️ **Roles/permissions:** Spatie Laravel-Permission (guard `api`)
- 🗄️ **Database:** MySQL 8 (dev/prod) · SQLite `:memory:` (tests)
- ⚙️ **Queues:** `database` driver (consensus worker); `sync` in tests
- 🧪 **Tests:** Pest (TDD)

## ✅ Prerequisites

- PHP 8.5+ with Laravel's standard extensions + **`ext-sodium`** (required by Passport at runtime — see `deployment-notes.md`)
- Composer 2.x
- **MySQL 8** running and reachable
- Node.js 18+ and npm (only if compiling the minimal OAuth views)

## 🚀 Installation

### 1. Clone the repository

```bash
git clone https://github.com/EdwinB1025/tazzavera-api.git
cd tazzavera-api
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` to point to MySQL 8:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tazavera
DB_USERNAME=root
DB_PASSWORD=
QUEUE_CONNECTION=database
```

Create the empty database before migrating:

```bash
mysql -u root -p -e "CREATE DATABASE tazavera;"
```

### 4. Install Passport (OAuth2)

```bash
php artisan install:api --passport
php artisan passport:keys
```

> ⚠️ If `install:api --passport` fails and reverts `composer.json`, it's almost certainly `ext-sodium` being disabled — enable `extension=sodium` in `php.ini` and retry (details in `deployment-notes.md`).

Create a public (PKCE) client for the front end:

```bash
php artisan passport:client --public
```

### 5. Migrate and seed

```bash
php artisan migrate --seed
```

🌱 The seeder populates the olfactory taxonomy (from CSV, source WCR Sensory Lexicon), the coffee catalog, roasters, inventory, and base data to test the flow end to end.

## ▶️ Running the project

### Server

```bash
php artisan serve
```

The API is available at `http://localhost:8000`.

### Consensus worker

In dev, to process the consensus recalculation in the background:

```bash
php artisan queue:work
```

> Alternatively, `QUEUE_CONNECTION=sync` in `.env` runs jobs inline without a worker (simpler for development; you lose the decoupling). In production the worker is kept alive with Supervisor / Docker `restart: always` — see `deployment-notes.md`.

### Tests

```bash
php artisan test
```

They run against SQLite `:memory:` with `QUEUE_CONNECTION=sync`, so the consensus executes inline without needing a worker.

## 🔑 Authentication (OAuth2 / PKCE)

Login in three steps:

1. `POST /login` — creates the web session (Fortify); returns `{"two_factor": false}`, **without** a token.
2. `GET /oauth/authorize` — with that session + PKCE parameters, returns the `code` (302, consent skipped for the first-party client).
3. `POST /oauth/token` — exchanges `code` + `code_verifier` for `access_token` + `refresh_token`.

The `access_token` authenticates the `auth:api` routes via `Authorization: Bearer`. Two scopes: `profile:read` (default) and `profile:write` (step-up for sensitive actions). Full detail in `endpoints.md`.

## 📊 Implementation status

**API complete.** All MVP endpoints are built and covered by Pest tests:

| Piece | Status |
|---|---|
| OAuth2 + PKCE auth (Passport) | ✅ End-to-end (Postman + Pest) |
| Scopes + step-up + wildcard rejection | ✅ Implemented |
| User CRUD (soft + hard delete) | ✅ Implemented |
| Offerings (batch create, delete single/batch, filtered index, show) | ✅ Implemented |
| Evaluations (create, update, close, delete, filtered index, show) | ✅ Implemented |
| Individual cupping score (0–100, 8 real axes) | ✅ Implemented |
| Aggregate consensus (event-driven worker on **close**) | ✅ Implemented |
| Inter-specialist concordance (normalized dispersion) | ✅ Implemented (columns + `axis_concordances`) |
| `−4d` (defects) and `−2u` (non-uniformity) deductions in cupping | ✅ Implemented |
| Consensus flavor tree (`offering_tastes`) | ✅ Populated (parents + leaves, count = distinct specialists) |
| `verification_status` (provisional → verified) | ✅ Implemented |

## 🗺️ Backlog

What was **deliberately left out of the MVP** to keep the scope manageable — future product functionality, not technical debt.

- 🖥️ **Front end / user interface** — this API is currently consumed from the `tazavera-app` monolith; a dedicated front end consuming this REST API (SPA or mobile) is yet to be developed.
- 👤 **Consumer evaluation** — the role exists in the ENUM, but its form, validation, and own structure (CATA restricted to the upper taxonomy levels) remain to be defined; likely a separate entity.
- 🏪 **Coffee shop baseline** — a dedicated endpoint for the provisional evaluation a coffee shop declares about its own offering (one per offering, with ownership and uniqueness). Designed, not built.
- 🔀 **Consensus segregated by extraction method** — today the consensus mixes espresso, V60, French press, etc. Split the calculation by method once there's enough volume to avoid losing sample size.
- ⏱️ **Automatic evaluation-close cron** — bulk-close evaluations left open beyond a certain time (Laravel scheduler), instead of relying on manual closing. Distinct from the reactive consensus worker.
- 🎯 **Q calibration tag on acidity** — cross the tag with the evaluator's certification to detect whether Q-certified specialists agree more with each other on acidity descriptors.
- 🧾 **Transactional marketplace** — orders, payments, and communication with the coffee shop. The MVP is a directory + verification, not sales.
- 💸 **Automated payout** to coffee shops (scheduled settlement).
- 🏅 **Loyalty panel for coffee shops** — subscriptions, points.
- 📈 **Market trend reports** — value weighted per attribute by consumer segment.
- 🎓 **Specialist verification — full subsystem** — question bank, self-validation, and a combination of Q Grader certification + exposure + community rating. The MVP settles for the minimal declaration.
- ☕ **Third side of the market — monetized specialists** — consulting, recipes, cupping workshops.
- 🚚 **Third-party logistics integration** — delivery APIs.
- 🌱 **Green coffee physical assessment** — evaluation of the unroasted bean; the SCA standard for this is in alpha.
- 📖 **Usage guide + FAQ** — deferred until the evaluation decisions are settled.
- 🧮 **Statistical refinement of the consensus** — revisit `σ_max` (theoretical vs. realistic) and evaluate ICC / Fleiss as a concordance index once there's enough multi-offering volume.

## 📝 Notes

- 🔍 `php artisan tinker` to inspect data quickly (e.g. `Evaluation::first()->affective`).
- 🩹 If something fails when migrating with a SQL syntax error, check that `.env` points to MySQL 8 and not `sqlite`.
- 🔄 The worker caches code in memory: after editing the consensus service in dev, restart `queue:work` (or use `sync`).
