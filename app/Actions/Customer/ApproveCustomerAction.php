<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Events\Customer\CustomerApproved;
use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;

final class ApproveCustomerAction
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {}

    public function execute(Customer $customer): Customer
    {
        $customer = $this->customerRepository->updateStatus($customer, 'active');

        event(new CustomerApproved($customer));

        return $customer;
    }
}
