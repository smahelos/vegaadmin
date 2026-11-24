# Product context

Status: maintained map of the code on `main`, last checked 2026-11-23. Start with [the system overview](OVERVIEW.md). The entity limit is described in [USER.md](USER.md#entity-limits), money in `rules/domain.md`; how a picked product becomes an invoice line is in [INVOICE.md](INVOICE.md#creating-and-updating-an-invoice).

The user's own catalogue of products with a price, currency, VAT rate and category, picked into invoice lines, and the product categories shared by all users. Composition root: `ProductServiceProvider`.

## Entities

| Entity | Table | Code |
|---|---|---|
| Product | `products`, owned by a user; `slug` unique per user | `App\Models\Product`, `ProductDTO`, `ProductId`, `ProductWriteData`, `ProductSearch`, `ProductSort`; ports `ProductDtoReadRepositoryInterface` and `ProductDtoWriteRepositoryInterface` (`EloquentProductDtoRepository`, mapper `EloquentProductMapper`) |
| Catalogue product | `products`: the active products of one currency, as offered by the admin invoice editor | `CatalogProductDTO`; port `CatalogProductReadRepositoryInterface` (`EloquentCatalogProductRepository`, mapper `EloquentCatalogProductMapper`) |
| Product category | `product_categories` (reference data without an owner, the same for every user) | `App\Models\ProductCategory`; no DTO, no port |
| Invoice line | `invoice_products` | Invoice's entity ([INVOICE.md](INVOICE.md#entities)); its write port `InvoiceProductDtoWriteRepositoryInterface` lives here (`EloquentInvoiceProductDtoRepository` over `EloquentInvoiceProductRepository`, mapper `EloquentInvoiceProductMapper`) |

Values: `ProductName`, `ProductPrice` (a float, "Product prices are not `Money` on the write path" in [TECH_DEBT](../TECH_DEBT.md)); `ProductDTO::$price` is `Money`, the unit Shared's `Unit`. The VAT rate is a row of `taxes` (`App\Models\Tax`, the rates with slug `dph`).

The Application port `InvoiceProductWriteRepositoryInterface` is bound in the provider but used by no code ("Two sets of repositories" in [TECH_DEBT](../TECH_DEBT.md)).

## Use cases

Commands in `app/Application/Product/Commands/`, queries in `app/Application/Product/Queries/`. The frontend uses `Frontend\ProductController` (`frontend.products` and `frontend.product.*` routes) and the Livewire components in `app/Livewire/Product/`; the admin uses `Admin\ProductCrudController`.

| Use case | Frontend | Admin | Access | Limit |
|---|---|---|---|---|
| `CreateProduct` | `store` (`allowed`) | `store` | `product.create` with owner | products (capacity) |
| `UpdateProduct` | `update` (`allowed`) | `update` | `product.update` with owner | — |
| `DeleteProduct` | `destroy` | `destroy` | `product.delete` | — |
| `GetProduct` | `show`, `ProductListSelect::selectProduct()` | — | `product.view` | — |
| `GetProductForEdit` | `edit` | — | `product.update` | — |
| `ListProducts` | `ProductList` (the products page), `ProductListSelect` (the product picker of the invoice form) | Backpack's list operation, reading the model | `product.view` | — |

"With owner" is `Access::permissionWithOwner()` (`rules/permissions.md`). `CreateProductHandler` calls `EntityLimit::assertCanCreate()` for `LimitedEntity::PRODUCT`; a product is a capacity limit, so deleting one frees a place (D-047). No product route has a rate limiter. `DeleteProduct` removes the image file after the commit, so it implements `CallsOutsideDatabaseInterface` and opens its own transaction.

The admin invoice editor reads the catalogue through Invoice's `ListInvoiceCatalogProducts` (`InvoiceItemsController`): up to 50 active products of the invoice's currency. The frontend picker `ProductListSelect` lists every product of the owner, active or not and in any currency, and leaves out those already on the invoice (`ProductSearch::$excluded`, sent by `resources/js/product-selector.js`); a line in another currency than the invoice is then refused by `InvoiceItemInput` ("The invoice product picker offers products the invoice refuses" in [TECH_DEBT](../TECH_DEBT.md)).

Product categories have no use cases: `Admin\ProductCategoryCrudController` writes them through the model, with the `product.*` permissions ("Product categories share the product permissions" in [TECH_DEBT](../TECH_DEBT.md)); `ProductCategoryRequest` validates them.

## Creating and updating a product

| Step | Code |
|---|---|
| Form fields | `ProductFormFields` (shared trait; no unit field, "Products have no unit field" in [TECH_DEBT](../TECH_DEBT.md)), the category and tax choices from `ProductFormDataServiceInterface` (`ProductFormDataService`, cached 6 hours); views `resources/views/frontend/products/{create,edit}.blade.php`; the admin builds its own fields in `ProductCrudController`, with the owner as a field |
| Validation | `ProductRequest` (frontend), `Admin\ProductRequest`; both build on `ProductInput` (price above 0, currency from `Currency`, slug unique per owner, image size from `config/file_upload.php`, `product_image`) |
| Request to Command | `toWriteData()` of either request gives `ProductWriteData`; the owner is the signed-in user, in the admin the chosen `user_id` |
| Handler | `CreateProductHandler`, `UpdateProductHandler`, `DeleteProductHandler`; update and delete refuse a product of another owner with `ProductNotFoundException` |
| Domain | `ProductService`: `ProductCreationValidator` checks the name, price and currency and fills the defaults (slug from the name, CZK, `Unit` pieces, active); the owner's first product becomes the default one. `deleteProduct()` refuses a product used on an invoice with `ProductInUseException` |
| Storage | `EloquentProductDtoRepository`: the owner comes from the scope, never from the data |

What the model still does on save: the image is stored by `Product`'s image mutator through `HasFileUploads`, inside the transaction ("Product images are stored inside the transaction" in [TECH_DEBT](../TECH_DEBT.md)); the slug comes from `Sluggable`; marking a product default clears the flag on the owner's other products (`Product::boot()`). Nothing outside the product pages reads the default flag ("Default product has no effect" in [TECH_DEBT](../TECH_DEBT.md)).

A product used on an invoice cannot be deleted: the `invoice_products` foreign key restricts it and `ProductInUseException` is answered with a flash message (`ExceptionHandling`, 409 for JSON; the admin's `destroy` answers it itself). Archiving instead is "Products cannot be archived" in [TECH_DEBT](../TECH_DEBT.md) (step 12).

## Products on invoices

An invoice line keeps its own copy of the name, price, unit, VAT rate and currency, and `product_id` only as a link: changing or deleting a product never changes an existing invoice. Picking a product in the frontend form dispatches `product-selected` with these values from `ProductListSelect::selectProduct()`; `resources/js/invoice-item-manager.js` fills the line. Saving the invoice checks that every linked product belongs to the invoice's owner (Invoice's `InvoiceProductOwnership`, through `CatalogProductReadRepositoryInterface::isOwnedBy()`). `InvoiceService` writes the lines through `InvoiceProductDtoWriteRepositoryInterface`.

## Events

No Product use case publishes a domain event. `ProductObserver` publishes `FormDataChanged` (`products`) when a product is created or deleted, or its name, price or category changes; `ProductCategoryObserver` publishes it (`categories`) when a category is created, renamed or deleted. `InvalidateFormDataCache` then clears the cached form choices. The observers are "Move cache invalidation from observers to domain event handlers" in [TECH_DEBT](../TECH_DEBT.md).
