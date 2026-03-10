<?php

declare(strict_types=1);

final class ReferralService
{
    public const REFERRAL_REWARD = 10;

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function ensureReferralCodeForUser(int $userId): string
    {
        $user = $this->getUserById($userId);
        if ($user === null) {
            throw new InvalidArgumentException('User not found.');
        }

        if ($user['referral_code'] !== '') {
            return $user['referral_code'];
        }

        $referralCode = $this->generateUniqueReferralCode();
        $statement = $this->pdo->prepare(
            'UPDATE users
             SET referral_code = :referral_code
             WHERE user_id = :user_id'
        );
        $statement->execute([
            ':referral_code' => $referralCode,
            ':user_id' => $userId,
        ]);

        return $referralCode;
    }

    public function applyReferralReward(int $newUserId, string $rawReferralCode): array
    {
        $referralCode = strtoupper(trim($rawReferralCode));
        if ($referralCode === '') {
            throw new InvalidArgumentException('Referral code is required.');
        }

        $this->pdo->beginTransaction();

        try {
            $newUser = $this->lockUserById($newUserId);
            if ($newUser === null) {
                throw new InvalidArgumentException('New user not found.');
            }

            if ($newUser['referred_by'] !== null) {
                throw new InvalidArgumentException('Referral reward has already been applied for this user.');
            }

            $referrer = $this->findUserByReferralCodeForUpdate($referralCode);
            if ($referrer === null) {
                throw new InvalidArgumentException('Referral code is invalid.');
            }

            if ((int) $referrer['user_id'] === $newUserId) {
                throw new InvalidArgumentException('A user cannot use their own referral code.');
            }

            $update = $this->pdo->prepare(
                'UPDATE users
                 SET referred_by = :referred_by
                 WHERE user_id = :user_id'
            );
            $update->execute([
                ':referred_by' => (int) $referrer['user_id'],
                ':user_id' => $newUserId,
            ]);

            $this->awardTokens($newUserId, self::REFERRAL_REWARD);
            $this->awardTokens((int) $referrer['user_id'], self::REFERRAL_REWARD);

            $this->pdo->commit();

            return [
                'new_user' => $this->getUserById($newUserId),
                'referrer' => $this->getUserById((int) $referrer['user_id']),
                'reward_amount' => self::REFERRAL_REWARD,
            ];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function getUserByReferralCode(string $referralCode): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT user_id, username, email, token_balance, referral_code, referred_by
             FROM users
             WHERE referral_code = :referral_code
             LIMIT 1'
        );
        $statement->execute([
            ':referral_code' => strtoupper(trim($referralCode)),
        ]);

        $user = $statement->fetch();

        return $user === false ? null : $this->normalizeUser($user);
    }

    public function getUserById(int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT user_id, username, email, token_balance, referral_code, referred_by
             FROM users
             WHERE user_id = :user_id
             LIMIT 1'
        );
        $statement->execute([
            ':user_id' => $userId,
        ]);

        $user = $statement->fetch();

        return $user === false ? null : $this->normalizeUser($user);
    }

    private function findUserByReferralCodeForUpdate(string $referralCode): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT user_id, username, referral_code
             FROM users
             WHERE referral_code = :referral_code
             LIMIT 1
             FOR UPDATE'
        );
        $statement->execute([
            ':referral_code' => strtoupper(trim($referralCode)),
        ]);

        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    private function lockUserById(int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT user_id, username, email, token_balance, referral_code, referred_by
             FROM users
             WHERE user_id = :user_id
             LIMIT 1
             FOR UPDATE'
        );
        $statement->execute([
            ':user_id' => $userId,
        ]);

        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    private function generateUniqueReferralCode(int $length = 8): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $maxIndex = strlen($alphabet) - 1;

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = '';
            for ($position = 0; $position < $length; $position++) {
                $code .= $alphabet[random_int(0, $maxIndex)];
            }

            if ($this->getUserByReferralCode($code) === null) {
                return $code;
            }
        }

        throw new RuntimeException('Failed to generate a unique referral code.');
    }

    private function awardTokens(int $userId, int $amount): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE users
             SET token_balance = token_balance + :amount
             WHERE user_id = :user_id'
        );
        $statement->execute([
            ':amount' => $amount,
            ':user_id' => $userId,
        ]);

        if ($statement->rowCount() !== 1) {
            throw new RuntimeException('Failed to update token balance.');
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO transactions (user_id, token_charge, timestamp)
             VALUES (:user_id, :token_charge, :timestamp)'
        );
        $insert->execute([
            ':user_id' => $userId,
            ':token_charge' => $amount,
            ':timestamp' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    private function normalizeUser(array $user): array
    {
        return [
            'id' => (int) $user['user_id'],
            'username' => (string) $user['username'],
            'email' => (string) $user['email'],
            'token_balance' => (int) $user['token_balance'],
            'referral_code' => (string) $user['referral_code'],
            'referred_by' => $user['referred_by'] === null ? null : (int) $user['referred_by'],
        ];
    }
}
