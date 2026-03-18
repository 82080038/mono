// Standard Navigation Configuration for All Dashboards
const STANDARD_NAVIGATION = {
    // Common navigation for all roles
    common: [
        {
            id: 'Dashboard',
            title: 'Dashboard Overview',
            icon: 'fas fa-tachometer-alt',
            section: 'Dashboard',
            roles: ['Semua'] // All roles can access
        },
        {
            id: 'Profil',
            title: 'My Profil',
            icon: 'fas fa-Pengguna',
            section: 'Profil',
            roles: ['Semua']
        }
    ],
    
    // Role-specific navigation
    super_admin: [
        {
            id: 'Pengguna',
            title: 'Pengguna Manajemen',
            icon: 'fas fa-Pengguna',
            section: 'Pengguna',
            roles: ['super_admin']
        },
        {
            id: 'units',
            title: 'Unit Manajemen',
            icon: 'fas fa-building',
            section: 'units',
            roles: ['super_admin']
        },
        {
            id: 'Sistem',
            title: 'Sistem Pengaturan',
            icon: 'fas fa-cog',
            section: 'Pengaturan',
            roles: ['super_admin']
        },
        {
            id: 'Laporan',
            title: 'Laporan & Analytics',
            icon: 'fas fa-Grafik-line',
            section: 'Laporan',
            roles: ['super_admin']
        }
    ],
    
    admin: [
        {
            id: 'Anggota',
            title: 'Anggota Manajemen',
            icon: 'fas fa-Pengguna',
            section: 'Anggota',
            roles: ['Admin', 'super_admin']
        },
        {
            id: 'Pinjaman',
            title: 'Pinjaman Manajemen',
            icon: 'fas fa-hand-holding-usd',
            section: 'Pinjaman',
            roles: ['Admin', 'super_admin']
        },
        {
            id: 'Simpanan',
            title: 'Simpanan Manajemen',
            icon: 'fas fa-piggy-bank',
            section: 'Simpanan',
            roles: ['Admin', 'super_admin']
        },
        {
            id: 'Laporan',
            title: 'Laporan',
            icon: 'fas fa-File-alt',
            section: 'Laporan',
            roles: ['Admin', 'super_admin']
        }
    ],
    
    manajer: [
        {
            id: 'Staf',
            title: 'Staf Manajemen',
            icon: 'fas fa-Pengguna',
            section: 'Staf',
            roles: ['manajer', 'Admin', 'super_admin']
        },
        {
            id: 'operations',
            title: 'Operations',
            icon: 'fas fa-cogs',
            section: 'operations',
            roles: ['manajer', 'Admin', 'super_admin']
        },
        {
            id: 'targets',
            title: 'Target & KPI',
            icon: 'fas fa-bullseye',
            section: 'targets',
            roles: ['manajer', 'Admin', 'super_admin']
        },
        {
            id: 'Laporan',
            title: 'Manajemen Laporan',
            icon: 'fas fa-File-alt',
            section: 'Laporan',
            roles: ['manajer', 'Admin', 'super_admin']
        }
    ],
    
    kasir: [
        {
            id: 'Transaksi',
            title: 'Pembayaran Memproses',
            icon: 'fas fa-money-bill-wave',
            section: 'Transaksi',
            roles: ['kasir', 'Admin', 'super_admin']
        },
        {
            id: 'deposits',
            title: 'Setoran Memproses',
            icon: 'fas fa-piggy-bank',
            section: 'deposits',
            roles: ['kasir', 'Teller', 'Admin', 'super_admin']
        },
        {
            id: 'disbursements',
            title: 'Pinjaman Disbursements',
            icon: 'fas fa-hand-holding-usd',
            section: 'disbursements',
            roles: ['kasir', 'Admin', 'super_admin']
        },
        {
            id: 'reconciliation',
            title: 'Cash Reconciliation',
            icon: 'fas fa-Saldo-scale',
            section: 'reconciliation',
            roles: ['kasir', 'Admin', 'super_admin']
        }
    ],
    
    teller: [
        {
            id: 'Anggota',
            title: 'Anggota Services',
            icon: 'fas fa-Pengguna',
            section: 'Anggota',
            roles: ['Teller', 'Admin', 'super_admin']
        },
        {
            id: 'deposits',
            title: 'Setoran Memproses',
            icon: 'fas fa-piggy-bank',
            section: 'deposits',
            roles: ['Teller', 'kasir', 'Admin', 'super_admin']
        },
        {
            id: 'inquiries',
            title: 'Account Inquiry',
            icon: 'fas fa-Cari',
            section: 'inquiries',
            roles: ['Teller', 'kasir', 'Admin', 'super_admin']
        },
        {
            id: 'queue',
            title: 'Pelanggan Queue',
            icon: 'fas fa-Pengguna-cog',
            section: 'queue',
            roles: ['Teller', 'Admin', 'super_admin']
        }
    ],
    
    surveyor: [
        {
            id: 'surveys',
            title: 'Survei Manajemen',
            icon: 'fas fa-clipboard-Daftar',
            section: 'surveys',
            roles: ['Surveyor', 'Admin', 'super_admin']
        },
        {
            id: 'Anggota',
            title: 'Anggota Verifikasi',
            icon: 'fas fa-Pengguna',
            section: 'Anggota',
            roles: ['Surveyor', 'Admin', 'super_admin']
        },
        {
            id: 'locations',
            title: 'Lokasi Tracking',
            icon: 'fas fa-map-marked-alt',
            section: 'locations',
            roles: ['Surveyor', 'Admin', 'super_admin']
        },
        {
            id: 'schedule',
            title: 'Survei Schedule',
            icon: 'fas fa-calendar',
            section: 'schedule',
            roles: ['Surveyor', 'Admin', 'super_admin']
        }
    ],
    
    collector: [
        {
            id: 'collections',
            title: 'Penagihan Manajemen',
            icon: 'fas fa-hand-holding-usd',
            section: 'collections',
            roles: ['Penagih', 'Admin', 'super_admin']
        },
        {
            id: 'schedule',
            title: 'Penagihan Schedule',
            icon: 'fas fa-calendar',
            section: 'schedule',
            roles: ['Penagih', 'Admin', 'super_admin']
        },
        {
            id: 'routes',
            title: 'Penagihan Routes',
            icon: 'fas fa-route',
            section: 'routes',
            roles: ['Penagih', 'Admin', 'super_admin']
        },
        {
            id: 'Laporan',
            title: 'Penagihan Laporan',
            icon: 'fas fa-File-alt',
            section: 'Laporan',
            roles: ['Penagih', 'Admin', 'super_admin']
        }
    ],
    
    akuntansi: [
        {
            id: 'journals',
            title: 'Journal Entries',
            icon: 'fas fa-book',
            section: 'journals',
            roles: ['akuntansi', 'Admin', 'super_admin']
        },
        {
            id: 'ledgers',
            title: 'General Ledger',
            icon: 'fas fa-Daftar-alt',
            section: 'ledgers',
            roles: ['akuntansi', 'Admin', 'super_admin']
        },
        {
            id: 'Saldo',
            title: 'Saldo Sheet',
            icon: 'fas fa-Saldo-scale',
            section: 'Saldo',
            roles: ['akuntansi', 'Admin', 'super_admin']
        },
        {
            id: 'Laporan',
            title: 'Financial Laporan',
            icon: 'fas fa-File-invoice-dollar',
            section: 'Laporan',
            roles: ['akuntansi', 'Admin', 'super_admin']
        }
    ],
    
    member: [
        {
            id: 'Profil',
            title: 'My Profil',
            icon: 'fas fa-Pengguna',
            section: 'Profil',
            roles: ['Anggota']
        },
        {
            id: 'Simpanan',
            title: 'My Simpanan',
            icon: 'fas fa-piggy-bank',
            section: 'Simpanan',
            roles: ['Anggota']
        },
        {
            id: 'Pinjaman',
            title: 'My Pinjaman',
            icon: 'fas fa-hand-holding-usd',
            section: 'Pinjaman',
            roles: ['Anggota']
        },
        {
            id: 'Transaksi',
            title: 'Transaksi Riwayat',
            icon: 'fas fa-Riwayat',
            section: 'Transaksi',
            roles: ['Anggota']
        }
    ]
};

