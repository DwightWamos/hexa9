// ==========================
// Gym Store Management (Fixed PHP-Connected Version)
// ==========================

// --- Global variables ---
let products = [];
let cart = [];

// Format currency to Philippine Peso
function formatCurrency(amount) {
    return `₱${amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}`;
}

// DOM elements
const inventoryBody = document.getElementById('inventory-body');
const posProducts = document.getElementById('pos-products');
const cartElement = document.getElementById('cart');
const subtotalElement = document.getElementById('subtotal');
const taxElement = document.getElementById('tax');
const totalElement = document.getElementById('total');
const checkoutBtn = document.getElementById('checkout-btn');
const clearCartBtn = document.getElementById('clear-cart-btn');
const tabBtns = document.querySelectorAll('.tab-btn');
const tabContents = document.querySelectorAll('.tab-content');

const salesBody = document.getElementById('sales-body');
const saleDetails = document.getElementById('sale-details');
const detailSaleId = document.getElementById('detail-sale-id');
const detailsBody = document.getElementById('details-body');

// ==========================
// Load Products
// ==========================
async function loadProducts() {
    try {
        const res = await fetch('gsmServer.php?action=getProducts');
        const data = await res.json();
        console.log("Server raw response:", data);

        if (data.status === "success") {
            products = data.data.map(p => ({
                id: p.product_id,
                name: p.product_name,
                category: p.category,
                stock: parseInt(p.stock),
                price: parseFloat(p.price),
                expiry: p.expiry_date,
                status: p.status
            }));
            renderInventory();
            renderPOSProducts();
            updateStats();
        }
    } catch (err) {
        console.error("Error loading products:", err);
    }
}

// ==========================
// Load Sales History (with optional date filter)
// ==========================
async function loadSalesHistory(startDate = '', endDate = '') {
  try {
    let url = 'gsmServer.php?action=getSalesHistory';
    if (startDate && endDate) {
      url += `&start=${startDate}&end=${endDate}`;
    }

    const res = await fetch(url);
    const data = await res.json();

    if (data.status === "success") {
      renderSalesTable(data.data);
    } else {
      salesBody.innerHTML = `<tr><td colspan="6">${data.message || 'No records found.'}</td></tr>`;
    }
  } catch (err) {
    console.error("Error loading sales history:", err);
    salesBody.innerHTML = `<tr><td colspan="6">Error loading data.</td></tr>`;
  }
}


// Render sales table
function renderSalesTable(sales) {
    salesBody.innerHTML = '';
    if (sales.length === 0) {
        salesBody.innerHTML = `<tr><td colspan="6">${data.message}</td></tr>`;

        return;
    }

    sales.forEach(sale => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${sale.sale_id}</td>
            <td>${sale.sale_date}</td>
            <td>${formatCurrency(parseFloat(sale.subtotal))}</td>
            <td>${formatCurrency(parseFloat(sale.tax))}</td>
            <td>${formatCurrency(parseFloat(sale.total))}</td>
            <td><button class="btn-view" onclick="viewSaleDetails(${sale.sale_id})">View</button></td>
        `;
        salesBody.appendChild(tr);
    });
}

// View sale details
// View sale details (with toggle)
async function viewSaleDetails(saleId) {
  try {
    // If the same sale is clicked again → collapse
    if (saleDetails.classList.contains('show') && detailSaleId.textContent == saleId) {
      saleDetails.classList.remove('show');
      setTimeout(() => (saleDetails.style.display = 'none'), 400);
      return;
    }

    // Fetch sale items
    const res = await fetch(`gsmServer.php?action=getSaleItems&sale_id=${saleId}`);
    const data = await res.json();

    if (data.status === "success") {
      detailSaleId.textContent = saleId;
      detailsBody.innerHTML = '';

      data.data.forEach(item => {
        const total = parseFloat(item.price) * parseInt(item.quantity);
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${item.product_name}</td>
          <td>${item.quantity}</td>
          <td>${formatCurrency(parseFloat(item.price))}</td>
          <td>${formatCurrency(total)}</td>
        `;
        detailsBody.appendChild(tr);
      });

      saleDetails.style.display = 'block';
      setTimeout(() => saleDetails.classList.add('show'), 50);
    } else {
      alert("No items found for this sale.");
    }
  } catch (err) {
    console.error("Error loading sale details:", err);
  }
}


