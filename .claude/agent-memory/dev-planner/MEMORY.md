# Dev-Planner Memory

## Типичные фазы для storage реализации

1. **Dependencies & Config** - composer, config files
2. **Domain Interfaces** - контракты без зависимостей
3. **Infrastructure Adapters** - реализации интерфейсов
4. **Application Services** - координация между слоями
5. **HTTP Layer** - controllers, routes
6. **DI & Providers** - service provider, bindings
7. **DevOps** - nginx, docker volumes
8. **Testing** - unit, integration, feature tests

## Частые риски при storage имплементации

| Риск | Митигация |
|------|-----------|
| Расхождение сигнатур интерфейса и реализации | Внимательно проверять существующие интерфейсы перед реализацией |
| Directory traversal атаки | Value Object валидация ".." |
| Потеря файлов при redeploy | Docker named volumes |
| Отсутствие PHP extensions в Docker | Проверить Dockerfile на gd/imagick |
| Cross-disk operations | Реализовать copy + delete pattern |

## Зависимости между слоями (Hexagonal Architecture)

```
Domain (Interfaces)
    ↓
Infrastructure (Adapters)
    ↓
Application (Services)
    ↓
Presentation (Controllers)
    ↓
DI (Providers)
```

## Паттерны для планирования

### Adapter Pattern
- LocalStorageAdapter реализует FileStorageInterface
- Позволяет подменять хранилище без изменения бизнес-логики

### Strategy Pattern
- Выбор диска на основе префикса пути (public/ vs private/)

### Factory Pattern
- FilePath::generateForUpload() для создания уникальных путей
- MimeType::fromExtension() для определения MIME типа

## Критерии готовности фазы

- [ ] Код компилируется без ошибок
- [ ] Статический анализ проходит (phpstan/psalm)
- [ ] Unit тесты для новой функциональности
- [ ] Integration тесты для end-to-end сценариев
- [ ] Документация (PHPDoc) полная
