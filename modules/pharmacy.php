<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'pharmacy'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$hospital_id = $_SESSION['hospital_id'];
$action = $_GET['action'] ?? '';
$medicine_id = $_GET['id'] ?? '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'add_medicine') {
        $name = sanitize($_POST['name']);
        $generic_name = sanitize($_POST['generic_name']);
        $brand = sanitize($_POST['brand']);
        $strength = sanitize($_POST['strength']);
        $form = sanitize($_POST['form']);
        $price = floatval($_POST['price']);
        $quantity = intval($_POST['quantity']);
        $minimum_stock = intval($_POST['minimum_stock']);
        $batch_number = sanitize($_POST['batch_number']);
        $expiry_date = $_POST['expiry_date'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO medicines (name, generic_name, brand, strength, form, price, quantity, minimum_stock, batch_number, expiry_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$name, $generic_name, $brand, $strength, $form, $price, $quantity, $minimum_stock, $batch_number, $expiry_date]);
            
            logActivity($user_id, 'create', "Added new medicine: $name");
            $success_message = "Medicine added successfully!";
        } catch (Exception $e) {
            $error_message = "Error adding medicine: " . $e->getMessage();
        }
    }
    
    if ($action == 'update_medicine') {
        $id = intval($_POST['id']);
        $name = sanitize($_POST['name']);
        $generic_name = sanitize($_POST['generic_name']);
        $brand = sanitize($_POST['brand']);
        $strength = sanitize($_POST['strength']);
        $form = sanitize($_POST['form']);
        $price = floatval($_POST['price']);
        $quantity = intval($_POST['quantity']);
        $minimum_stock = intval($_POST['minimum_stock']);
        $batch_number = sanitize($_POST['batch_number']);
        $expiry_date = $_POST['expiry_date'];
        
        try {
            $stmt = $pdo->prepare("UPDATE medicines SET name=?, generic_name=?, brand=?, strength=?, form=?, price=?, quantity=?, minimum_stock=?, batch_number=?, expiry_date=? WHERE id=?");
            $stmt->execute([$name, $generic_name, $brand, $strength, $form, $price, $quantity, $minimum_stock, $batch_number, $expiry_date, $id]);
            
            logActivity($user_id, 'update', "Updated medicine: $name");
            $success_message = "Medicine updated successfully!";
        } catch (Exception $e) {
            $error_message = "Error updating medicine: " . $e->getMessage();
        }
    }
    
    if ($action == 'update_stock') {
        $id = intval($_POST['id']);
        $quantity = intval($_POST['quantity']);
        $operation = $_POST['operation']; // 'add' or 'subtract'
        
        try {
            if ($operation == 'add') {
                $stmt = $pdo->prepare("UPDATE medicines SET quantity = quantity + ? WHERE id = ?");
            } else {
                $stmt = $pdo->prepare("UPDATE medicines SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");
                $stmt->execute([$quantity, $id, $quantity]);
                if ($stmt->rowCount() == 0) {
                    throw new Exception("Insufficient stock for this operation");
                }
                $stmt = $pdo->prepare("UPDATE medicines SET quantity = quantity - ? WHERE id = ?");
            }
            $stmt->execute([$quantity, $id]);
            
            logActivity($user_id, 'update', "Updated stock for medicine ID: $id");
            $success_message = "Stock updated successfully!";
        } catch (Exception $e) {
            $error_message = "Error updating stock: " . $e->getMessage();
        }
    }
}

// Get filters
$filter = $_GET['filter'] ?? '';
$search = $_GET['search'] ?? '';

// Build query based on filters
$where_conditions = ['1=1'];
$params = [];