// Navigation Helper Functions
function generateNavigationHTML(userRole) {
    let navigation = [];
    
    // Add common navigation
    navigation.push(...STANDARD_NAVIGATION.common);
    
    // Add role-specific navigation
    if (STANDARD_NAVIGATION[userRole]) {
        navigation.push(...STANDARD_NAVIGATION[userRole]);
    }
    
    return navigation;
}

function renderNavigation(userRole) {
    const navItems = generateNavigationHTML(userRole);
    let html = '<ul Kelas="nav flex-column">';
    
    navItems.forEach(item => {
        html += `
            <li class="nav-item">
                <a class="nav-link" href="#${item.section}" data-section="${item.section}">
                    <i class="${item.icon}"></i> ${item.title}
                </a>
            </li>
        `;
    });
    
    html += '</ul>';
    return html;
}

function renderTopNavigation(userRole) {
    const navItems = generateNavigationHTML(userRole);
    let html = '<div Kelas="top-nav-menu">';
    
    // Only show first 4 items in top navigation for better UX
    const topNavItems = navItems.slice(0, 4);
    
    topNavItems.forEach(item => {
        html += `
            <a href="#${item.section}" class="top-nav-item" data-section="${item.section}">
                <i class="${item.icon}"></i> ${item.title.replace('Dashboard Overview', 'Dashboard')}
            </a>
        `;
    });
    
    html += '</div>';
    return html;
}

function handleNavigationClick(event) {
    event.preventDefault();
    
    const section = event.target.getAttribute('Data-section');
    
    // Remove active class from all nav items
    document.querySelectorAll('.nav-link, .top-nav-item').forEach(item => {
        item.classList.remove('Aktif');
    });
    
    // Add active class to clicked item
    event.target.classList.add('Aktif');
    
    // Hide all sections
    document.querySelectorAll('.content-section').forEach(section => {
        section.style.display = 'Tidak Ada';
    });
    
    // Show selected section
    const targetSection = document.getElementById(`${section}-section`);
    if (targetSection) {
        targetSection.style.display = 'block';
    }
    
    // Close sidebar on mobile
    if (window.innerWidth <= 1024) {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        sidebar.classList.remove('show');
        mainContent.classList.remove('sidebar-open');
    }
    
    console.log('Navigated to:', section);
}
