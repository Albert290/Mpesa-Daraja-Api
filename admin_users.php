<?php
session_start();
include 'dbconnection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get all admin users
$admins_query = "SELECT id, username, email, role, created_at, last_login FROM admin_users ORDER BY created_at DESC";
$admins = mysqli_query($db, $admins_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins - Urban Swaras</title>
    <style>
        :root {
            --primary: #1F3C5F;
            --secondary: #6BA2D9;
            --accent: #F57C51;
            --text: #333333;
            --light-bg: #F5F5F5;
            --white: #FFFFFF;
            --success: #28a745;
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
        
        .nav-links {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .nav-links a {
            color: var(--white);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .page-title {
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .add-admin-section {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .section-title {
            color: var(--primary);
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--accent);
            padding-bottom: 0.5rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary);
        }
        
        .form-group input, .form-group select {
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--secondary);
        }
        
        .add-btn {
            background: var(--accent);
            color: var(--white);
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .add-btn:hover {
            background: #e56b42;
        }
        
        .admins-list {
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
        
        .role-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .role-admin {
            background: #d4edda;
            color: #155724;
        }
        
        .role-manager {
            background: #cce5ff;
            color: #004085;
        }
        
        .delete-btn {
            background: var(--danger);
            color: var(--white);
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .delete-btn:hover {
            background: #c82333;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .table {
                min-width: 600px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">Urban Swaras Admin</div>
        <div class="nav-links">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <h1 class="page-title">Manage Admin Users</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php 
                if ($_GET['success'] == 'added') echo 'Admin user added successfully!';
                if ($_GET['success'] == 'deleted') echo 'Admin user deleted successfully!';
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php 
                if ($_GET['error'] == 'exists') echo 'Username or email already exists!';
                if ($_GET['error'] == 'failed') echo 'Failed to add admin user!';
                if ($_GET['error'] == 'delete_failed') echo 'Failed to delete admin user!';
                ?>
            </div>
        <?php endif; ?>
        
        <div class="add-admin-section">
            <h2 class="section-title">Add New Admin</h2>
            <form method="POST" action="add_admin.php">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="manager">Manager</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="add-btn">Add Admin User</button>
            </form>
        </div>
        
        <div class="admins-list">
            <table class="table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($admin = mysqli_fetch_assoc($admins)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($admin['username']); ?></td>
                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                        <td>
                            <span class="role-badge role-<?php echo $admin['role']; ?>">
                                <?php echo ucfirst($admin['role']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($admin['created_at'])); ?></td>
                        <td>
                            <?php 
                            echo $admin['last_login'] 
                                ? date('M j, Y H:i', strtotime($admin['last_login'])) 
                                : 'Never';
                            ?>
                        </td>
                        <td>
                            <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                <button class="delete-btn" onclick="deleteAdmin(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['username']); ?>')">Delete</button>
                            <?php else: ?>
                                <span style="color: #666; font-size: 12px;">Current User</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        function deleteAdmin(adminId, username) {
            if (confirm(`Are you sure you want to delete admin user "${username}"?`)) {
                window.location.href = `delete_admin.php?id=${adminId}`;
            }
        }
    </script>
</body>
</html>