if (!empty($search)) {
    $where_conditions[] = '(name LIKE ? OR generic_name LIKE ? OR brand LIKE ? OR batch_number LIKE ?)';
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

if ($filter == 'low_stock') {
    $where_conditions[] = 'quantity <= minimum_stock';
} elseif ($filter == 'expired') {
    $where_conditions[] = 'expiry_date <= CURDATE()';
} elseif ($filter == 'expiring_soon') {
    $where_conditions[] = 'expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date > CURDATE()';
}

$where_clause = implode(' AND ', $where_conditions);

// Get medicines
$stmt = $pdo->prepare("SELECT * FROM medicines WHERE $where_clause ORDER BY name ASC");
$stmt->execute($params);
$medicines = $stmt->fetchAll();

// Get specific medicine for editing
$edit_medicine = null;
if ($action == 'edit' && $medicine_id) {
    $stmt = $pdo->prepare("SELECT * FROM medicines WHERE id = ?");
    $stmt->execute([$medicine_id]);
    $edit_medicine = $stmt->fetch();
}

// Get statistics
$total_medicines = getTotalRecords('medicines', ['status' => 'active']);
$low_stock_count = count($pdo->query("SELECT id FROM medicines WHERE quantity <= minimum_stock AND status = 'active'")->fetchAll());
$expired_count = count($pdo->query("SELECT id FROM medicines WHERE expiry_date <= CURDATE() AND status = 'active'")->fetchAll());
$expiring_soon_count = count($pdo->query("SELECT id FROM medicines WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date > CURDATE() AND status = 'active'")->fetchAll());
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Management - <?php echo getHospitalSetting('hospital_name', 'Hospital CRM'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-pills me-2"></i>Pharmacy Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addMedicineModal">
                                <i class="fas fa-plus"></i> Add Medicine
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="exportInventory()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Medicines</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_medicines; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-pills fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Low Stock</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $low_stock_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-danger shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Expired</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $expired_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-times fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Expiring Soon</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $expiring_soon_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters and Search -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Search & Filters</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search Medicine</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, Brand, or Batch">
                            </div>
                            <div class="col-md-3">
                                <label for="filter" class="form-label">Filter</label>
                                <select class="form-control" id="filter" name="filter">
                                    <option value="">All Medicines</option>
                                    <option value="low_stock" <?php echo $filter == 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                                    <option value="expired" <?php echo $filter == 'expired' ? 'selected' : ''; ?>>Expired</option>
                                    <option value="expiring_soon" <?php echo $filter == 'expiring_soon' ? 'selected' : ''; ?>>Expiring Soon</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <a href="pharmacy.php" class="btn btn-secondary d-block">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Medicine Inventory -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Medicine Inventory</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Medicine Name</th>
                                        <th>Brand</th>
                                        <th>Strength</th>
                                        <th>Form</th>
                                        <th>Price (₹)</th>
                                        <th>Stock</th>
                                        <th>Batch</th>
                                        <th>Expiry</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($medicines as $medicine): ?>
                                        <tr class="<?php echo getRowClass($medicine); ?>">
                                            <td>
                                                <strong><?php echo htmlspecialchars($medicine['name']); ?></strong>
                                                <?php if ($medicine['generic_name']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($medicine['generic_name']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($medicine['brand']); ?></td>
                                            <td><?php echo htmlspecialchars($medicine['strength']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo ucfirst($medicine['form']); ?></span>
                                            </td>
                                            <td class="text-end">₹<?php echo number_format($medicine['price'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo getStockStatusColor($medicine['quantity'], $medicine['minimum_stock']); ?>">
                                                    <?php echo $medicine['quantity']; ?>
                                                </span>
                                                <small class="text-muted d-block">Min: <?php echo $medicine['minimum_stock']; ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($medicine['batch_number']); ?></td>
                                            <td>
                                                <?php if ($medicine['expiry_date']): ?>
                                                    <span class="<?php echo getExpiryClass($medicine['expiry_date']); ?>">
                                                        <?php echo date('M j, Y', strtotime($medicine['expiry_date'])); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $medicine['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($medicine['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="editMedicine(<?php echo $medicine['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-success" onclick="updateStock(<?php echo $medicine['id']; ?>, '<?php echo htmlspecialchars($medicine['name']); ?>')">
                                                        <i class="fas fa-boxes"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Medicine Modal -->
    <div class="modal fade" id="addMedicineModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Medicine</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_medicine">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Medicine Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="generic_name" class="form-label">Generic Name</label>
                                    <input type="text" class="form-control" id="generic_name" name="generic_name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="brand" class="form-label">Brand</label>
                                    <input type="text" class="form-control" id="brand" name="brand">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="strength" class="form-label">Strength</label>
                                    <input type="text" class="form-control" id="strength" name="strength" placeholder="e.g., 500mg">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="form" class="form-label">Form *</label>
                                    <select class="form-control" id="form" name="form" required>
                                        <option value="tablet">Tablet</option>
                                        <option value="capsule">Capsule</option>
                                        <option value="syrup">Syrup</option>
                                        <option value="injection">Injection</option>
                                        <option value="cream">Cream</option>
                                        <option value="drops">Drops</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="price" class="form-label">Price (₹) *</label>
                                    <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Initial Quantity *</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="minimum_stock" class="form-label">Minimum Stock Level *</label>
                                    <input type="number" class="form-control" id="minimum_stock" name="minimum_stock" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="batch_number" class="form-label">Batch Number</label>
                                    <input type="text" class="form-control" id="batch_number" name="batch_number">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="expiry_date" class="form-label">Expiry Date</label>
                                    <input type="date" class="form-control" id="expiry_date" name="expiry_date">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Medicine</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Stock Update Modal -->
    <div class="modal fade" id="stockUpdateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="stockUpdateForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_stock">
                        <input type="hidden" name="id" id="stock_medicine_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Medicine: <span id="stock_medicine_name"></span></label>
                        </div>
                        
                        <div class="mb-3">
                            <label for="operation" class="form-label">Operation *</label>
                            <select class="form-control" id="operation" name="operation" required>
                                <option value="add">Add Stock (Purchase/Restock)</option>
                                <option value="subtract">Subtract Stock (Sale/Usage)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="stock_quantity" class="form-label">Quantity *</label>
                            <input type="number" class="form-control" id="stock_quantity" name="quantity" min="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editMedicine(id) {
            window.location.href = 'pharmacy.php?action=edit&id=' + id;
        }
        
        function updateStock(id, name) {
            document.getElementById('stock_medicine_id').value = id;
            document.getElementById('stock_medicine_name').textContent = name;
            new bootstrap.Modal(document.getElementById('stockUpdateModal')).show();
        }
        
        function exportInventory() {
            // Implement export functionality
            alert('Export functionality will be implemented');
        }

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>

<?php
// Helper functions
function getRowClass($medicine) {
    if ($medicine['expiry_date'] && $medicine['expiry_date'] <= date('Y-m-d')) {
        return 'table-danger';
    }
    if ($medicine['quantity'] <= $medicine['minimum_stock']) {
        return 'table-warning';
    }
    return '';
}

function getStockStatusColor($quantity, $minimum_stock) {
    if ($quantity <= 0) return 'danger';
    if ($quantity <= $minimum_stock) return 'warning';
    return 'success';
}

function getExpiryClass($expiry_date) {
    $days_to_expiry = (strtotime($expiry_date) - time()) / (60 * 60 * 24);
    if ($days_to_expiry <= 0) return 'text-danger';
    if ($days_to_expiry <= 30) return 'text-warning';
    return 'text-success';
}
?>
