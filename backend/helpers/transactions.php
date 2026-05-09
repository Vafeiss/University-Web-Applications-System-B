<?php
/**
 * File: transactions.php
 * Layer: Helper
 * Module: Token Transactions
 * System: University Web Applications System B
 *
 * Description:
 * Shared helper για περιγραφές token transactions. Χρησιμοποιείται
 * από posts.php και token_history.php για συνεπή ονομασία τύπου
 * συναλλαγών στο UI.
 *
 * Functions:
 * - describeTransaction() returns human-readable label από token charge
 *
 * Author: Pelagia Koniotaki
 * Date: 2026
 */

if (!function_exists('describeTransaction')) {
    function describeTransaction(int $tokenCharge, ?string $source = null): string {
        if ($source === 'advertisement_reward') {
            return "Advertisement reward";
        }

        if ($tokenCharge === 10) {
            return "Referral reward";
        }

        if ($tokenCharge === 1) {
            return "Approved upload reward";
        }

        if ($tokenCharge === 0) {
            return "Free daily download";
        }

        if ($tokenCharge === -1) {
            return "Download charge";
        }

        return $tokenCharge > 0 ? "Token gain" : "Token usage";
    }
}
