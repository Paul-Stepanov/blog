---
name: coordinator
description: |
  Pipeline orchestration instructions for dev-coordinator.
  Manages development workflow and agent coordination.
---

# Skill: Coordinator

Инструкции для оркестратора pipeline разработки.

---

## Обязанности

### 1. Управление Pipeline (автоматическая часть)
- Принимать задачи от пользователя
- Определять текущий этап
- Запускать агентов: Research, Design, Plan
- Координировать переходы между этапами

### 2. Approval Management
- Получать подтверждение пользователя на ключевых этапах
- After Research → approve Design
- After Design → approve Plan
- After Plan → СТОП, передать управление пользователю

### 3. Передача интерактивных этапов
После Plan пользователь вручную запускает:
- `/implement` — реализация кода
- `/review` — ревью кода
- `/test` — тестирование
- `/devops` — Docker/CI/CD (опционально)

---

## Pipeline States

| State | Description | Next |
|-------|-------------|------|
| `idle` | Ожидание задачи | → research |
| `research` | Исследование | → design (after approval) |
| `design` | Проектирование | → plan |
| `plan` | Планирование | → СТОП |
| `ready` | План готов | → /implement (вручную) |

---

## Workflow

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  Research   │ ──► │   Design    │ ──► │    Plan     │
│  (haiku)    │     │  (sonnet)   │     │  (sonnet)   │
└─────────────┘     └─────────────┘     └─────────────┘
                                               │
                                               ▼
                                        ╔═════════════╗
                                        ║    СТОП     ║
                                        ║ Plan готов  ║
                                        ╚═════════════╝
                                               │
                           ┌───────────────────┼───────────────────┐
                           ▼                   ▼                   ▼
                    ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
                    │  /implement │ ──► │   /review   │ ──► │    /test    │
                    │  (вручную)  │     │  (вручную)  │     │  (вручную)  │
                    └─────────────┘     └─────────────┘     └─────────────┘
                                                                   │
                                                                   ▼
                                                            ┌─────────────┐
                                                            │   /devops   │
                                                            │  (вручную)  │
                                                            └─────────────┘
```

---

## Agent Invocation Patterns

### Standard Invocation
```
Agent(
  subagent_type="{agent_name}",
  description="{Task description}",
  prompt="{Detailed prompt}"
)
```

### Background Invocation (parallel)
```
Agent(
  subagent_type="{agent_name}",
  description="{Task description}",
  prompt="{Detailed prompt}",
  run_in_background=true
)
```

---

## Decision Matrix

### After Research
| Condition | Action |
|-----------|--------|
| Requirements clear | → Design |
| Missing info | → Ask user |
| Too complex | → Split into subtasks |

### After Design
| Condition | Action |
|-----------|--------|
| Architecture approved | → Plan |
| Issues found | → Redesign |

### After Plan
| Condition | Action |
|-----------|--------|
| Plan approved | → СТОП, сообщить пользователю |
| Issues found | → Revise plan |

---

## Communication Templates

### Status Update
```markdown
## Pipeline Update

**Этап:** {current_stage}
**Статус:** {status}

**Прогресс:**
- [x] Research
- [x] Design
- [ ] Plan (current)

**Следующий шаг:** {next_action}
```

### Final Message (после Plan)
```markdown
## ✅ Pipeline подготовка завершена

**Созданные документы:**
- 01-research.md — исследование
- 02-design.md — архитектура
- 04-plan.md — план реализации

**Следующие шаги (запустите вручную):**

1. **`/implement`** — начать реализацию
2. **`/review`** — после реализации
3. **`/test`** — после ревью
4. **`/devops`** — если нужен Docker/деплой

**Рекомендация:** Начните с `/implement`
```

### Error Report
```markdown
## ⚠️ Pipeline Error

**Этап:** {stage}
**Агент:** {agent}
**Ошибка:** {error}

**Возможные решения:**
- {solution_1}
- {solution_2}

**Рекомендация:** {recommendation}
```

---

## Memory Updates

После каждого проекта сохранять:
- Общее время по этапам
- Типичные проблемы
- Best practices выявленные