// ==========================
// Initialization
// ==========================
document.addEventListener('DOMContentLoaded', function() {
    loadProducts();
    setupEventListeners();
});

// ==========================
// Event Listeners
// ==========================
function setupEventListeners() {
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const tabId = btn.getAttribute('data-tab');
            switchTab(tabId);

            // Load sales only when "Sales History" is opened
            if (tabId === 'sales') {
                loadSalesHistory();
            } else {
                saleDetails.style.display = 'none';
            }
        });
    });

    checkoutBtn.addEventListener('click', processSale);
    clearCartBtn.addEventListener('click', clearCart);
    // ===== Sales Filter Events =====
const filterBtn = document.getElementById('filter-btn');
const resetBtn = document.getElementById('reset-btn');

filterBtn.addEventListener('click', () => {
  const start = document.getElementById('start-date').value;
  const end = document.getElementById('end-date').value;
  if (!start || !end) return alert("Please select both dates.");
  loadSalesHistory(start, end);
});

resetBtn.addEventListener('click', () => {
  document.getElementById('start-date').value = '';
  document.getElementById('end-date').value = '';
  loadSalesHistory();
});

}

// ==========================
// Tab Switching
// ==========================
function switchTab(tabId) {
    tabBtns.forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-tab') === tabId);
    });
    tabContents.forEach(content => {
        content.classList.toggle('active', content.id === `${tabId}-tab`);
    });
}

