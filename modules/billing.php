<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'receptionist', 'doctor'])) {
    header('Location: ../index.php');
    exit();
}

$action = $_GET['action'] ?? 'list';
$bill_id = $_GET['id'] ?? null;
$patient_id = $_GET['patient_id'] ?? null;
$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if ($action == 'add') {
        if (addBill($_POST)) {
            $message = 'Bill created successfully!';
            logActivity($_SESSION['user_id'], 'add', 'Created new bill for patient ID: ' . $_POST['patient_id']);
        } else {
            $error = 'Failed to create bill. Please try again.';
        }
    } elseif ($action == 'payment' && $bill_id) {
        if (recordPayment($bill_id, $_POST)) {
            $message = 'Payment recorded successfully!';
            logActivity($_SESSION['user_id'], 'payment', 'Recorded payment for bill ID: ' . $bill_id);
        } else {
            $error = 'Failed to record payment. Please try again.';
        }
    }
}

// Get data based on action
switch ($action) {
    case 'list':
        $bills = getBills();
        break;
    case 'view':
        if ($bill_id) {
            $bill = getBillById($bill_id);
            $bill_items = getBillItems($bill_id);
            $payments = getBillPayments($bill_id);
            if (!$bill) {
                $error = 'Bill not found.';
                $action = 'list';
                $bills = getBills();
            }
        }
        break;
    case 'add':
        $patients = getPatients();
        $services = getServices();
        $medicines = getMedicines();
        $tests = getLabTests();
        break;
}

