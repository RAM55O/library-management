<?php
/**
 * Members Management Page
 * 
 * This page handles all member-related operations:
 * - View all members in a table
 * - Add new members
 * - Edit existing members
 * - Delete members
 * - Search members by name/email/phone
 * - Activate/Deactivate member accounts
 */

require_once 'config.php';

// Handle form submissions
$message = '';
$message_type = '';

// ADD NEW MEMBER
if (isset($_POST['add_member'])) {
    $full_name = clean_input($_POST['full_name']);
    $email = clean_input($_POST['email']);
    $phone = clean_input($_POST['phone']);
    $address = clean_input($_POST['address']);
    
    // Validate inputs
    if (empty($full_name) || empty($email)) {
        $message = "Full name and email are required!";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format!";
        $message_type = "error";
    } else {
        // Check if email already exists
        $check_sql = "SELECT * FROM members WHERE email = '$email'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $message = "Member with this email already exists!";
            $message_type = "error";
        } else {
            // Insert new member
            $sql = "INSERT INTO members (full_name, email, phone, address, membership_date, status) 
                    VALUES ('$full_name', '$email', '$phone', '$address', CURDATE(), 'active')";
            
            if ($conn->query($sql) === TRUE) {
                $message = "Member added successfully!";
                $message_type = "success";
            } else {
                $message = "Error: " . $conn->error;
                $message_type = "error";
            }
        }
    }
}

// UPDATE MEMBER
if (isset($_POST['update_member'])) {
    $member_id = (int)$_POST['member_id'];
    $full_name = clean_input($_POST['full_name']);
    $email = clean_input($_POST['email']);
    $phone = clean_input($_POST['phone']);
    $address = clean_input($_POST['address']);
    $status = clean_input($_POST['status']);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format!";
        $message_type = "error";
    } else {
        $sql = "UPDATE members SET 
                full_name = '$full_name', 
                email = '$email', 
                phone = '$phone', 
                address = '$address',
                status = '$status'
                WHERE member_id = $member_id";
        
        if ($conn->query($sql) === TRUE) {
            $message = "Member updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error: " . $conn->error;
            $message_type = "error";
        }
    }
}

// DELETE MEMBER
if (isset($_GET['delete'])) {
    $member_id = (int)$_GET['delete'];
    
    // Check if member has active loans
    $check_sql = "SELECT COUNT(*) as count FROM loans WHERE member_id = $member_id AND status = 'issued'";
    $check_result = $conn->query($check_sql);
    $check = $check_result->fetch_assoc();
    
    if ($check['count'] > 0) {
        $message = "Cannot delete member with active loans!";
        $message_type = "error";
    } else {
        $sql = "DELETE FROM members WHERE member_id = $member_id";
        
        if ($conn->query($sql) === TRUE) {
            $message = "Member deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error: " . $conn->error;
            $message_type = "error";
        }
    }
}

// TOGGLE STATUS (Activate/Deactivate)
if (isset($_GET['toggle_status'])) {
    $member_id = (int)$_GET['toggle_status'];
    
    $sql = "UPDATE members SET status = IF(status='active', 'inactive', 'active') WHERE member_id = $member_id";
    
    if ($conn->query($sql) === TRUE) {
        $message = "Member status updated successfully!";
        $message_type = "success";
    } else {
        $message = "Error: " . $conn->error;
        $message_type = "error";
    }
}

// SEARCH FUNCTIONALITY
$search = '';
$where_clause = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = clean_input($_GET['search']);
    $where_clause = "WHERE full_name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%'";
}

