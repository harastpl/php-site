<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Get filter parameters
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Get all products with filters
$products = getProducts(null, false, false, $categoryId, $search, $sortBy, $sortOrder);
$categories = getCategories();

include 'includes/header.php';
?>
<style>
@media (max-width: 768px) {
  nav.navbar.mobile-only { display: none !important; }
}
</style>

<!-- Page Header (Desktop only toolbar) -->
<div class="d-flex align-items-center gap-3 mb-4 desktop-only">
    <h2 class="mb-0" style="font-size: 1.25rem;">All Products</h2>
</div>

<!-- Desktop Filters -->
<div class="search-filter-section desktop-only">
    <form method="GET" class="row g-3">
        <div class="col-md-4">
            <input type="text" class="form-control search-input" name="search" 
                   placeholder="Search products..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
        </div>
        <div class="col-md-2">
            <select class="form-select filter-select" name="category">
                <option value="">All Categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" 
                            <?php echo $categoryId == $category['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select filter-select" name="sort">
                <option value="created_at" <?php echo $sortBy == 'created_at' ? 'selected' : ''; ?>>Sort by: Date Added</option>
                <option value="name" <?php echo $sortBy == 'name' ? 'selected' : ''; ?>>Sort by: Name</option>
                <option value="price" <?php echo $sortBy == 'price' ? 'selected' : ''; ?>>Sort by: Price</option>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select filter-select" name="order">
                <option value="ASC" <?php echo $sortOrder == 'ASC' ? 'selected' : ''; ?>>Order by: Ascending</option>
                <option value="DESC" <?php echo $sortOrder == 'DESC' ? 'selected' : ''; ?>>Order by: Descending</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>
</div>

<?php if (empty($products)): ?>
    <div class="text-center py-5">
        <h4>No products available</h4>
        <p>Check back later for new products or place a custom order.</p>
        <a href="custom-order.php" class="btn btn-primary">Place Custom Order</a>
    </div>
<?php else: ?>
    <!-- Desktop Product Grid -->
    <div class="product-grid desktop-only" style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.5rem; align-items: stretch;">
        <?php foreach ($products as $product): ?>
            <?php 
                $galleryImages = !empty($product['image_gallery']) ? explode(',', $product['image_gallery']) : [$product['image']];
                $primaryImage = $galleryImages[0];
            ?>
            <div class="card h-100 product-card-clickable" 
                 onclick="window.location.href='product.php?id=<?php echo $product['id']; ?>'"
                 data-gallery='<?php echo json_encode($galleryImages); ?>' 
                 data-primary-image="<?php echo htmlspecialchars($primaryImage); ?>">
                
                <?php if (!empty($product['is_featured'])): ?>
                    <div class="featured-badge">Featured</div>
                <?php endif; ?>

                <img src="uploads/products/<?php echo htmlspecialchars($primaryImage); ?>" 
                     class="card-img-top product-card-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                    <?php if (!empty($product['category_name'])): ?>
                        <small class="text-muted mb-2"><?php echo htmlspecialchars($product['category_name']); ?></small>
                    <?php endif; ?>
                                        
                    <div class="mb-3">
                        <span class="price"><?php echo formatCurrency($product['price']); ?></span>
                        <?php if (!empty($product['stock']) && $product['stock'] <= 0): ?>
                            <span class="badge bg-danger ms-2">Out of Stock</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary" onclick="event.stopPropagation()">View Details</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Mobile Search Header -->
<div class="mobile-only">
    <div class="mobile-search-header" style="background-color: #008BFF; padding: 0;">
        <div class="container">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm text-white border-white" style="background: transparent;" onclick="history.back()">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="flex-grow-1">
                    <input type="text" class="form-control form-control-sm" placeholder="Search products..." id="mobile-search" onclick="toggleMobileFilters()">
                </div>
                <button class="btn btn-sm text-white border-white" style="background: transparent;" onclick="window.location.href='cart.php'">
                    <i class="fas fa-shopping-cart"></i>
                </button>
            </div>
            
            <div class="mobile-search-filters mt-2" id="mobile-filters" style="display: none;">
                <form method="GET" class="row g-2">
                    <div class="col-6">
                        <select class="form-select form-select-sm" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo $categoryId == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <select class="form-select form-select-sm" name="sort">
                            <option value="created_at" <?php echo $sortBy == 'created_at' ? 'selected' : ''; ?>>Date Added</option>
                            <option value="name" <?php echo $sortBy == 'name' ? 'selected' : ''; ?>>Name</option>
                            <option value="price" <?php echo $sortBy == 'price' ? 'selected' : ''; ?>>Price</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Apply Filters</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Mobile Product List -->
    <div class="container mt-3 mb-5">
        <?php if (empty($products)): ?>
            <div class="text-center py-5">
                <h4>No products available</h4>
                <p>Check back later for new products or place a custom order.</p>
                <a href="custom-order.php" class="btn btn-primary">Place Custom Order</a>
            </div>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <?php 
                    $galleryImages = !empty($product['image_gallery']) ? explode(',', $product['image_gallery']) : [$product['image']];
                    $primaryImage = $galleryImages[0];
                ?>
                <div class="mobile-product-card" onclick="window.location.href='product.php?id=<?php echo $product['id']; ?>'">
                    <div class="product-image">
                        <img src="uploads/products/<?php echo htmlspecialchars($primaryImage); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>
                    <div class="product-info">
                        <div class="product-name" style="color: var(--primary-blue);"><?php echo htmlspecialchars($product['name']); ?></div>
                        <?php if (!empty($product['category_name'])): ?>
                            <div class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></div>
                        <?php endif; ?>
                        <div class="product-price"><?php echo formatCurrency($product['price']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Mobile Bottom Navigation -->
<div class="mobile-only">
    <div class="mobile-bottom-nav">
        <a href="index.php">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="products.php" class="active">
            <i class="fas fa-box"></i>
            <span>Products</span>
        </a>
        <a href="orders.php">
            <i class="fas fa-user"></i>
            <span>Account</span>
        </a>
        <a href="cart.php">
            <i class="fas fa-shopping-cart"></i>
            <span>Cart</span>
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    function toggleMobileFilters() {
        const filters = document.getElementById('mobile-filters');
        if (filters.style.display === 'none' || filters.style.display === '') {
            filters.style.display = 'block';
        } else {
            filters.style.display = 'none';
        }
    }
</script>
