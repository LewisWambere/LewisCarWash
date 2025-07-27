<?php
session_start();
include('db_connection.php');

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Function to safely execute queries and handle errors
function safe_query($conn, $query) {
    $result = mysqli_query($conn, $query);
    if ($result === false) {
        error_log("Database query failed: " . mysqli_error($conn) . " | Query: " . $query);
        return false;
    }
    return $result;
}

// Handle AJAX requests for customer actions
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get_customer_bookings':
            $customer_id = intval($_POST['customer_id']);
            $bookings_query = "SELECT b.*, s.price, s.service_type as service_name 
                              FROM bookings b 
                              LEFT JOIN services s ON b.service_type = s.service_type 
                              WHERE b.customer_username = (SELECT username FROM users WHERE id = ? AND role = 'customer') 
                              ORDER BY b.booking_date DESC 
                              LIMIT 10";
            
            $stmt = mysqli_prepare($conn, $bookings_query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $customer_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $bookings = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $bookings[] = $row;
                }
                mysqli_stmt_close($stmt);
                echo json_encode(['success' => true, 'bookings' => $bookings]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to fetch bookings']);
            }
            exit();
            
        case 'search_customers':
            $search = trim($_POST['search']);
            $search_param = "%{$search}%";
            
            // Updated search query with proper JOIN
            $search_query = "SELECT u.id, u.username, u.created_at as date_added,
                            c.name, c.email, c.phone,
                            COUNT(b.id) as total_bookings,
                            COALESCE(SUM(CASE WHEN b.status = 'completed' THEN s.price ELSE 0 END), 0) as total_spent,
                            MAX(b.booking_date) as last_visit
                            FROM users u 
                            LEFT JOIN customers c ON u.id = c.user_id
                            LEFT JOIN bookings b ON u.username = b.customer_username 
                            LEFT JOIN services s ON b.service_type = s.service_type
                            WHERE u.role = 'customer' 
                            AND (c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ? OR u.username LIKE ?)
                            GROUP BY u.id 
                            ORDER BY c.name ASC, u.username ASC";
            
            $stmt = mysqli_prepare($conn, $search_query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ssss", $search_param, $search_param, $search_param, $search_param);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $customers = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $customers[] = $row;
                }
                mysqli_stmt_close($stmt);
                echo json_encode(['success' => true, 'customers' => $customers]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Search failed']);
            }
            exit();
    }
}

// Get customers with their booking statistics - JOIN users and customers tables
$customers_query = "SELECT u.id, u.username, u.created_at as date_added,
                    c.name, c.email, c.phone,
                    COUNT(b.id) as total_bookings,
                    COALESCE(SUM(CASE WHEN b.status = 'completed' THEN s.price ELSE 0 END), 0) as total_spent,
                    MAX(b.booking_date) as last_visit
                    FROM users u 
                    LEFT JOIN customers c ON u.id = c.user_id
                    LEFT JOIN bookings b ON u.username = b.customer_username 
                    LEFT JOIN services s ON b.service_type = s.service_type
                    WHERE u.role = 'customer'
                    GROUP BY u.id 
                    ORDER BY c.name ASC, u.username ASC";

$customers_result = safe_query($conn, $customers_query);
$customers = [];

if ($customers_result) {
    while ($row = mysqli_fetch_assoc($customers_result)) {
        $customers[] = $row;
    }
}

