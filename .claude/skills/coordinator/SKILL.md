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

### 1. Управление Pipeline
- Принимать задачи от пользователя
- Определять текущий этап
- Запускать соответствующих агентов
- Координировать переходы между этапами

### 2. Approval Management
- Получать подтверждение пользователя на ключевых этапах
- After Research → approve Design
- After Design → approve Plan
- After Plan → approve Implementation

### 3. Loop Management
- Review → Coder (если Critical issues)
- Test → Coder (если Failed tests)
- Повторять пока не будет ✅

### 4. Parallel Execution
- Запускать независимые задачи параллельно
- Координировать результаты

---

## Pipeline States

| State | Description | Next |
|-------|-------------|------|
| `idle` | Ожидание задачи | → research |
| `research` | Исследование | → design (after approval) |
| `design` | Проектирование | → devops (optional) → plan |
| `devops_setup` | Инфраструктура | → plan |
| `plan` | Планирование | → implement (after approval) |
| `implement` | Реализация | → review |
| `review` | Ревью кода | → test ✅ / → implement ❌ |
| `test` | Тестирование | → deploy ✅ / → implement ❌ |
| `deploy` | Развёртывание | → done |
| `done` | Завершено | → idle |

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
| Needs infrastructure | → DevOps Setup → Plan |
| Issues found | → Redesign |

### After Review
| Critical Count | Action |
|----------------|--------|
| 0 | → Test |
| 1-2 | → Coder for fixes |
| 3+ | → Redesign consideration |

### After Test
| Failed Count | Action |
|--------------|--------|
| 0 | → Deploy (optional) |
| 1-3 | → Coder for fixes |
| 4+ | → Review implementation |

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
- [ ] Implement
- [ ] Review
- [ ] Test

**Следующий шаг:** {next_action}
```

### Approval Request
```markdown
## Требуется утверждение

**Этап:** {stage}
**Результат:** {result_file}

**Ключевые решения:**
- {decision_1}
- {decision_2}

**Вопросы:**
- {question_1}

Продолжить? (да/нет/доработать)
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
- Количество итераций (loops)
- Типичные проблемы
- Best practices выявленные