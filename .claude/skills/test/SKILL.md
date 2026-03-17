---
name: test
description: |
  Инструкции для тестирования. Написание тестов, запуск, анализ покрытия.
  Создаёт .claude/pipeline/07-test.md
---

# Skill: Test

Инструкции для QA-инженера.

---

## Обязанности

### 1. Unit-тесты
- Тестирование функций/методов
- Изоляция через моки
- Граничные случаи

### 2. Integration-тесты
- Взаимодействие компонентов
- Работа с БД (test database)

### 3. Анализ покрытия
- Запуск с coverage
- Идентификация непокрытого кода

---

## Структура теста (AAA)

```php
public function testMethodName(): void
{
    // Arrange
    $service = new Service($mock);

    // Act
    $result = $service->method();

    // Assert
    $this->assertEquals($expected, $result);
}
```

---

## Именование тестов

```php
// Паттерн: test_{method}_{scenario}_{expectedResult}
public function testFindById_WhenUserExists_ReturnsUser(): void {}
public function testFindById_WhenUserNotFound_ReturnsNull(): void {}
public function testSave_WithInvalidData_ThrowsException(): void {}
```

---

## Команды

### PHPUnit
```bash
# Все тесты
./vendor/bin/phpunit

# Конкретный файл
./vendor/bin/phpunit tests/Unit/ServiceTest.php

# С coverage
./vendor/bin/phpunit --coverage-html coverage/

# Фильтр
./vendor/bin/phpunit --filter testName
```

---

## Чек-лист

### Unit
- [ ] Публичные методы протестированы
- [ ] Граничные случаи покрыты
- [ ] Моки для изоляции
- [ ] Имена тестов описательны

### Integration
- [ ] Взаимодействие с БД
- [ ] Транзакции (rollback в tearDown)
- [ ] Тестовые данные (fixtures)

### Качество
- [ ] Тесты детерминированы
- [ ] Тесты быстрые (< 1s каждый)
- [ ] Тесты независимы

---

## Формат отчёта

**Файл:** `.claude/pipeline/07-test.md`

```markdown
# Test: {Модуль}

**Дата:** {дата}
**Этап:** Test (7/7)

## Созданные тесты

### Unit Tests
| Файл | Тестов | Описание |
|------|--------|----------|
| `ServiceTest.php` | 5 | Тесты Service |

### Integration Tests
| Файл | Тестов | Описание |
|------|--------|----------|
| `RepositoryTest.php` | 3 | Тесты с БД |

## Результаты запуска

```
PHPUnit 10.x

Tests: 15, Assertions: 23
✅ Passed: 15
❌ Failed: 0
⏭ Skipped: 0
```

## Покрытие

| Файл | Lines | Functions |
|------|-------|-----------|
| Service.php | 95% | 100% |
| Repository.php | 87% | 90% |

**Общее покрытие:** 91%

## Непокрытый код

| Файл | Строки | Причина |
|------|--------|---------|
| ... | 42-45 | Edge case (TODO) |

## Рекомендации

1. Добавить тесты для ...
2. Увеличить покрытие до 90%+

## Вердикт

**Статус:** ✅ Все тесты пройдены / ❌ Есть падающие тесты

---
**Статус:** ⏳ ОЖИДАЕТ
```

---

## Важно

- Тесты независимы (каждый работает отдельно)
- Тестируй поведение, не реализацию
- Не пиши хрупкие тесты