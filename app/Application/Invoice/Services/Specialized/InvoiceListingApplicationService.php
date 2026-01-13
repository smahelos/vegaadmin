<?php

namespace App\Application\Invoice\Services\Specialized;

use App\Application\Invoice\Contracts\InvoiceListingApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceReadRepositoryInterface;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\DTO\InvoiceDTO;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Service for invoice listing and search operations
 */
class InvoiceListingApplicationService implements InvoiceListingApplicationServiceInterface
{
    public function __construct(
        private InvoiceReadRepositoryInterface $invoiceReadRepository
    ) {}

    /**
     * Get all invoices for user
     */
    public function getAll(UserId $userId): Collection
    {
        $invoices = $this->invoiceReadRepository->allForUser($userId->toInt());
        
        return $invoices->map(function ($invoice) {
            return InvoiceDTO::fromArray($invoice->toArray());
        });
    }

    /**
     * Get all invoices with filters applied
     */
    public function getAllWithFilters(UserId $userId, array $filters = []): Collection
    {
        // For now, use the basic getAll and apply filters
        $invoices = $this->getAll($userId);
        
        return $this->applyFilters($invoices, $filters);
    }

    /**
     * Get paginated invoices with search
     */
    public function getWithSearch(UserId $userId, array $searchParams = [], int $perPage = 15): LengthAwarePaginator
    {
        // Get all invoices first
        $allInvoices = $this->getAll($userId);
        
        // Apply search if provided
        if (!empty($searchParams['query'])) {
            $allInvoices = $this->searchInCollection($allInvoices, $searchParams['query']);
        }
        
        // Apply filters if provided
        if (!empty($searchParams['filters'])) {
            $allInvoices = $this->applyFilters($allInvoices, $searchParams['filters']);
        }
        
        // Manual pagination
        $currentPage = request()->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $paginatedItems = $allInvoices->slice($offset, $perPage);
        
        return new LengthAwarePaginator(
            $paginatedItems,
            $allInvoices->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url()]
        );
    }

    /**
     * Get invoice by ID for viewing
     */
    public function getById(InvoiceId $invoiceId, UserId $userId): InvoiceDTO
    {
        $invoice = $this->invoiceReadRepository->findForUser($userId->toInt(), $invoiceId->getValue());
        
        if (!$invoice) {
            throw new ModelNotFoundException("Invoice not found or access denied");
        }
        
        return InvoiceDTO::fromArray($invoice->toArray());
    }

    /**
     * Get recent invoices for user
     */
    public function getRecent(UserId $userId, int $limit = 10): Collection
    {
        $allInvoices = $this->getAll($userId);
        
        return $allInvoices
            ->sortByDesc('created_at')
            ->take($limit);
    }

    /**
     * Search invoices by various criteria
     */
    public function search(UserId $userId, string $query, array $fields = []): Collection
    {
        $allInvoices = $this->getAll($userId);
        
        return $this->searchInCollection($allInvoices, $query, $fields);
    }

    /**
     * Get invoices for specific date range
     */
    public function getByDateRange(UserId $userId, \DateTime $from, \DateTime $to): Collection
    {
        $allInvoices = $this->getAll($userId);
        
        return $allInvoices->filter(function ($invoice) use ($from, $to) {
            $issueDate = $invoice->issue_date ? new \DateTime($invoice->issue_date) : null;
            
            if (!$issueDate) {
                return false;
            }
            
            return $issueDate >= $from && $issueDate <= $to;
        });
    }

    /**
     * Get invoices with specific criteria
     */
    public function getByCriteria(UserId $userId, array $criteria): Collection
    {
        $allInvoices = $this->getAll($userId);
        
        return $this->applyFilters($allInvoices, $criteria);
    }

    /**
     * Apply filters to collection
     */
    private function applyFilters(Collection $invoices, array $filters): Collection
    {
        return $invoices->filter(function ($invoice) use ($filters) {
            foreach ($filters as $field => $value) {
                if (empty($value)) {
                    continue;
                }
                
                switch ($field) {
                    case 'status':
                        if ($invoice->status !== $value) {
                            return false;
                        }
                        break;
                        
                    case 'client_id':
                        if ($invoice->client_id != $value) {
                            return false;
                        }
                        break;
                        
                    case 'supplier_id':
                        if ($invoice->supplier_id != $value) {
                            return false;
                        }
                        break;
                        
                    case 'payment_method_id':
                        if ($invoice->payment_method_id != $value) {
                            return false;
                        }
                        break;
                        
                    case 'currency':
                        if ($invoice->currency !== $value) {
                            return false;
                        }
                        break;
                        
                    case 'min_amount':
                        if ($invoice->total_amount->getValue() < $value) {
                            return false;
                        }
                        break;
                        
                    case 'max_amount':
                        if ($invoice->total_amount->getValue() > $value) {
                            return false;
                        }
                        break;
                }
            }
            
            return true;
        });
    }

    /**
     * Search in collection by query
     */
    private function searchInCollection(Collection $invoices, string $query, array $fields = []): Collection
    {
        $searchFields = !empty($fields) ? $fields : [
            'number', 'payment_reference', 'constant_code', 'invoice_text'
        ];
        
        $query = strtolower($query);
        
        return $invoices->filter(function ($invoice) use ($query, $searchFields) {
            foreach ($searchFields as $field) {
                $value = $invoice->{$field} ?? '';
                if (str_contains(strtolower($value), $query)) {
                    return true;
                }
            }
            
            return false;
        });
    }
}
