# Research: Блог с DDD архитектурой и Bento Grid дизайном

**Дата:** 2026-03-17 (обновлено: SEO optimization для SPA)
**Этап:** Research (1/7)

---

## Требования

### Функциональные

1. **Главная страница (лендинг)**
   - Bento Grid дизайн с Nature Distilled стилистикой
   - Hero секция с информацией об авторе
   - Блок "Обо мне" с контактной информацией
   - Отображение последних статей
   - Форма обратной связи

2. **Рубрики и Статьи**
   - Список статей с пагинацией и фильтрацией
   - Детальная страница статьи
   - Категории и теги
   - Поиск по статьям

3. **Административная панель**
   - Управление статьями (CRUD, публикация, категории, теги)
   - Управление медиа-файлами (загрузка, галерея)
   - Контактные сообщения
   - Управление пользователями
   - Редактирование информации "Обо мне"

### Нефункциональные

- **Производительность:** Кэширование (Redis), lazy loading компонентов
- **Безопасность:** Аутентификация (Laravel Sanctum), валидация данных, защита от XSS/CSRF/SQL-инъекций
- **SEO:** Мета-теги, Open Graph, sitemap.xml, robots.txt
- **Отзывчивость:** Адаптивный Bento Grid для desktop, tablet, mobile
- **Читаемость кода:** PSR-12, строгая типизация, PHPDoc
- **Тестирование:** Unit тесты (PHPUnit для Laravel, Vitest для Vue.js)

### Граничные случаи

- Публикация статьи с запланированной датой (future publish_at)
- Удаление категории, привязанной к статьям (cascade delete или prevent)
- Загрузка слишком больших файлов (limit: 10MB)
- Дубликаты slug (автогенерация с суффиксом)
- Архивные статьи не должны отображаться в публичном API
- Отправка контактной формы с rate limiting (5 запросов/мин)
- SEO канонические URL для статей

---

## Анализ кода

### Текущее состояние проекта

Проект находится на **начальной стадии разработки**:
- Отсутствуют composer.json, package.json, docker-compose.yml
- Отсутствует Laravel и Vue.js код
- Настроены инструменты PhpStorm (PHPStan, Psalm, CodeSniffer, CS Fixer, PHPMD)
- Создана структура папок для pipeline (.claude/pipeline/)

### Похожие реализации

| Файл | Описание | Паттерн |
|------|----------|---------|
| - | Проект пустой, нет реализаций | - |

### Точки интеграции

- **Laravel ↔ PostgreSQL:** Eloquent ORM, миграции
- **Laravel ↔ Redis:** Кэширование, очереди, сессии
- **Vue.js ↔ Laravel API:** Axios HTTP клиент, JSON API
- **Vue.js Router:** клиентская маршрутизация
- **Docker:** Контейнеризация всех сервисов

### Потенциальные конфликты

| Конфликт | Описание | Решение |
|----------|----------|---------|
| DDD vs Laravel Conventions | Laravel не нативно поддерживает Clean Architecture | Использовать Repository паттерн, четкое разделение слоев в папках |
| Vue.js SPA vs SEO | Поисковики плохо индексируют SPA | SSG с vite-ssg, Unhead для мета-тегов, ISR для динамического контента |
| Bento Grid Responsive | Сложная адаптация сетки на мобильных | CSS Grid с responsive breakpoints, mobile-first подход |
| SSG для блога с частыми обновлениями | Medium | ISR (Incremental Static Regeneration) для быстрой публикации |
| Задержка индексации новых статей | Medium | ISR для перегенерации конкретных страниц + webhook триггеры |
| Управление мета-тегами для SPA | Medium | Unhead с реактивным API + Schema.org разметка |

---

## SEO Strategy для SPA

### Проблемы SEO для Vue.js SPA

Чистый Vue.js SPA имеет следующие SEO ограничения:
1. **Пустой HTML на начальной загрузке**: Поисковики получают только базовый HTML без контента
2. **Клиентский рендеринг**: Контент генерируется через JavaScript после загрузки
3. **Медленная индексация**: Ботам нужно выполнять JS, что занимает больше времени
4. **Проблемы с социальным шарингом**: Open Graph теги не видны при первичном запросе (Facebook, Twitter, LinkedIn)
5. **Ухудшенные Core Web Vitals**: LCP и CLS хуже из-за JS рендеринга
6. **Отсутствие контента при отключенном JS**: Проблема для пользователей с ограничениями

