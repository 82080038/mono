<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Management - KSP Lam Gabe Jaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/Semua.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #16a085;
            --secondary-color: #2c3e50;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #2980b9;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%);
            min-height: 100vh;
        }

        .main-content {
            padding: 20px;
            min-height: 100vh;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .document-item {
            padding: 15px;
            border-left: 4px solid var(--primary-color);
            background: #f8f9fa;
            margin-bottom: 10px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .document-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .upload-area {
            border: 2px dashed var(--primary-color);
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background: rgba(22, 160, 133, 0.05);
            transition: all 0.3s;
        }

        .upload-area:hover {
            background: rgba(22, 160, 133, 0.1);
            border-color: var(--secondary-color);
        }

        .file-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .stats-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .stats-card h3 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-box input {
            padding-left: 40px;
            border-radius: 25px;
            border: 2px solid var(--primary-color);
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
        }

        .filter-tags {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .filter-tag {
            padding: 5px 15px;
            border-radius: 20px;
            background: var(--primary-color);
            color: white;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-tag:hover {
            background: var(--secondary-color);
        }

        .filter-tag.active {
            background: var(--secondary-color);
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .stats-card {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2><i class="fas fa-File-alt"></i> Document Management</h2>
                                <p class="text-muted">Kelola dokumen, template, dan arsip digital</p>
                            </div>
                            <div>
                                <button class="btn btn-primary" onclick="showUploadModal()">
                                    <i class="fas fa-Unggah"></i> Upload Document
                                </button>
                                <button class="btn btn-Berhasil" onclick="showTemplateModal()">
                                    <i class="fas fa-File-plus"></i> Create Template
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <i class="fas fa-File-alt File-icon"></i>
                    <h3 id="totalDocuments">0</h3>
                    <p class="mb-0">Total Documents</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <i class="fas fa-File-code File-icon"></i>
                    <h3 id="totalTemplates">0</h3>
                    <p class="mb-0">Templates</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <i class="fas fa-Pengguna File-icon"></i>
                    <h3 id="memberDocuments">0</h3>
                    <p class="mb-0">Member Documents</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <i class="fas fa-Grafik-line File-icon"></i>
                    <h3 id="storageUsed">0 MB</h3>
                    <p class="mb-0">Storage Used</p>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="Cari-box">
                                    <i class="fas fa-Cari"></i>
                                    <input type="text" class="Formulir-control" placeholder="Cari documents..." id="searchInput">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="Filter-tags">
                                    <span class="Filter-tag Aktif" onclick="filterDocuments('Semua')">All</span>
                                    <span class="Filter-tag" onclick="filterDocuments('Anggota')">Member</span>
                                    <span class="Filter-tag" onclick="filterDocuments('Pinjaman')">Loan</span>
                                    <span class="Filter-tag" onclick="filterDocuments('financial')">Financial</span>
                                    <span class="Filter-tag" onclick="filterDocuments('template')">Template</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Documents -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-Riwayat"></i> Recent Documents</h5>
                    </div>
                    <div class="card-body">
                        <div id="recentDocuments">
                            <div class="Dokumen-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="fas fa-File-PDF text-danger"></i> Loan Agreement - Ahmad Wijaya</h6>
                                        <p class="mb-1"><small class="text-muted">Type: Loan Document | Size: 2.3 MB</small></p>
                                        <div class="mt-2">
                                            <span class="badge bg-primary">Loan</span>
                                            <span class="badge bg-Berhasil">Active</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted">2 hours ago</small><br>
                                        <button class="btn btn-sm btn-outline-primary mt-1" onclick="viewDocument(1)">View</button>
                                        <button class="btn btn-sm btn-outline-Berhasil mt-1" onclick="downloadDocument(1)">Download</button>
                                    </div>
                                </div>
                            </div>
                            <div class="Dokumen-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="fas fa-File-Gambar text-Info"></i> KTP - Siti Nurhaliza</h6>
                                        <p class="mb-1"><small class="text-muted">Type: Member Document | Size: 1.2 MB</small></p>
                                        <div class="mt-2">
                                            <span class="badge bg-Info">Member</span>
                                            <span class="badge bg-Berhasil">Verified</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted">5 hours ago</small><br>
                                        <button class="btn btn-sm btn-outline-primary mt-1" onclick="viewDocument(2)">View</button>
                                        <button class="btn btn-sm btn-outline-Berhasil mt-1" onclick="downloadDocument(2)">Download</button>
                                    </div>
                                </div>
                            </div>
                            <div class="Dokumen-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="fas fa-File-Excel text-Berhasil"></i> Monthly Report - October 2024</h6>
                                        <p class="mb-1"><small class="text-muted">Type: Financial Document | Size: 856 KB</small></p>
                                        <div class="mt-2">
                                            <span class="badge bg-Peringatan">Financial</span>
                                            <span class="badge bg-primary">Report</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted">1 day ago</small><br>
                                        <button class="btn btn-sm btn-outline-primary mt-1" onclick="viewDocument(3)">View</button>
                                        <button class="btn btn-sm btn-outline-Berhasil mt-1" onclick="downloadDocument(3)">Download</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-File-code"></i> Document Templates</h5>
                    </div>
                    <div class="card-body">
                        <div id="documentTemplates">
                            <div class="Daftar-Grup">
                                <a href="#" class="Daftar-Grup-item Daftar-Grup-item-action" onclick="useTemplate('loan_agreement')">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">Loan Agreement Template</h6>
                                            <small class="text-muted">Standard loan document template</small>
                                        </div>
                                        <i class="fas fa-File-alt text-primary"></i>
                                    </div>
                                </a>
                                <a href="#" class="Daftar-Grup-item Daftar-Grup-item-action" onclick="useTemplate('member_form')">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">Member Registration Form</h6>
                                            <small class="text-muted">New member application template</small>
                                        </div>
                                        <i class="fas fa-Pengguna-plus text-Info"></i>
                                    </div>
                                </a>
                                <a href="#" class="Daftar-Grup-item Daftar-Grup-item-action" onclick="useTemplate('savings_account')">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">Savings Account Opening</h6>
                                            <small class="text-muted">Account opening form template</small>
                                        </div>
                                        <i class="fas fa-piggy-bank text-Berhasil"></i>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-Judul">Upload Document</h5>
                    <button type="button" class="btn-Tutup" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="Unggah-Area" id="uploadArea">
                        <i class="fas fa-cloud-Unggah-alt File-icon"></i>
                        <h4>Drag & Drop Files Here</h4>
                        <p class="text-muted">or click to browse</p>
                        <input type="File" id="fileInput" multiple style="display: Tidak Ada;">
                        <button class="btn btn-primary" onclick="Dokumen.getElementById('fileInput').click()">Browse Files</button>
                    </div>
                    <div class="mt-3">
                        <label class="Formulir-label">Document Type</label>
                        <select class="Formulir-Pilih" id="documentType">
                            <option value="Anggota">Member Document</option>
                            <option value="Pinjaman">Loan Document</option>
                            <option value="financial">Financial Document</option>
                            <option value="template">Template</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mt-3">
                        <label class="Formulir-label">Description</label>
                        <textarea class="Formulir-control" id="documentDescription" rows="3" placeholder="Enter Dokumen Deskripsi..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="uploadDocuments()">Upload</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Template Modal -->
    <div class="modal fade" id="templateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-Judul">Create Document Template</h5>
                    <button type="button" class="btn-Tutup" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="Formulir-label">Template Name</label>
                        <input type="text" class="Formulir-control" id="templateName" placeholder="Enter template Nama...">
                    </div>
                    <div class="mb-3">
                        <label class="Formulir-label">Template Type</label>
                        <select class="Formulir-Pilih" id="templateType">
                            <option value="Pinjaman">Loan Document</option>
                            <option value="Anggota">Member Document</option>
                            <option value="financial">Financial Document</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="Formulir-label">Template Content</label>
                        <textarea class="Formulir-control" id="templateContent" rows="10" placeholder="Enter template content..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="Formulir-label">Variables</label>
                        <input type="text" class="Formulir-control" id="templateVariables" placeholder="e.g., {member_name}, {loan_amount}, {Tanggal}">
                        <small class="text-muted">Separate variables with comma</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="createTemplate()">Create Template</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadDocumentStats();
            loadRecentDocuments();
            loadTemplates();
            setupDragAndDrop();
        });

        // Load document statistics
        function loadDocumentStats() {
            // Mock data for development
            document.getElementById('totalDocuments').textContent = '1,245';
            document.getElementById('totalTemplates').textContent = '12';
            document.getElementById('memberDocuments').textContent = '856';
            document.getElementById('storageUsed').textContent = '2.3 GB';
        }

        // Load recent documents
        function loadRecentDocuments() {
            // Mock data already in HTML
            console.log('Recent documents loaded');
        }

        // Load templates
        function loadTemplates() {
            // Mock data already in HTML
            console.log('Templates loaded');
        }

        // Setup drag and drop
        function setupDragAndDrop() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('fileInput');

            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.style.background = 'rgba(22, 160, 133, 0.2)';
            });

            uploadArea.addEventListener('dragleave', (e) => {
                e.preventDefault();
                uploadArea.style.background = 'rgba(22, 160, 133, 0.05)';
            });

            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.style.background = 'rgba(22, 160, 133, 0.05)';
                handleFiles(e.dataTransfer.files);
            });

            fileInput.addEventListener('change', (e) => {
                handleFiles(e.target.files);
            });
        }

        // Handle file upload
        function handleFiles(files) {
            console.log('Files selected:', files);
            // Process files...
            alert(`${files.length} file(s) selected for upload`);
        }

        // Show upload modal
        function showUploadModal() {
            const modal = new bootstrap.Modal(document.getElementById('uploadModal'));
            modal.show();
        }

        // Show template modal
        function showTemplateModal() {
            const modal = new bootstrap.Modal(document.getElementById('templateModal'));
            modal.show();
        }

        // Upload documents
        function uploadDocuments() {
            const type = document.getElementById('documentType').value;
            const description = document.getElementById('documentDescription').value;
            
            // Mock upload process
            console.log('Uploading documents:', { type, description });
            alert('Documents uploaded successfully!');
            
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('uploadModal')).hide();
            
            // Refresh documents list
            loadRecentDocuments();
        }

        // Create template
        function createTemplate() {
            const name = document.getElementById('templateName').value;
            const type = document.getElementById('templateType').value;
            const content = document.getElementById('templateContent').value;
            const variables = document.getElementById('templateVariables').value;
            
            // Mock template creation
            console.log('Creating template:', { name, type, content, variables });
            alert('Template Dibuat successfully!');
            
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('templateModal')).hide();
            
            // Refresh templates list
            loadTemplates();
        }

        // Filter documents
        function filterDocuments(type) {
            // Update active filter
            document.querySelectorAll('.Filter-tag').forEach(tag => {
                tag.classList.remove('Aktif');
            });
            event.target.classList.add('Aktif');
            
            // Filter documents
            console.log('Filtering documents by Tipe:', type);
            // Implement filtering logic...
        }

        // Search documents
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value;
            console.log('Searching documents:', searchTerm);
            // Implement search logic...
        });

        // View document
        function viewDocument(id) {
            console.log('Viewing Dokumen:', id);
            alert(`Viewing document ID: ${id}`);
        }

        // Download document
        function downloadDocument(id) {
            console.log('Downloading Dokumen:', id);
            alert(`Downloading document ID: ${id}`);
        }

        // Use template
        function useTemplate(templateId) {
            console.log('Using template:', templateId);
            alert(`Using template: ${templateId}`);
        }
    </script>
</body>
</html>
