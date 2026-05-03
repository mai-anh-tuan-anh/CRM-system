<?php
/**
 * Quy trình bán hàng
 * Giao diện Kanban cho thỏa thuận
 */
$pageTitle = 'Quy trình bán hàng - Hệ thống CRM';

include 'components/header.php';
include 'components/sidebar.php';
?>

<style>
.pipeline-board {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    padding-bottom: 1rem;
    min-height: 500px;
}

.pipeline-column {
    min-width: 300px;
    max-width: 300px;
    background: #f8f9fc;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
}

.column-header {
    padding: 1rem;
    border-bottom: 2px solid #e3e6f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.column-header h5 {
    margin: 0;
    font-weight: 600;
}

.column-total {
    font-size: 0.875rem;
    color: #6c757d;
}

.column-body {
    padding: 1rem;
    flex: 1;
    overflow-y: auto;
    min-height: 200px;
}

.deal-card {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    cursor: grab;
    transition: all 0.2s;
}

.deal-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.deal-card.dragging {
    opacity: 0.5;
}

.deal-title {
    font-weight: 600;
    color: #4e73df;
    margin-bottom: 0.5rem;
}

.deal-company {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.deal-value {
    font-weight: 700;
    color: #1cc88a;
    margin-bottom: 0.5rem;
}

.deal-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.75rem;
    color: #858796;
}

.deal-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #4e73df;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.625rem;
    font-weight: 600;
}

.column-drop-zone {
    min-height: 100px;
    border: 2px dashed transparent;
    border-radius: 8px;
    transition: all 0.2s;
}

.column-drop-zone.drag-over {
    border-color: #4e73df;
    background: rgba(78, 115, 223, 0.05);
}
</style>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>
    
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Quy trình bán hàng</h1>
            <p class="text-muted mb-0">Kéo và thả để di chuyển thỏa thuận giữa các giai đoạn</p>
        </div>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm" id="userFilter" style="width: auto;">
                <option value="">Tất cả nhân viên</option>
            </select>
            <a href="deals.php" class="btn btn-outline-primary">
                <i class="bi bi-list me-2"></i>Danh sách
            </a>
            <a href="deals.php?action=add" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>Thêm thỏa thuận
            </a>
        </div>
    </div>
    
    <!-- Bảng quy trình bán hàng -->
    <div class="pipeline-board" id="pipelineBoard">
        <div class="pipeline-column" data-stage="prospect">
            <div class="column-header">
                <div>
                    <h5><i class="bi bi-circle text-primary me-2"></i>Tiềm năng</h5>
                    <div class="column-total" id="prospect-total">0 thỏa thuận • 0 ₫</div>
                </div>
                <span class="badge bg-primary" id="prospect-count">0</span>
            </div>
            <div class="column-body column-drop-zone" id="prospect-deals"></div>
        </div>
        
        <div class="pipeline-column" data-stage="qualification">
            <div class="column-header">
                <div>
                    <h5><i class="bi bi-question-circle text-info me-2"></i>Xác minh</h5>
                    <div class="column-total" id="qualification-total">0 thỏa thuận • 0 ₫</div>
                </div>
                <span class="badge bg-info" id="qualification-count">0</span>
            </div>
            <div class="column-body column-drop-zone" id="qualification-deals"></div>
        </div>
        
        <div class="pipeline-column" data-stage="proposal">
            <div class="column-header">
                <div>
                    <h5><i class="bi bi-file-text text-warning me-2"></i>Đề xuất</h5>
                    <div class="column-total" id="proposal-total">0 thỏa thuận • 0 ₫</div>
                </div>
                <span class="badge bg-warning" id="proposal-count">0</span>
            </div>
            <div class="column-body column-drop-zone" id="proposal-deals"></div>
        </div>
        
        <div class="pipeline-column" data-stage="negotiation">
            <div class="column-header">
                <div>
                    <h5><i class="bi bi-chat-dots text-danger me-2"></i>Thương lượng</h5>
                    <div class="column-total" id="negotiation-total">0 thỏa thuận • 0 ₫</div>
                </div>
                <span class="badge bg-danger" id="negotiation-count">0</span>
            </div>
            <div class="column-body column-drop-zone" id="negotiation-deals"></div>
        </div>
    </div>
</div>

<!-- Quick Edit Modal -->
<div class="modal fade" id="quickEditModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chuyển giai đoạn</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn đang chuyển thỏa thuận sang giai đoạn: <strong id="newStageName"></strong></p>
                <div class="mb-3">
                    <label class="form-label">Ghi chú (tùy chọn)</label>
                    <textarea class="form-control" id="stageChangeNote" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="confirmStageChange()">Xác nhận</button>
            </div>
        </div>
    </div>