### Сравнение подходов к решению

| Подход | Описание | Плюсы | Минусы | Рекомендация для блога |
|--------|----------|-------|--------|------------------------|
| **SSR (Nuxt.js)** | Рендеринг HTML на сервере для каждого запроса | Динамический контент, быстрая индексация | Сложность инфраструктуры, нужна Node.js | Medium - избыточно для блога |
| **SSG (Static Site Generation)** | Генерация статического HTML при билде | Максимальная скорость, простая инфраструктура, CDN хостинг | Требует ребилда при изменении контента | **High** - оптимально |
| **Dynamic Rendering** | Разный контент для ботов и пользователей | Сохраняет существующую инфраструктуру | Дополнительная сложность, задержки | Low - временный костыль |
| **Hybrid (ISR)** | SSG + Incremental Static Regeneration | Быстрая доставка, динамические обновления | Сложнее чистого SSG | **High** - с ISR |

**Рекомендация для блога:** SSG с возможностью ISR

### Интеграция с Laravel Backend

#### API-driven Architecture

Laravel выступает как REST API, Vue.js как отдельное приложение:

```
┌─────────────┐         ┌─────────────┐         ┌─────────────┐
│  Laravel    │  API    │   Vue.js    │  SSG    │  CDN (Vite) │
│  Backend    │────────►│   Frontend  │────────►│   Assets    │
│  (REST API) │         │   (SPA)     │         │             │
└─────────────┘         └─────────────┘         └─────────────┘
        ▲                       ▲
        │                       │
        └───────────────────────┘
     Meta-tags (Unhead)
```

#### Генерация мета-тегов

Использование **Unhead** для реактивного управления мета-тегами:

```typescript
// composables/useSeo.ts
import { useHead } from '@unhead/vue'

export function useArticleSEO(article: Article) {
  useHead({
    title: article.title,
    meta: [
      { name: 'description', content: article.excerpt },
      { property: 'og:title', content: article.title },
      { property: 'og:description', content: article.excerpt },
      { property: 'og:image', content: article.cover_image },
      { property: 'og:type', content: 'article' },
      { property: 'og:url', content: `https://blog.com/articles/${article.slug}` },
      { name: 'twitter:card', content: 'summary_large_image' },
      { name: 'twitter:title', content: article.title },
      { name: 'twitter:description', content: article.excerpt },
      { name: 'twitter:image', content: article.cover_image },
    ],
    link: [
      { rel: 'canonical', href: `https://blog.com/articles/${article.slug}` }
    ],
    script: [
      {
        type: 'application/ld+json',
        children: JSON.stringify({
          '@context': 'https://schema.org',
          '@type': 'Article',
          headline: article.title,
          description: article.excerpt,
          image: article.cover_image,
          author: {
            '@type': 'Person',
            name: article.author_name
          },
          datePublished: article.published_at,
          dateModified: article.updated_at,
          mainEntityOfPage: {
            '@type': 'WebPage',
            '@id': `https://blog.com/articles/${article.slug}`
          }
        })
      }
    ]
  })
}
```

### Инструменты для SEO

#### 1. vite-ssg (Vite Static Site Generation)

Генерация статического HTML из Vue.js SPA:

```typescript
// vite.config.ts
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { ViteSSG } from 'vite-ssg'

export default defineConfig({
  plugins: [
    vue(),
    ViteSSG({
      build: {
        // Генерация для всех маршрутов
        routes: async () => {
          const { data } = await fetch('https://api.blog.com/articles')
          return data.map((article: Article) => `/articles/${article.slug}`)
        }
      }
    })
  ]
})
```

#### 2. Unhead

Современная библиотека для управления мета-тегами:
- Vue 3 + Composition API поддержка
- SSR готовность
- Типизация TypeScript
- Поддержка всех мета-тегов

#### 3. vite-plugin-sitemap

Автоматическая генерация sitemap.xml:

```typescript
// vite.config.ts
import { defineConfig } from 'vite'
import { VitePluginSitemap } from 'vite-plugin-sitemap'

