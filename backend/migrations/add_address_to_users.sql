-- Migration: Add address column to users table
-- Created: 2026-05-06

ALTER TABLE users ADD COLUMN address VARCHAR(255) NULL AFTER phone;
