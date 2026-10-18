<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Google\Client as GoogleClient;
use Google\Service\Directory;
use Google\Service\Directory\Member;
use Google\Service\Exception as GoogleServiceException;
use Illuminate\Support\Facades\Log;

final class GoogleWorkspaceService implements GoogleWorkspaceServiceInterface
{
    public function __construct(
        private readonly string $serviceAccountJson,
        private readonly string $adminEmail,
        private readonly string $delegateGroup,
        private ?Directory $service = null
    ) {}

    /**
     * Whether every Google Workspace setting required to talk to the Directory API is present.
     */
    public static function configIsPresent(): bool
    {
        return (bool) (config('services.google.workspace.service_account_json')
            && config('services.google.workspace.admin_email')
            && config('services.google.workspace.delegate_group'));
    }

    public static function fromConfig(): self
    {
        return new self(
            (string) config('services.google.workspace.service_account_json'),
            (string) config('services.google.workspace.admin_email'),
            (string) config('services.google.workspace.delegate_group'),
        );
    }

    public function isConfigured(): bool
    {
        return true;
    }

    /**
     * Add a user to the delegate group.
     */
    public function addToGroup(string $email): bool
    {
        try {
            $member = new Member;
            $member->setEmail($email);
            $member->setRole('MEMBER');

            $this->getService()->members->insert($this->delegateGroup, $member);

            Log::info('Added user to Google Workspace delegate group', [
                'email' => $email,
                'group' => $this->delegateGroup,
            ]);

            return true;
        } catch (GoogleServiceException $e) {
            // If user is already a member, consider it a success
            if ($e->getCode() === 409) {
                Log::info('User already in Google Workspace delegate group', [
                    'email' => $email,
                    'group' => $this->delegateGroup,
                ]);

                return true;
            }

            Log::error('Failed to add user to Google Workspace delegate group', [
                'email' => $email,
                'group' => $this->delegateGroup,
                'error' => $e->getMessage(),
            ]);

            return false;
        } catch (Exception $e) {
            Log::error('Unexpected error adding user to Google Workspace delegate group', [
                'email' => $email,
                'group' => $this->delegateGroup,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Remove a user from the delegate group.
     */
    public function removeFromGroup(string $email): bool
    {
        try {
            $this->getService()->members->delete($this->delegateGroup, $email);

            Log::info('Removed user from Google Workspace delegate group', [
                'email' => $email,
                'group' => $this->delegateGroup,
            ]);

            return true;
        } catch (GoogleServiceException $e) {
            // If user is not a member, consider it a success
            if ($e->getCode() === 404) {
                Log::info('User not in Google Workspace delegate group', [
                    'email' => $email,
                    'group' => $this->delegateGroup,
                ]);

                return true;
            }

            Log::error('Failed to remove user from Google Workspace delegate group', [
                'email' => $email,
                'group' => $this->delegateGroup,
                'error' => $e->getMessage(),
            ]);

            return false;
        } catch (Exception $e) {
            Log::error('Unexpected error removing user from Google Workspace delegate group', [
                'email' => $email,
                'group' => $this->delegateGroup,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if a user is in the delegate group.
     */
    public function isInGroup(string $email): bool
    {
        try {
            $this->getService()->members->get($this->delegateGroup, $email);

            return true;
        } catch (GoogleServiceException $e) {
            if ($e->getCode() === 404) {
                return false;
            }

            Log::error('Failed to check Google Workspace delegate group membership', [
                'email' => $email,
                'group' => $this->delegateGroup,
                'error' => $e->getMessage(),
            ]);

            return false;
        } catch (Exception $e) {
            Log::error('Unexpected error checking Google Workspace delegate group membership', [
                'email' => $email,
                'group' => $this->delegateGroup,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get or initialize the Google Directory service.
     */
    private function getService(): Directory
    {
        if ($this->service !== null) {
            return $this->service;
        }

        $client = new GoogleClient;
        $client->setAuthConfig(json_decode($this->serviceAccountJson, true));
        $client->setSubject($this->adminEmail);
        $client->addScope(Directory::ADMIN_DIRECTORY_GROUP_MEMBER);

        $this->service = new Directory($client);

        return $this->service;
    }
}
