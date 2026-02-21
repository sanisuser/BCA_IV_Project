-- Add admin_remark column to orders table for cancellation remarks
ALTER TABLE orders ADD COLUMN IF NOT EXISTS admin_remark TEXT NULL COMMENT 'Admin remark for order cancellation or other notes';
