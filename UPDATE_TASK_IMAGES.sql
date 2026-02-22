-- ============================================
-- Update All Task Images to Rotate Product Images
-- This SQL updates all tasks to cycle through P1-P5 images
-- ============================================

-- Create a temporary table with row numbers
SET @row_number = 0;

-- Update tasks with rotating images
UPDATE admin_tasks
SET image_url = CASE
    WHEN (@row_number := @row_number + 1) % 5 = 1 THEN '/public/products/P1.jpg'
    WHEN @row_number % 5 = 2 THEN '/public/products/P2.jpg'
    WHEN @row_number % 5 = 3 THEN '/public/products/P3.jpg'
    WHEN @row_number % 5 = 4 THEN '/public/products/P4.jpg'
    ELSE '/public/products/P5.jpg'
END
ORDER BY task_order;

-- Reset the variable
SET @row_number = 0;

-- Verify the update
SELECT
    task_order,
    brand_name,
    image_url,
    earning_amount
FROM admin_tasks
ORDER BY task_order
LIMIT 10;
