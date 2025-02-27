<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Http\Controllers\UserController;

class GenerateInvoiceJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    protected $payment;

    public function __construct($payment)
    {
        $this->payment = $payment;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $controller = new UserController();
        $controller->generateInvoice($this->payment);
    }
}
