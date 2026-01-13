<?php

namespace App\Application\Invoice\DTO;

/** Unified status enum for all invoice application layer actions (mutation, pdf, frontend actions). */
enum InvoiceActionStatus: string
{
    case SUCCESS = 'success';
    case FAILURE = 'failure';
}
