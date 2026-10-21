---
paths:
  - "app/Http/**"
  - "routes/**"
  - "bootstrap/app.php"
---

# HTTP

Controllers are delivery: allowed dependencies in `architecture.md`, Backpack CRUD specifics in `admin.md`. Shape of a controller action: `references/layers.md`.

## Controllers

- A controller action does three things: take validated input from its form request, call one application service method, turn the result into a response (view, redirect, JSON). No queries, no business decisions, no loops over domain data.
- Dependencies by constructor property promotion; the current user goes to Application as `UserId`.
- Error handling: the context's not-found exception becomes a 404 and a domain exception a translated flash message or validation error — mapped once in `app/Exceptions/ExceptionHandling.php`, not by a `try/catch` around every action.
- Client and supplier controllers, requests and views share one implementation where their behaviour is the same (`architecture.md`, Bounded contexts).

## Form requests

- Every action that accepts input has its own form request; `authorize()` checks the permission, `rules()` holds the complete request validation. This is the only place request input is validated.
- One rule set per entity: a frontend and an admin request for the same entity share their rules (a common rules class or base request) and differ only in authorization and admin-only fields.
- Values that select a column, sort direction, template or status are validated against an allowlist or an enum (`Rule::enum`).

## Routes

- HTTP verbs follow the effect: `GET` never changes state (no `GET …/set-default`, `GET …/delete/{token}`, `GET …/run`); creating is `POST`, updating `PUT`/`PATCH`, deleting `DELETE`.
- URLs use the plural resource name: `/clients`, `/clients/{client}/edit`, `/invoices/{invoice}/mark-paid`. CRUD routes are registered with `Route::resource()` (or `apiResource()`), so names and verbs come from Laravel.
- Route names: `<area>.<resource plural>.<action>` — Laravel resource actions (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`) for CRUD, a kebab-case verb for custom actions: `frontend.clients.index`, `frontend.invoices.mark-paid`, `api.clients.show`, `api.admin.users.search`. The area tells the guard: `frontend`, `api`, `api.admin`.
- Actions are referenced as `[Controller::class, 'method']`. Route parameters are typed by `whereNumber()` / an enum constraint.
- Frontend routes live inside `Route::localized()` (`{locale}` prefix, `app.available_locales`); links are generated with `route()` including the current locale. Unlocalized routes are only those an external party calls (payment callbacks, `/login` redirect target).

## Middleware

- Each middleware is registered once: aliases in `bootstrap/app.php`, and the `web` / `api` groups keep Laravel's defaults — never re-append `StartSession`, CSRF or cookie middleware that the group already contains.
- Every route group states its guard: `auth:web` (frontend), `api.require.frontend` / `api.require.backpack` (API), `admin` (Backpack). A public route is public on purpose and returns no user data.
- External callbacks (payment notifications) are excluded from CSRF explicitly and authenticate the call by asking the gateway (`payments.md`).

## API responses

- Every JSON response goes through a `JsonResource` (`app/Http/Resources/<Entity>Resource`) built from a DTO; its `toArray()` lists every field the API returns, so nothing leaves the app by accident. Never `response()->json($object)` or ad-hoc arrays in the controller.
- Laravel's default `{"data": …}` wrapping; paginated collections add `links` and `meta`. JSON keys are snake_case like request keys; the resource converts from the DTO's camelCase properties.
- Errors use Laravel's standard JSON format (401, 403, 404, 422 with `errors`), mapped once in `app/Exceptions/ExceptionHandling.php`. Another user's record is a 404 (`application.md`).
- An endpoint exists only for a real consumer; data a Livewire component can load server-side gets no JSON endpoint.
