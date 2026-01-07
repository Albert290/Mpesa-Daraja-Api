<?php
session_start();
include 'dbconnection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get statistics
$stats = [];
$stats['total_registrations'] = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM registrations"))['count'];
$stats['paid_registrations'] = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM registrations WHERE payment_status = 'paid'"))['count'];
$stats['pending_payments'] = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM registrations WHERE payment_status = 'pending'"))['count'];
$stats['failed_payments'] = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM registrations WHERE payment_status = 'failed'"))['count'];
$stats['total_revenue'] = mysqli_fetch_assoc(mysqli_query($db, "SELECT SUM(total_amount) as total FROM registrations WHERE payment_status = 'paid'"))['total'] ?? 0;

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
if ($status_filter !== 'all') {
    $where_conditions[] = "payment_status = '" . mysqli_real_escape_string($db, $status_filter) . "'";
}
if ($search) {
    $search_escaped = mysqli_real_escape_string($db, $search);
    $where_conditions[] = "(full_name LIKE '%$search_escaped%' OR email LIKE '%$search_escaped%' OR phone LIKE '%$search_escaped%')";
}

$where_clause = empty($where_conditions) ? "" : "WHERE " . implode(" AND ", $where_conditions);
$registrations_query = "SELECT * FROM registrations $where_clause ORDER BY created_at DESC";
$registrations = mysqli_query($db, $registrations_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Urban Swaras</title>
    <style>
        :root {
            --primary: #1F3C5F;
            --secondary: #6BA2D9;
            --accent: #F57C51;
            --text: #333333;
            --light-bg: #F5F5F5;
            --white: #FFFFFF;
            --neon-yellow: #FFFF00;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Arial', sans-serif;
            background: var(--light-bg);
            color: var(--text);
        }
        
        .header {
            background: var(--primary);
            color: var(--white);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logout-btn {
            background: var(--accent);
            color: var(--white);
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .stat-label {
            color: #666;
            margin-top: 0.5rem;
        }
        
        .revenue {
            color: var(--success) !important;
        }
        
        .controls {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .search-box, .filter-select {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .search-box {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-btn {
            background: var(--secondary);
            color: var(--white);
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .table-container {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            background: var(--primary);
            color: var(--white);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }
        
        .table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .table tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }
            
            .controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .table {
                min-width: 800px;
            }
            
            .modal-content {
                margin: 2% auto;
                width: 95%;
                max-height: 95vh;
                overflow-y: auto;
            }
            
            .modal-header {
                padding: 1rem;
            }
            
            .modal-title {
                font-size: 1.2rem;
            }
            
            .modal-body {
                padding: 1rem;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .detail-section {
                padding: 1rem;
            }
            
            .section-title {
                font-size: 1rem;
            }
            
            .detail-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.3rem;
            }
            
            .detail-value {
                text-align: left;
                font-weight: 500;
            }
        }
        
        @media (max-width: 480px) {
            .modal-content {
                margin: 1% auto;
                width: 98%;
                border-radius: 10px;
            }
            
            .modal-header {
                padding: 0.8rem;
                border-radius: 10px 10px 0 0;
            }
            
            .modal-title {
                font-size: 1.1rem;
            }
            
            .close {
                font-size: 24px;
            }
            
            .modal-body {
                padding: 0.8rem;
            }
            
            .details-grid {
                gap: 0.8rem;
            }
            
            .detail-section {
                padding: 0.8rem;
            }
            
            .section-title {
                font-size: 0.95rem;
                margin-bottom: 0.8rem;
            }
            
            .detail-item {
                margin-bottom: 0.6rem;
                padding-bottom: 0.4rem;
            }
            
            .detail-label {
                font-size: 0.9rem;
            }
            
            .detail-value {
                font-size: 0.9rem;
            }
            
            .payment-info {
                padding: 0.8rem;
                margin-top: 0.8rem;
            }
            
            .payment-info h3 {
                font-size: 1.1rem;
                margin-bottom: 0.5rem;
            }
            
            .payment-info p {
                font-size: 0.9rem;
                margin-bottom: 0.3rem;
            }
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
            overflow-y: auto;
        }
        
        .modal-content {
            background-color: var(--white);
            margin: 5% auto;
            padding: 0;
            border-radius: 15px;
            width: 85%;
            max-width: 900px;
            max-height: 90vh;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            animation: slideIn 0.3s;
            overflow: hidden;
            position: relative;
        }
        
        .modal-header {
            background: var(--primary);
            color: var(--white);
            padding: 1.5rem 2rem;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .close {
            color: var(--white);
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
            padding: 5px;
            border-radius: 50%;
            transition: background-color 0.3s;
        }
        
        .close:hover {
            background-color: rgba(255,255,255,0.2);
        }
        
        .modal-body {
            padding: 2rem;
            overflow-y: auto;
            max-height: calc(90vh - 80px);
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .detail-section {
            background: var(--light-bg);
            padding: 1.5rem;
            border-radius: 10px;
            border-left: 4px solid var(--accent);
        }
        
        .section-title {
            color: var(--primary);
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid var(--accent);
            padding-bottom: 0.5rem;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.8rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #ddd;
            gap: 1rem;
        }
        
        .detail-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--text);
            flex-shrink: 0;
            min-width: 120px;
        }
        
        .detail-value {
            color: #666;
            text-align: right;
            word-break: break-word;
            flex: 1;
        }
        
        .payment-info {
            background: linear-gradient(135deg, var(--success) 0%, #20c997 100%);
            color: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            margin-top: 1rem;
        }
        
        .payment-info.pending {
            background: linear-gradient(135deg, var(--warning) 0%, #ffca2c 100%);
            color: var(--text);
        }
        
        .payment-info.failed {
            background: linear-gradient(135deg, var(--danger) 0%, #e74c3c 100%);
        }
        
        .payment-info h3 {
            margin-bottom: 0.8rem;
            font-size: 1.3rem;
        }
        
        .payment-info p {
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
        
        .payment-info p:last-child {
            margin-bottom: 0;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">Urban Swaras Admin</div>
        <div class="user-info">
            <span>Welcome, <?php echo $_SESSION['admin_username']; ?></span>
            <a href="admin_users.php" style="background: var(--secondary); color: var(--white); padding: 8px 16px; border: none; border-radius: 5px; text-decoration: none; font-size: 14px; margin-right: 10px;">Manage Admins</a>
            <a href="admin_logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_registrations']; ?></div>
                <div class="stat-label">Total Registrations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['paid_registrations']; ?></div>
                <div class="stat-label">Paid Registrations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_payments']; ?></div>
                <div class="stat-label">Pending Payments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['failed_payments']; ?></div>
                <div class="stat-label">Failed Payments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number revenue">KSH <?php echo number_format($stats['total_revenue']); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>
        
        <div class="controls">
            <input type="text" class="search-box" placeholder="Search by name, email, or phone..." value="<?php echo htmlspecialchars($search); ?>" id="searchBox">
            <select class="filter-select" id="statusFilter">
                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
            </select>
            <button class="filter-btn" onclick="applyFilters()">Filter</button>
        </div>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Race</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($reg = mysqli_fetch_assoc($registrations)): ?>
                    <tr onclick="showDetails(<?php echo $reg['id']; ?>)" style="cursor: pointer;">
                        <td><strong style="color: var(--primary);"><?php echo htmlspecialchars($reg['full_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($reg['email']); ?></td>
                        <td><?php echo htmlspecialchars($reg['phone']); ?></td>
                        <td><?php echo htmlspecialchars($reg['race_category']); ?></td>
                        <td>KSH <?php echo number_format($reg['total_amount']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $reg['payment_status']; ?>">
                                <?php echo ucfirst($reg['payment_status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y H:i', strtotime($reg['created_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Registration Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Registration Details</div>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Details will be loaded here -->
            </div>
        </div>
    </div>
    
    <script>
        function showDetails(registrationId) {
            fetch('get_registration_details.php?id=' + registrationId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayDetails(data.registration);
                        document.getElementById('detailsModal').style.display = 'block';
                    } else {
                        alert('Error loading details: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
        }
        
        function displayDetails(reg) {
            const modalBody = document.getElementById('modalBody');
            
            const paymentStatusClass = reg.payment_status === 'paid' ? '' : reg.payment_status;
            const paymentStatusText = reg.payment_status === 'paid' ? 'Payment Completed' : 
                                    reg.payment_status === 'pending' ? 'Payment Pending' : 'Payment Failed';
            
            modalBody.innerHTML = `
                <div class="details-grid">
                    <div class="detail-section">
                        <div class="section-title">Personal Information</div>
                        <div class="detail-item">
                            <span class="detail-label">Full Name:</span>
                            <span class="detail-value">${reg.full_name}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value">${reg.email}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Phone:</span>
                            <span class="detail-value">${reg.phone}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Gender:</span>
                            <span class="detail-value">${reg.gender}</span>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <div class="section-title">Race Information</div>
                        <div class="detail-item">
                            <span class="detail-label">Race Category:</span>
                            <span class="detail-value">${reg.race_category}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">First Time:</span>
                            <span class="detail-value">${reg.first_time}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Finisher Medal:</span>
                            <span class="detail-value">${reg.finisher_medal}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Transport:</span>
                            <span class="detail-value">${reg.transport}</span>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <div class="section-title">Emergency Contact</div>
                        <div class="detail-item">
                            <span class="detail-label">Name:</span>
                            <span class="detail-value">${reg.emergency_name}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Phone:</span>
                            <span class="detail-value">${reg.emergency_phone}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Relationship:</span>
                            <span class="detail-value">${reg.relationship}</span>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <div class="section-title">Medical Information</div>
                        <div class="detail-item">
                            <span class="detail-label">Medical Conditions:</span>
                            <span class="detail-value">${reg.medical_conditions || 'None specified'}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Allergies:</span>
                            <span class="detail-value">${reg.allergies || 'None specified'}</span>
                        </div>
                    </div>
                </div>
                
                <div class="payment-info ${paymentStatusClass}">
                    <h3>${paymentStatusText}</h3>
                    <p>Total Amount: KSH ${parseInt(reg.total_amount).toLocaleString()}</p>
                    <p>Registration Date: ${new Date(reg.created_at).toLocaleString()}</p>
                    <p>Order ID: ${reg.order_id}</p>
                </div>
            `;
        }
        
        function closeModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('detailsModal');
            if (event.target === modal) {
                closeModal();
            }
        }
        
        function applyFilters() {
            const search = document.getElementById('searchBox').value;
            const status = document.getElementById('statusFilter').value;
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (status !== 'all') params.append('status', status);
            
            window.location.href = 'admin_dashboard.php?' + params.toString();
        }
        
        // Allow Enter key to trigger search
        document.getElementById('searchBox').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
    </script>
</body>
</html>
