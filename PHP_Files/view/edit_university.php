<?php
session_start();
require_once '../controllers/university_admin_controller.php';
require_once '../settings/db_class.php';

$current_user = $_SESSION['user_id'] ?? null;
if (!is_global_admin_ctr($current_user)) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<h2>Access denied</h2><p>You must be a global admin to access this page.</p>';
    exit;
}

$university_id = $_GET['id'] ?? null;
if (!$university_id) {
    header('Location: global_admin_panel.php');
    exit;
}

$db = new db_connection();
$conn = $db->db_conn();

// Fetch university details
$stmt = $conn->prepare("SELECT * FROM University WHERE university_id = ?");
$stmt->bind_param('i', $university_id);
$stmt->execute();
$result = $stmt->get_result();
$university = $result->fetch_assoc();
$stmt->close();

if (!$university) {
    header('Location: global_admin_panel.php');
    exit;
}

// Fetch departments
$stmt = $conn->prepare("SELECT * FROM AcademicDepartment WHERE university_id = ? ORDER BY faculty, department_name");
$stmt->bind_param('i', $university_id);
$stmt->execute();
$result = $stmt->get_result();
$departments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit University - ReConnect</title>
  <link rel="stylesheet" href="../fontawesome/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { 
      font-family: 'Segoe UI', Arial, sans-serif; 
      background: #f4f6f8;
      padding-top: 70px;
    }
    
    /* Navigation Bar */
    .navbar {
      background: white;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
      height: 70px;
    }
    
    .navbar-container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 30px;
      height: 100%;
      display: flex;
      align-items: center;
      gap: 20px;
    }
    
    .navbar-brand {
      font-size: 1.5rem;
      font-weight: 700;
      color: #667eea;
      text-decoration: none;
    }
    
    .navbar-brand i { font-size: 1.8rem; }
    
    .navbar-menu {
      display: flex;
      list-style: none;
      gap: 30px;
      align-items: center;
      margin: 0;
      padding: 0;
      margin-left: auto;
    }
    
    .navbar-menu a {
      text-decoration: none;
      color: #2c3e50;
      font-weight: 500;
      transition: color 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .navbar-menu a:hover { color: #667eea; }
    
    .container {
      max-width: 900px;
      margin: 30px auto;
      padding: 0 20px;
    }
    
    .header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 30px;
      border-radius: 12px;
      margin-bottom: 30px;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    
    .header h1 {
      font-size: 28px;
      margin-bottom: 8px;
    }
    
    .header p {
      opacity: 0.9;
      font-size: 15px;
    }
    
    .card {
      background: white;
      border-radius: 12px;
      padding: 30px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      margin-bottom: 25px;
    }
    
    .card h2 {
      font-size: 20px;
      color: #2c3e50;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f0f0f0;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #2c3e50;
      font-size: 14px;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 12px;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      font-size: 14px;
      transition: border-color 0.3s;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: #667eea;
    }
    
    .form-group textarea {
      min-height: 100px;
      resize: vertical;
    }
    
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }
    
    .btn {
      padding: 12px 24px;
      border-radius: 8px;
      border: none;
      cursor: pointer;
      font-size: 15px;
      font-weight: 600;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    
    .btn-primary {
      background: #667eea;
      color: white;
    }
    
    .btn-primary:hover {
      background: #5568d3;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    .btn-secondary {
      background: #6c757d;
      color: white;
    }
    
    .btn-secondary:hover {
      background: #5a6268;
    }
    
    .btn-danger {
      background: #dc3545;
      color: white;
      padding: 8px 12px;
      font-size: 13px;
    }
    
    .btn-danger:hover {
      background: #c82333;
    }
    
    .btn-success {
      background: #28a745;
      color: white;
      padding: 8px 16px;
      font-size: 14px;
    }
    
    .btn-success:hover {
      background: #218838;
    }
    
    .department-item {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 12px;
      border: 2px solid #e9ecef;
    }
    
    .department-item.new {
      border-color: #28a745;
      background: #d4edda;
    }
    
    .department-header {
      display: flex;
      gap: 10px;
      align-items: flex-start;
    }
    
    .department-header > div {
      flex: 1;
    }
    
    .actions {
      display: flex;
      gap: 15px;
      padding-top: 20px;
      border-top: 2px solid #f0f0f0;
      margin-top: 20px;
    }
    
    .alert {
      padding: 15px 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      display: none;
    }
    
    .alert.success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    
    .alert.error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    
    .alert.show {
      display: block;
    }
  </style>
</head>
<body>
  <!-- Navigation Bar -->
  <nav class="navbar">
    <div class="navbar-container">
      <a href="dashboard.php" class="navbar-brand">
        <i class="fas fa-graduation-cap"></i>
        ReConnect
      </a>
      
      <ul class="navbar-menu">
        <li><a href="global_admin_panel.php"><i class="fas fa-arrow-left"></i> Back to Admin Panel</a></li>
      </ul>
    </div>
  </nav>
  
  <div class="container">
    <div class="header">
      <h1><i class="fas fa-university"></i> Edit University</h1>
      <p>Update university information and manage departments</p>
    </div>
    
    <div id="alertBox" class="alert"></div>
    
    <form id="editUniversityForm">
      <input type="hidden" name="university_id" value="<?php echo $university_id; ?>">
      
      <div class="card">
        <h2><i class="fas fa-info-circle"></i> University Information</h2>
        
        <div class="form-group">
          <label>University Name *</label>
          <input type="text" name="name" required value="<?php echo htmlspecialchars($university['name']); ?>">
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label>Location *</label>
            <input type="text" name="location" required value="<?php echo htmlspecialchars($university['location']); ?>">
          </div>
          
          <div class="form-group">
            <label>University Type *</label>
            <select name="university_type" required>
              <option value="public" <?php echo $university['university_type'] === 'public' ? 'selected' : ''; ?>>Public</option>
              <option value="private" <?php echo $university['university_type'] === 'private' ? 'selected' : ''; ?>>Private</option>
              <option value="religious" <?php echo $university['university_type'] === 'religious' ? 'selected' : ''; ?>>Religious</option>
              <option value="technical" <?php echo $university['university_type'] === 'technical' ? 'selected' : ''; ?>>Technical</option>
              <option value="other" <?php echo $university['university_type'] === 'other' ? 'selected' : ''; ?>>Other</option>
            </select>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label>Website</label>
            <input type="url" name="website" value="<?php echo htmlspecialchars($university['website'] ?? ''); ?>">
          </div>
          
          <div class="form-group">
            <label>Established Year</label>
            <input type="number" name="established_year" min="1800" max="2025" value="<?php echo htmlspecialchars($university['established_year'] ?? ''); ?>">
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label>Contact Email</label>
            <input type="email" name="contact_email" value="<?php echo htmlspecialchars($university['contact_email'] ?? ''); ?>">
          </div>
          
          <div class="form-group">
            <label>Contact Phone</label>
            <input type="tel" name="contact_phone" value="<?php echo htmlspecialchars($university['contact_phone'] ?? ''); ?>">
          </div>
        </div>
        
        <div class="form-group">
          <label>Address</label>
          <textarea name="address" rows="2"><?php echo htmlspecialchars($university['address'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" rows="4"><?php echo htmlspecialchars($university['description'] ?? ''); ?></textarea>
        </div>
      </div>
      
      <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
          <h2 style="margin: 0; padding: 0; border: none;"><i class="fas fa-building"></i> Academic Departments</h2>
          <button type="button" class="btn btn-success" onclick="addNewDepartment()">
            <i class="fas fa-plus"></i> Add Department
          </button>
        </div>
        
        <div id="departmentsContainer">
          <?php if (empty($departments)): ?>
            <p style="color: #6c757d; text-align: center; padding: 20px;">No departments added yet. Click "Add Department" to create one.</p>
          <?php else: ?>
            <?php foreach ($departments as $dept): ?>
              <div class="department-item" data-id="<?php echo $dept['department_id']; ?>">
                <div class="department-header">
                  <div>
                    <label style="font-size: 13px; margin-bottom: 5px; display: block; font-weight: 600;">Faculty/School</label>
                    <input type="text" class="dept-faculty" value="<?php echo htmlspecialchars($dept['faculty'] ?? ''); ?>" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                  </div>
                  <div>
                    <label style="font-size: 13px; margin-bottom: 5px; display: block; font-weight: 600;">Department Name</label>
                    <input type="text" class="dept-name" value="<?php echo htmlspecialchars($dept['department_name']); ?>" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                  </div>
                  <button type="button" class="btn btn-danger" onclick="deleteDepartment(this, <?php echo $dept['department_id']; ?>)" style="margin-top: 20px;">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
      
      <div class="actions">
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Save Changes
        </button>
        <a href="global_admin_panel.php" class="btn btn-secondary">
          <i class="fas fa-times"></i> Cancel
        </a>
      </div>
    </form>
  </div>
  
  <script>
    function showAlert(message, type) {
      const alertBox = document.getElementById('alertBox');
      alertBox.textContent = message;
      alertBox.className = `alert ${type} show`;
      setTimeout(() => alertBox.classList.remove('show'), 5000);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    function addNewDepartment() {
      const container = document.getElementById('departmentsContainer');
      const emptyMessage = container.querySelector('p');
      if (emptyMessage) emptyMessage.remove();
      
      const html = `
        <div class="department-item new" data-id="new">
          <div class="department-header">
            <div>
              <label style="font-size: 13px; margin-bottom: 5px; display: block; font-weight: 600;">Faculty/School</label>
              <input type="text" class="dept-faculty" placeholder="e.g., School of Engineering" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
            </div>
            <div>
              <label style="font-size: 13px; margin-bottom: 5px; display: block; font-weight: 600;">Department Name</label>
              <input type="text" class="dept-name" placeholder="e.g., Computer Science" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
            </div>
            <button type="button" class="btn btn-danger" onclick="this.closest('.department-item').remove()" style="margin-top: 20px;">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      `;
      container.insertAdjacentHTML('beforeend', html);
    }
    
    function deleteDepartment(button, deptId) {
      if (!confirm('Delete this department? This action cannot be undone.')) return;
      
      button.closest('.department-item').remove();
      
      // Mark for deletion
      if (!window.deletedDepartments) window.deletedDepartments = [];
      window.deletedDepartments.push(deptId);
    }
    
    document.getElementById('editUniversityForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      
      const formData = new FormData(e.target);
      const data = Object.fromEntries(formData);
      
      // Collect existing and new departments
      const departments = {
        existing: [],
        new: [],
        deleted: window.deletedDepartments || []
      };
      
      document.querySelectorAll('#departmentsContainer .department-item').forEach(item => {
        const deptId = item.dataset.id;
        const faculty = item.querySelector('.dept-faculty').value.trim();
        const name = item.querySelector('.dept-name').value.trim();
        
        if (name) {
          if (deptId === 'new') {
            departments.new.push({ faculty: faculty || null, department_name: name });
          } else {
            departments.existing.push({ 
              department_id: parseInt(deptId), 
              faculty: faculty || null, 
              department_name: name 
            });
          }
        }
      });
      
      data.departments = departments;
      
      try {
        const response = await fetch('../actions/admin/update_university.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
          showAlert('University updated successfully!', 'success');
          setTimeout(() => window.location.href = 'global_admin_panel.php', 1500);
        } else {
          showAlert('Error: ' + (result.message || 'Unknown error'), 'error');
        }
      } catch (error) {
        showAlert('Error updating university: ' + error.message, 'error');
      }
    });
  </script>
</body>
</html>