export default defineConfig({
  plugins: [
    VitePluginSitemap({
      hostname: 'https://blog.com',
      dynamicRoutes: async () => {
        const { data: articles } = await fetch('https://api.blog.com/articles')
        return articles.map((article: Article) => ({
          url: `/articles/${article.slug}`,
          changefreq: 'weekly',
          priority: 0.8,
          lastmod: article.updated_at
        }))
      }
    })
  ]
})
```

#### 4. robots.txt

Статический файл в `public/robots.txt`:

```
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /api/

Sitemap: https://blog.com/sitemap.xml
```

### Schema.org Разметка для блога

#### Article Schema

```typescript
// components/ArticleDetail.vue
<script setup lang="ts">
const article = useArticle()

const articleSchema = {
  '@context': 'https://schema.org',
  '@type': 'Article',
  headline: article.value.title,
  description: article.value.excerpt,
  image: [article.value.cover_image],
  author: {
    '@type': 'Person',
    name: article.value.author.name,
    url: `https://blog.com/authors/${article.value.author.slug}`
  },
  datePublished: article.value.published_at,
  dateModified: article.value.updated_at,
  mainEntityOfPage: {
    '@type': 'WebPage',
    '@id': `https://blog.com/articles/${article.value.slug}`
  },
  publisher: {
    '@type': 'Organization',
    name: 'Blog Name',
    url: 'https://blog.com',
    logo: {
      '@type': 'ImageObject',
      url: 'https://blog.com/logo.png'
    }
  }
}

