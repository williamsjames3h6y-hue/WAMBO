# Database and Tawk.to Integration Updates

## Summary
This update includes:
1. Task image rotation system - all tasks now cycle through 5 product images
2. Tawk.to live chat integration on all pages

---

## 1. Update Task Images in Database

You have **3 options** to update the task images:

### Option A: Run PHP Script (Recommended if you have SSH access)
```bash
php update_task_images_database.php
```

### Option B: Run SQL File (For phpMyAdmin or MySQL client)
1. Open phpMyAdmin or your MySQL client
2. Select your database: `u800179901_70`
3. Go to the SQL tab
4. Copy and paste the contents of `UPDATE_TASK_IMAGES.sql`
5. Click "Go" or "Execute"

### Option C: Manual SQL Query
Run this SQL directly in phpMyAdmin:

```sql
SET @row_number = 0;

UPDATE admin_tasks
SET image_url = CASE
    WHEN (@row_number := @row_number + 1) % 5 = 1 THEN '/public/products/P1.jpg'
    WHEN @row_number % 5 = 2 THEN '/public/products/P2.jpg'
    WHEN @row_number % 5 = 3 THEN '/public/products/P3.jpg'
    WHEN @row_number % 5 = 4 THEN '/public/products/P4.jpg'
    ELSE '/public/products/P5.jpg'
END
ORDER BY task_order;
```

---

## 2. Image Rotation Behavior

After updating, tasks will display images in this pattern:
- **Task 1**: P1.jpg
- **Task 2**: P2.jpg
- **Task 3**: P3.jpg
- **Task 4**: P4.jpg
- **Task 5**: P5.jpg
- **Task 6**: P1.jpg (cycle repeats)
- And so on...

This ensures that when users complete tasks, they see a different product image with each submission, creating visual variety.

---

## 3. Tawk.to Live Chat Integration

The Tawk.to chat widget has been added to all pages:
- ✓ Home page (index.php)
- ✓ Login page (login.php)
- ✓ Register page (register.php)
- ✓ Dashboard (dashboard.php)
- ✓ Tasks page (tasks.php)
- ✓ Payment Methods (payment_methods.php)
- ✓ Admin panel (admin.php)

**Your Tawk.to Details:**
- Property ID: `699b1633c165071c358882c8`
- Widget Key: `1ji2stfiq`

The chat widget will appear in the bottom-right corner of all pages, allowing users to contact support instantly.

---

## 4. Files Created

- `update_task_images_database.php` - PHP script to update images
- `UPDATE_TASK_IMAGES.sql` - SQL file for direct database execution
- `UPDATE_INSTRUCTIONS.md` - This instruction file

---

## 5. Verification

After running the update, verify it worked:

1. **Check Database:**
   ```sql
   SELECT task_order, brand_name, image_url
   FROM admin_tasks
   ORDER BY task_order
   LIMIT 10;
   ```

2. **Check Website:**
   - Visit your tasks page
   - Complete a task
   - Submit it
   - The next task should show a different product image

3. **Check Tawk.to:**
   - Visit any page on your site
   - The chat widget should appear in the bottom-right corner

---

## Support

If you encounter any issues:
- Make sure your database credentials in `config/database.php` are correct
- Verify the product image files exist in `/public/products/` directory
- Check browser console for any JavaScript errors related to Tawk.to
