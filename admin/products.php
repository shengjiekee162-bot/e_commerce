<?php
include_once '../config/db.php';
include_once 'admin_header.php';
include_once 'admin_auth.php';

// Handle success/error messages
$success = '';
$error = '';

if (isset($_GET['success']) && $_GET['success'] === 'deleted') {
    $success = 'Product deleted successfully!';
}

if (isset($_GET['error'])) {
    if ($_GET['error'] === 'invalid') {
        $error = 'Invalid product ID.';
    } elseif ($_GET['error'] === 'notfound') {
        $error = 'Product not found.';
    } elseif ($_GET['error'] === 'deletefailed') {
        $error = 'Failed to delete product.';
    }
}


// ...existing code...
$sql = "SELECT products.*, categories.name AS category_name FROM products INNER JOIN categories ON products.category_id = categories.id";
$result = $conn->query($sql);
$catSql = "SELECT * FROM categories";
$catResult = $conn->query($catSql);

?>

<!-- =================== CSS =================== -->
<style>

.product-list-table {
    box-shadow: 0 2px 12px rgba(0,0,0,0.12);
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
}

.product-list-table th {
    background: linear-gradient(90deg, #222 0%, #888 100%);
    color: #fff;
    font-weight: bold;
    letter-spacing: 1px;
    font-size: 1.08em;
}
.product-list-table tbody tr {
    background: linear-gradient(90deg, #fff 0%, #eee 100%);
    color: #222;
    transition: background 0.2s, color 0.2s;
}
.product-list-table tbody tr:hover {
    background: linear-gradient(90deg, #eee 0%, #ccc 100%);
    color: #000;
    transform: scale(1.01);
}
.active-row {
    background: linear-gradient(90deg, #000 0%, #444 100%) !important;
    color: #fff !important;
}
.btn {
    background: linear-gradient(90deg, #222 0%, #555 100%);
    color: #fff;
    border: none;
    transition: background 0.2s, color 0.2s, transform 0.2s;
}
.btn:hover {
    background: linear-gradient(90deg, #000 0%, #333 100%);
    color: #fff;
    transform: translateY(-2px) scale(1.06);
}
</style>

<main class="content-wrapper">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold">Product List</h2>
        <a href="add_product.php" class="btn btn-primary">＋ Add New Product</a>
    </div>


    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success); ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- 🔍 Search + Filter Bar -->
    <div class="card p-3 mb-4 shadow-sm">
        <div class="row g-3">

            <div class="col-md-4 ms-auto">
                <input type="search" id="productSearch" class="form-control" placeholder="🔍 Search products..." onkeyup="searchProducts()">
            </div>

            <div class="col-md-3">
                <select id="categoryFilter" class="form-select" onchange="filterProducts()">
                    <option value="">All Categories</option>
                    <?php while ($cat = $catResult->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-3">
                <select id="priceSort" class="form-select" onchange="filterProducts()">
                    <option value="">Sort by Price</option>
                    <option value="asc">Low → High</option>
                    <option value="desc">High → Low</option>
                </select>
            </div>

        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle product-list-table" id="productTable">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th style="width: 160px;">Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <img src="<?php echo !empty($row['image_url']) ? '../'.htmlspecialchars($row['image_url']) : 'https://via.placeholder.com/55'; ?>"
                                     style="width:55px;height:55px;object-fit:cover;">
                            </td>
                            <td><?php echo htmlspecialchars($row["name"]); ?></td>
                            <td><?php echo htmlspecialchars($row["category_name"]); ?></td>
                            <td><?php echo htmlspecialchars($row["description"]); ?></td>
                            <td class="fw-bold text-success">RM <?php echo number_format($row["price"], 2); ?></td>
                            <td><?php echo htmlspecialchars($row["stock"]); ?></td>

                            <td>
                                <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                <a href="delete_product.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger"
                                   onclick="return confirm('Delete this product?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">No products found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</main>

<!-- =================== JAVASCRIPT =================== -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.product-list-table tbody tr').forEach(row => {
        row.addEventListener('click', () => {
            document.querySelectorAll('.product-list-table tbody tr')
                    .forEach(r => r.classList.remove('active-row'));
            row.classList.add('active-row');
        });
    });
});

/* Search */
function searchProducts() {
    let input = document.getElementById("productSearch").value.toLowerCase();
    let rows = document.querySelectorAll("#productTable tbody tr");

    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(input) ? "" : "none";
    });
}

/* Filter + Sort */
function filterProducts() {
    let category = document.getElementById("categoryFilter").value;
    let sort = document.getElementById("priceSort").value;
    let table = document.querySelector("#productTable tbody");
    let rows = Array.from(table.querySelectorAll("tr"));

    rows.forEach(row => {
        let cat = row.children[2].innerText;
        row.style.display = !category || category === cat ? "" : "none";
    });

    if (sort) {
        rows.sort((a, b) => {
            let p1 = parseFloat(a.children[4].innerText.replace("RM", ""));
            let p2 = parseFloat(b.children[4].innerText.replace("RM", ""));
            return sort === "asc" ? p1 - p2 : p2 - p1;
        });
        rows.forEach(r => table.appendChild(r));
    }
}
</script>
