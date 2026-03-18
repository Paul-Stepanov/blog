# Pipeline Development Process

Эта директория содержит документы, создаваемые на каждом этапе pipeline разработки.

## 🎯 Двухфазная архитектура

### Фаза 1: ПОДГОТОВКА (автономные subagents)
```
Research ──► Design ──► Plan
(haiku)      (sonnet)    (sonnet)
```
- Координатор запускает subagents последовательно
- Approval арбитра между этапами

### Фаза 2: РЕАЛИЗАЦИЯ (интерактивные teammates + арбитр)
```
Team Assembly ──► Implement ──► Review ──► Test
(coordinator)    (teammate)    (teammate)  (teammate)
                      │             │           │
                      └─────────────┴───────────┘
                           👤 АРБИТР
                    (пользователь подтверждает/отклоняет)
```
- Координатор собирает команду и передаёт управление
- Арбитр (пользователь) управляет teammates

---

## Роли в команде

| Роль | Кто | Обязанности |
|------|-----|-------------|
| **Team Lead** | dev-coordinator | Запускает агентов, собирает команду, передаёт управление |
| **Арбитр** | Пользователь | Подтверждает/отклоняет файлы, решает конфликты, approve на этапах |
| **Teammates** | dev-coder, dev-reviewer, dev-tester | Выполняют работу, запрашивают подтверждения |

---

## Pipeline

```
Research → Design → Plan → [Team Assembly] → Implement → Review → Test → [Deploy]
```

## Этапы и агенты

| # | Этап | Агент | Файл отчёта | Тип | Фаза |
|---|------|-------|-------------|-----|------|
| 1 | Research | `dev-researcher` | `01-research.md` | Subagent | Подготовка |
| 2 | Design | `dev-architect` | `02-design.md` | Subagent | Подготовка |
| 3 | Plan | `dev-planner` | `04-plan.md` | Subagent | Подготовка |
| - | **Team Assembly** | `dev-coordinator` | - | Coordinator | Переход |
| 4 | DevOps Setup | `dev-devops` | `03-devops-setup.md` | **Teammate** | Реализация |
| 5 | Implement | `dev-coder` | `05-implement.md` | **Teammate** | Реализация |
| 6 | Review | `dev-reviewer` | `06-review.md` | **Teammate** | Реализация |
| 7 | Test | `dev-tester` | `07-test.md` | **Teammate** | Реализация |
| 8 | Deploy | `dev-devops` | `08-deploy.md` | **Teammate** | Реализация |

**Координатор:** `dev-coordinator` — управляет Фазой 1 и Team Assembly

---

## Workflow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    ФАЗА 1: ПОДГОТОВКА                        │
│                                                              │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐    │
│  │  Research   │ ──► │   Design    │ ──► │    Plan     │    │
│  │  (subagent) │     │  (subagent) │     │  (subagent) │    │
│  │  haiku      │     │  sonnet     │     │  sonnet     │    │
│  │  context7   │     │  context7   │     │  context7   │    │
│  │  web-reader │     │  figma      │     │             │    │
│  └─────────────┘     └─────────────┘     └─────────────┘    │
│        │                   │                   │            │
│        └───────────────────┴───────────────────┘            │
│                      👤 Approval арбитра                    │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                ФАЗА 2: TEAM ASSEMBLY                         │
│                                                              │
│  Координатор:                                                │
│  1. TeamCreate("dev-implementation")                        │
│  2. TaskCreate для фаз из 04-plan.md                        │
│  3. Spawn teammates (coder, reviewer, tester)               │
│  4. Передача управления арбитру                             │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                ФАЗА 3: РЕАЛИЗАЦИЯ                            │
│                                                              │
│  ┌─────────────┐ (опционально)                              │
│  │   DevOps    │ ──► Docker/инфраструктура                  │
│  │  (teammate) │                                             │
│  └─────────────┘                                             │
│         │                                                    │
│         ▼                                                    │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐    │
│  │  Implement  │ ──► │   Review    │ ──► │    Test     │    │
│  │  (teammate) │     │  (teammate) │     │  (teammate) │    │
│  │  sonnet     │     │  sonnet     │     │  haiku      │    │
│  │  context7   │     │             │     │  chrome     │    │
│  │  knowledge  │     │             │     │             │    │
│  └─────────────┘     └─────────────┘     └─────────────┘    │
│         ▲                   │                   │           │
│         └───────────────────┴───────────────────┘           │
│                    Loop при issues/fails                     │
│                                                              │
│  👤 АРБИТР (пользователь):                                   │
│     - Подтверждает/отклоняет файлы                          │
│     - Решает конфликты между агентами                       │
│     - Даёт approve на переход между этапами                  │
│     - Shift+Down для переключения между teammates           │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
                    ┌─────────────┐
                    │   Deploy    │
                    │  (teammate) │
                    │  sonnet     │
                    │  context7   │
                    └─────────────┘