</div>

<?php
$inlineJS = '
let draggedDeal = null;
let targetStage = null;
let quickEditModal;

// Initialize
document.addEventListener("DOMContentLoaded", function() {
    quickEditModal = new bootstrap.Modal(document.getElementById("quickEditModal"));
    loadPipeline();
    loadUsers();
    
    // User filter
    document.getElementById("userFilter").addEventListener("change", function() {
        loadPipeline();
    });
    
    // Setup drag and drop
    setupDragAndDrop();
});

function loadPipeline() {
    const userId = document.getElementById("userFilter").value;
    const url = userId ? `${API_BASE_URL}/deals.php?pipeline=1&assigned_to=${userId}` : `${API_BASE_URL}/deals.php?pipeline=1`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderPipeline(data.data);
            }
        });
}

function renderPipeline(pipelineData) {
    const stages = ["prospect", "qualification", "proposal", "negotiation"];
    
    stages.forEach(stage => {
        const deals = pipelineData.stages[stage] || [];
        const total = pipelineData.totals[stage] || 0;
        
        // Update counts
        document.getElementById(`${stage}-count`).textContent = deals.length;
        document.getElementById(`${stage}-total`).textContent = `${deals.length} thỏa thuận • ${formatCurrency(total)}`;
        
        // Render deals
        const container = document.getElementById(`${stage}-deals`);
        container.innerHTML = deals.map(deal => `
            <div class="deal-card" draggable="true" data-deal-id="${deal.id}" data-deal-value="${deal.value || 0}">
                <div class="deal-title">${deal.title}</div>
                <div class="deal-company">${deal.customer_company || deal.customer_name || "-"}</div>
                <div class="deal-value">${formatCurrency(deal.value || 0)}</div>
                <div class="deal-meta">
                    <span>${deal.expected_close_date ? formatDate(deal.expected_close_date) : "Không có ngày"}</span>
                    <div class="deal-avatar" title="${deal.assigned_to_name || "Chưa gán"}">
                        ${(deal.assigned_to_name || "C").charAt(0).toUpperCase()}
                    </div>
                </div>
            </div>
        `).join("");
    });
    
    // Re-attach drag handlers
    attachDragHandlers();
}

function loadUsers() {
    fetch(`${API_BASE_URL}/users.php`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById("userFilter");
                data.data.data.forEach(user => {
                    select.innerHTML += `<option value="${user.id}">${user.full_name}</option>`;
                });
            }
        });
}

function setupDragAndDrop() {
    const dropZones = document.querySelectorAll(".column-drop-zone");
    
    dropZones.forEach(zone => {
        zone.addEventListener("dragover", function(e) {
            e.preventDefault();
            this.classList.add("drag-over");
        });
        
        zone.addEventListener("dragleave", function(e) {
            this.classList.remove("drag-over");
        });
        
        zone.addEventListener("drop", function(e) {
            e.preventDefault();
            this.classList.remove("drag-over");
            
            if (draggedDeal) {
                const newStage = this.closest(".pipeline-column").dataset.stage;
                const currentStage = draggedDeal.closest(".pipeline-column").dataset.stage;
                
                if (newStage !== currentStage) {
                    targetStage = newStage;
                    const stageNames = {prospect: "Tiềm năng", qualification: "Xác minh", proposal: "Đề xuất", negotiation: "Thương lượng"};
                    document.getElementById("newStageName").textContent = stageNames[newStage] || newStage;
                    document.getElementById("stageChangeNote").value = "";
                    quickEditModal.show();
                }
            }
        });
    });
}

function attachDragHandlers() {
    const dealCards = document.querySelectorAll(".deal-card");
    
    dealCards.forEach(card => {
        card.addEventListener("dragstart", function(e) {
            draggedDeal = this;
            this.classList.add("dragging");
            e.dataTransfer.effectAllowed = "move";
        });
        
        card.addEventListener("dragend", function() {
            this.classList.remove("dragging");
            draggedDeal = null;
        });
    });
}

function confirmStageChange() {
    if (!draggedDeal || !targetStage) return;
    
    const dealId = draggedDeal.dataset.dealId;
    const note = document.getElementById("stageChangeNote").value;
    
    fetch(`${API_BASE_URL}/deals.php`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            id: dealId,
            stage: targetStage,
            stage_change_notes: note
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            quickEditModal.hide();
            showAlert("Đã cập nhật giai đoạn thỏa thuận!", "success");
            loadPipeline();
        } else {
            showAlert(result.message, "danger");
        }
    });
}
';

include 'components/footer.php';
?>
