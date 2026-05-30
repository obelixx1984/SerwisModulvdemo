<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class PhotoTokenModel extends BaseModel
{
    public function create(int $failureId, int $userId): string
    {
        $this->execute(
            "DELETE FROM photo_upload_tokens WHERE failure_id = ? AND user_id = ?",
            [$failureId, $userId]
        );
        $token     = \bin2hex(\random_bytes(32));
        $expiresAt = \date('Y-m-d H:i:s', \time() + 900);
        $this->execute(
            "INSERT INTO photo_upload_tokens (token, failure_id, user_id, expires_at)
             VALUES (?, ?, ?, ?)",
            [$token, $failureId, $userId, $expiresAt]
        );
        return $token;
    }

    public function validate(string $token): ?array
    {
        return $this->fetchOne(
            "SELECT * FROM photo_upload_tokens
             WHERE token = ? AND expires_at > NOW() AND used = 0",
            [$token]
        );
    }
}

// ────────────────────────────────────────────────────────────