```

---

## Пример использования

### Запуск через координатора (рекомендуется)

```
Agent(
  subagent_type="dev-coordinator",
  description="Pipeline: Auth Feature",
  prompt="Реализуй аутентификацию пользователей. Координируй весь pipeline."
)
```

**Workflow:**
1. **Research** → dev-researcher (subagent) → 👤 Approval
2. **Design** → dev-architect (subagent) → 👤 Approval
3. **Plan** → dev-planner (subagent) → 👤 Approval
4. **Team Assembly** → координатор собирает команду
5. **Передача управления** → арбитр (ты) управляет teammates
6. **Implement** → dev-coder (teammate) ← *интерактивно с арбитром*
7. **Review** → dev-reviewer (teammate) ← *интерактивно*
8. **Test** → dev-tester (teammate) ← *интерактивно*
9. При issues → loop back к соответствующему teammate

---

## 👤 Роль Арбитра

Как арбитр ты:

### Подтверждаешь/отклоняешь файлы
Когда teammate создаёт файл:
- ✅ **Approve** — файл принят, teammate продолжает
- ❌ **Reject** — файл отклонён, teammate спрашивает как исправить

### Решаешь конфликты
Когда агенты disagree:
```
## ⚠️ Конфликт
Coder хочет X, Reviewer рекомендует Y.
Выбери: 1) X  2) Y  3) Свой вариант
```

### Даёшь approval на переходы
Между этапами Фазы 1:
- Research → Design
- Design → Plan
- Plan → Team Assembly

### Переключаешься между teammates
**Shift+Down** для переключения на конкретного teammate.

---

## 🔄 Интерактивный диалог при reject

Когда teammate (coder) получает reject файла:

1. **Teammate спрашивает:**
   ```
   Файл отклонён. Как вы хотите указать замечания?

   1. Редактировать draft-файл
   2. Описать проблемы текстом
   3. Отменить создание файла
   ```

2. **Ты отвечаешь** (Shift+Down для переключения на teammate)

3. **Teammate продолжает** работу с учётом замечаний

---

## Типы агентов

### Subagents (автономные) — Фаза 1
- `dev-researcher` — анализ требований, кода, документации
- `dev-architect` — проектирование архитектуры, диаграммы
- `dev-planner` — декомпозиция на фазы, критерии готовности

### Teammates (интерактивные) — Фаза 2
- `dev-devops` — Docker, CI/CD, инфраструктура
- `dev-coder` — реализация кода по спецификации
- `dev-reviewer` — ревью кода (read-only)
- `dev-tester` — написание и запуск тестов

### Orchestrator
- `dev-coordinator` — Team Lead, управляет pipeline

---

## MCP интеграция

| Агент | MCP Servers |
|-------|-------------|
| dev-researcher | context7, web-reader, web-search-prime |
| dev-architect | context7, figma |
| dev-planner | context7 |
| dev-devops | context7, knowledge-graph |
| dev-coder | context7, knowledge-graph |
| dev-reviewer | knowledge-graph |
| dev-tester | chrome-devtools, knowledge-graph |
| dev-coordinator | knowledge-graph |

---

## Hooks

| Hook | Файл | Назначение |
|------|------|------------|
| validate-bash.sh | hooks/ | Блокирует опасные команды |
| run-linter.sh | hooks/ | Запускает линтер после изменений |
| validate-sql.sh | hooks/ | Блокирует SQL write операции |

---

## Быстрый справочник

| Команда | Агент | Тип | Фаза | Что делает |
|---------|-------|-----|------|------------|
| Research | `dev-researcher` | Subagent | Подготовка | Анализирует требования |
| Design | `dev-architect` | Subagent | Подготовка | Проектирует архитектуру |
| Plan | `dev-planner` | Subagent | Подготовка | Декомпозиция на фазы |
| Team Assembly | `dev-coordinator` | Coordinator | Переход | Собирает команду |
| DevOps | `dev-devops` | Teammate | Реализация | Docker, CI/CD |
| Implement | `dev-coder` | Teammate | Реализация | Пишет код |
| Review | `dev-reviewer` | Teammate | Реализация | Проверяет код |
| Test | `dev-tester` | Teammate | Реализация | Тесты |
| Coordinate | `dev-coordinator` | Orchestrator | - | Управляет pipeline |