$currencies = getCurrencies();
$payment_methods = getPaymentMethods();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing System - <?php echo getSetting('site_title', 'Hospital CRM'); ?></title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/font-awesome.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .page-header {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 600;
        }
        
        .billing-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .stat-card i {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .stat-card.total-revenue i { color: #28a745; }
        .stat-card.pending-bills i { color: #ffc107; }
        .stat-card.paid-bills i { color: #17a2b8; }
        .stat-card.overdue-bills i { color: #dc3545; }
        
        .stat-card h3 {
            margin: 10px 0 5px;
            font-size: 28px;
            font-weight: 600;
        }
        
        .stat-card p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .card-header {
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px 30px;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .bill-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-overdue {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-partial {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .bill-total {
            font-size: 24px;
            font-weight: 600;
            color: #17a2b8;
        }
        
        .invoice-header {
            border-bottom: 2px solid #17a2b8;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .invoice-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .bill-items-table {
            margin: 20px 0;
        }
        
        .bill-items-table th {
            background: #17a2b8;
            color: white;
            border: none;
        }
        
        .bill-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #17a2b8;
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .payment-method {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-method:hover {
            border-color: #17a2b8;
            background: #f8f9fa;
        }
        
        .payment-method.selected {
            border-color: #17a2b8;
            background: #e7f3ff;
        }
        
        .payment-method i {
            font-size: 32px;
            color: #17a2b8;
            margin-bottom: 10px;
        }
        
        .add-item-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .item-type-tabs .nav-link {
            border-radius: 25px;
            margin-right: 10px;
        }
        
        .item-type-tabs .nav-link.active {
            background: #17a2b8;
            color: white;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Billing System</li>
                <?php if ($action != 'list'): ?>
                    <li class="breadcrumb-item active"><?php echo ucfirst($action); ?></li>
                <?php endif; ?>
            </ol>
        </nav>
        
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1>Billing System</h1>
                    <p>Manage invoices, payments, and financial transactions</p>
                </div>
                <div>
                    <?php if ($action == 'list'): ?>
                        <a href="?action=add" class="btn btn-light btn-lg">
                            <i class="fa fa-plus"></i> Create New Bill
                        </a>
                    <?php else: ?>
                        <a href="?" class="btn btn-light btn-lg">
                            <i class="fa fa-list"></i> Back to Bills
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Billing Stats -->
        <?php if ($action == 'list'): ?>
            <div class="billing-stats">
                <div class="stat-card total-revenue">
                    <i class="fa fa-money"></i>
                    <h3><?php echo formatCurrency(getTotalRevenue()); ?></h3>
                    <p>Total Revenue</p>
                </div>
                <div class="stat-card pending-bills">
                    <i class="fa fa-clock-o"></i>
                    <h3><?php echo getPendingBillsCount(); ?></h3>
                    <p>Pending Bills</p>
                </div>
                <div class="stat-card paid-bills">
                    <i class="fa fa-check-circle"></i>
                    <h3><?php echo getPaidBillsCount(); ?></h3>
                    <p>Paid Bills</p>
                </div>
                <div class="stat-card overdue-bills">
                    <i class="fa fa-exclamation-triangle"></i>
                    <h3><?php echo getOverdueBillsCount(); ?></h3>
                    <p>Overdue Bills</p>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Alerts -->
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fa fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Main Content -->
        <?php if ($action == 'list'): ?>
            <!-- Bills List -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">All Bills (<?php echo count($bills); ?>)</h5>
                        <div class="search-box">
                            <input type="text" id="billSearch" class="form-control" placeholder="Search bills...">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="billsTable">
                            <thead>
                                <tr>
                                    <th>Bill #</th>
                                    <th>Patient</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Paid</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bills as $bill): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo str_pad($bill['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                        </td>
                                        <td>
                                            <strong><?php echo $bill['patient_name']; ?></strong>
                                            <br>
                                            <small class="text-muted">ID: #<?php echo str_pad($bill['patient_id'], 4, '0', STR_PAD_LEFT); ?></small>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($bill['created_at'])); ?></td>
                                        <td class="bill-total"><?php echo formatCurrency($bill['total_amount'], $bill['currency']); ?></td>
                                        <td><?php echo formatCurrency($bill['paid_amount'], $bill['currency']); ?></td>
                                        <td><?php echo formatCurrency($bill['total_amount'] - $bill['paid_amount'], $bill['currency']); ?></td>
                                        <td>
                                            <span class="bill-status status-<?php echo $bill['status']; ?>">
                                                <?php echo ucfirst($bill['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="?action=view&id=<?php echo $bill['id']; ?>" class="btn btn-sm btn-info" title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="?action=print&id=<?php echo $bill['id']; ?>" class="btn btn-sm btn-secondary" title="Print" target="_blank">
                                                    <i class="fa fa-print"></i>
                                                </a>
                                                <?php if ($bill['status'] != 'paid'): ?>
                                                    <button class="btn btn-sm btn-success" onclick="recordPayment(<?php echo $bill['id']; ?>)" title="Record Payment">
                                                        <i class="fa fa-money"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        <?php elseif ($action == 'add'): ?>
            <!-- Create New Bill -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Create New Bill</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="billForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Patient *</label>
                                    <select name="patient_id" class="form-control" required <?php echo $patient_id ? 'readonly' : ''; ?>>
                                        <option value="">Select Patient</option>
                                        <?php foreach ($patients as $patient): ?>
                                            <option value="<?php echo $patient['id']; ?>" <?php echo ($patient_id == $patient['id']) ? 'selected' : ''; ?>>
                                                <?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?> (#<?php echo str_pad($patient['id'], 4, '0', STR_PAD_LEFT); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Currency</label>
                                    <select name="currency" class="form-control">
                                        <?php foreach ($currencies as $currency): ?>
                                            <option value="<?php echo $currency['code']; ?>" <?php echo $currency['code'] == 'INR' ? 'selected' : ''; ?>>
                                                <?php echo $currency['code'] . ' - ' . $currency['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Bill Date</label>
                                    <input type="date" name="bill_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Add Items Section -->
                        <div class="add-item-section">
                            <h6>Add Items to Bill</h6>
                            <ul class="nav nav-pills item-type-tabs mb-3">
                                <li class="nav-item">
                                    <a class="nav-link active" data-toggle="tab" href="#consultations">Consultations</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#medicines">Medicines</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#tests">Lab Tests</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#equipments">Equipment</a>
                                </li>
                            </ul>
                            
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="consultations">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <select class="form-control" id="consultationService">
                                                <option value="">Select Consultation Type</option>
                                                <?php foreach ($services as $service): ?>
                                                    <option value="<?php echo $service['id']; ?>" data-price="<?php echo $service['price']; ?>">
                                                        <?php echo $service['name']; ?> - <?php echo formatCurrency($service['price']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="number" class="form-control" id="consultationQty" placeholder="Quantity" value="1">
                                        </div>
                                        <div class="col-md-3">
                                            <button type="button" class="btn btn-primary" onclick="addItem('consultation')">Add</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="tab-pane fade" id="medicines">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <select class="form-control" id="medicineItem">
                                                <option value="">Select Medicine</option>
                                                <?php foreach ($medicines as $medicine): ?>
                                                    <option value="<?php echo $medicine['id']; ?>" data-price="<?php echo $medicine['price']; ?>">
                                                        <?php echo $medicine['name']; ?> - <?php echo formatCurrency($medicine['price']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="number" class="form-control" id="medicineQty" placeholder="Quantity" value="1">
                                        </div>
                                        <div class="col-md-3">
                                            <button type="button" class="btn btn-primary" onclick="addItem('medicine')">Add</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="tab-pane fade" id="tests">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <select class="form-control" id="testItem">
                                                <option value="">Select Lab Test</option>
                                                <?php foreach ($tests as $test): ?>
                                                    <option value="<?php echo $test['id']; ?>" data-price="<?php echo $test['price']; ?>">
                                                        <?php echo $test['name']; ?> - <?php echo formatCurrency($test['price']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="number" class="form-control" id="testQty" placeholder="Quantity" value="1">
                                        </div>
                                        <div class="col-md-3">
                                            <button type="button" class="btn btn-primary" onclick="addItem('test')">Add</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="tab-pane fade" id="equipments">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <input type="text" class="form-control" id="equipmentName" placeholder="Equipment Name">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" class="form-control" id="equipmentPrice" placeholder="Price" step="0.01">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" class="form-control" id="equipmentQty" placeholder="Qty" value="1">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-primary" onclick="addItem('equipment')">Add</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bill Items Table -->
                        <div class="bill-items-table">
                            <table class="table table-bordered" id="billItemsTable">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Type</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Items will be added here dynamically -->
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Bill Summary -->
                        <div class="bill-summary">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tax (%)</label>
                                        <input type="number" name="tax_percentage" class="form-control" value="0" step="0.01" onchange="calculateTotal()">
                                    </div>
                                    <div class="form-group">
                                        <label>Discount</label>
                                        <input type="number" name="discount" class="form-control" value="0" step="0.01" onchange="calculateTotal()">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Subtotal:</strong></td>
                                            <td class="text-right"><span id="subtotal">₹0.00</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Tax:</strong></td>
                                            <td class="text-right"><span id="taxAmount">₹0.00</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Discount:</strong></td>
                                            <td class="text-right"><span id="discountAmount">₹0.00</span></td>
                                        </tr>
                                        <tr class="table-success">
                                            <td><strong>Total:</strong></td>
                                            <td class="text-right"><strong><span id="grandTotal">₹0.00</span></strong></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="items" id="billItems">
                        <input type="hidden" name="total_amount" id="totalAmount">
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fa fa-save"></i> Generate Bill
                            </button>
                            <a href="?" class="btn btn-secondary btn-lg ml-2">
                                <i class="fa fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
        <?php elseif ($action == 'view' && isset($bill)): ?>
            <!-- View Bill Details -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Bill Details</h5>
                        <div>
                            <a href="?action=print&id=<?php echo $bill['id']; ?>" class="btn btn-secondary" target="_blank">
                                <i class="fa fa-print"></i> Print
                            </a>
                            <?php if ($bill['status'] != 'paid'): ?>
                                <button class="btn btn-success ml-2" onclick="recordPayment(<?php echo $bill['id']; ?>)">
                                    <i class="fa fa-money"></i> Record Payment
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Invoice Header -->
                    <div class="invoice-header">
                        <div class="row">
                            <div class="col-md-6">
                                <h2>Invoice #<?php echo str_pad($bill['id'], 6, '0', STR_PAD_LEFT); ?></h2>
                                <p class="text-muted">Date: <?php echo date('M d, Y', strtotime($bill['created_at'])); ?></p>
                            </div>
                            <div class="col-md-6 text-right">
                                <h3 class="bill-total"><?php echo formatCurrency($bill['total_amount'], $bill['currency']); ?></h3>
                                <span class="bill-status status-<?php echo $bill['status']; ?>">
                                    <?php echo ucfirst($bill['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Invoice Details -->
                    <div class="invoice-details">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Bill To:</h6>
                                <strong><?php echo $bill['patient_name']; ?></strong><br>
                                Patient ID: #<?php echo str_pad($bill['patient_id'], 4, '0', STR_PAD_LEFT); ?><br>
                                <?php echo $bill['patient_contact']; ?><br>
                                <?php echo $bill['patient_email']; ?>
                            </div>
                            <div class="col-md-6">
                                <h6>Hospital Details:</h6>
                                <strong><?php echo getSetting('site_title', 'Hospital CRM'); ?></strong><br>
                                <?php echo getSetting('hospital_address', 'Hospital Address'); ?><br>
                                <?php echo getSetting('hospital_phone', 'Phone Number'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bill Items -->
                    <div class="table-responsive">
                        <table class="table bill-items-table">
                            <thead>
                                <tr>
                                    <th>Item/Service</th>
                                    <th>Type</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bill_items as $item): ?>
                                    <tr>
                                        <td><?php echo $item['item_name']; ?></td>
                                        <td><span class="badge badge-info"><?php echo ucfirst($item['item_type']); ?></span></td>
                                        <td><?php echo formatCurrency($item['price'], $bill['currency']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><?php echo formatCurrency($item['total'], $bill['currency']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Bill Summary -->
                    <div class="bill-summary">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Payment History</h6>
                                <?php if (!empty($payments)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Amount</th>
                                                    <th>Method</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($payments as $payment): ?>
                                                    <tr>
                                                        <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                                        <td><?php echo formatCurrency($payment['amount'], $bill['currency']); ?></td>
                                                        <td><?php echo ucfirst($payment['payment_method']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No payments recorded yet.</p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <table class="table">
                                    <tr>
                                        <td><strong>Subtotal:</strong></td>
                                        <td class="text-right"><?php echo formatCurrency($bill['subtotal'], $bill['currency']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tax (<?php echo $bill['tax_percentage']; ?>%):</strong></td>
                                        <td class="text-right"><?php echo formatCurrency($bill['tax_amount'], $bill['currency']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Discount:</strong></td>
                                        <td class="text-right"><?php echo formatCurrency($bill['discount'], $bill['currency']); ?></td>
                                    </tr>
                                    <tr class="table-success">
                                        <td><strong>Total:</strong></td>
                                        <td class="text-right"><strong><?php echo formatCurrency($bill['total_amount'], $bill['currency']); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Paid:</strong></td>
                                        <td class="text-right"><?php echo formatCurrency($bill['paid_amount'], $bill['currency']); ?></td>
                                    </tr>
                                    <tr class="table-warning">
                                        <td><strong>Balance:</strong></td>
                                        <td class="text-right"><strong><?php echo formatCurrency($bill['total_amount'] - $bill['paid_amount'], $bill['currency']); ?></strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="paymentForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="bill_id" id="paymentBillId">
                        
                        <div class="form-group">
                            <label>Payment Amount</label>
                            <input type="number" name="amount" class="form-control" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Payment Method</label>
                            <div class="payment-methods">
                                <?php foreach ($payment_methods as $method): ?>
                                    <div class="payment-method" data-method="<?php echo $method['code']; ?>">
                                        <i class="fa fa-<?php echo $method['icon']; ?>"></i>
                                        <h6><?php echo $method['name']; ?></h6>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="payment_method" id="selectedPaymentMethod" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Transaction Reference</label>
                            <input type="text" name="transaction_ref" class="form-control" placeholder="Optional">
                        </div>
                        
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Optional"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Record Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        let billItems = [];
        
        // Add item to bill
        function addItem(type) {
            let item = {};
            
            switch(type) {
                case 'consultation':
                    const consultationSelect = document.getElementById('consultationService');
                    const consultationQty = document.getElementById('consultationQty');
                    if (!consultationSelect.value) return;
                    
                    item = {
                        id: consultationSelect.value,
                        name: consultationSelect.options[consultationSelect.selectedIndex].text.split(' - ')[0],
                        type: 'consultation',
                        price: parseFloat(consultationSelect.options[consultationSelect.selectedIndex].dataset.price),
                        quantity: parseInt(consultationQty.value)
                    };
                    break;
                    
                case 'medicine':
                    const medicineSelect = document.getElementById('medicineItem');
                    const medicineQty = document.getElementById('medicineQty');
                    if (!medicineSelect.value) return;
                    
                    item = {
                        id: medicineSelect.value,
                        name: medicineSelect.options[medicineSelect.selectedIndex].text.split(' - ')[0],
                        type: 'medicine',
                        price: parseFloat(medicineSelect.options[medicineSelect.selectedIndex].dataset.price),
                        quantity: parseInt(medicineQty.value)
                    };
                    break;
                    
                case 'test':
                    const testSelect = document.getElementById('testItem');
                    const testQty = document.getElementById('testQty');
                    if (!testSelect.value) return;
                    
                    item = {
                        id: testSelect.value,
                        name: testSelect.options[testSelect.selectedIndex].text.split(' - ')[0],
                        type: 'test',
                        price: parseFloat(testSelect.options[testSelect.selectedIndex].dataset.price),
                        quantity: parseInt(testQty.value)
                    };
                    break;
                    
                case 'equipment':
                    const equipmentName = document.getElementById('equipmentName');
                    const equipmentPrice = document.getElementById('equipmentPrice');
                    const equipmentQty = document.getElementById('equipmentQty');
                    if (!equipmentName.value || !equipmentPrice.value) return;
                    
                    item = {
                        id: 'equipment_' + Date.now(),
                        name: equipmentName.value,
                        type: 'equipment',
                        price: parseFloat(equipmentPrice.value),
                        quantity: parseInt(equipmentQty.value)
                    };
                    break;
            }
            
            item.total = item.price * item.quantity;
            billItems.push(item);
            
            updateBillItemsTable();
            calculateTotal();
            
            // Clear inputs
            document.getElementById(type + (type === 'equipment' ? 'Name' : type === 'consultation' ? 'Service' : 'Item')).value = '';
            if (type === 'equipment') {
                document.getElementById('equipmentPrice').value = '';
            }
        }
        
        // Update bill items table
        function updateBillItemsTable() {
            const tbody = document.querySelector('#billItemsTable tbody');
            tbody.innerHTML = '';
            
            billItems.forEach((item, index) => {
                const row = `
                    <tr>
                        <td>${item.name}</td>
                        <td><span class="badge badge-info">${item.type}</span></td>
                        <td>₹${item.price.toFixed(2)}</td>
                        <td>${item.quantity}</td>
                        <td>₹${item.total.toFixed(2)}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(${index})">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
            
            document.getElementById('billItems').value = JSON.stringify(billItems);
        }
        
        // Remove item from bill
        function removeItem(index) {
            billItems.splice(index, 1);
            updateBillItemsTable();
            calculateTotal();
        }
        
        // Calculate total
        function calculateTotal() {
            const subtotal = billItems.reduce((sum, item) => sum + item.total, 0);
            const taxPercentage = parseFloat(document.querySelector('[name="tax_percentage"]').value) || 0;
            const discount = parseFloat(document.querySelector('[name="discount"]').value) || 0;
            
            const taxAmount = (subtotal * taxPercentage) / 100;
            const grandTotal = subtotal + taxAmount - discount;
            
            document.getElementById('subtotal').textContent = '₹' + subtotal.toFixed(2);
            document.getElementById('taxAmount').textContent = '₹' + taxAmount.toFixed(2);
            document.getElementById('discountAmount').textContent = '₹' + discount.toFixed(2);
            document.getElementById('grandTotal').textContent = '₹' + grandTotal.toFixed(2);
            document.getElementById('totalAmount').value = grandTotal.toFixed(2);
        }
        
        // Record payment
        function recordPayment(billId) {
            document.getElementById('paymentBillId').value = billId;
            $('#paymentModal').modal('show');
        }
        
        // Payment method selection
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('selectedPaymentMethod').value = this.dataset.method;
            });
        });
        
        // Payment form submission
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const billId = document.getElementById('paymentBillId').value;
            this.action = '?action=payment&id=' + billId;
            this.submit();
        });
        
        // Search functionality
        $('#billSearch').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('#billsTable tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });
    </script>
</body>
</html>
