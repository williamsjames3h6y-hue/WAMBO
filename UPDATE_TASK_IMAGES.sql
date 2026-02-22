-- ============================================
-- Update All Task Images to Rotate Product Images
-- This SQL updates all tasks to cycle through P1-P37 images
-- ============================================

-- Create a temporary table with row numbers
SET @row_number = 0;

-- Update tasks with rotating images (cycling through 37 product images)
UPDATE admin_tasks
SET image_url = CASE
    WHEN (@row_number := @row_number + 1) % 37 = 1 THEN '/public/products/P1.jpg'
    WHEN @row_number % 37 = 2 THEN '/public/products/P2.jpg'
    WHEN @row_number % 37 = 3 THEN '/public/products/P3.jpg'
    WHEN @row_number % 37 = 4 THEN '/public/products/P4.jpg'
    WHEN @row_number % 37 = 5 THEN '/public/products/P5.jpg'
    WHEN @row_number % 37 = 6 THEN '/public/products/P6.jpg'
    WHEN @row_number % 37 = 7 THEN '/public/products/P7.jpg'
    WHEN @row_number % 37 = 8 THEN '/public/products/P8.jpg'
    WHEN @row_number % 37 = 9 THEN '/public/products/P9.jpg'
    WHEN @row_number % 37 = 10 THEN '/public/products/p10.jpg'
    WHEN @row_number % 37 = 11 THEN '/public/products/p11.jpg'
    WHEN @row_number % 37 = 12 THEN '/public/products/p12.jpg'
    WHEN @row_number % 37 = 13 THEN '/public/products/p13.jpg'
    WHEN @row_number % 37 = 14 THEN '/public/products/p14.jpg'
    WHEN @row_number % 37 = 15 THEN '/public/products/p15.jpg'
    WHEN @row_number % 37 = 16 THEN '/public/products/p16.jpg'
    WHEN @row_number % 37 = 17 THEN '/public/products/p17.jpg'
    WHEN @row_number % 37 = 18 THEN '/public/products/p18.jpg'
    WHEN @row_number % 37 = 19 THEN '/public/products/p19.jpg'
    WHEN @row_number % 37 = 20 THEN '/public/products/p20.jpg'
    WHEN @row_number % 37 = 21 THEN '/public/products/p21.jpg'
    WHEN @row_number % 37 = 22 THEN '/public/products/p22.jpg'
    WHEN @row_number % 37 = 23 THEN '/public/products/p23.jpg'
    WHEN @row_number % 37 = 24 THEN '/public/products/p24.jpg'
    WHEN @row_number % 37 = 25 THEN '/public/products/p25.jpg'
    WHEN @row_number % 37 = 26 THEN '/public/products/p26.jpg'
    WHEN @row_number % 37 = 27 THEN '/public/products/p27.jpg'
    WHEN @row_number % 37 = 28 THEN '/public/products/p28.jpg'
    WHEN @row_number % 37 = 29 THEN '/public/products/p29.jpg'
    WHEN @row_number % 37 = 30 THEN '/public/products/p30.jpg'
    WHEN @row_number % 37 = 31 THEN '/public/products/p31.jpg'
    WHEN @row_number % 37 = 32 THEN '/public/products/p32.jpg'
    WHEN @row_number % 37 = 33 THEN '/public/products/p33.jpg'
    WHEN @row_number % 37 = 34 THEN '/public/products/p34.jpg'
    WHEN @row_number % 37 = 35 THEN '/public/products/p35.jpg'
    WHEN @row_number % 37 = 36 THEN '/public/products/p36.jpg'
    ELSE '/public/products/p37.jpg'
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
LIMIT 37;
