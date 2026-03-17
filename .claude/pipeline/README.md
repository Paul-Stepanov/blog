# Pipeline Development Process

Эта директория содержит документы, создаваемые на каждом этапе pipeline разработки.

## Pipeline

```
Research → Design → [DevOps Setup] → Plan → Implement → Review → Test → [Deploy]
```

## Как работает

1. **Вы вызываете субагента** для текущего этапа через Agent tool
2. **Агент работает изолированно** — не засоряет контекст
3. **Агент создаёт файл отчёта** в этой директории
4. **Вы проверяете** результат и либо:
   - ✅ Утверждаете → вызываете следующего агента
   - 🔄 Возвращаете на доработку → вызываете того же агента с замечаниями

---

## Этапы и агенты

| # | Этап | Агент | Файл отчёта | Обязательный |
|---|------|-------|-------------|--------------|
| 1 | Research | `dev-researcher` | `01-research.md` | ✅ |
| 2 | Design | `dev-architect` | `02-design.md` | ✅ |
| 3 | DevOps Setup | `dev-devops` | `03-devops-setup.md` | ❌ |
| 4 | Plan | `dev-planner` | `04-plan.md` | ✅ |
| 5 | Implement | `dev-coder` | `05-implement.md` | ✅ |
| 6 | Review | `dev-reviewer` | `06-review.md` | ✅ |
| 7 | Test | `dev-tester` | `07-test.md` | ✅ |
| 8 | Deploy | `dev-devops` | `08-deploy.md` | ❌ |

---

## Пример использования

### Этап 1: Research
```
Agent(
  subagent_type="dev-researcher",
  description="Research: Auth",
  prompt="Исследуй задачу: реализовать аутентификацию пользователей"
)
```

→ Создаёт `01-research.md`
→ Возвращает summary

**Утверждение:** Проверьте файл. Если OK → переходите к Design.

**Доработка:**
```
Agent(
  subagent_type="dev-researcher",
  description="Research: revise",
  prompt="Переработай 01-research.md с учётом: добавить анализ безопасности"
)
```

### Этап 2: Design
```
Agent(
  subagent_type="dev-architect",
  description="Design: Auth",
  prompt="Спроектируй архитектуру на основе .claude/pipeline/01-research.md"
)
```

→ Создаёт `02-design.md`

### Этап 3: DevOps Setup (если нужен)
```
Agent(
  subagent_type="dev-devops",
  description="DevOps Setup",
  prompt="Настрой Docker для проекта. Стек из .claude/pipeline/02-design.md"
)
```

→ Создаёт `03-devops-setup.md`, Dockerfile, docker-compose.yml

### Этап 4: Plan
```
Agent(
  subagent_type="dev-planner",
  description="Plan: Auth",
  prompt="Создай план реализации на основе 01-research.md и 02-design.md"
)
```

→ Создаёт `04-plan.md`

### Этап 5: Implement
```
Agent(
  subagent_type="dev-coder",
  description="Implement: Phase 1",
  prompt="Реализуй Фазу 1 из .claude/pipeline/04-plan.md"
)
```

→ Пишет код, создаёт `05-implement.md`

### Этап 6: Review
```
Agent(
  subagent_type="dev-reviewer",
  description="Review",
  prompt="Проверь код из .claude/pipeline/05-implement.md"
)
```

→ Создаёт `06-review.md`

**Если есть Critical issues** → dev-coder исправляет → повторить review

### Этап 7: Test
```
Agent(
  subagent_type="dev-tester",
  description="Test",
  prompt="Напиши тесты для кода из .claude/pipeline/05-implement.md"
)
```

→ Пишет тесты, запускает, создаёт `07-test.md`

### Этап 8: Deploy (если нужен)
```
Agent(
  subagent_type="dev-devops",
  description="Deploy",
  prompt="Настрой CI/CD для проекта"
)
```

→ Создаёт `08-deploy.md`, GitHub Actions

---

## Workflow Diagram

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  Research   │ ──► │   Design    │ ──► │  DevOps     │
│  (haiku)    │     │  (sonnet)   │     │  (sonnet)   │
└─────────────┘     └─────────────┘     └─────────────┘
                                               │
                                               ▼
                                        ┌─────────────┐
                                        │    Plan     │
                                        │  (sonnet)   │
                                        └─────────────┘
                                               │
                                               ▼
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Deploy    │ ◄── │    Test     │ ◄── │   Review    │
│  (sonnet)   │     │   (haiku)   │     │  (sonnet)   │
└─────────────┘     └─────────────┘     └─────────────┘
                           ▲                   ▲
                           │                   │
                    ┌──────┴──────┐      ┌─────┴─────┐
                    │  Implement  │ ───► │  (цикл)   │
                    │  (sonnet)   │      │  Revise   │
                    └─────────────┘      └───────────┘
```

---

## Быстрый справочник

| Команда | Агент | Что делает |
|---------|-------|------------|
| Research | `dev-researcher` | Анализирует требования, код, документацию |
| Design | `dev-architect` | Проектирует архитектуру, диаграммы |
| DevOps | `dev-devops` | Docker, CI/CD |
| Plan | `dev-planner` | Декомпозиция на фазы, критерии готовности |
| Implement | `dev-coder` | Пишет код |
| Review | `dev-reviewer` | Проверяет код |
| Test | `dev-tester` | Пишет и запускает тесты |