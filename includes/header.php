<!-- Header partial (assumes `includes/head.php` was included earlier) -->

<!-- ────────────────────────────────────── -->
<!-- 🔶 TOP BAR (Desktop Only) -->
<!-- ────────────────────────────────────── -->
<div class="top-bar">
  <div class="container d-flex justify-content-between align-items-center">

    <!-- Left -->
    <div>
      <a href="#">Help Center</a> |
      <a href="#">English / 中文</a>
    </div>

    <!-- Right -->
    <div class="d-flex align-items-center">

      <a href="#">Notifications</a> |
      <a href="orders.php">My Orders</a> |
      <a href="cart.php">Cart</a>

      <!-- User status -->
      <?php if (isset($_SESSION['user'])): ?>
        <div class="dropdown d-inline position-relative ms-2">
          <a href="#" class="dropdown-toggle text-white" role="button" data-bs-toggle="dropdown">
            👋 <?= htmlspecialchars($_SESSION['user']) ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> My Profile</a></li>
            <li><a class="dropdown-item" href="orders.php"><i class="bi bi-box-seam"></i> My Orders</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
          </ul>
        </div>
      <?php else: ?>
        <a class="ms-2" href="login.php">Login</a>
        |
        <a href="register.php">Register</a>
      <?php endif; ?> |

      <!-- User status -->
      <?php if (isset($_SESSION['user'])): ?>
        <div class="dropdown d-inline position-relative ms-2">
          <a href="#" class="dropdown-toggle text-white" role="button" data-bs-toggle="dropdown">
            👋 <?= htmlspecialchars($_SESSION['user']) ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
          </ul>
        </div>
      <?php else: ?>
        <a class="ms-2" href="login.php">Login</a>
        |
        <a href="register.php">Register</a>
      <?php endif; ?>

    </div>
  </div>
</div>


<!-- ────────────────────────────────────── -->
<!-- 🔶 MAIN NAV (Logo + Search + Cart) -->
<!-- ────────────────────────────────────── -->
<nav class="main-nav">
  <div class="container d-flex justify-content-between align-items-center">

    <!-- Logo -->
    <div class="logo">
      <a href="index.php" class="text-dark text-decoration-none">
          <i class="bi bi-shop fs-1"></i>
      </a>
    </div>

    <!-- Search -->
    <form class="d-flex flex-grow-1 search-bar" action="search.php" method="get">
      <input type="search" class="form-control" name="q" placeholder="Search products..." required>
      <button type="submit" class="btn">Search</button>
    </form>

    <!-- Cart (Desktop) -->
    <a href="cart.php" class="cart-btn">
      🛒 <span>0</span>
    </a>

    <!-- Hamburger Menu (Mobile/Tablet) -->
    <button class="hamburger" id="hamburgerBtn">
      <span></span>
      <span></span>
      <span></span>
    </button>

  </div>
</nav>

<!-- ────────────────────────────────────── -->
<!-- 🔶 NAVIGATION MENU (Mobile Collapsible) -->
<!-- ────────────────────────────────────── -->
<nav class="navbar" id="navMenu">
  <div class="container-fluid">
    <div class="nav-menu">
      <a href="index.php">Home</a>
      <a href="products.php">Products</a>
      <a href="categories.php">Categories</a>
      <a href="about.php">About</a>
      <a href="contact.php">Contact</a>
    </div>
  </div>
</nav>

<script>
  // Hamburger menu toggle
  const hamburger = document.getElementById('hamburgerBtn');
  const navMenu = document.getElementById('navMenu');

  if (hamburger) {
    hamburger.addEventListener('click', function() {
      hamburger.classList.toggle('active');
      navMenu.classList.toggle('active');
    });

    // Close menu when a link is clicked
    const navLinks = navMenu.querySelectorAll('a');
    navLinks.forEach(link => {
      link.addEventListener('click', function() {
        hamburger.classList.remove('active');
        navMenu.classList.remove('active');
      });
    });
  }
</script>

<!-- footer will include Bootstrap JS and close body/html -->

