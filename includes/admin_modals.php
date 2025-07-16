<!-- Quick Add Modal -->
<div class="modal fade" id="quickAddModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick Add</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="quick-add-options">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="quick-add-card" onclick="openQuickAddForm('patient')">
                                <i class="fa fa-user-plus"></i>
                                <h6>Add Patient</h6>
                                <p>Register new patient</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="quick-add-card" onclick="openQuickAddForm('doctor')">
                                <i class="fa fa-user-md"></i>
                                <h6>Add Doctor</h6>
                                <p>Add new doctor</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="quick-add-card" onclick="openQuickAddForm('appointment')">
                                <i class="fa fa-calendar-plus"></i>
                                <h6>Book Appointment</h6>
                                <p>Schedule appointment</p>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <div class="quick-add-card" onclick="openQuickAddForm('department')">
                                <i class="fa fa-building"></i>
                                <h6>Add Department</h6>
                                <p>Create new department</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="quick-add-card" onclick="openQuickAddForm('nurse')">
                                <i class="fa fa-plus-square"></i>
                                <h6>Add Nurse</h6>
                                <p>Register new nurse</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="quick-add-card" onclick="openQuickAddForm('staff')">
                                <i class="fa fa-users"></i>
                                <h6>Add Staff</h6>
                                <p>Add staff member</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Doctor Modal -->
<div class="modal fade" id="addDoctorModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Doctor</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="addDoctorForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>First Name *</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Middle Name</label>
                                <input type="text" name="middle_name" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Last Name *</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Phone *</label>
                                <input type="tel" name="phone" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Username *</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Password *</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Department</label>
                                <select name="department_id" class="form-control">
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>"><?php echo $dept['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Profile Image</label>
                                <input type="file" name="profile_image" class="form-control" accept="image/*">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Education</label>
                                <textarea name="education" class="form-control" rows="3" placeholder="MBBS, MD, etc."></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Experience</label>
                                <textarea name="experience" class="form-control" rows="3" placeholder="Years of experience, previous positions"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Certificates</label>
                                <textarea name="certificates" class="form-control" rows="2" placeholder="Medical certificates, licenses"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Awards</label>
                                <textarea name="awards" class="form-control" rows="2" placeholder="Awards and recognitions"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Doctor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Patient Modal -->
<div class="modal fade" id="addPatientModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Patient</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="addPatientForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>First Name *</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Middle Name</label>
                                <input type="text" name="middle_name" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Last Name *</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Date of Birth *</label>
                                <input type="date" name="date_of_birth" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Gender *</label>
                                <select name="gender" class="form-control" required>
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Contact *</label>
                                <input type="tel" name="contact" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Emergency Contact</label>
                                <input type="tel" name="emergency_contact" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Visit Reason *</label>
                                <textarea name="visit_reason" class="form-control" rows="2" required></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Attendant Details</label>
                                <textarea name="attendant_details" class="form-control" rows="2" placeholder="Name, relation, contact"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Patient</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Department Modal -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Department</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="addDepartmentForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Department Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Modal Styles */
.modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.modal-header {
    border-bottom: 1px solid #e9ecef;
    background: linear-gradient(135deg, var(--primary-color), #0056b3);
    color: white;
    border-radius: 15px 15px 0 0;
}

.modal-header .close {
    color: white;
    opacity: 0.8;
}

.modal-header .close:hover {
    opacity: 1;
}

.quick-add-options {
    padding: 20px 0;
}

.quick-add-card {
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 15px;
    padding: 25px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    height: 140px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.quick-add-card:hover {
    border-color: var(--primary-color);
    background: #fff;
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.quick-add-card i {
    font-size: 32px;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.quick-add-card h6 {
    margin: 10px 0 5px;
    font-weight: 600;
    color: #333;
}

.quick-add-card p {
    margin: 0;
    font-size: 12px;
    color: #666;
}

.dark-mode .quick-add-card {
    background: #2a2a2a;
    border-color: #444;
    color: #fff;
}

.dark-mode .quick-add-card:hover {
    background: #333;
    border-color: var(--primary-color);
}

.dark-mode .quick-add-card h6 {
    color: #fff;
}

.dark-mode .quick-add-card p {
    color: #ccc;
}

.form-group label {
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.dark-mode .form-group label {
    color: #fff;
}

.form-control {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    padding: 12px 15px;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

.btn {
    border-radius: 8px;
    padding: 10px 25px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary {
    background: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-primary:hover {
    background: #0056b3;
    border-color: #0056b3;
    transform: translateY(-2px);
}
</style>

<script>
// Quick add functionality
function openQuickAddForm(type) {
    $('#quickAddModal').modal('hide');
    
    setTimeout(() => {
        switch(type) {
            case 'patient':
                $('#addPatientModal').modal('show');
                break;
            case 'doctor':
                $('#addDoctorModal').modal('show');
                break;
            case 'department':
                $('#addDepartmentModal').modal('show');
                break;
            case 'appointment':
                window.location.href = 'modules/appointments.php?action=add';
                break;
            default:
                alert('Feature coming soon!');
        }
    }, 300);
}

// Form submissions
$('#addDoctorForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    $.ajax({
        url: 'api/add-doctor.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                alert('Doctor added successfully!');
                $('#addDoctorModal').modal('hide');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('An error occurred. Please try again.');
        }
    });
});

$('#addPatientForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = $(this).serialize();
    
    $.ajax({
        url: 'api/add-patient.php',
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                alert('Patient added successfully!');
                $('#addPatientModal').modal('hide');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('An error occurred. Please try again.');
        }
    });
});

$('#addDepartmentForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = $(this).serialize();
    
    $.ajax({
        url: 'api/add-department.php',
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                alert('Department added successfully!');
                $('#addDepartmentModal').modal('hide');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('An error occurred. Please try again.');
        }
    });
});
</script>
