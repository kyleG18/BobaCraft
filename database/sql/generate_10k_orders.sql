-- =============================================================================
-- BobaCraft Milk Tea Bar - Safe Test Dataset Generation Script
-- Laboratory Activity: API Performance, Reliability, and Monitoring
-- Purpose: Generates 10,000 realistic milk tea orders for performance benchmarking
-- Target Database: bobacraft_db
-- =============================================================================

USE `bobacraft_db`;

DELIMITER $$

DROP PROCEDURE IF EXISTS `SeedBobaCraft10kOrders`$$

CREATE PROCEDURE `SeedBobaCraft10kOrders`(IN total_records INT)
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE base_id INT DEFAULT 0;
    DECLARE rand_flavor VARCHAR(100);
    DECLARE rand_status VARCHAR(50);
    DECLARE rand_sugar VARCHAR(30);
    DECLARE rand_ice VARCHAR(30);
    DECLARE rand_size VARCHAR(20);
    DECLARE rand_topping VARCHAR(50);
    DECLARE rand_cust VARCHAR(100);
    DECLARE rand_mins INT;
    DECLARE rand_time DATETIME;
    DECLARE full_name VARCHAR(255);

    -- Get current max id
    SELECT IFNULL(MAX(id), 0) INTO base_id FROM `orders`;

    START TRANSACTION;

    WHILE i <= total_records DO
        -- Random flavor selection
        SET rand_flavor = ELT(FLOOR(1 + (RAND() * 10)),
            'Brown Sugar Boba Fresh Milk',
            'Okinawa Milk Tea w/ Cream Cheese',
            'Taro Pearl Milk Tea w/ Pudding',
            'Wintermelon Cream Float',
            'Uji Matcha Latte w/ Boba',
            'Thai Milk Tea w/ Grass Jelly',
            'Mango Green Tea w/ Coconut Jelly',
            'Roasted Oolong Milk Tea',
            'Hokkaido Milk Tea w/ Pearls',
            'Jasmine Green Tea w/ Aloe'
        );

        -- Random customer name
        SET rand_cust = ELT(FLOOR(1 + (RAND() * 10)),
            'Juan Dela Cruz',
            'Maria Santos',
            'Angela Reyes',
            'Mark Villanueva',
            'Sofia Mendoza',
            'Liam Tan',
            'Emma Garcia',
            'Carlos Torres',
            'Andrea Aquino',
            'Joshua Castro'
        );

        -- Random drink configurations
        SET rand_size = ELT(FLOOR(1 + (RAND() * 2)), 'Regular', 'Large');
        SET rand_sugar = ELT(FLOOR(1 + (RAND() * 4)), '25% Sugar', '50% Sugar', '70% Sugar', '100% Sugar');
        SET rand_ice = ELT(FLOOR(1 + (RAND() * 3)), 'No Ice', 'Less Ice', 'Standard Ice');
        SET rand_topping = ELT(FLOOR(1 + (RAND() * 4)), 'Extra Pearls', 'Egg Pudding', 'Grass Jelly', 'Cheese Foam');

        -- Status distribution (Weighted)
        SET rand_status = ELT(FLOOR(1 + (RAND() * 4)), 'Pending', 'Preparing', 'Ready', 'Completed');

        -- Distributed creation timestamps across last 30 days
        SET rand_mins = FLOOR(RAND() * 43200);
        SET rand_time = DATE_SUB(NOW(), INTERVAL rand_mins MINUTE);

        SET full_name = CONCAT('Cup #', LPAD(base_id + i, 5, '0'), ' — ', rand_flavor, ' (', rand_size, ', ', rand_sugar, ', ', rand_ice, ', ', rand_topping, ') [', rand_cust, ']');

        INSERT INTO `orders` (`customer_name`, `status`, `created_at`, `updated_at`)
        VALUES (full_name, rand_status, rand_time, DATE_ADD(rand_time, INTERVAL 5 MINUTE));

        -- Commit every 1000 records for memory efficiency
        IF MOD(i, 1000) = 0 THEN
            COMMIT;
            START TRANSACTION;
        END IF;

        SET i = i + 1;
    END WHILE;

    COMMIT;
END$$

DELIMITER ;

-- Execute generation for 10,000 records
CALL `SeedBobaCraft10kOrders`(10000);

-- Clean up procedure
DROP PROCEDURE IF EXISTS `SeedBobaCraft10kOrders`;

-- Verification Query
SELECT 
    COUNT(*) AS total_records,
    SUM(CASE WHEN `status` = 'Pending' THEN 1 ELSE 0 END) AS pending_orders,
    SUM(CASE WHEN `status` = 'Preparing' THEN 1 ELSE 0 END) AS preparing_orders,
    SUM(CASE WHEN `status` = 'Ready' THEN 1 ELSE 0 END) AS ready_orders,
    SUM(CASE WHEN `status` = 'Completed' THEN 1 ELSE 0 END) AS completed_orders
FROM `orders`;
