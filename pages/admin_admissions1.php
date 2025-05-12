<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$dataFile = 'admissions.json';
$entries = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];

// Calculate statistics
$totalApplications = count($entries);
$approvedCount = count(array_filter($entries, function($entry) { 
    return isset($entry['status']) && $entry['status'] === 'approved'; 
}));
$rejectedCount = count(array_filter($entries, function($entry) { 
    return isset($entry['status']) && $entry['status'] === 'rejected'; 
}));
$pendingCount = $totalApplications - $approvedCount - $rejectedCount;

// Filter entries for different tabs
$pendingApplications = array_filter($entries, function($entry) {
    return !isset($entry['status']) || $entry['status'] === 'pending';
});
$approvedApplications = array_filter($entries, function($entry) { 
    return isset($entry['status']) && $entry['status'] === 'approved'; 
});
$rejectedApplications = array_filter($entries, function($entry) { 
    return isset($entry['status']) && $entry['status'] === 'rejected'; 
});

// Handle all POST actions (logout, approve, reject, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['logout'])) {
        header('Location: index.php');
        exit;
    }
    
    if (isset($_POST['action']) && isset($_POST['index'])) {
        $action = $_POST['action'];
        $index = $_POST['index'];
        
        if (isset($entries[$index])) {
            if ($action === 'delete') {
                // Remove the entry from the array
                array_splice($entries, $index, 1);
            } else {
                // Update status for approve/reject
                $entries[$index]['status'] = $action;
                $entries[$index]['processedAt'] = date('Y-m-d H:i:s');
            }
            
            // Save the updated entries
            file_put_contents($dataFile, json_encode($entries, JSON_PRETTY_PRINT));
        }
        
        header('Location: admin_admissions1.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Admission Submissions</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .approved { background-color: #e6ffe6; }
        .rejected { background-color: #ffe6e6; }
        .pending { background-color: #ffffe6; }
        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .approved-badge { background-color: #28a745; color: white; }
        .rejected-badge { background-color: #dc3545; color: white; }
        .pending-badge { background-color: #ffc107; color: black; }
        .nav-tabs .nav-link.active { font-weight: bold; }
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        .stat-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card.approved-card { border-left: 5px solid #28a745; }
        .stat-card.rejected-card { border-left: 5px solid #dc3545; }
        .stat-card.pending-card { border-left: 5px solid #ffc107; }
        .stat-card.total-card { border-left: 5px solid #007bff; }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
        }
        .document-btn {
            white-space: nowrap;
            margin-bottom: 5px;
        }
        .application-table th {
            background-color: #343a40;
            color: white;
        }
        @media (max-width: 768px) {
            .logout-btn {
                position: static;
                margin-bottom: 20px;
                display: block;
                width: 100%;
            }
            .actions-column .btn {
                margin-bottom: 5px;
                width: 100%;
            }
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <form method="post" action="logout.php" class="logout-form">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <button type="submit" class="btn btn-danger logout-btn">
                <i class="bi bi-box-arrow-right"></i> Logout
            </button>
        </form>
        
        <h2 class="mb-4">Admission Dashboard</h2>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card stat-card total-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Total Applications</h5>
                        <p class="stat-value"><?= $totalApplications ?></p>
                        <p class="text-muted mb-0">All submissions</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card stat-card approved-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Approved</h5>
                        <p class="stat-value"><?= $approvedCount ?></p>
                        <p class="text-muted mb-0"><?= $totalApplications ? round(($approvedCount/$totalApplications)*100, 1) : 0 ?>% of total</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card stat-card rejected-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Rejected</h5>
                        <p class="stat-value"><?= $rejectedCount ?></p>
                        <p class="text-muted mb-0"><?= $totalApplications ? round(($rejectedCount/$totalApplications)*100, 1) : 0 ?>% of total</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card stat-card pending-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Pending</h5>
                        <p class="stat-value"><?= $pendingCount ?></p>
                        <p class="text-muted mb-0"><?= $totalApplications ? round(($pendingCount/$totalApplications)*100, 1) : 0 ?>% of total</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs mb-4" id="statusTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="true">
                    Pending (<?= $pendingCount ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved" type="button" role="tab" aria-controls="approved" aria-selected="false">
                    Approved (<?= $approvedCount ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected" type="button" role="tab" aria-controls="rejected" aria-selected="false">
                    Rejected (<?= $rejectedCount ?>)
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="statusTabsContent">
            <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                <?php displayApplications($pendingApplications, true); ?>
            </div>
            <div class="tab-pane fade" id="approved" role="tabpanel" aria-labelledby="approved-tab">
                <?php displayApplications($approvedApplications, false); ?>
            </div>
            <div class="tab-pane fade" id="rejected" role="tabpanel" aria-labelledby="rejected-tab">
                <?php displayApplications($rejectedApplications, false); ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Enhanced document opening function
    function openDocument(url, title) {
        // Try to open in a new tab first
        const win = window.open(url, '_blank');
        if (!win || win.closed || typeof win.closed === 'undefined') {
            // Fallback to download if popup is blocked
            const a = document.createElement('a');
            a.href = url;
            a.download = title;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    }
    // Initialize Bootstrap tabs with enhanced reliability
    document.addEventListener('DOMContentLoaded', function() {
        // Activate the first tab
        const firstTab = new bootstrap.Tab(document.querySelector('#pending-tab'));
        firstTab.show();
        
        // Ensure tab switching works even if there are JS errors
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                new bootstrap.Tab(this).show();
            });
        });
    });
    </script>

    <!-- In admin_admissions1.php, before the closing </body> tag -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const logoutForm = document.querySelector('.logout-form');
    if (logoutForm) {
        logoutForm.addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to logout?')) {
                e.preventDefault();
            }
        });
    }
});
</script>
</body>
</body>
</html>

<?php
function cleanDocumentPath($path) {
    // Remove any directory traversal attempts
    $clean = str_replace(['../', '..\\'], '', $path);
    
    // If path already includes 'uploads/', remove it to avoid duplication
    $clean = preg_replace('~^uploads/~', '', $clean);
    
    // Return clean path relative to uploads folder
    return 'uploads/' . $clean;
}
function displayApplications($applications, $showActions = true) {
    if (empty($applications)) {
        echo '<div class="alert alert-info">No applications found in this category.</div>';
        return;
    }
    ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>Course</th>
                    <th>Contact</th>
                    <th>District</th>
                    <th>Documents</th>
                    <th>Status</th>
                    <th>Submitted At</th>
                    <th>Processed At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $index => $entry): 
                    $status = $entry['status'] ?? 'pending';
                    $statusClass = $status === 'approved' ? 'approved' : ($status === 'rejected' ? 'rejected' : 'pending');
                    $statusBadgeClass = $status === 'approved' ? 'approved-badge' : ($status === 'rejected' ? 'rejected-badge' : 'pending-badge');
                ?>
                <tr class="<?= $statusClass ?>">
                    <td><?= htmlspecialchars($entry['name']) ?></td>
                    <td><?= htmlspecialchars($entry['course'] ?? 'Not specified') ?></td>
                    <td>
                        <div><?= htmlspecialchars($entry['email']) ?></div>
                        <div><?= htmlspecialchars($entry['phone']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($entry['district']) ?></td>
                    <td>
                        <?php if (!empty($entry['academicDoc'])): ?>
                            <button onclick="openDocument('<?= cleanDocumentPath($entry['academicDoc']) ?>', 'Academic Document')" 
                                    class="btn btn-sm btn-outline-primary document-btn">
                                Academic Doc
                            </button>
                        <?php endif; ?>
                        
                        <?php if (!empty($entry['paymentProof'])): ?>
                            <button onclick="openDocument('<?= cleanDocumentPath($entry['paymentProof']) ?>', 'Payment Proof')" 
                                    class="btn btn-sm btn-outline-success document-btn">
                                Payment Proof
                            </button>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge <?= $statusBadgeClass ?>">
                            <?= ucfirst($status) ?>
                        </span>
                    </td>
                    <td><?= $entry['submittedAt'] ?></td>
                    <td><?= $entry['processedAt'] ?? 'Not processed' ?></td>
                    <td class="actions-column">
                        <form method="post" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="index" value="<?= $index ?>">
                            <?php if ($showActions): ?>
                                <?php if ($status !== 'approved'): ?>
                                    <button type="submit" name="action" value="approved" class="btn btn-sm btn-success">Approve</button>
                                <?php endif; ?>
                                <?php if ($status !== 'rejected'): ?>
                                    <button type="submit" name="action" value="rejected" class="btn btn-sm btn-danger">Reject</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger" 
                                    onclick="return confirm('Are you sure you want to delete this application?')">
                                    Delete
                                </button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}