useHead({
  script: [{
    type: 'application/ld+json',
    children: JSON.stringify(articleSchema)
  }]
})
</script>
```

#### BreadcrumbList Schema

```typescript
// composables/useBreadcrumb.ts
export function useBreadcrumb(items: BreadcrumbItem[]) {
  const breadcrumbSchema = {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: items.map((item, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: item.name,
      item: `https://blog.com${item.url}`
    }))
  }

  useHead({
    script: [{
      type: 'application/ld+json',
      children: JSON.stringify(breadcrumbSchema)
    }]
  })
}
```

#### WebSite Schema (для главной страницы)

```typescript
// pages/Home.vue
<script setup lang="ts">
const webSiteSchema = {
  '@context': 'https://schema.org',
  '@type': 'WebSite',
  name: 'Blog Name',
  url: 'https://blog.com',
  description: 'Технический блог о разработке',
  potentialAction: {
    '@type': 'SearchAction',
    target: 'https://blog.com/search?q={search_term_string}',
    'query-input': 'required name=search_term_string'
  }
}
</script>
```

### Структура URL для SEO

Рекомендуемая структура URL:

```
/                              # Главная страница
/articles                      # Список всех статей
/articles/{slug}               # Детальная статья
/categories                    # Список категорий
/categories/{slug}             # Категория со статьями
/tags                          # Облако тегов
/tags/{slug}                   # Тег со статьями
/search                        # Поиск
/page/{number}                 # Пагинация
/admin/*                       # Админ-панель (noindex)
/api/*                         # API endpoints (noindex)
```

**Правила:**
- Использовать slug вместо ID (`/articles/my-article` вместо `/articles/123`)
- Короткие, читаемые URL с ключевыми словами
- Канонические URL для избежания дублей
- Lowercase, дефисы вместо пробелов

### Core Web Vitals Оптимизация

#### LCP (Largest Contentful Paint)

Для блога LCP = контент статьи:

```vue
<!-- ArticleDetail.vue -->
<template>
  <article>
    <!-- Приоритетная загрузка контента -->
    <h1>{{ article.title }}</h1>
    <img
      :src="article.cover_image"
      :width="1200"
      :height="630"
      loading="eager"
      fetchpriority="high"
      alt="..."
    />
    <div class="content" v-html="article.content" />

    <!-- Lazy loading для вторичного контента -->
    <CommentsSection lazy />
    <RelatedArticles lazy />
  </article>
</template>

<script setup lang="ts">
// Предзагрузка шрифтов и критических ресурсов
useHead({
  link: [
    { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
    { rel: 'preload', href: '/fonts/inter.woff2', as: 'font', crossorigin: '' }
  ]
})
</script>
```

#### FID (First Input Delay)

```typescript
// Код-сплиттинг для тяжелых компонентов
const CommentsSection = defineAsyncComponent(() =>
  import('@/components/CommentsSection.vue')
)

const RichEditor = defineAsyncComponent(() =>
  import('@/components/admin/RichEditor.vue')
)
```

#### CLS (Cumulative Layout Shift)

```vue
<!-- Резервирование места для изображений -->
<img
  :src="article.cover_image"
  :width="1200"
  :height="630"
  style="aspect-ratio: 1200/630; object-fit: cover;"
/>

<!-- Резервирование для рекламы/виджетов -->
<div class="ad-slot" style="height: 250px; width: 100%;"></div>
```

### Процесс SSG с ISR

#### Build Process

```bash
# 1. Сборка данных из Laravel API
npm run build:ssg

# 2. Генерация статического HTML для всех статей
# frontend/dist/
#   ├── articles/
#   │   ├── article-1/index.html
#   │   ├── article-2/index.html
#   │   └── ...
#   ├── index.html
#   └── sitemap.xml

# 3. Деплой на CDN (Netlify, Vercel, GitHub Pages)
```

#### ISR (Incremental Static Regeneration)

При создании новой статьи:

```typescript
// webhook handler в Laravel
public function handleWebhook(Request $request): JsonResponse
{
    $article = Article::findOrFail($request->input('article_id'));

    // Trigger rebuild для конкретной страницы
    Http::post(config('services.vercel.webhook_url'), [
        'target' => "articles/{$article->slug}",
        'force' => true
    ]);

    // Или инвалидация CDN кэша
    Cloudflare::purge("https://blog.com/articles/{$article->slug}");

    return response()->json(['status' => 'ok']);
}
```

### Sitemap.xml и Robots.txt

#### Динамический sitemap endpoint

```php
// Laravel Routes
Route::get('/sitemap.xml', [SitemapController::class, 'index']);

// SitemapController
public function index(): Response
{
    $articles = Article::published()
        ->orderBy('updated_at', 'desc')
        ->get();

    return response()->view('sitemap', compact('articles'))
        ->header('Content-Type', 'text/xml');
}
```

```xml
<!-- resources/views/sitemap.blade.php -->
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://blog.com</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    @foreach($articles as $article)
    <url>
        <loc>https://blog.com/articles/{{ $article->slug }}</loc>
        <lastmod>{{ $article->updated_at->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    @endforeach
</urlset>
```

### Мета-теги для блога

#### Главная страница

```typescript
// pages/Home.vue
useHead({
  title: 'Blog Name - Технический блог',
  meta: [
    { name: 'description', content: 'Статьи о разработке, архитектуре и best practices' },
    { property: 'og:title', content: 'Blog Name' },
    { property: 'og:description', content: 'Технический блог о разработке' },
    { property: 'og:image', content: 'https://blog.com/og-image.jpg' },
    { property: 'og:type', content: 'website' },
    { property: 'og:url', content: 'https://blog.com' }
  ]
})
```

#### Статья

```typescript
// pages/ArticleDetail.vue
useHead({
  title: `${article.title} | Blog Name`,
  meta: [
    { name: 'description', content: article.excerpt },
    { property: 'article:published_time', content: article.published_at },
    { property: 'article:modified_time', content: article.updated_at },
    { property: 'article:author', content: article.author.name },
    { property: 'article:section', content: article.category?.name },
    { property: 'article:tag', content: article.tags.map(t => t.name).join(',') }
  ]
})
```

### Кэширование API для SSG

```php
// Laravel API Caching
public function index(Request $request): JsonResponse
{
    return Cache::remember('articles.index', 3600, function () use ($request) {
        return ArticleResource::collection(
            Article::published()->paginate(12)
        );
    });
}

public function show(string $slug): JsonResponse
{
    return Cache::remember("articles.{$slug}", 86400, function () use ($slug) {
        $article = Article::where('slug', $slug)->published()->firstOrFail();
        return new ArticleResource($article);
    });
}
```

### Мониторинг SEO

Инструменты для проверки:

1. **Google Search Console** - индексация, производительность, ошибки
2. **Lighthouse** - Core Web Vitals, SEO аудит
3. **Rich Results Test** - проверка Schema.org разметки
4. **Open Graph Debugger** - проверка мета-тегов для соцсетей

--

## Внешние зависимости

### Библиотеки (Backend - Laravel)

| Пакет | Назначение |
|-------|-----------|
| laravel/sanctum | SPA authentication |
| spatie/laravel-permission | Управление правами пользователей |
| spatie/laravel-medialibrary | Управление медиа-файлами |
| spatie/laravel-query-builder | Построение сложных API запросов |
| laravel-telescope | Отладка (dev) |
| barryvdh/laravel-debugbar | Отладка (dev) |
| laravel/horizon | Управление очередями (dev) |
| laravel-ide-helper | Помощь PhpStorm |
| laravel/pint | Автоматическое форматирование кода |
| intervention/image | Обработка изображений |

### Библиотеки (Frontend - Vue.js)

| Пакет | Назначение |
|-------|-----------|
| Vue 3 | Фреймворк (Composition API) |
| Vue Router 4 | Маршрутизация |
| Pinia | Управление состоянием |
| Axios | HTTP клиент |
| @vueuse/core | Composition utilities |
| @heroicons/vue | Иконки |
| vite-ssg | Static Site Generation для SEO |
| vite-plugin-sitemap | Генерация sitemap.xml |
| @unhead/vue | Управление мета-тегами (SEO) |
| medium-zoom | Увеличение изображений |
| highlight.js | Подсветка синтаксиса кода |
| Vite | Сборщик |
| ESLint + Prettier | Линтинг и форматирование |
| Vitest + Vue Test Utils | Тестирование |

### Best practices

#### DDD в Laravel

```
app/
├── Domain/
│   ├── Article/
│   │   ├── Entities/
│   │   │   ├── Article.php
│   │   │   ├── Category.php
│   │   │   └── Tag.php
│   │   ├── ValueObjects/
│   │   │   ├── ArticleContent.php
│   │   │   └── ArticleStatus.php
│   │   └── Repositories/
│   │       └── ArticleRepositoryInterface.php
│   ├── Contact/
│   └── User/
├── Application/
│   ├── Article/
│   │   ├── Commands/
│   │   │   ├── CreateArticle.php
│   │   │   └── PublishArticle.php
│   │   ├── DTOs/
│   │   └── Services/
└── Infrastructure/
    ├── Persistence/
    │   ├── Eloquent/
    │   │   ├── ArticleModel.php
    │   │   └── ArticleRepository.php
    └── Http/
        ├── Controllers/
        └── Requests/
```

#### Vue.js Structure

```
frontend/
├── src/
│   ├── components/
│   │   ├── base/          # Переиспользуемые UI компоненты
│   │   ├── layout/        # Layout компоненты
│   │   ├── article/       # Статьи
│   │   ├── home/          # Главная страница
│   │   ├── admin/         # Админ-панель
│   │   └── bento/         # Bento Grid компоненты
│   ├── composables/       # Vue Composition API логика
│   ├── stores/            # Pinia stores
│   ├── services/          # API клиенты
│   ├── types/             # TypeScript типы
│   ├── utils/             # Утилиты
│   ├── router/            # Vue Router конфигурация
│   └── App.vue
├── public/
└── vite.config.ts
```

---

## Архитектура Базы Данных

### Таблицы и схемы

#### **articles** (статьи)
```sql
CREATE TABLE articles (
    id BIGSERIAL PRIMARY KEY,
    slug VARCHAR(255) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    excerpt TEXT,
    content TEXT NOT NULL,
    cover_image_id BIGINT REFERENCES media_files(id),
    status VARCHAR(50) DEFAULT 'draft',
    published_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_articles_slug ON articles(slug);
CREATE INDEX idx_articles_status ON articles(status);
CREATE INDEX idx_articles_published_at ON articles(published_at);
CREATE INDEX idx_articles_created_at ON articles(created_at);
```

#### **categories** (рубрики)
```sql
CREATE TABLE categories (
    id BIGSERIAL PRIMARY KEY,
    slug VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_categories_slug ON categories(slug);
```

#### **article_category** (связь статьи-категории)
```sql
CREATE TABLE article_category (
    article_id BIGINT NOT NULL REFERENCES articles(id) ON DELETE CASCADE,
    category_id BIGINT NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    PRIMARY KEY (article_id, category_id)
);
```

#### **tags** (теги)
```sql
CREATE TABLE tags (
    id BIGSERIAL PRIMARY KEY,
    slug VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(50) NOT NULL
);

CREATE INDEX idx_tags_slug ON tags(slug);
CREATE INDEX idx_tags_name ON tags(name);
```

#### **article_tag** (связь статьи-теги)
```sql
CREATE TABLE article_tag (
    article_id BIGINT NOT NULL REFERENCES articles(id) ON DELETE CASCADE,
    tag_id BIGINT NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
    PRIMARY KEY (article_id, tag_id)
);
```

#### **media_files** (медиа-файлы)
```sql
CREATE TABLE media_files (
    id BIGSERIAL PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    path VARCHAR(500) NOT NULL,
    url VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    size_bytes BIGINT NOT NULL,
    width INTEGER,
    height INTEGER,
    alt_text VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_media_files_path ON media_files(path);
```

#### **contact_messages** (сообщения контактов)
```sql
CREATE TABLE contact_messages (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_contact_messages_created_at ON contact_messages(created_at);
CREATE INDEX idx_contact_messages_email ON contact_messages(email);
```

#### **site_settings** (настройки сайта)
```sql
CREATE TABLE site_settings (
    id BIGSERIAL PRIMARY KEY,
    key VARCHAR(100) UNIQUE NOT NULL,
    value TEXT,
    type VARCHAR(50) DEFAULT 'string',
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_site_settings_key ON site_settings(key);
```

#### **users** (пользователи)
```sql
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_users_email ON users(email);
```

---

## API Endpoints

### Public API (без авторизации)

| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/api/health` | Проверка работоспособности |
| GET | `/api/articles` | Список статей (пагинация, фильтры) |
| GET | `/api/articles/{slug}` | Детальная статья |
| GET | `/api/categories` | Список рубрик |
| GET | `/api/categories/{slug}` | Рубрика со статьями |
| GET | `/api/tags` | Список тегов |
| GET | `/api/tags/{slug}` | Тег со статьями |
| GET | `/api/settings` | Настройки сайта (обо мне, контакты) |
| POST | `/api/contact` | Отправка сообщения |

### Admin API (требует авторизации через Laravel Sanctum)

| Метод | Endpoint | Описание |
|-------|----------|----------|
| POST | `/api/admin/login` | Авторизация |
| POST | `/api/admin/logout` | Выход |
| GET | `/api/admin/profile` | Профиль пользователя |
| GET | `/api/admin/articles` | Список всех статей |
| POST | `/api/admin/articles` | Создание статьи |
| GET | `/api/admin/articles/{id}` | Детальная статья (edit form) |
| PUT | `/api/admin/articles/{id}` | Обновление статьи |
| PATCH | `/api/admin/articles/{id}/publish` | Публикация |
| PATCH | `/api/admin/articles/{id}/archive` | Архивация |
| DELETE | `/api/admin/articles/{id}` | Удаление статьи |
| GET | `/api/admin/categories` | Список категорий |
| POST | `/api/admin/categories` | Создание категории |
| PUT | `/api/admin/categories/{id}` | Обновление категории |
| DELETE | `/api/admin/categories/{id}` | Удаление категории |
| GET | `/api/admin/tags` | Список тегов |
| POST | `/api/admin/tags` | Создание тега |
| PUT | `/api/admin/tags/{id}` | Обновление тега |
| DELETE | `/api/admin/tags/{id}` | Удаление тега |
| POST | `/api/admin/media/upload` | Загрузка файла |
| GET | `/api/admin/media` | Список медиа-файлов |
| DELETE | `/api/admin/media/{id}` | Удаление файла |
| GET | `/api/admin/messages` | Список сообщений |
| GET | `/api/admin/messages/{id}` | Детальное сообщение |
| DELETE | `/api/admin/messages/{id}` | Удаление сообщения |
| GET | `/api/admin/settings` | Получение настроек |
| PUT | `/api/admin/settings` | Обновление настроек |
| GET | `/api/admin/users` | Список пользователей |
| POST | `/api/admin/users` | Создание пользователя |
| PUT | `/api/admin/users/{id}` | Обновление пользователя |
| DELETE | `/api/admin/users/{id}` | Удаление пользователя |

---

## Docker Compose Сервисы

```yaml
services:
  app:
    image: php:8.3-fpm
    volumes:
      - ./laravel:/var/www/html
      - ./php/local.ini:/usr/local/etc/php/conf.d/local.ini
    depends_on:
      - db
      - redis

  web:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./laravel:/var/www/html
      - ./nginx/default.conf:/etc/nginx/conf.d/default.conf
      - ./frontend/dist:/var/www/html/public/assets
    depends_on:
      - app

  db:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: blog
      POSTGRES_USER: blog_user
      POSTGRES_PASSWORD: secret
    volumes:
      - postgres_data:/var/lib/postgresql/data
    ports:
      - "5432:5432"

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data

  node:
    image: node:20-alpine
    volumes:
      - ./frontend:/app
    working_dir: /app
    command: npm run dev
    ports:
      - "5173:5173"

  adminer:
    image: adminer:latest
    ports:
      - "8080:8080"

volumes:
  postgres_data:
  redis_data:
```

---

## Frontend Компоненты (Bento Grid Design)

### Базовые компоненты
- `BaseButton` - кнопка с акцентным цветом #4A6C5B
- `BaseInput` / `BaseTextarea` - поля ввода с фоном #F5F2ED
- `BaseCard` - универсальная карточка (тень, скругление 24px)
- `BaseTag` - теги (Inter Medium 12px caps)
- `BaseModal` - модальное окно
- `BaseLoader` - индикатор загрузки

### Bento Grid Layout
- `BentoGrid` - контейнер (CSS Grid, 12 колонок, gutter 20px, margin 40px)
- `BentoCard` - ячейка сетки с динамическим span
- `BentoGridItem` - базовый компонент карточки

### Страницы
- `HeroSection` - главный баннер (Space Grotesk Bold 64px, padding 60px)
- `AboutMeCard` - блок "Обо мне" с контактной информацией
- `ContactForm` - форма обратной связи
- `ArticleCard` / `ArticleList` / `ArticleDetail` - статьи
- `CategoryList` / `CategoryCard` - рубрики
- `TagCloud` / `TagBadge` - теги

### Админ-панель
- `AdminLayout` / `AdminSidebar` / `AdminHeader` - layout админки
- `ArticleEditor` - редактор статей
- `MediaUploader` / `MediaGallery` - управление медиа
- `MessageList` - список сообщений
- `SettingsEditor` - редактор настроек сайта
- `DataTable` - универсальная таблица

### Утилитарные
- `AppIcon` - компонент иконки
- `DateTimeFormat` - форматирование даты
- `LazyImage` - ленивая загрузка изображений
- `SEOHead` - управление мета-тегами

---

## Дизайн система (Nature Distilled)

### Цвета
- Фон страницы: `#E8E4DD`
- Фон карточки: `#F5F2ED`
- Акцент (Зеленый): `#4A6C5B`
- Текст основной: `#2B2821`
- Текст вторичный: `#7A746A`

### Типографика
- Логотип: `Space Grotesk Bold / 24px`
- Hero заголовок: `Space Grotesk Bold / 64px`
- Заголовок карточки: `Space Grotesk Bold / 24px`
- Текст статьи: `Inter Regular / 18px`
- Теги/Мета: `Inter Medium / 12px (Caps)`

### Эффекты
- Тень карточки: `0 4px 12px rgba(43, 40, 33, 0.08)`
- Скругление: `24px` (универсальное)
- Отступы (Padding): `30px` (стандарт), `60px` (Hero)

### Сетка
- Колонки: `12`
- Отступы (Gutter): `20px`
- Поля (Margin): `40px`

---

## Риски

| Риск | Уровень | Митигация |
|------|---------|-----------|
| Сложность адаптивности Bento Grid на мобильных устройствах | Medium | CSS Grid с responsive breakpoints, mobile-first подход |
| Интеграция DDD с Laravel (конфликт с Eloquent) | High | Repository паттерн, четкое разделение слоев в папках |
| Управление медиа-файлами (загрузка, хранение, оптимизация) | Medium | spatie/laravel-medialibrary, image intervention, S3 или локальное хранилище |
| Performance фронтенда (множество компонентов в Bento Grid) | Medium | Lazy loading, оптимизация изображений, кэширование API, Vue Suspense |
| SEO для Vue.js SPA | **High** | SSG с vite-ssg, Unhead для мета-тегов, ISR для динамического контента |
| Задержка индексации новых статей при SSG | Medium | ISR для перегенерации конкретных страниц, webhook триггеры |
| Управление мета-тегами для SPA | Medium | Unhead с реактивным API + Schema.org разметка |
| Выбор редактора контента (Markdown vs WYSIWYG) | Low | TinyMCE или Quill для WYSIWYG, поддержка Markdown |
| Безопасность админ-панели (CSRF, XSS, SQL-injection) | High | Laravel Sanctum, валидация данных, rate limiting |
| Сложность интеграции SSG с существующей инфраструктурой | Medium | API-driven архитектура, отделение фронтенда от бэкенда |

---

## Рекомендации для Design

1. **Определить SEO стратегию для блога:**
   - Выбрать между SSG (vite-ssg) и SSR (Nuxt.js)
   - Определить подход к ISR (Incremental Static Regeneration)
   - Спроектировать структуру URL для SEO
   - Определить систему мета-тегов и Schema.org разметки

2. **Создать детальную диаграмму слоев Clean Architecture** с отображением:
   - Папок Laravel (Domain, Application, Infrastructure)
   - Vue.js структуры (components, stores, services)
   - Потока данных между слоями

2. **Создать ER-диаграмму базы данных** с:
   - Все таблицы и поля
   - Отношения (one-to-many, many-to-many)
   - Индексы и ключи

3. **Определить структуру папок для DDD**:
   ```
   app/
   ├── Domain/
   │   ├── Shared/
   │   │   ├── ValueObjects/
   │   │   └── Exceptions/
   │   ├── Article/
   │   ├── Contact/
   │   └── User/
   ├── Application/
   └── Infrastructure/
   ```

4. **Создать API спецификацию (OpenAPI/Swagger)** для:
   - Всех public endpoints
   - Всех admin endpoints
   - Схемы запросов/ответов
   - Аутентификация (Bearer token)

5. **Создать mockups Bento Grid** для:
   - Desktop (1440px): Полная сетка 12 колонок
   - Tablet (768px): Адаптивная сетка 6 колонок
   - Mobile (375px): Одиночная колонка

6. **Определить систему компонентов UI (Design System)**:
   - Переиспользуемые компоненты (BaseCard, BaseButton и т.д.)
   - Цветовая палитра (CSS variables)
   - Типографика (fonts, sizes, weights)
   - Spacing scale

7. **Определить strategy для аутентификации**:
   - Laravel Sanctum vs JWT
   - Cookie-based vs Token-based
   - Refresh токены

8. **Определить strategy для деплоя**:
   - Docker production compose
   - CI/CD pipeline (GitHub Actions)
   - Стратегия миграций БД
   - Активация фронтенда (Vite build)

9. **Определить редактор контента**:
   - WYSIWYG (TinyMCE, Quill)
   - Markdown с preview
   - Поддержка изображений и кода

10. **Создать план тестирования**:
    - Unit тесты для Domain Layer
    - Integration тесты для API
    - E2E тесты для фронтенда (Cypress или Playwright)

---