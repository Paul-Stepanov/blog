---
name: implement
description: |
  Инструкции для реализации кода по спецификации.
  Создаёт .claude/pipeline/05-implement.md
---

# Skill: Implement

Инструкции для разработчика. Реализация кода строго по спецификации.

---

## 1. Обязанности

### Реализация
- Строго следуй `04-plan.md` и `02-design.md`
- Реализовывай интерфейсы из Design
- Соответствуй паттернам проекта

### Качество
- PSR-12 + strict_types=1
- Типы для всех аргументов и возвратов
- PHPDoc для публичных API

### Интеграция
- Не ломай существующий код
- Интегрируй с существующей архитектурой

---

## 2. Workflow создания файлов

### Правила

1. **Один файл — один ответ**
   - Создавай только ОДИН файл за раз
   - Жди подтверждения перед следующим

2. **При reject файла:**
   - НЕ обходи permission system
   - Спроси пользователя как поступить:
     1. Описать проблему текстом
     2. Отменить создание файла
   - Внеси исправления и предложи снова

3. **Порядок создания:**
   1. Value Objects
   2. Domain Exceptions
   3. Entities
   4. Repository Interfaces
   5. DTO / Commands / Queries
   6. Handlers
   7. Factories / Mappers
   8. Repository Implementations
   9. Form Requests
   10. Controllers
   11. Маршруты / ServiceProvider

---

## 3. Архитектурные Best Practices

### Domain-Driven Design

```php
// Entity — имеет идентичность
abstract class Entity
{
    protected readonly Uuid $id;

    public function equals(self $other): bool
    {
        return $this::class === $other::class
            && $this->id->equals($other->id);
    }
}

// Value Object — неизменяемый, без идентичности
abstract readonly class ValueObject
{
    abstract public function equals(self $other): bool;
}

// Aggregate Root — точка входа в aggregate
class Order extends Entity implements AggregateRoot
{
    /** @var DomainEvent[] */
    private array $events = [];

    public function addOrderItem(OrderItem $item): void
    {
        // Бизнес-логика
        $this->events[] = new OrderItemAdded($this->id, $item);
    }

    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];
        return $events;
    }
}
```

### Repository Pattern

```php
// Interface в Domain
interface UserRepository
{
    public function find(Uuid $id): ?User;
    public function save(User $user): void;
    public function delete(User $user): void;
}

// Implementation в Infrastructure
final readonly class EloquentUserRepository implements UserRepository
{
    public function __construct(
        private UserModel $model
    ) {}

    public function find(Uuid $id): ?User
    {
        $model = $this->model->newQuery()
            ->where('uuid', $id->toString())
            ->first();

        return $model ? $this->toDomain($model) : null;
    }
}
```

### Service Layer

```php
final readonly class UserService
{
    public function __construct(
        private UserRepository $users,
        private EventDispatcher $events,
        private LoggerInterface $logger
    ) {}

    public function createUser(CreateUserCommand $command): User
    {
        $user = User::create(
            $command->email,
            $command->name
        );

        $this->users->save($user);
        $this->events->dispatch($user->releaseEvents());

        return $user;
    }
}
```

### Dependency Injection

```php
// ✅ Правильно — DI через конструктор
final readonly class OrderService
{
    public function __construct(
        private OrderRepository $orders,
        private PaymentGateway $payments
    ) {}
}

// ❌ Неправильно — создание внутри
class OrderService
{
    public function process(int $orderId): void
    {
        $gateway = new StripeGateway(); // Жёсткая связь
    }
}
```

---

## 4. Laravel Best Practices

### Eloquent

```php
// ✅ Eager Loading — предотвращает N+1
$users = User::query()
    ->with(['posts', 'roles'])
    ->where('active', true)
    ->get();

// ✅ Scopes для переиспользования
class User extends Model
{
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeWithRole(Builder $query, string $role): Builder
    {
        return $query->whereHas('roles', fn($q) => $q->where('name', $role));
    }
}

// Использование
$users = User::query()
    ->active()
    ->withRole('admin')
    ->get();

// ✅ Chunk для больших выборок
User::query()
    ->where('active', true)
    ->chunk(100, function (Collection $users) {
        foreach ($users as $user) {
            // Обработка
        }
    });
```

### Form Requests

```php
final readonly class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'unique:users,email'],
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(['admin', 'user'])],
        ];
    }

    public function toDto(): CreateUserDto
    {
        return new CreateUserDto(
            email: $this->validated('email'),
            name: $this->validated('name'),
            role: $this->validated('role'),
        );
    }
}
```

### API Resources

```php
final readonly class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'email' => $this->email,
            'name' => $this->name,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

---

## 5. PHP 8.3 Best Practices

### Constructor Property Promotion + Readonly

```php
// ✅ Современный стиль
final readonly class CreateUserHandler
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
        private EventDispatcher $events,
    ) {}

    public function handle(CreateUserCommand $command): User
    {
        // ...
    }
}
```

### Named Arguments

```php
// ✅ Named arguments для читаемости
$user = User::create(
    email: $command->email,
    name: $command->name,
    role: $command->role,
);

