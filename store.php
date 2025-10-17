<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>G14 Gym Store Management</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="gsm.css">
</head>
<body>
  <div class="container">
    <header>
      <div class="logo">
        <div class="logo-icon">G14</div>
        <h1>GYM STORE</h1>
      </div>
    </header>
    
    <!-- Dashboard Stats -->
    <div class="stats-container">
      <div class="stat-card">
        <h2 id="total-products">5</h2>
        <p>TOTAL PRODUCTS</p>
      </div>
      <div class="stat-card">
        <h2 id="in-stock-count" class="in-stock">3</h2>
        <p>IN STOCK</p>
      </div>
      <div class="stat-card">
        <h2 id="low-stock-count" class="low-stock">1</h2>
        <p>LOW STOCK</p>
      </div>
      <div class="stat-card">
        <h2 id="out-of-stock-count" class="out-of-stock">1</h2>
        <p>OUT OF STOCK</p>
      </div>
    </div>
    
    <!-- Tabs -->
    <div class="tabs">
      <button class="tab-btn active" data-tab="inventory">Inventory Management</button>
      <button class="tab-btn" data-tab="pos">Sell Product (POS)</button>
      <button class="tab-btn" data-tab="sales">Sales History</button>
    </div>
    
    <!-- INVENTORY TAB -->
    <div class="tab-content active" id="inventory-tab">
      <div class="inventory-table">
        <table>
          <thead>
            <tr>
              <th>PRODUCT ID</th>
              <th>PRODUCT NAME</th>
              <th>CATEGORY</th>
              <th>IN STOCK</th>
              <th>PRICE (₱)</th>
              <th>EXPIRY DATE</th>
              <th>STATUS</th>
              <th>ACTIONS</th>
            </tr>
          </thead>
          <tbody id="inventory-body">
            <!-- Inventory items will load here -->
             
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- POS TAB -->
    <div class="tab-content" id="pos-tab">
      <div class="pos-container">
        <div class="pos-products">
          <h3>Available Products</h3>
          <div class="product-grid" id="pos-products">
            <!-- Products will be loaded here -->
          </div>
        </div>
        
        <div class="cart-container">
          <h3>Shopping Cart</h3>    
          <div class="cart" id="cart">
            <div class="empty-cart">No products added yet</div>
          </div>
          
          <div class="cart-total">
            <div class="total-line">
              <span>Subtotal:</span>
              <span id="subtotal">₱0.00</span>
            </div>
            <div class="total-line">
              <span>Tax (10%):</span>
              <span id="tax">₱0.00</span>
            </div>
            <div class="total-line grand-total">
              <span>Total:</span>
              <span id="total">₱0.00</span>
            </div>
          </div>
          
          <div class="checkout-buttons">
            <button class="btn-checkout" id="checkout-btn">Complete Sale</button>
            <button class="btn-clear" id="clear-cart-btn">Clear Cart</button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- SALES HISTORY TAB -->
<div class="tab-content" id="sales-tab">
  <div class="sales-history-container">
    <h3>Sales History</h3>

    <!-- 📅 Date Filter Bar -->
    <div class="filter-bar">
      <label>From:</label>
      <input type="date" id="start-date">
      <label>To:</label>
      <input type="date" id="end-date">
      <button id="filter-btn" class="btn-filter"><i class="fa fa-filter"></i> Filter</button>
      <button id="reset-btn" class="btn-reset"><i class="fa fa-rotate-left"></i> Reset</button>
    </div>

    <table class="sales-table">
      <thead>
        <tr>
          <th>Sale ID</th>
          <th>Date</th>
          <th>Subtotal (₱)</th>
          <th>Tax (₱)</th>
          <th>Total (₱)</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="sales-body">
        <!-- Sales will load here -->
      </tbody>
    </table>

    <!-- Expandable details section -->
    <div id="sale-details" class="sale-details" style="display:none;">
      <h4>Sale Details (ID: <span id="detail-sale-id"></span>)</h4>
      <table class="details-table">
        <thead>
          <tr>
            <th>Product Name</th>
            <th>Quantity</th>
            <th>Price (₱)</th>
            <th>Total (₱)</th>
          </tr>
        </thead>
        <tbody id="details-body">
          <!-- Sale items load here -->
        </tbody>
      </table>
    </div>
  </div>
</div>

  <script src="gsm.js"></script>
</body>
</html>