// Fetch all members
$sql = "SELECT *, 
        (SELECT COUNT(*) FROM loans WHERE member_id = members.member_id AND status = 'issued') as active_loans
        FROM members $where_clause ORDER BY member_id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members Management - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-book"></i> Library Management
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="books.php">
                            <i class="fas fa-book"></i> Books
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="members.php">
                            <i class="fas fa-users"></i> Members
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="issue.php">
                            <i class="fas fa-hand-holding"></i> Issue Book
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="return.php">
                            <i class="fas fa-undo"></i> Return Book
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-users"></i> Members Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                <i class="fas fa-user-plus"></i> Add New Member
            </button>
        </div>

        <!-- Display Messages -->
        <?php if (!empty($message)): ?>
            <?php echo show_message($message, $message_type); ?>
        <?php endif; ?>

        <!-- Search Bar -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="members.php" class="row g-3">
                    <div class="col-md-10">
                        <div class="search-box">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by name, email, or phone..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </form>
                <?php if (!empty($search)): ?>
                    <div class="mt-2">
                        <a href="members.php" class="btn btn-sm btn-secondary">
                            <i class="fas fa-times"></i> Clear Search
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Members Table -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> All Members</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Membership Date</th>
                                <th>Active Loans</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $status_badge = $row['status'] == 'active' 
                                        ? '<span class="badge bg-success">Active</span>' 
                                        : '<span class="badge bg-secondary">Inactive</span>';
                                    
                                    $toggle_text = $row['status'] == 'active' ? 'Deactivate' : 'Activate';
                                    $toggle_icon = $row['status'] == 'active' ? 'fa-ban' : 'fa-check-circle';
                                    
                                    echo "<tr>
                                            <td>{$row['member_id']}</td>
                                            <td><strong>" . htmlspecialchars($row['full_name']) . "</strong></td>
                                            <td>" . htmlspecialchars($row['email']) . "</td>
                                            <td>" . htmlspecialchars($row['phone']) . "</td>
                                            <td>" . date('M d, Y', strtotime($row['membership_date'])) . "</td>
                                            <td><span class='badge bg-warning'>{$row['active_loans']}</span></td>
                                            <td>$status_badge</td>
                                            <td>
                                                <button class='btn btn-sm btn-warning edit-btn' 
                                                        data-id='{$row['member_id']}'
                                                        data-name='" . htmlspecialchars($row['full_name']) . "'
                                                        data-email='" . htmlspecialchars($row['email']) . "'
                                                        data-phone='" . htmlspecialchars($row['phone']) . "'
                                                        data-address='" . htmlspecialchars($row['address']) . "'
                                                        data-status='{$row['status']}'
                                                        data-bs-toggle='modal' 
                                                        data-bs-target='#editMemberModal'>
                                                    <i class='fas fa-edit'></i>
                                                </button>
                                                <a href='members.php?toggle_status={$row['member_id']}' 
                                                   class='btn btn-sm btn-info' 
                                                   title='$toggle_text'>
                                                    <i class='fas $toggle_icon'></i>
                                                </a>
                                                <a href='members.php?delete={$row['member_id']}' 
                                                   class='btn btn-sm btn-danger' 
                                                   onclick='return confirm(\"Are you sure you want to delete this member?\")'>
                                                    <i class='fas fa-trash'></i>
                                                </a>
                                            </td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center'>No members found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Member Modal -->
    <div class="modal fade" id="addMemberModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="members.php">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-user-plus"></i> Add New Member</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_member" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Member Modal -->
    <div class="modal fade" id="editMemberModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="members.php">
                    <input type="hidden" name="member_id" id="edit_member_id">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Member</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" id="edit_phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" id="edit_address" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_member" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <p class="mb-0">&copy; 2025 Library Management System. All Rights Reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Populate edit modal with member data
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_member_id').value = this.dataset.id;
                document.getElementById('edit_full_name').value = this.dataset.name;
                document.getElementById('edit_email').value = this.dataset.email;
                document.getElementById('edit_phone').value = this.dataset.phone;
                document.getElementById('edit_address').value = this.dataset.address;
                document.getElementById('edit_status').value = this.dataset.status;
            });
        });
    </script>
</body>
</html>
