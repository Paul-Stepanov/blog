# Pipeline Development Process

Эта директория содержит документы, создаваемые на каждом этапе pipeline разработки.

## Pipeline

```
Research → Design → [DevOps Setup] → Plan → 👤 Approval → Implement → Review → Test → [Deploy]
```

## Как работает

1. **Вы вызываете агента** для текущего этапа
2. **Агент создаёт файл** с результатами в этой директории
3. **Вы проверяете** файл и либо:
   - ✅ Утверждаете → вызываете следующего агента
   - 🔄 Возвращаете на доработку → вызываете того же агента с замечаниями

## Этапы и файлы

| # | Этап | Файл | Агент | Обязательный |
|---|------|------|-------|--------------|
| 1 | Research | `01-research.md` | `dev-researcher` | ✅ |
| 2 | Design | `02-design.md` | `dev-architect` | ✅ |
| 3 | DevOps Setup | `03-devops-setup.md` | `dev-devops` | ❌ |
| 4 | Plan | `04-plan.md` | `dev-architect` или вы сами | ✅ |
| — | **Approval** | — | **Вы** | ✅ |
| 5 | Implement | `05-implement.md` | `dev-coder` | ✅ |
| 6 | Review | `06-review.md` | `dev-reviewer` | ✅ |
| 7 | Test | `07-test.md` | `dev-tester` | ✅ |
| 8 | Deploy | `08-deploy.md` | `dev-devops` | ❌ |

## Пример использования

### Этап 1: Research
```
Вы: /research нужно реализовать аутентификацию пользователей

dev-researcher:
- Анализирует требования
- Изучает существующий код
- Создаёт 01-research.md

Вы: читаете 01-research.md
- ✅ Утверждаете → переходите к Design
- 🔄 Замечания → /research учти: {ваши замечания}
```

### Этап 2: Design
```
Вы: /design на основе 01-research.md

dev-architect:
- Читает 01-research.md
- Проектирует архитектуру
- Создаёт диаграммы
- Создаёт 02-design.md

Вы: проверяете 02-design.md
```

### Этап 4: Plan → Approval
```
Вы: /plan на основе 01-research.md и 02-design.md

dev-architect:
- Создаёт 04-plan.md с фазами реализации

Вы: читаете план
- Задаёте вопросы
- Утверждаете
- ✅ Только после утверждения → Implement
```

### Этап 5-7: Implement → Review → Test
```
Вы: /implement фаза 1 из 04-plan.md

dev-coder:
- Пишет код
- Создаёт 05-implement.md

Вы: /review

dev-reviewer:
- Проверяет код
- Создаёт 06-review.md

Вы: /test

dev-tester:
- Пишет тесты
- Создаёт 07-test.md
```

## Возврат на доработку

Если результат не устраивает:

```
Вы: /research переработай 01-research.md с учётом:
- Нужно добавить анализ безопасности
- Не хватает граничных случаев
```

Агент перечитает свой предыдущий файл и учтёт замечания.

## Статусы в файлах

Каждый файл содержит в конце:

```markdown
---
## Статус

**Статус:** ⏳ ОЖИДАЕТ / ✅ УТВЕРЖДЕНО / 🔄 ДОРАБОТКА

**Комментарии пользователя:**
- ...
---
```

При доработке агент обновляет статус и добавляет правки.

## Sequential Thinking

На этапах **Research**, **Design** и **Plan** агенты используют `mcp__sequential-thinking__sequentialthinking` для глубокого анализа перед созданием отчёта.

## Быстрый старт

```
/research {задача}           → 01-research.md
/design                      → 02-design.md
/plan                        → 04-plan.md
--- УТВЕРЖДЕНИЕ ---
/implement {фаза}            → 05-implement.md
/review                      → 06-review.md
/test                        → 07-test.md
```