// ✅ При вызове с many parameters
$this->sendNotification(
    to: $user,
    subject: 'Welcome',
    template: 'emails.welcome',
    data: ['user' => $user],
);
```

### Match Expressions

```php
// ✅ Match вместо switch
$status = match ($order->status) {
    OrderStatus::Pending => 'pending',
    OrderStatus::Paid => 'paid',
    OrderStatus::Shipped => 'shipped',
    OrderStatus::Delivered => 'delivered',
    default => 'unknown',
};
```

### Attributes

```php
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class Route
{
    public function __construct(
        public string $path,
        public string $method = 'GET',
    ) {}
}

#[Route('/api/users', method: 'POST')]
final readonly class CreateUserController
{
    // ...
}
```

### Strict Types

```php
<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\Shared\{Entity, Uuid};

/**
 * @psalm-immutable
 */
final readonly class User extends Entity
{
    /**
     * @param non-empty-string $email
     * @param non-empty-string $name
     */
    public function __construct(
        Uuid $id,
        private string $email,
        private string $name,
    ) {
        parent::__construct($id);
    }

    /**
     * @return non-empty-string
     */
    public function email(): string
    {
        return $this->email;
    }
}
```

---

## 6. Безопасность

### SQL Injection

```php
// ❌ Никогда
$sql = "SELECT * FROM users WHERE email = '{$email}'";

// ✅ Prepared statements
$users = User::query()
    ->where('email', $email)
    ->get();

// ✅ Raw с биндингом
$users = DB::select(
    'SELECT * FROM users WHERE email = ?',
    [$email]
);
```

### Mass Assignment

```php
// ✅ Явное указание полей
class User extends Model
{
    protected $fillable = ['name', 'email'];
    // ИЛИ
    protected $guarded = ['id', 'created_at', 'updated_at'];
}

// ✅ В контроллере
$user = User::create($request->only(['name', 'email']));
```

### XSS Protection

```php
// В Blade автоматически экранируется
{{ $user->name }}

// Если нужен HTML (осторожно!)
{!! $trustedHtml !!}

// Явное экранирование
htmlspecialchars($input, ENT_QUOTES, 'UTF-8')
```

### Authorization

```php
// ✅ Policy
class UserPolicy
{
    public function update(User $authUser, User $target): bool
    {
        return $authUser->id === $target->id;
    }
}

// В контроллере
public function update(User $user): Response
{
    $this->authorize('update', $user);
    // ...
}
```

---

## 7. Производительность

### N+1 Prevention

```php
// ❌ N+1 проблема
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->author->name; // +1 запрос на каждый пост
}

// ✅ Eager loading
$posts = Post::query()
    ->with('author')
    ->get();
```

### Caching

```php
// ✅ Cache remember
$user = Cache::remember(
    "user.{$id}",
    ttl: 3600,
    callback: fn() => User::findOrFail($id)
);

// ✅ Cache tags для инвалидации
Cache::tags(['users', "user.{$id}"])
    ->remember($key, $ttl, $callback);

Cache::tags(['users'])->flush(); // Инвалидация всех users
```

### Query Optimization

```php
// ✅ Select только нужных полей
$users = User::query()
    ->select(['id', 'email', 'name'])
    ->get();

// ✅ Exists вместо count
$hasOrders = Order::query()
    ->where('user_id', $userId)
    ->exists();

// ✅ Index hints
$users = User::query()
    ->from('users USE INDEX (idx_email)')
    ->where('email', $email)
    ->first();
```

---

## 8. Чек-лист перед завершением

- [ ] Соответствует Design (`02-design.md`)
- [ ] Соответствует Plan (`04-plan.md`)
- [ ] `declare(strict_types=1)` в начале файла
- [ ] Типы для всех аргументов/возвратов
- [ ] PHPDoc для публичных API
- [ ] Нет синтаксических ошибок (`php -l file.php`)
- [ ] Нет захардкоженных значений (константы/env)
- [ ] Обработаны граничные случаи (null, empty)
- [ ] Нет N+1 проблем (eager loading)
- [ ] Валидация входных данных
- [ ] Авторизация действий

---

## 9. Формат отчёта

**Файл:** `.claude/pipeline/05-implement.md`

```markdown
# Implement: {Задача}

**Дата:** {дата}
**Этап:** Implement (5/7)
**Фаза:** {номер из Plan}

## Созданные файлы

| Файл | Описание | Строк |
|------|----------|-------|
| `path/to/file.php` | ... | X |

## Изменённые файлы

| Файл | Изменения |
|------|-----------|
| `path/to/file.php` | +X -Y |

## Реализованные функции

| Класс | Метод | Описание |
|-------|-------|----------|
| ClassName | methodName() | ... |

## Соответствие Design

| Требование | Статус | Комментарий |
|------------|--------|-------------|
| Interface X | ✅ | ... |
| Pattern Y | ✅ | ... |

## Проверки

- [x] Синтаксис: OK (`php -l`)
- [x] PSR-12: OK
- [x] Типизация: OK
- [x] Безопасность: OK
- [x] N+1: OK (eager loading)

## Замечания

{Если были отклонения от плана}
```

---

## Важно

- Не импровизируй — следуй спецификации
- Не добавляй "улучшения" без запроса
- Если что-то неясно — остановись и спроси