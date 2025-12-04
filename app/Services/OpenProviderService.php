<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenProviderService
{
    protected string $apiUrl;

    protected string $username;

    protected string $password;

    protected ?string $token = null;

    public function __construct()
    {
        $this->apiUrl = config('services.openprovider.api_url');
        $this->username = config('services.openprovider.username');
        $this->password = config('services.openprovider.password');
    }

    /**
     * Authenticate with OpenProvider API and get token.
     *
     * @throws \Exception
     */
    protected function getAuthToken(): string
    {
        if ($this->token) {
            return $this->token;
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->apiUrl}/auth/login", [
                    'username' => $this->username,
                    'password' => $this->password,
                ]);

            $response->throw();

            $data = $response->json();
            $this->token = $data['data']['token'] ?? null;

            if (! $this->token) {
                throw new \Exception('Failed to get authentication token from OpenProvider');
            }

            Log::info('OpenProvider authenticated successfully');

            return $this->token;
        } catch (RequestException $e) {
            Log::error('Failed to authenticate with OpenProvider', [
                'error' => $e->getMessage(),
                'response' => $e->response->json() ?? null,
            ]);
            throw new \Exception('Failed to authenticate with OpenProvider: '.$e->getMessage());
        }
    }

    /**
     * Check if a domain is available for registration.
     *
     * @throws \Exception
     */
    public function checkDomainAvailability(string $domainName): bool
    {
        try {
            $response = $this->makeRequest('POST', '/domains/check', [
                'domains' => [
                    ['name' => $domainName],
                ],
            ]);

            $result = $response['data']['results'][0] ?? null;

            if (! $result) {
                throw new \Exception('Invalid response from OpenProvider');
            }

            $isAvailable = ($result['status'] ?? '') === 'free';

            Log::info('Domain availability checked', [
                'domain' => $domainName,
                'available' => $isAvailable,
            ]);

            return $isAvailable;
        } catch (RequestException $e) {
            Log::error('Failed to check domain availability', [
                'domain' => $domainName,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to check domain availability: '.$e->getMessage());
        }
    }

    /**
     * Register a new domain.
     *
     * @param  int  $period  Years (default: 1)
     *
     * @throws \Exception
     */
    public function registerDomain(string $domainName, array $contactData, int $period = 1): ?array
    {
        try {
            // First, create or get contact handle
            $ownerHandle = $this->createContact($contactData);

            // Register the domain
            $response = $this->makeRequest('POST', '/domains', [
                'domain' => [
                    'name' => $domainName,
                    'period' => $period,
                    'owner_handle' => $ownerHandle,
                    'admin_handle' => $ownerHandle,
                    'tech_handle' => $ownerHandle,
                    'billing_handle' => $ownerHandle,
                    'ns_group' => 'dns-openprovider', // Use OpenProvider DNS
                ],
                'autorenew' => 'on',
            ]);

            Log::info('Domain registered successfully', [
                'domain' => $domainName,
                'response' => $response,
            ]);

            return $response['data'] ?? null;
        } catch (RequestException $e) {
            Log::error('Failed to register domain', [
                'domain' => $domainName,
                'error' => $e->getMessage(),
                'response' => $e->response->json() ?? null,
            ]);
            throw new \Exception('Failed to register domain: '.$e->getMessage());
        }
    }

    /**
     * Create a contact handle.
     *
     * @throws \Exception
     */
    protected function createContact(array $contactData): string
    {
        try {
            $response = $this->makeRequest('POST', '/customers/contacts', [
                'company_name' => $contactData['company'] ?? '',
                'first_name' => $contactData['first_name'],
                'last_name' => $contactData['last_name'],
                'email' => $contactData['email'],
                'phone' => [
                    'country_code' => '+31',
                    'area_code' => '',
                    'subscriber_number' => preg_replace('/[^0-9]/', '', $contactData['phone'] ?? ''),
                ],
                'address' => [
                    'street' => $contactData['address'] ?? '',
                    'number' => '',
                    'zipcode' => $contactData['postal_code'] ?? '',
                    'city' => $contactData['city'] ?? '',
                    'country' => $contactData['country'] ?? 'NL',
                ],
            ]);

            $handle = $response['data']['handle'] ?? null;

            if (! $handle) {
                throw new \Exception('Failed to get contact handle from response');
            }

            Log::info('Contact handle created', ['handle' => $handle]);

            return $handle;
        } catch (RequestException $e) {
            Log::error('Failed to create contact', [
                'error' => $e->getMessage(),
                'response' => $e->response->json() ?? null,
            ]);
            throw new \Exception('Failed to create contact: '.$e->getMessage());
        }
    }

    /**
     * Get domain information.
     *
     * @throws \Exception
     */
    public function getDomainInfo(string $domainName): ?array
    {
        try {
            $response = $this->makeRequest('GET', "/domains/{$domainName}");

            return $response['data'] ?? null;
        } catch (RequestException $e) {
            Log::error('Failed to get domain info', [
                'domain' => $domainName,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to get domain info: '.$e->getMessage());
        }
    }

    /**
     * Renew a domain.
     *
     * @throws \Exception
     */
    public function renewDomain(string $domainName, int $period = 1): ?array
    {
        try {
            $response = $this->makeRequest('POST', "/domains/{$domainName}/renew", [
                'period' => $period,
            ]);

            Log::info('Domain renewed', [
                'domain' => $domainName,
                'period' => $period,
            ]);

            return $response['data'] ?? null;
        } catch (RequestException $e) {
            Log::error('Failed to renew domain', [
                'domain' => $domainName,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to renew domain: '.$e->getMessage());
        }
    }

    /**
     * Update domain nameservers.
     *
     * @throws \Exception
     */
    public function updateNameservers(string $domainName, array $nameservers): bool
    {
        try {
            $this->makeRequest('PUT', "/domains/{$domainName}", [
                'name_servers' => array_map(fn ($ns) => ['name' => $ns], $nameservers),
            ]);

            Log::info('Nameservers updated', [
                'domain' => $domainName,
                'nameservers' => $nameservers,
            ]);

            return true;
        } catch (RequestException $e) {
            Log::error('Failed to update nameservers', [
                'domain' => $domainName,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Make an HTTP request to OpenProvider API.
     *
     * @throws RequestException
     */
    protected function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $token = $this->getAuthToken();

        $response = Http::timeout(30)
            ->withToken($token)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->{strtolower($method)}($this->apiUrl.$endpoint, $data);

        $response->throw();

        return $response->json() ?? [];
    }
}
