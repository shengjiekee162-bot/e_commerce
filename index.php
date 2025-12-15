<?php include 'includes/head.php'; ?>
<?php include 'includes/header.php'; ?>
<?php require_once 'config/db.php'; ?>

<!-- Hero Banner / Carousel -->
<div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
    </div>
    <div class="carousel-inner">
        <div class="carousel-item active">
            <div class="hero-slide hero-slide-1">
                <div class="container">
                    <div class="hero-content">
                        <h1 class="display-3 fw-bold">Welcome to Our Store</h1>
                        <p class="lead">Discover amazing products at great prices</p>
                        <a href="#products" class="btn btn-primary btn-lg">Shop Now</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="carousel-item">
            <div class="hero-slide hero-slide-2">
                <div class="container">
                    <div class="hero-content">
                        <h1 class="display-3 fw-bold">Special Offers</h1>
                        <p class="lead">Up to 50% off on selected items</p>
                        <a href="#products" class="btn btn-warning btn-lg">View Deals</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="carousel-item">
            <div class="hero-slide hero-slide-3">
                <div class="container">
                    <div class="hero-content">
                        <h1 class="display-3 fw-bold">New Arrivals</h1>
                        <p class="lead">Check out our latest collection</p>
                        <a href="#products" class="btn btn-success btn-lg">Explore</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>

<!-- Categories Section -->
<section class="categories-section py-5">
    <div class="container">
        <h2 class="text-center mb-4">Shop by Category</h2>
        <div class="row g-4">
            <?php
            $categories_query = "SELECT * FROM categories LIMIT 6";
            $categories_result = $conn->query($categories_query);
            
            if ($categories_result && $categories_result->num_rows > 0):
                while($category = $categories_result->fetch_assoc()):
            ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="products.php?category=<?= $category['id'] ?>" class="category-card">
                        <div class="card text-center h-100">
                            <div class="card-body">
                                <i class="bi bi-tag-fill fs-1 text-primary mb-3"></i>
                                <h5 class="card-title"><?= htmlspecialchars($category['name']) ?></h5>
                            </div>
                        </div>
                    </a>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <div class="col-12 text-center">
                    <p class="text-muted">No categories available</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section id="products" class="products-section py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-4">Featured Products</h2>
        <div class="row g-4">
            <?php
            $products_query = "SELECT p.*, c.name as category_name 
                              FROM products p 
                              LEFT JOIN categories c ON p.category_id = c.id 
                              ORDER BY p.created_at DESC 
                              LIMIT 8";
            $products_result = $conn->query($products_query);
            
            if ($products_result && $products_result->num_rows > 0):
                while($product = $products_result->fetch_assoc()):
                    $image_path = !empty($product['image_url']) ? 'uploads/products/' . $product['image_url'] : 'https://via.placeholder.com/300x300?text=No+Image';
            ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card product-card h-100">
                        <img src="<?= htmlspecialchars($image_path) ?>" class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>">
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-secondary mb-2 align-self-start"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></span>
                            <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                            <p class="card-text text-muted small"><?= htmlspecialchars(substr($product['description'], 0, 80)) ?>...</p>
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="h5 mb-0 text-primary">RM<?= number_format($product['price'], 2) ?></span>
                                    <span class="text-muted small">Stock: <?= $product['stock'] ?></span>
                                </div>
                                <button class="btn btn-primary w-100" onclick="addToCart(<?= $product['id'] ?>)">
                                    <i class="bi bi-cart-plus"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <div class="col-12 text-center">
                    <p class="text-muted">No products available</p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($products_result && $products_result->num_rows > 0): ?>
        <div class="text-center mt-4">
            <a href="products.php" class="btn btn-outline-primary btn-lg">View All Products</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Features Section -->
<section class="features-section py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3 col-6">
                <div class="feature-box text-center">
                    <i class="bi bi-truck fs-1 text-primary mb-3"></i>
                    <h5>Free Shipping</h5>
                    <p class="text-muted small">On orders over $50</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="feature-box text-center">
                    <i class="bi bi-shield-check fs-1 text-success mb-3"></i>
                    <h5>Secure Payment</h5>
                    <p class="text-muted small">100% secure payment</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="feature-box text-center">
                    <i class="bi bi-arrow-repeat fs-1 text-warning mb-3"></i>
                    <h5>Easy Returns</h5>
                    <p class="text-muted small">30 days return policy</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="feature-box text-center">
                    <i class="bi bi-headset fs-1 text-info mb-3"></i>
                    <h5>24/7 Support</h5>
                    <p class="text-muted small">Dedicated support</p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Hero Carousel Styles */
.hero-slide {
    height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.hero-slide-1 {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.hero-slide-2 {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.hero-slide-3 {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.hero-content {
    text-align: center;
}

/* Category Cards */
.category-card {
    text-decoration: none;
    transition: transform 0.3s;
}

.category-card:hover {
    transform: translateY(-5px);
}

.category-card .card {
    border: 1px solid #ddd;
    transition: box-shadow 0.3s;
}

.category-card:hover .card {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

/* Product Cards */
.product-card {
    border: 1px solid #ddd;
    transition: transform 0.3s, box-shadow 0.3s;
    overflow: hidden;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.product-card img {
    height: 200px;
    object-fit: cover;
}

.product-card .card-title {
    font-size: 1rem;
    font-weight: 600;
    color: #333;
}

/* Feature Boxes */
.feature-box {
    padding: 20px;
    transition: transform 0.3s;
}

.feature-box:hover {
    transform: translateY(-5px);
}

/* Responsive */
@media (max-width: 576px) {
    .hero-slide {
        height: 300px;
    }
    
    .hero-content h1 {
        font-size: 2rem;
    }
    
    .product-card img {
        height: 150px;
    }
}
</style>

<script>
// Add to cart function
function addToCart(productId) {
    // Check if user is logged in
    <?php if (!isset($_SESSION['user'])): ?>
        alert('Please login to add items to cart');
        window.location.href = 'login.php';
        return;
    <?php endif; ?>
    
    // Create form data
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', 1);
    
    // Send AJAX request
    fetch('cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // Show success message
        alert('Product added to cart successfully!');
        
        // Optionally redirect to cart page
        if (confirm('Go to cart?')) {
            window.location.href = 'cart.php';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to add product to cart. Please try again.');
    });
}
</script>

<?php include 'includes/footer.php'; ?>