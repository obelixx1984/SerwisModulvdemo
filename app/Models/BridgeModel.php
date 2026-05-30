<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class BridgeModel extends BaseModel
{
    public function generateQrToken(string $login, string $ticket): ?string
    {
        $apiUrl = defined('BRIDGE_URL') ? BRIDGE_URL : '';
        $apiKey = defined('BRIDGE_API_KEY') ? BRIDGE_API_KEY : '';

        if (!$apiUrl) return null;

        $ch = \curl_init($apiUrl . '?action=generate_qr');
        \curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => \http_build_query([
                'api_key' => $apiKey,
                'login'   => $login,
                'ticket'  => $ticket,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $resp = \curl_exec($ch);

        $data = \json_decode($resp, true);
        return ($data['success'] ?? false) ? $data['qr_token'] : null;
    }

    public function getPhotos(): array
    {
        $apiUrl = defined('BRIDGE_URL') ? BRIDGE_URL : '';
        if (!$apiUrl) return [];

        $ch = \curl_init($apiUrl . '?action=get_photos');
        \curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->getToken()],
        ]);
        $resp = \curl_exec($ch);

        $data = \json_decode($resp, true);
        return ($data['success'] ?? false) ? ($data['photos'] ?? []) : [];
    }

    public function downloadPhoto(int $photoId): array
    {
        $apiUrl = defined('BRIDGE_URL') ? BRIDGE_URL : '';
        if (!$apiUrl) return [];

        $ch = \curl_init($apiUrl . '?action=download_photo&id=' . $photoId);
        \curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HEADER         => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->getToken()],
        ]);
        $raw      = \curl_exec($ch);
        $hdrSize  = \curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        $headers = \substr($raw, 0, $hdrSize);
        $body    = \substr($raw, $hdrSize);

        $ticket   = '';
        $isPublic = 0;
        foreach (\explode("\r\n", $headers) as $line) {
            if (\str_starts_with($line, 'X-Ticket:'))    $ticket   = trim(\explode(':', $line, 2)[1]);
            if (\str_starts_with($line, 'X-Is-Public:')) $isPublic = (int)trim(\explode(':', $line, 2)[1]);
        }

        return [
            'body'      => $body,
            'ticket'    => $ticket,
            'is_public' => $isPublic,
        ];
    }

    private function getToken(): string
    {
        return $_SESSION['bridge_token'] ?? '';
    }

    public function login(): bool
    {
        if (!empty($_SESSION['bridge_token'])) return true;

        $apiUrl = defined('BRIDGE_URL') ? BRIDGE_URL : '';
        $ch = \curl_init($apiUrl . '?action=login');
        \curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => \http_build_query([
                'login'    => defined('BRIDGE_SERVICE_LOGIN') ? BRIDGE_SERVICE_LOGIN : '',
                'password' => defined('BRIDGE_SERVICE_PASS')  ? BRIDGE_SERVICE_PASS  : '',
                'device'   => 'local_php',
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $resp = \curl_exec($ch);

        $data = \json_decode($resp, true);
        if ($data['success'] ?? false) {
            $_SESSION['bridge_token'] = $data['token'];
            return true;
        }
        return false;
    }
}

// ────────────────────────────────────────────────────────────
