<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class MoneybirdService
{
    protected string $apiUrl;
    protected string $apiToken;
    protected string $administrationId;

    public function __construct()
    {
        $this->apiUrl = config('services.moneybird.api_url');
        $this->apiToken = config('services.moneybird.api_token');
        $this->administrationId = config('services.moneybird.administration_id');
    }

    /**
     * Create a new contact in Moneybird.
     *
     * @param array $customerData
     * @return array|null
     * @throws \Exception
     */
    public function createContact(array $customerData): ?array
    {
        try {
            $response = $this->makeRequest('POST', '/contacts', [
                'contact' => [
                    'company_name' => $customerData['company'] ?? '',
                    'firstname' => $customerData['first_name'],
                    'lastname' => $customerData['last_name'],
                    'email' => $customerData['email'],
                    'phone' => $customerData['phone'] ?? '',
                    'address1' => $customerData['address'] ?? '',
                    'zipcode' => $customerData['postal_code'] ?? '',
                    'city' => $customerData['city'] ?? '',
                    'country' => $customerData['country'] ?? 'NL',
                    'tax_number' => $customerData['vat_number'] ?? '',
                    'send_invoices_to_email' => $customerData['email'],
                    'send_estimates_to_email' => $customerData['email'],
                ],
            ]);

            Log::info('Moneybird contact created', [
                'contact_id' => $response['id'] ?? null,
            ]);

            return $response;
        } catch (RequestException $e) {
            Log::error('Failed to create Moneybird contact', [
                'error' => $e->getMessage(),
                'response' => $e->response->json() ?? null,
            ]);
            throw new \Exception('Failed to create Moneybird contact: ' . $e->getMessage());
        }
    }

    /**
     * Get contact by ID.
     *
     * @param string $contactId
     * @return array|null
     * @throws \Exception
     */
    public function getContact(string $contactId): ?array
    {
        try {
            return $this->makeRequest('GET', "/contacts/{$contactId}");
        } catch (RequestException $e) {
            Log::error('Failed to get Moneybird contact', [
                'contact_id' => $contactId,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to get Moneybird contact: ' . $e->getMessage());
        }
    }

    /**
     * Create a new sales invoice.
     *
     * @param string $contactId
     * @param array $orderData
     * @param array $lineItems
     * @return array|null
     * @throws \Exception
     */
    public function createInvoice(string $contactId, array $orderData, array $lineItems): ?array
    {
        try {
            $details = [];
            foreach ($lineItems as $item) {
                $details[] = [
                    'description' => $item['description'],
                    'amount' => $item['quantity'] ?? 1,
                    'price' => $item['price'],
                    'tax_rate_id' => $this->getDefaultTaxRateId(),
                ];
            }

            $response = $this->makeRequest('POST', '/sales_invoices', [
                'sales_invoice' => [
                    'contact_id' => $contactId,
                    'reference' => $orderData['order_number'] ?? '',
                    'invoice_date' => now()->format('Y-m-d'),
                    'details_attributes' => $details,
                ],
            ]);

            Log::info('Moneybird invoice created', [
                'invoice_id' => $response['id'] ?? null,
                'contact_id' => $contactId,
            ]);

            return $response;
        } catch (RequestException $e) {
            Log::error('Failed to create Moneybird invoice', [
                'contact_id' => $contactId,
                'error' => $e->getMessage(),
                'response' => $e->response->json() ?? null,
            ]);
            throw new \Exception('Failed to create Moneybird invoice: ' . $e->getMessage());
        }
    }

    /**
     * Send an invoice by email.
     *
     * @param string $invoiceId
     * @param string $email
     * @return bool
     * @throws \Exception
     */
    public function sendInvoice(string $invoiceId, string $email): bool
    {
        try {
            $this->makeRequest('PATCH', "/sales_invoices/{$invoiceId}/send_invoice", [
                'sales_invoice_sending' => [
                    'delivery_method' => 'Email',
                    'email_address' => $email,
                    'email_message' => 'Beste klant, hierbij ontvangt u uw factuur.',
                ],
            ]);

            Log::info('Moneybird invoice sent', [
                'invoice_id' => $invoiceId,
                'email' => $email,
            ]);

            return true;
        } catch (RequestException $e) {
            Log::error('Failed to send Moneybird invoice', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get invoice by ID.
     *
     * @param string $invoiceId
     * @return array|null
     * @throws \Exception
     */
    public function getInvoice(string $invoiceId): ?array
    {
        try {
            return $this->makeRequest('GET', "/sales_invoices/{$invoiceId}");
        } catch (RequestException $e) {
            Log::error('Failed to get Moneybird invoice', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to get Moneybird invoice: ' . $e->getMessage());
        }
    }

    /**
     * Register a payment for an invoice.
     *
     * @param string $invoiceId
     * @param float $amount
     * @param string $paymentDate
     * @return array|null
     * @throws \Exception
     */
    public function registerPayment(string $invoiceId, float $amount, string $paymentDate = null): ?array
    {
        try {
            $response = $this->makeRequest('POST', "/sales_invoices/{$invoiceId}/payments", [
                'payment' => [
                    'payment_date' => $paymentDate ?? now()->format('Y-m-d'),
                    'price' => $amount,
                    'financial_account_id' => $this->getDefaultFinancialAccountId(),
                ],
            ]);

            Log::info('Payment registered in Moneybird', [
                'invoice_id' => $invoiceId,
                'amount' => $amount,
            ]);

            return $response;
        } catch (RequestException $e) {
            Log::error('Failed to register payment in Moneybird', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to register payment: ' . $e->getMessage());
        }
    }

    /**
     * Get default tax rate ID (21% for NL).
     *
     * @return string
     */
    protected function getDefaultTaxRateId(): string
    {
        // This should be configured or fetched from Moneybird
        // For now, return a placeholder
        return config('services.moneybird.default_tax_rate_id', '1');
    }

    /**
     * Get default financial account ID.
     *
     * @return string
     */
    protected function getDefaultFinancialAccountId(): string
    {
        // This should be configured or fetched from Moneybird
        return config('services.moneybird.default_financial_account_id', '1');
    }

    /**
     * Make an HTTP request to Moneybird API.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws RequestException
     */
    protected function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = "{$this->apiUrl}/{$this->administrationId}{$endpoint}";

        $response = Http::timeout(30)
            ->withToken($this->apiToken)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->{strtolower($method)}($url, $data);

        $response->throw();

        return $response->json() ?? [];
    }
}
