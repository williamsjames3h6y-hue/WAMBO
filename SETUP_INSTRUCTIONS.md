# Setup Instructions

## Step 1: Run Database Migration

Visit this URL in your browser to add the product details columns and update existing tasks:

```
https://earningsllc.online/migrations/add_product_details.php
```

This will:
- Add `product_name` and `price` columns to the `admin_tasks` table
- Update the first 5 tasks with real product information:
  1. Nike Air Max Sneakers ($120.00, Earn $2.25)
  2. Apple iPhone 15 Pro ($999.00, Earn $2.10)
  3. Samsung Galaxy S24 Ultra ($899.00, Earn $2.30)
  4. Adidas Ultraboost Running Shoes ($180.00, Earn $1.95)
  5. Sony WH-1000XM5 Headphones ($399.00, Earn $2.40)

## What's Changed

### Task Display
- Now shows **product name** as the main heading instead of "Brand X"
- Displays both product price (Amount) and earning amount (Profit)
- Clean, professional layout matching your reference design

### Admin Panel
- Fixed all admin actions (they now work properly!)
- Added fields for Product Name, Brand Name, and Product Price
- Admin can now add/edit tasks with full product details
- Tasks display shows product name prominently

### Database
- Added `product_name` column (VARCHAR 255)
- Added `price` column (DECIMAL 10,2)
- Existing `brand_name` and `earning_amount` columns remain

## Admin Panel Features

Now fully functional:
- ✓ Update user balances (add/subtract)
- ✓ Change user VIP tiers
- ✓ Add new tasks with product details
- ✓ Edit existing tasks
- ✓ Delete tasks
- ✓ Update all system settings

All changes are saved to the database immediately and reflected on user accounts.
