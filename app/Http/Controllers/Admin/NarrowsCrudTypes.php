<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Backpack returns the validated request and the current entry with their base types; these return the
 * class the screen configured.
 */
trait NarrowsCrudTypes
{
    /**
     * @template TRequest of FormRequest
     *
     * @param  class-string<TRequest>  $class
     * @return TRequest
     */
    private function validatedRequest(string $class): FormRequest
    {
        $request = $this->crud->validateRequest();
        if (!$request instanceof $class) {
            throw new LogicException('The operation is not validated by ' . $class);
        }

        return $request;
    }

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $class
     * @return TModel
     */
    private function currentEntry(string $class): Model
    {
        $entry = $this->crud->getCurrentEntry();
        if (!$entry instanceof $class) {
            throw new NotFoundHttpException;
        }

        return $entry;
    }
}