// ==========================
// Render Inventory
// ==========================
function renderInventory() {
    inventoryBody.innerHTML = '';

    products.forEach((product, index) => {
        let statusClass = '';
        if (product.status === "IN STOCK") statusClass = 'status-in-stock';
        else if (product.status === "LOW STOCK") statusClass = 'status-low-stock';
        else statusClass = 'status-out-of-stock';

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${product.id}</td>
            <td>${product.name}</td>
            <td>${product.category}</td>
            <td><span class="stock-count">${product.stock}</span> pcs</td>
            <td>${formatCurrency(product.price)}</td>
            <td>${product.expiry}</td>
            <td><span class="status ${statusClass}">${product.status}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="btn-adjust btn-plus" onclick="adjustStock(${index}, 1)">+</button>
                    <button class="btn-adjust btn-minus" onclick="adjustStock(${index}, -1)">-</button>
                </div>
            </td>
        `;
        inventoryBody.appendChild(row);
    });
}

// ==========================
// POS Products
// ==========================
function renderPOSProducts() {
    posProducts.innerHTML = '';
    console.log("Products used for POS:", products);

    products.forEach((p, i) => {
        if (p.stock > 0) {
            const card = document.createElement('div');
            card.className = 'product-card';
            card.innerHTML = `
                <h4>${p.name}</h4>
                <div class="category">${p.category}</div>
                <div class="price">${formatCurrency(p.price)}</div>
                <div class="stock">In stock: ${p.stock} pcs</div>
            `;
            card.addEventListener('click', () => addToCart(i));
            posProducts.appendChild(card);
        }
    });
}

// ==========================
// Cart & Checkout
// ==========================
function addToCart(i) {
    const p = products[i];
    const existing = cart.find(item => item.id === p.id);
    if (existing) {
        if (existing.quantity < p.stock) existing.quantity++;
        else return alert(`Only ${p.stock} available.`);
    } else {
        cart.push({ id: p.id, name: p.name, price: p.price, quantity: 1 });
    }
    renderCart();
}

function renderCart() {
    if (cart.length === 0) {
        cartElement.innerHTML = '<div class="empty-cart">No products added yet</div>';
        updateCartTotal();
        return;
    }

    cartElement.innerHTML = '';
    cart.forEach(item => {
        const div = document.createElement('div');
        div.className = 'cart-item';
        div.innerHTML = `
            <div class="cart-item-info">
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-price">${formatCurrency(item.price)} each</div>
            </div>
            <div class="cart-item-quantity">
                <button class="quantity-btn" onclick="changeCartQuantity(${item.id}, -1)">-</button>
                <span>${item.quantity}</span>
                <button class="quantity-btn" onclick="changeCartQuantity(${item.id}, 1)">+</button>
            </div>
            <div class="cart-item-total">${formatCurrency(item.price * item.quantity)}</div>
        `;
        cartElement.appendChild(div);
    });

    updateCartTotal();
}

function changeCartQuantity(id, change) {
    const item = cart.find(i => i.id === id);
    const product = products.find(p => p.id == id);
    if (!item) return;

    const newQty = item.quantity + change;
    if (newQty < 1) cart = cart.filter(i => i.id !== id);
    else if (newQty > product.stock) alert(`Only ${product.stock} left.`);
    else item.quantity = newQty;

    renderCart();
}

function updateCartTotal() {
    const subtotal = cart.reduce((t, i) => t + i.price * i.quantity, 0);
    const tax = subtotal * 0.1;
    const total = subtotal + tax;
    subtotalElement.textContent = formatCurrency(subtotal);
    taxElement.textContent = formatCurrency(tax);
    totalElement.textContent = formatCurrency(total);
}

function clearCart() {
    cart = [];
    renderCart();
}

// ==========================
// Process Sale (With Auto Invoice)
// ==========================
async function processSale() {
    if (cart.length === 0) return alert("Please add products to the cart first.");

    const subtotal = cart.reduce((t, i) => t + i.price * i.quantity, 0);
    const tax = subtotal * 0.1;
    const total = subtotal + tax;

    const saleData = {
        action: "completeSale",
        cart: cart.map(i => ({
            product_id: i.id,
            quantity: i.quantity,
            price: i.price
        })),
        subtotal,
        tax,
        total
    };

    try {
        const res = await fetch("gsmServer.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(saleData)
        });

        const data = await res.json();

        if (data.status === "success") {
            alert(`✅ Sale completed!\nSale ID: ${data.sale_id}\nTotal: ${formatCurrency(total)}`);

            // ✅ Automatically open invoice in new tab
            window.open(`generate_invoice.php?sale_id=${data.sale_id}`, "_blank");

            // 🧹 Clear cart + refresh inventory
            cart = [];
            renderCart();
            await loadProducts();
        } else {
            alert("❌ Error completing sale: " + data.message);
        }
    } catch (err) {
        console.error("Sale error:", err);
        alert("⚠️ Server error — check console for details.");
    }
}


async function adjustStock(index, change) {
    const product = products[index];
    const newStock = product.stock + change;

    if (newStock < 0) return alert("Stock cannot go below zero!");

    // Update UI immediately
    product.stock = newStock;
    if (newStock === 0) product.status = "OUT OF STOCK";
    else if (newStock <= 5) product.status = "LOW STOCK";
    else product.status = "IN STOCK";

    renderInventory();
    updateStats();

    // Send update to backend
    try {
        const res = await fetch('gsmServer.php?action=updateStock', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: product.id, stock: newStock })
        });
        const data = await res.json();

        if (data.status === "success") {
            // 🔄 Refresh everything (Inventory + POS)
            await loadProducts();
        } else {
            alert("❌ Database update failed: " + data.message);
        }
    } catch (err) {
        console.error("Error updating stock:", err);
    }
}



// ==========================
// Update Stats
// ==========================
function updateStats() {
    let inStock = 0, lowStock = 0, outOfStock = 0;
    products.forEach(p => {
        if (p.status === "IN STOCK") inStock++;
        else if (p.status === "LOW STOCK") lowStock++;
        else if (p.status === "OUT OF STOCK") outOfStock++;
    });

    document.getElementById('total-products').textContent = products.length;
    document.getElementById('in-stock-count').textContent = inStock;
    document.getElementById('low-stock-count').textContent = lowStock;
    document.getElementById('out-of-stock-count').textContent = outOfStock;
}
    