// If no customers found, try simpler query with JOIN
if (empty($customers)) {
    $simple_query = "SELECT u.id, u.username, u.created_at as date_added,
                     c.name, c.email, c.phone
                     FROM users u 
                     LEFT JOIN customers c ON u.id = c.user_id
                     WHERE u.role = 'customer' 
                     ORDER BY c.name ASC, u.username ASC";
    $simple_result = safe_query($conn, $simple_query);
    
    if ($simple_result) {
        while ($row = mysqli_fetch_assoc($simple_result)) {
            // Add default values for missing fields
            $row['total_bookings'] = 0;
            $row['total_spent'] = 0;
            $row['last_visit'] = null;
            $customers[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - Lewis Car Wash</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #ffffff;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            padding: 1.5rem 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            border-bottom: 3px solid #ffd700;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo-section h1 {
            font-size: 2rem;
            color: #ffd700;
            font-weight: bold;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .welcome-text {
            font-size: 1.1rem;
            color: #e0e0e0;
        }

        .welcome-text .username {
            color: #ffd700;
            font-weight: 600;
        }

        .badge {
            background: #ffd700;
            color: #1a1a1a;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 0.5rem;
        }

        .back-btn {
            background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%);
            color: white;
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 205, 196, 0.3);
        }

        .main-content {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title h2 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-title p {
            color: #b0b0b0;
            font-size: 1.1rem;
        }

        .controls-section {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 2rem;
            border-radius: 16px;
            border: 2px solid #333;
            margin-bottom: 2rem;
        }

        .search-container {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1rem;
        }

        .search-input {
            flex: 1;
            padding: 1rem;
            border: 2px solid #444;
            border-radius: 8px;
            background: #2d2d2d;
            color: #ffffff;
            font-size: 1rem;
        }

        .search-input:focus {
            outline: none;
            border-color: #ffd700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .search-btn {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
        }

        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .stat-item {
            background: rgba(255, 215, 0, 0.1);
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            border: 1px solid rgba(255, 215, 0, 0.2);
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #ffd700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #b0b0b0;
            font-size: 0.9rem;
        }

        .customers-container {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            border-radius: 16px;
            border: 2px solid #333;
            overflow: hidden;
        }

        .customers-header {
            background: rgba(255, 215, 0, 0.1);
            padding: 1.5rem 2rem;
            border-bottom: 2px solid #333;
        }

        .customers-title {
            color: #ffd700;
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .customers-table {
            width: 100%;
            border-collapse: collapse;
        }

        .customers-table th {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #ffd700;
            border-bottom: 2px solid #333;
        }

        .customers-table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .customers-table tbody tr {
            transition: all 0.3s ease;
        }

        .customers-table tbody tr:hover {
            background: rgba(255, 215, 0, 0.05);
        }

        .customer-name {
            font-weight: 600;
            color: #ffd700;
        }

        .customer-contact {
            color: #b0b0b0;
            font-size: 0.9rem;
        }

        .stat-badge {
            background: rgba(78, 205, 196, 0.2);
            color: #4ecdc4;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .money-badge {
            background: rgba(249, 202, 36, 0.2);
            color: #f9ca24;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .date-text {
            color: #b0b0b0;
            font-size: 0.9rem;
        }

        .action-btn {
            background: linear-gradient(135deg, #45b7d1 0%, #3867d6 100%);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(69, 183, 209, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            color: #444;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            color: #888;
            margin-bottom: 0.5rem;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
        }

        .modal-content {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            margin: 5% auto;
            padding: 2rem;
            border-radius: 16px;
            width: 90%;
            max-width: 800px;
            border: 2px solid #ffd700;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #333;
        }

        .modal-title {
            color: #ffd700;
            font-size: 1.5rem;
        }

        .close {
            color: #aaa;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #ffd700;
        }

        .booking-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #ffd700;
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .booking-service {
            color: #4ecdc4;
            font-weight: 600;
        }

        .booking-status {
            padding: 0.2rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-completed { background: rgba(46, 204, 113, 0.2); color: #2ecc71; }
        .status-waiting { background: rgba(241, 196, 15, 0.2); color: #f1c40f; }
        .status-in-progress { background: rgba(52, 152, 219, 0.2); color: #3498db; }
        .status-cancelled { background: rgba(231, 76, 60, 0.2); color: #e74c3c; }

        .booking-details {
            color: #b0b0b0;
            font-size: 0.9rem;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            z-index: 1001;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
        }

        .notification.show {
            opacity: 1;
            transform: translateX(0);
        }

        .notification.error {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }

        @media (max-width: 768px) {
            .customers-table {
                font-size: 0.8rem;
            }
            
            .search-container {
                flex-direction: column;
            }
            
            .stats-summary {
                grid-template-columns: 1fr;
            }
            
            .customers-table th,
            .customers-table td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo-section">
                <?php
                $dashboard_url = 'dashboard.php';
                if (isset($_SESSION['role'])) {
                    if ($_SESSION['role'] === 'attendant') {
                        $dashboard_url = 'attendant_dashboard.php';
                    } elseif ($_SESSION['role'] === 'customer') {
                        $dashboard_url = 'customer_dashboard.php';
                    }
                }
                ?>
                <a href="<?= $dashboard_url ?>" style="text-decoration: none;">
                    <h1><i class="fas fa-car-wash"></i> Lewis Car Wash</h1>
                </a>
            </div>
            <div class="user-section">
                <div class="welcome-text">
                    <span class="username"><?= htmlspecialchars($username) ?></span>
                    <span class="badge"><?= strtoupper($role) ?></span>
                </div>
                <a href="<?= $role === 'admin' ? 'dashboard.php' : 'attendant_dashboard.php' ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="page-title">
            <h2>Customer Management</h2>
            <p>View and manage customer information and booking history</p>
        </div>

        <!-- Search and Controls -->
        <div class="controls-section">
            <div class="search-container">
                <input type="text" id="searchInput" class="search-input" placeholder="Search customers by name, phone, email, or username...">
                <button onclick="searchCustomers()" class="search-btn">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
            
            <div class="stats-summary" id="statsSummary">
                <div class="stat-item">
                    <div class="stat-number" id="totalCustomers"><?= count($customers) ?></div>
                    <div class="stat-label">Total Customers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="activeCustomers">
                        <?= count(array_filter($customers, function($c) { return $c['total_bookings'] > 0; })) ?>
                    </div>
                    <div class="stat-label">Active Customers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="totalRevenue">
                        KES <?= number_format(array_sum(array_column($customers, 'total_spent')), 0) ?>
                    </div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="customers-container">
            <div class="customers-header">
                <h3 class="customers-title">
                    <i class="fas fa-users"></i> Customer List
                </h3>
            </div>
            
            <div id="customersTableContainer">
                <?php if (empty($customers)): ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h3>No customers found</h3>
                        <p>No customer records are available in the system.</p>
                    </div>
                <?php else: ?>
                    <table class="customers-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Total Bookings</th>
                                <th>Total Spent</th>
                                <th>Last Visit</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="customersTableBody">
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td>
                                        <div class="customer-name"><?= htmlspecialchars($customer['name'] ?? $customer['username'] ?? 'Unknown') ?></div>
                                        <div class="customer-contact">@<?= htmlspecialchars($customer['username'] ?? 'N/A') ?></div>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($customer['phone'] ?? 'N/A') ?></div>
                                        <div class="customer-contact"><?= htmlspecialchars($customer['email'] ?? 'N/A') ?></div>
                                    </td>
                                    <td>
                                        <span class="stat-badge"><?= intval($customer['total_bookings']) ?></span>
                                    </td>
                                    <td>
                                        <span class="money-badge">KES <?= number_format($customer['total_spent'], 0) ?></span>
                                    </td>
                                    <td>
                                        <div class="date-text">
                                            <?= $customer['last_visit'] ? date('M j, Y', strtotime($customer['last_visit'])) : 'Never' ?>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="action-btn" onclick="viewCustomerBookings(<?= $customer['id'] ?>, '<?= htmlspecialchars($customer['name'] ?? $customer['username']) ?>')">
                                            <i class="fas fa-eye"></i> View History
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Customer Bookings Modal -->
    <div id="bookingsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Customer Booking History</h2>
                <span class="close" onclick="closeBookingsModal()">&times;</span>
            </div>
            <div id="bookingsContent">
                <!-- Booking history will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Notification -->
    <div class="notification" id="notification"></div>

    <script>
        // Search customers
        function searchCustomers() {
            const searchTerm = document.getElementById('searchInput').value.trim();
            
            if (searchTerm === '') {
                location.reload();
                return;
            }
            
            const searchBtn = document.querySelector('.search-btn');
            const originalText = searchBtn.innerHTML;
            searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
            searchBtn.disabled = true;
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=search_customers&search=${encodeURIComponent(searchTerm)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCustomersTable(data.customers);
                    updateStats(data.customers);
                } else {
                    showNotification(data.error || 'Search failed', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Search error occurred', 'error');
            })
            .finally(() => {
                searchBtn.innerHTML = originalText;
                searchBtn.disabled = false;
            });
        }

        // Update customers table
        function updateCustomersTable(customers) {
            const container = document.getElementById('customersTableContainer');
            
            if (customers.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>No customers found</h3>
                        <p>No customers match your search criteria.</p>
                    </div>
                `;
                return;
            }
            
            let tableHTML = `
                <table class="customers-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Total Bookings</th>
                            <th>Total Spent</th>
                            <th>Last Visit</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            customers.forEach(customer => {
                const lastVisit = customer.last_visit ? 
                    new Date(customer.last_visit).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 
                    'Never';
                    
                tableHTML += `
                    <tr>
                        <td>
                            <div class="customer-name">${escapeHtml(customer.name || customer.username || 'Unknown')}</div>
                            <div class="customer-contact">@${escapeHtml(customer.username || 'N/A')}</div>
                        </td>
                        <td>
                            <div>${escapeHtml(customer.phone || 'N/A')}</div>
                            <div class="customer-contact">${escapeHtml(customer.email || 'N/A')}</div>
                        </td>
                        <td>
                            <span class="stat-badge">${parseInt(customer.total_bookings) || 0}</span>
                        </td>
                        <td>
                            <span class="money-badge">KES ${new Intl.NumberFormat().format(customer.total_spent || 0)}</span>
                        </td>
                        <td>
                            <div class="date-text">${lastVisit}</div>
                        </td>
                        <td>
                            <button class="action-btn" onclick="viewCustomerBookings(${customer.id}, '${escapeHtml(customer.name || customer.username)}')">
                                <i class="fas fa-eye"></i> View History
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            tableHTML += '</tbody></table>';
            container.innerHTML = tableHTML;
        }

        // Update statistics
        function updateStats(customers) {
            const totalCustomers = customers.length;
            const activeCustomers = customers.filter(c => parseInt(c.total_bookings) > 0).length;
            const totalRevenue = customers.reduce((sum, c) => sum + parseFloat(c.total_spent || 0), 0);
            
            document.getElementById('totalCustomers').textContent = totalCustomers;
            document.getElementById('activeCustomers').textContent = activeCustomers;
            document.getElementById('totalRevenue').textContent = 'KES ' + new Intl.NumberFormat().format(totalRevenue);
        }

        // View customer bookings
        function viewCustomerBookings(customerId, customerName) {
            document.getElementById('modalTitle').textContent = `${customerName} - Booking History`;
            document.getElementById('bookingsContent').innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            document.getElementById('bookingsModal').style.display = 'block';
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=get_customer_bookings&customer_id=${customerId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayBookings(data.bookings);
                } else {
                    document.getElementById('bookingsContent').innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3>Error Loading Bookings</h3>
                            <p>${data.error || 'Failed to load booking history'}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('bookingsContent').innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Connection Error</h3>
                        <p>Unable to load booking history. Please try again.</p>
                    </div>
                `;
            });
        }

        // Display bookings in modal
        function displayBookings(bookings) {
            const content = document.getElementById('bookingsContent');
            
            if (bookings.length === 0) {
                content.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No Bookings Found</h3>
                        <p>This customer hasn't made any bookings yet.</p>
                    </div>
                `;
                return;
            }
            
            let bookingsHTML = '';
            bookings.forEach(booking => {
                const statusClass = `status-${booking.status || 'waiting'}`;
                const bookingDate = booking.booking_date ? new Date(booking.booking_date).toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }) : 'N/A';
                
                bookingsHTML += `
                    <div class="booking-item">
                        <div class="booking-header">
                            <span class="booking-service">${escapeHtml(booking.service_name || booking.service_type || 'Unknown Service')}</span>
                            <span class="booking-status ${statusClass}">${escapeHtml(booking.status || 'Waiting')}</span>
                        </div>
                        <div class="booking-details">
                            <p><strong>Date:</strong> ${bookingDate}</p>
                            <p><strong>Price:</strong> KES ${new Intl.NumberFormat().format(booking.price || 0)}</p>
                            ${booking.notes ? `<p><strong>Notes:</strong> ${escapeHtml(booking.notes)}</p>` : ''}
                        </div>
                    </div>
                `;
            });
            
            content.innerHTML = bookingsHTML;
        }

        // Close bookings modal
        function closeBookingsModal() {
            document.getElementById('bookingsModal').style.display = 'none';
        }

        // Show notification
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('bookingsModal');
            if (event.target === modal) {
                closeBookingsModal();
            }
        }

        // Allow search on Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                searchCustomers();
            }
        });
    </script>
</body>
</html>