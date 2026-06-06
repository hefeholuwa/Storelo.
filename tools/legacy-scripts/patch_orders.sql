ALTER TABLE orders ADD COLUMN promo_code VARCHAR(50) DEFAULT NULL AFTER total_price;
ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(10, 2) DEFAULT 0 AFTER promo_code;
