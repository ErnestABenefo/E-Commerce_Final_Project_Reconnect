<!-- University Context Switcher Component -->
<!-- Include this in your navigation/header area -->

<style>
.context-switcher {
    position: relative;
    display: inline-block;
}

.context-switcher-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.context-switcher-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.context-switcher-btn i {
    font-size: 16px;
}

.context-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.context-badge.personal {
    background: #4caf50;
    color: white;
}

.context-badge.university {
    background: #ff9800;
    color: white;
}

.context-dropdown {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 8px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    min-width: 300px;
    z-index: 1000;
}

.context-dropdown.show {
    display: block;
}

.context-dropdown-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    font-weight: bold;
    color: #333;
}

.context-dropdown-item {
    padding: 12px 20px;
    cursor: pointer;
    transition: background 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #555;
}

.context-dropdown-item:hover {
    background: #f5f5f5;
}

.context-dropdown-item.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.context-dropdown-item i {
    font-size: 16px;
}

.context-divider {
    height: 1px;
    background: #eee;
    margin: 5px 0;
}

.context-name {
    font-weight: 500;
}

.context-type {
    font-size: 12px;
    opacity: 0.7;
}

@media (max-width: 768px) {
    .context-dropdown {
        right: auto;
        left: 0;
        min-width: 250px;
    }
}
</style>

<div class="context-switcher">
    <button class="context-switcher-btn" id="contextSwitcherBtn" onclick="toggleContextDropdown()">
        <i class="fas fa-exchange-alt"></i>
        <span id="currentContextLabel">Loading...</span>
        <span class="context-badge personal" id="contextBadge">Personal</span>
    </button>
    
    <div class="context-dropdown" id="contextDropdown">
        <div class="context-dropdown-header">
            Switch Context
        </div>
        <div class="context-dropdown-item active" onclick="switchToPersonal()">
            <div>
                <div class="context-name">
                    <i class="fas fa-user"></i> Personal Account
                </div>
                <div class="context-type">Act as yourself</div>
            </div>
            <i class="fas fa-check" id="personalCheck" style="display:none;"></i>
        </div>
        
        <div class="context-divider"></div>
        
        <div id="universityContextList">
            <!-- University options will be populated here -->
            <div style="padding: 20px; text-align: center; color: #999;">
                Loading universities...
            </div>
        </div>
    </div>
</div>

<script>
// Context Switcher JavaScript
let currentContext = {
    acting_as_university: false,
    active_university_id: null,
    active_university_name: null
};

let userUniversities = [];

// Initialize context switcher on page load
document.addEventListener('DOMContentLoaded', function() {
    loadContextSwitcher();
});

function toggleContextDropdown() {
    const dropdown = document.getElementById('contextDropdown');
    dropdown.classList.toggle('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const switcher = document.querySelector('.context-switcher');
    if (!switcher.contains(event.target)) {
        document.getElementById('contextDropdown').classList.remove('show');
    }
});

async function loadContextSwitcher() {
    try {
        // Get current context
        const contextResponse = await fetch('../actions/switch_university_context_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_current_context'
        });
        
        const contextData = await contextResponse.json();
        
        if (contextData.status === 'success') {
            currentContext = contextData.context;
            updateContextDisplay();
        }
        
        // Get user's universities
        const universitiesResponse = await fetch('../actions/switch_university_context_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_my_universities'
        });
        
        const universitiesData = await universitiesResponse.json();
        
        if (universitiesData.status === 'success') {
            userUniversities = universitiesData.universities || [];
            populateUniversityList();
            
            // Hide the context switcher if user has no universities
            if (userUniversities.length === 0) {
                document.querySelector('.context-switcher').style.display = 'none';
            }
        }
        
    } catch (error) {
        console.error('Error loading context switcher:', error);
    }
}

function updateContextDisplay() {
    const label = document.getElementById('currentContextLabel');
    const badge = document.getElementById('contextBadge');
    
    if (currentContext.acting_as_university) {
        label.textContent = currentContext.active_university_name;
        badge.textContent = 'University';
        badge.className = 'context-badge university';
    } else {
        label.textContent = currentContext.user_name;
        badge.textContent = 'Personal';
        badge.className = 'context-badge personal';
    }
}

function populateUniversityList() {
    const list = document.getElementById('universityContextList');
    
    if (userUniversities.length === 0) {
        list.innerHTML = '<div style="padding: 20px; text-align: center; color: #999;">No universities to manage</div>';
        return;
    }
    
    let html = '';
    userUniversities.forEach(university => {
        const isActive = currentContext.acting_as_university && 
                        currentContext.active_university_id === university.university_id;
        
        html += `
            <div class="context-dropdown-item ${isActive ? 'active' : ''}" 
                 onclick="switchToUniversity(${university.university_id}, '${escapeHtml(university.name)}')">
                <div>
                    <div class="context-name">
                        <i class="fas fa-university"></i> ${escapeHtml(university.name)}
                    </div>
                    <div class="context-type">${escapeHtml(university.location || 'University')}</div>
                </div>
                <i class="fas fa-check" style="display: ${isActive ? 'block' : 'none'};"></i>
            </div>
        `;
    });
    
    list.innerHTML = html;
}

async function switchToPersonal() {
    try {
        const response = await fetch('../actions/switch_university_context_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=switch_to_personal'
        });
        
        // Log the raw response for debugging
        const rawText = await response.text();
        console.log('Raw response:', rawText);
        
        // Try to parse as JSON
        let data;
        try {
            data = JSON.parse(rawText);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', rawText);
            alert('Server returned invalid response. Check console for details.');
            return;
        }
        
        if (data.status === 'success') {
            currentContext = data.context;
            currentContext.acting_as_university = false;
            updateContextDisplay();
            populateUniversityList();
            toggleContextDropdown();
            
            // Reload the page to reflect the context change
            location.reload();
        } else {
            alert('Failed to switch context: ' + data.message);
        }
    } catch (error) {
        console.error('Error switching context:', error);
        alert('An error occurred while switching context');
    }
}

async function switchToUniversity(universityId, universityName) {
    try {
        const response = await fetch('../actions/switch_university_context_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=switch_to_university&university_id=${universityId}`
        });
        
        // Log the raw response for debugging
        const rawText = await response.text();
        console.log('Raw response:', rawText);
        
        // Try to parse as JSON
        let data;
        try {
            data = JSON.parse(rawText);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', rawText);
            alert('Server returned invalid response. Check console for details.');
            return;
        }
        
        if (data.status === 'success') {
            currentContext = data.context;
            updateContextDisplay();
            populateUniversityList();
            toggleContextDropdown();
            
            // Reload the page to reflect the context change
            location.reload();
        } else {
            alert('Failed to switch to university context: ' + data.message);
        }
    } catch (error) {
        console.error('Error switching context:', error);
        alert('An error occurred while switching context');
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
