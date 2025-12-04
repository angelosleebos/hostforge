<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class PleskService
{
    protected string $host;
    protected string $username;
    protected string $password;
    protected string $port;
    protected string $protocol;
    protected string $baseUrl;

    public function __construct()
    {
        $this->host = config('services.plesk.host');
        $this->username = config('services.plesk.username');
        $this->password = config('services.plesk.password');
        $this->port = config('services.plesk.port', '8443');
        $this->protocol = config('services.plesk.protocol', 'https');
        $this->baseUrl = "{$this->protocol}://{$this->host}:{$this->port}/api/v2";
    }

    /**
     * Create a new customer in Plesk.
     *
     * @param array $customerData
     * @return array|null
     * @throws \Exception
     */
    public function createCustomer(array $customerData): ?array
    {
        try {
            $response = $this->makeRequest('POST', '/clients', [
                'company' => $customerData['company'] ?? $customerData['name'],
                'name' => $customerData['name'],
                'login' => $this->generateLogin($customerData['email']),
                'email' => $customerData['email'],
                'phone' => $customerData['phone'] ?? '',
                'address' => $customerData['address'] ?? '',
                'city' => $customerData['city'] ?? '',
                'country' => $customerData['country'] ?? 'NL',
                'postcode' => $customerData['postal_code'] ?? '',
            ]);

            Log::info('Plesk customer created', ['response' => $response]);

            return $response;
        } catch (RequestException $e) {
            Log::error('Failed to create Plesk customer', [
                'error' => $e->getMessage(),
                'response' => $e->response->json() ?? null,
            ]);
            throw new \Exception('Failed to create Plesk customer: ' . $e->getMessage());
        }
    }

    /**
     * Create a new domain in Plesk.
     *
     * @param string $clientId
     * @param string $domainName
     * @param array $options
     * @return array|null
     * @throws \Exception
     */
    public function createDomain(string $clientId, string $domainName, array $options = []): ?array
    {
        try {
            $response = $this->makeRequest('POST', '/domains', [
                'name' => $domainName,
                'owner_id' => $clientId,
                'hosting_type' => 'virtual',
                'hosting_settings' => array_merge([
                    'ftp_login' => $this->generateFtpLogin($domainName),
                    'ftp_password' => $this->generateSecurePassword(),
                ], $options['hosting_settings'] ?? []),
            ]);

            Log::info('Plesk domain created', [
                'domain' => $domainName,
                'response' => $response,
            ]);

            return $response;
        } catch (RequestException $e) {
            Log::error('Failed to create Plesk domain', [
                'domain' => $domainName,
                'error' => $e->getMessage(),
                'response' => $e->response->json() ?? null,
            ]);
            throw new \Exception('Failed to create Plesk domain: ' . $e->getMessage());
        }
    }

    /**
     * Suspend a domain in Plesk.
     *
     * @param string $domainId
     * @return bool
     */
    public function suspendDomain(string $domainId): bool
    {
        try {
            $this->makeRequest('PUT', "/domains/{$domainId}", [
                'status' => 'suspended',
            ]);

            Log::info('Plesk domain suspended', ['domain_id' => $domainId]);

            return true;
        } catch (RequestException $e) {
            Log::error('Failed to suspend Plesk domain', [
                'domain_id' => $domainId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Activate a domain in Plesk.
     *
     * @param string $domainId
     * @return bool
     */
    public function activateDomain(string $domainId): bool
    {
        try {
            $this->makeRequest('PUT', "/domains/{$domainId}", [
                'status' => 'active',
            ]);

            Log::info('Plesk domain activated', ['domain_id' => $domainId]);

            return true;
        } catch (RequestException $e) {
            Log::error('Failed to activate Plesk domain', [
                'domain_id' => $domainId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Delete a domain from Plesk.
     *
     * @param string $domainId
     * @return bool
     */
    public function deleteDomain(string $domainId): bool
    {
        try {
            $this->makeRequest('DELETE', "/domains/{$domainId}");

            Log::info('Plesk domain deleted', ['domain_id' => $domainId]);

            return true;
        } catch (RequestException $e) {
            Log::error('Failed to delete Plesk domain', [
                'domain_id' => $domainId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Make an HTTP request to Plesk API.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws RequestException
     */
    protected function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $response = Http::timeout(30)
            ->withBasicAuth($this->username, $this->password)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->{strtolower($method)}($this->baseUrl . $endpoint, $data);

        $response->throw();

        return $response->json() ?? [];
    }

    /**
     * Generate a unique login from email.
     *
     * @param string $email
     * @return string
     */
    protected function generateLogin(string $email): string
    {
        $username = explode('@', $email)[0];
        $username = preg_replace('/[^a-zA-Z0-9]/', '', $username);
        $username = strtolower($username);

        // Add random suffix to ensure uniqueness
        return substr($username, 0, 10) . rand(100, 999);
    }

    /**
     * Generate FTP login from domain.
     *
     * @param string $domain
     * @return string
     */
    protected function generateFtpLogin(string $domain): string
    {
        $domain = str_replace(['.', '-'], '', $domain);
        return strtolower(substr($domain, 0, 12));
    }

    /**
     * Generate a secure password.
     *
     * @param int $length
     * @return string
     */
    protected function generateSecurePassword(int $length = 16): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        $max = strlen($characters) - 1;

        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, $max)];
        }

        return $password;
    }
}
