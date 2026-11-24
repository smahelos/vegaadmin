<?php

declare(strict_types=1);

namespace App\Application\Content\Services;

use App\Application\Content\Contracts\PageReadRepositoryInterface;
use App\Domain\Content\Contracts\PageDtoReadRepositoryInterface;
use App\Domain\Content\Contracts\PageServiceInterface;
use App\Domain\Content\DTO\PageDTO;
use App\Models\Page;
use App\Models\PageCategory;
use Illuminate\Database\Eloquent\Collection;

final class PageApplicationService
{
    public function __construct(
        private readonly PageReadRepositoryInterface $pages,
        private readonly PageServiceInterface $pageDomainService,
        private readonly PageDtoReadRepositoryInterface $pageDtos,
    ) {}

    /**
     * Resolve Eloquent Page model by PageDTO (published context).
     * Keeps UI on models while selection logic lives in Domain.
     */
    private function resolvePublishedModelByDto(?PageDTO $dto): ?Page
    {
        if (!$dto) {
            return null;
        }

        return $this->pages->findPublishedById($dto->id);
    }

    // delegate to domain service and map back to model
    // we use domain service, because it contains the business logic to select propper the homepage
    public function getHomepage(?string $locale = null): ?Page
    {
        $dto = $this->pageDomainService->getHomepage($locale);
        if ($dto) {
            $model = $this->resolvePublishedModelByDto($dto);
            if ($model) {
                return $model;
            }
        }

        // Fallback: if domain returns null but model repo can still provide a first published.
        return $this->pages->firstPublished();
    }

    // delegate to domain service and map back to model
    // we use domain service, because it contains the business logic to select propper page (not just by slug)
    // we save Page slug data as JSON, so searching by slug is not trivial in Eloquent
    // also, we want to support searching by ID if slug is numeric
    public function getPageBySlug(string $slug, ?string $locale = null): ?Page
    {
        $dto = $this->pageDomainService->getPageBySlug($slug, $locale);

        return $this->resolvePublishedModelByDto($dto);
    }

    // we simply delegate to the model repo, as NO business logic is needed
    public function getPagesForNavigation(): Collection
    {
        return $this->pages->getRootNavigationPages();
    }

    // we simply delegate to the model repo, as NO business logic is needed
    /** @return Collection<int, Page> */
    public function getPagesByCategory(int $categoryId): Collection
    {
        return $this->pages->getPublishedByCategory($categoryId);
    }

    /** @return list<PageDTO> */
    public function getPagesByCategorySlug(string $slug, string $locale): array
    {
        return $this->pageDtos->getPublishedByCategorySlug($slug, $locale);
    }

    // we simply delegate to the model repo, as NO business logic is needed
    public function getCategoriesWithPages(): Collection
    {
        return $this->pages->getCategoriesWithPublishedPages();
    }

    // we simply delegate to the model repo, as NO business logic is needed
    public function searchPages(string $keyword, string $locale): Collection
    {
        return $this->pages->searchPublished($keyword, $locale);
    }

    // we simply delegate to the model repo, as NO business logic is needed
    /** @return array<int, array{name:string,slug:string}> */
    public function getBreadcrumbs(Page $page): array
    {
        return $page->breadcrumbs;
    }

    // we simply delegate to the model repo, as NO business logic is needed
    public function getRelatedPages(Page $page, int $limit = 5): Collection
    {
        if (!$page->category_id) {
            return new Collection;
        }

        return $this->pages->getRelatedByCategory(
            $page->category_id,
            $page->id,
            $limit,
        );
    }

    // we simply delegate to the model repo, as NO business logic is needed
    public function getAllPublishedPages(): Collection
    {
        return $this->pages->getAllPublishedWithCategory();
    }

    // we simply delegate to the model repo, as NO business logic is needed
    public function getCategoryBySlug(string $slug, string $locale): ?PageCategory
    {
        return $this->pages->findCategoryBySlug($slug, $locale);
    }
}
