/*
 * File: ban_migration.sql
 * Layer: Database Migration
 * Module: Ban Enforcement
 * System: University Web Applications System B
 *
 * Description:
 * Migration script adding ban enforcement columns to users table.
 * Enables system to track and enforce user bans based on report counts.
 *
 * Changes:
 * - is_banned column (TINYINT) → tracks ban status
 * - ban_reason column (TEXT) → stores reason for ban
 *
 * Used By:
 * - BanGuard middleware
 * - PostModel (automatic banning on high reports)
 *
 * Author: Antriani Theofanous
 * Date: 2026
 */

ALTER TABLE users
    ADD COLUMN is_banned TINYINT(1) DEFAULT 0 AFTER referred_by,
    ADD COLUMN ban_reason TEXT NULL AFTER is_banned;
