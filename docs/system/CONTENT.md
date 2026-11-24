# Content context

Status: maintained map of the code on `main`, last checked 2026-11-24. Start with [the system overview](OVERVIEW.md). Permissions are in `rules/permissions.md`.

The public CMS pages of the website and their categories: written in the admin, read by guests on the homepage and under `/{locale}/pages`. Composition root: `ContentServiceProvider`. The context predates the standard: it has no Commands or Queries, the admin writes pages through Backpack and the model, and the public pages read models through an Application service ("Two sets of repositories" and "Interfaces without a port" in [TECH_DEBT](../TECH_DEBT.md)).

## Entities

| Entity | Table | Code |
|---|---|---|
| Page | `pages` (`database/migrations/2026_10_25_000011_create_page_tables.php`) | `App\Models\Page`, `PageDTO`, `PageWriteData`; ports `PageDtoReadRepositoryInterface`, `PageDtoWriteRepositoryInterface` (`EloquentPageDtoRepository`, mapper `EloquentPageMapper`) and the model repositories `PageReadRepositoryInterface`, `PageWriteRepositoryInterface` (`EloquentPageRepository`) |
| Page category | `page_categories` | `App\Models\PageCategory`, `PageCategoryDTO` |

The name, slug, description, content and meta fields of a page hold one value per locale as JSON. A page has an optional category and parent page, a sort order, a main image and gallery images (`HasFileUploads`), and is public when `published` is set and the time is inside its optional publishing window (`Page::scopePublished()`). On save the model fills a missing sort order and the admin who published it (`published_by`). A fresh database gets the website's pages from `PageContentSeeder`.

## Use cases

There are no Commands or Queries.

| Use case | Entry | Access | Code |
|---|---|---|---|
| Manage pages and categories | `Admin\PageCrudController`, `Admin\PageCategoryCrudController` (`page`, `page-category`) | `page.view`, `page.create`, `page.update`, `page.delete`, for categories too | Backpack writes the model; `PageRequest` and `PageCategoryRequest` merge the per-locale inputs and validate them |
| Show the website | `Frontend\PageController`, the routes in `routes/web.php` below | none: public, also for guests | `PageApplicationService` returns models from `PageReadRepositoryInterface`, except the homepage features, which come as `PageDTO`s from `PageDtoReadRepositoryInterface`; for the homepage and a page by slug it asks the Domain `PageService` first |

Nothing calls the write ports (`PageDtoWriteRepositoryInterface`, `PageWriteRepositoryInterface` and `PageWriteData`); pages are written only by the admin screens ("Unused page write ports" in [TECH_DEBT](../TECH_DEBT.md)).

## The public website

| Route | Action | Shows |
|---|---|---|
| `home` (`/{locale}`) | `homepage()` | the homepage, then the pages of the category `funkce` as features and the page `cenik` as prices; `frontend.pages.homepage-empty` when no page is published |
| `frontend.pages.index` | `index()` | every published page and the categories with their pages |
| `frontend.pages.show` (`/pages/{slug}`) | `show()` | a page with its breadcrumbs and three related pages of its category |
| `frontend.pages.category.slug`, `frontend.pages.category` | `categoryBySlug()`, `category()` | the pages of a category by its slug or id |
| `frontend.pages.search` | `search()` | the published pages whose name, description or content in the locale contains `q` (at least 3 characters) |
| `frontend.pages.sitemap` | `sitemap()` | the categories with their pages |

Every action also loads the navigation (root pages with their published children). The views are in `resources/views/frontend/pages/`; the layout's navigation and footer link to them.

| Rule | Code |
|---|---|
| Homepage | `PageService::getHomepage()`: the published page whose slug in the locale is `home`, `homepage` or `uvod`, otherwise the first published page by sort order |
| Page by slug | `PageService::getPageBySlug()`: the slug in the URL's locale, then a numeric slug as the page id, then the slug in the other locales; `show()` redirects to the page's slug in the URL's locale when it differs |

The homepage finds its features and prices by the Czech slugs that `PageContentSeeder` creates, kept as constants in `PageController`; a missing category or page leaves its section empty. The footer links to the Czech slugs `funkce`, `cenik` and `sablony`, which other locales reach through the lookup in the other locales. The search reads `q` unvalidated and `EloquentPageRepository::searchPublished()` puts the locale into raw SQL ("Page search reads its query unvalidated" and the conversion of `app/Infrastructure` in [TECH_DEBT](../TECH_DEBT.md)).

## Events

Content publishes no events and listens to none.
