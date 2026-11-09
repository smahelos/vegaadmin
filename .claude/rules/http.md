---
paths:
  - "app/Http/**"
  - "routes/**"
  - "bootstrap/app.php"
---

# HTTP

Controllers are delivery: allowed dependencies in `architecture.md`, Backpack CRUD specifics in `admin.md`. Shape of a controller action: `references/layers.md`.

## Controllers

- A controller action does three things: take validated input from its form request, run one use case through the gate, turn the result into a response (view, redirect, JSON). No queries, no business decisions, no loops over domain data.
- Dependencies by constructor property promotion; the gate is `private readonly AuthorizingUseCaseRunnerInterface $useCases` and the current user goes to it as `Actor` (`permissions.md`).
- Error handling: the context's not-found exception becomes a 404, `PermissionDeniedException` a 403 and a domain exception a translated flash message or validation error, mapped once in `app/Exceptions/ExceptionHandling.php`. A controller or Livewire action catches only specific exceptions it answers differently; a broad catch around a use case is not allowed, because it would turn a 403 or 404 into a generic error.
- An action never authorizes apart from its effect: no `authorize…()` call followed by the action. A page showing a form runs its own Query, which declares the same permission as the write.
- Business and contact controllers, requests and views share one implementation where their behaviour is the same (`architecture.md`, Bounded contexts).

## Form requests

- Every action that accepts input has its own form request; `rules()` holds the complete request validation and is the only place request input is validated. A form request has no `authorize()` and checks neither permissions nor ownership (`permissions.md`). A user without the permission gets a 403 from the route before validation (Routes); the owner check runs in the gate after validation, so rules must reveal nothing about another user's records.
- One rule set per entity: a frontend and an admin request for the same entity share their rules (a common rules class or base request) and differ only in admin-only fields.
- Values that select a column, sort direction, template or status are validated against an allowlist or an enum (`Rule::enum`).

## Routes

- HTTP verbs follow the effect: `GET` never changes state (no `GET …/set-default`, `GET …/delete/{token}`, `GET …/run`); creating is `POST`, updating `PUT`/`PATCH`, deleting `DELETE`.
- URLs use the plural resource name: `/contacts`, `/contacts/{contact}/edit`, `/invoices/{invoice}/mark-paid`. CRUD routes are registered with `Route::resource()` (or `apiResource()`), so names and verbs come from Laravel.
- Route names: `<area>.<resource plural>.<action>` — Laravel resource actions (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`) for CRUD, a kebab-case verb for custom actions: `frontend.contacts.index`, `frontend.invoices.mark-paid`, `api.contacts.show`, `api.admin.users.search`. The area tells the guard: `frontend`, `api`, `api.admin`.
- Actions are referenced as `[Controller::class, 'method']`. Route parameters are typed by `whereNumber()` / an enum constraint.
- An action with a form request names its use case on the route: `$route->middleware('allowed:' . CreateProduct::class)` (alias in `bootstrap/app.php`, calls `$useCases->allowed(...)`), so a missing permission is a 403 before validation. Backpack CRUD gets this from `setup()`. Forgetting it changes only the order of the responses, because the gate still protects the action. The two places name one use case and nothing connects them, so a short comment at the gate call and at the route says that the other one must change too (example: `references/layers.md`).
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
