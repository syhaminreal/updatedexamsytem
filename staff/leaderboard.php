<?php
session_start();
require_once 'db_connection.php';

// // Check if admin is logged in
// if (!isset($_SESSION['admin_id'])) {
//     header('Location: login.php');
//     exit();
// }

// Check if user has permission (superadmin or admin can view)
// if (!isset($_SESSION['admin_role']) || ($_SESSION['admin_role'] != 'superadmin' && $_SESSION['admin_role'] != 'admin')) {
//     die("Access denied. You need admin privileges to view this page.");
// }

try {
    // Fetch all admins with statistics
    $query = "
        SELECT 
            admin_id,
            username,
            email,
            full_name,
            role,
            is_active,
            last_login,
            created_at,
            CASE 
                WHEN last_login IS NULL THEN 'Never'
                ELSE TIMESTAMPDIFF(DAY, last_login, NOW())
            END as days_since_last_login
        FROM admin_users 
        ORDER BY 
            CASE role 
                WHEN 'superadmin' THEN 1
                WHEN 'admin' THEN 2
                WHEN 'moderator' THEN 3
                ELSE 4
            END,
            created_at DESC
    ";
    
    $stmt = $pdo->query($query);
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate statistics
    $total_admins = count($admins);
    $active_admins = 0;
    $admins_today = 0;
    $superadmins = 0;
    $moderators = 0;
    $today = date('Y-m-d');
    
    foreach ($admins as $admin) {
        if ($admin['is_active']) {
            $active_admins++;
        }
        if ($admin['role'] == 'superadmin') {
            $superadmins++;
        }
        if ($admin['role'] == 'moderator') {
            $moderators++;
        }
        if ($admin['last_login'] && date('Y-m-d', strtotime($admin['last_login'])) == $today) {
            $admins_today++;
        }
    }
    
} catch (PDOException $e) {
    die("Error fetching admin data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Leaderboard - Exam System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            margin-bottom: 30px;
            border-bottom: 2px solid #334155;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo i {
            font-size: 2.5rem;
            color: #3b82f6;
        }

        .logo h1 {
            font-size: 2rem;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-weight: 700;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(30, 41, 59, 0.7);
            padding: 10px 20px;
            border-radius: 10px;
            border: 1px solid #475569;
        }

        .user-info i {
            font-size: 1.2rem;
            color: #60a5fa;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(30, 41, 59, 0.8);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid #475569;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            border-color: #60a5fa;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }

        .stat-value {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            background: linear-gradient(90deg, #60a5fa, #93c5fd);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .stat-label {
            color: #94a3b8;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .leaderboard-container {
            background: rgba(30, 41, 59, 0.8);
            border-radius: 15px;
            padding: 30px;
            border: 1px solid #475569;
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .section-header h2 {
            font-size: 1.8rem;
            background: linear-gradient(90deg, #60a5fa, #93c5fd);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-filter {
            display: flex;
            gap: 15px;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            background: #1e293b;
            border: 1px solid #475569;
            border-radius: 8px;
            padding: 10px 15px 10px 40px;
            color: #e2e8f0;
            width: 250px;
            transition: all 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .filter-select {
            background: #1e293b;
            border: 1px solid #475569;
            border-radius: 8px;
            padding: 10px 15px;
            color: #e2e8f0;
            cursor: pointer;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            border-radius: 10px;
        }

        .admin-table thead {
            background: rgba(59, 130, 246, 0.2);
        }

        .admin-table th {
            padding: 18px 15px;
            text-align: left;
            color: #93c5fd;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
            border-bottom: 2px solid #475569;
        }

        .admin-table tbody tr {
            border-bottom: 1px solid #334155;
            transition: background 0.3s;
        }

        .admin-table tbody tr:hover {
            background: rgba(59, 130, 246, 0.1);
        }

        .admin-table td {
            padding: 18px 15px;
            color: #cbd5e1;
        }

        .admin-avatar {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            font-size: 1.1rem;
        }

        .admin-info h4 {
            color: #f1f5f9;
            font-size: 1rem;
            margin-bottom: 3px;
        }

        .admin-info p {
            color: #94a3b8;
            font-size: 0.85rem;
        }

        .role-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .role-superadmin {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .role-admin {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }

        .role-moderator {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-active {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .status-inactive {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .last-login {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #94a3b8;
        }

        .last-login i {
            color: #60a5fa;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 8px 12px;
            border-radius: 6px;
            border: none;
            background: rgba(59, 130, 246, 0.1);
            color: #60a5fa;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .btn-action:hover {
            background: rgba(59, 130, 246, 0.2);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .btn-danger:hover {
            background: rgba(239, 68, 68, 0.2);
        }

        .footer {
            text-align: center;
            padding: 20px;
            color: #94a3b8;
            font-size: 0.9rem;
            border-top: 1px solid #334155;
            margin-top: 30px;
        }

        .no-data {
            text-align: center;
            padding: 50px;
            color: #94a3b8;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        @media (max-width: 1024px) {
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .search-filter {
                flex-direction: column;
                width: 100%;
            }
            
            .search-box input {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .admin-table {
                display: block;
                overflow-x: auto;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .search-filter {
                width: 100%;
            }
        }

        /* Loading animation */
        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 0.7; }
            50% { opacity: 1; }
            100% { opacity: 0.7; }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <i class="fas fa-crown"></i>
                <h1>Admin Leaderboard</h1>
            </div>
            <div class="user-info">
                <i class="fas fa-user-shield"></i>
                <div>
                    <div style="font-weight: 600;"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></div>
                    <div style="font-size: 0.85rem; color: #94a3b8;"><?php echo htmlspecialchars($_SESSION['admin_role'] ?? 'Admin'); ?></div>
                </div>
            </div>
        </header>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <i class="fas fa-users stat-icon" style="color: #3b82f6;"></i>
                <div class="stat-value"><?php echo $total_admins; ?></div>
                <div class="stat-label">Total Admins</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-user-check stat-icon" style="color: #10b981;"></i>
                <div class="stat-value"><?php echo $active_admins; ?></div>
                <div class="stat-label">Active Admins</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-user-tie stat-icon" style="color: #f59e0b;"></i>
                <div class="stat-value"><?php echo $superadmins; ?></div>
                <div class="stat-label">Super Admins</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-sign-in-alt stat-icon" style="color: #8b5cf6;"></i>
                <div class="stat-value"><?php echo $admins_today; ?></div>
                <div class="stat-label">Logged In Today</div>
            </div>
        </div>

        <!-- Leaderboard Table -->
        <div class="leaderboard-container">
            <div class="section-header">
                <h2><i class="fas fa-list-ol"></i> Admin Management</h2>
                <div class="search-filter">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search admins...">
                    </div>
                    <select class="filter-select" id="roleFilter">
                        <option value="all">All Roles</option>
                        <option value="superadmin">Super Admin</option>
                        <option value="admin">Admin</option>
                        <option value="moderator">Moderator</option>
                    </select>
                    <select class="filter-select" id="statusFilter">
                        <option value="all">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="admin-table" id="adminTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Admin</th>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Member Since</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($admins)): ?>
                            <tr>
                                <td colspan="8">
                                    <div class="no-data">
                                        <i class="fas fa-user-slash"></i>
                                        <h3>No Admins Found</h3>
                                        <p>There are no administrators in the system yet.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($admins as $index => $admin): ?>
                                <?php
                                $initials = strtoupper(substr($admin['full_name'], 0, 2));
                                $role_class = 'role-' . $admin['role'];
                                $status_class = $admin['is_active'] ? 'status-active' : 'status-inactive';
                                $status_text = $admin['is_active'] ? 'Active' : 'Inactive';
                                
                                // Calculate days since last login
                                $days_ago = $admin['days_since_last_login'];
                                $last_login_text = $admin['last_login'] 
                                    ? date('M d, Y H:i', strtotime($admin['last_login']))
                                    : 'Never';
                                    
                                // Add "X days ago" if applicable
                                if ($admin['last_login'] && $days_ago > 0) {
                                    $last_login_text .= " ($days_ago days ago)";
                                }
                                
                                // Member since
                                $member_since = date('M d, Y', strtotime($admin['created_at']));
                                ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <div class="admin-avatar">
                                            <div class="avatar"><?php echo $initials; ?></div>
                                            <div class="admin-info">
                                                <h4><?php echo htmlspecialchars($admin['full_name']); ?></h4>
                                                <p>@<?php echo htmlspecialchars($admin['username']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="role-badge <?php echo $role_class; ?>">
                                            <?php echo htmlspecialchars(ucfirst($admin['role'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <i class="fas fa-circle" style="font-size: 0.6rem;"></i>
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="last-login">
                                            <i class="fas fa-clock"></i>
                                            <?php echo $last_login_text; ?>
                                        </div>
                                    </td>
                                    <td><?php echo $member_since; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action" onclick="viewAdmin(<?php echo $admin['admin_id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                           <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>

    <button class="btn-action" onclick="editAdmin(<?php echo $admin['admin_id']; ?>)">
        <i class="fas fa-edit"></i> Edit
    </button>

    <?php if ($admin['admin_id'] != $_SESSION['admin_id']): ?>
        <button class="btn-action btn-danger" onclick="deleteAdmin(<?php echo $admin['admin_id']; ?>)">
            <i class="fas fa-trash"></i> Delete
        </button>
    <?php endif; ?>

<?php endif; ?>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="leaderboard-container">
            <div class="section-header">
                <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            </div>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <button class="btn-action" onclick="location.href='admin_reg.php'" style="padding: 12px 20px; font-size: 1rem;">
                    <i class="fas fa-user-plus"></i> Add New Admin
                </button>
                <button class="btn-action" onclick="exportToCSV()" style="padding: 12px 20px; font-size: 1rem;">
                    <i class="fas fa-file-export"></i> Export to CSV
                </button>
                <button class="btn-action" onclick="refreshPage()" style="padding: 12px 20px; font-size: 1rem;">
                    <i class="fas fa-sync-alt"></i> Refresh Data
                </button>
                <button class="btn-action" onclick="location.href='index.php'" style="padding: 12px 20px; font-size: 1rem;">
                    <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                </button>
            </div>
        </div>

        <div class="footer">
            <p>Exam System Admin Panel &copy; <?php echo date('Y'); ?> • Last updated: <?php echo date('F j, Y H:i:s'); ?></p>
            <p style="margin-top: 5px; font-size: 0.8rem; color: #64748b;">
                <i class="fas fa-database"></i> <?php echo $total_admins; ?> admins • 
                <i class="fas fa-server"></i> MariaDB 10.4.32
            </p>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#adminTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Role filter functionality
        document.getElementById('roleFilter').addEventListener('change', function(e) {
            filterTable();
        });

        // Status filter functionality
        document.getElementById('statusFilter').addEventListener('change', function(e) {
            filterTable();
        });

        function filterTable() {
            const roleFilter = document.getElementById('roleFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('#adminTable tbody tr');
            
            rows.forEach(row => {
                if (row.querySelector('.no-data')) return;
                
                const role = row.querySelector('.role-badge').textContent.toLowerCase().trim();
                const status = row.querySelector('.status-badge').textContent.toLowerCase().trim();
                
                const roleMatch = roleFilter === 'all' || role === roleFilter;
                const statusMatch = statusFilter === 'all' || status === statusFilter;
                
                row.style.display = (roleMatch && statusMatch) ? '' : 'none';
            });
        }

        // Admin actions
        function viewAdmin(adminId) {
            alert('Viewing admin with ID: ' + adminId);
            // In a real application, you would redirect to view_admin.php?id=adminId
            // window.location.href = 'view_admin.php?id=' + adminId;
        }

        function editAdmin(adminId) {
            if (confirm('Are you sure you want to edit this admin?')) {
                // In a real application, you would redirect to edit_admin.php?id=adminId
                // window.location.href = 'edit_admin.php?id=' + adminId;
                alert('Redirecting to edit page for admin ID: ' + adminId);
            }
        }

        function deleteAdmin(adminId) {
            if (confirm('Are you sure you want to delete this admin? This action cannot be undone.')) {
                // In a real application, you would send an AJAX request or redirect
                // fetch('delete_admin.php', { method: 'POST', body: JSON.stringify({id: adminId}) })
                alert('Deleting admin with ID: ' + adminId + ' (This is a demo action)');
            }
        }

        function exportToCSV() {
            alert('Exporting data to CSV...');
            // In a real application, you would generate and download a CSV file
            // window.location.href = 'export_admins.php?format=csv';
        }

        function refreshPage() {
            document.querySelector('.container').classList.add('pulse');
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+F to focus search
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.getElementById('searchInput').focus();
            }
            // F5 to refresh
            if (e.key === 'F5') {
                e.preventDefault();
                refreshPage();
            }
        });

        // Initialize tooltips
        const buttons = document.querySelectorAll('.btn-action');
        buttons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>