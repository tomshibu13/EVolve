<<<<<<< HEAD
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

// Station Data
const stationData = [
    { id: 'ST001', location: 'Downtown Mall', status: 'active', load: '75%', revenue: '$450' },
    { id: 'ST002', location: 'Central Park', status: 'maintenance', load: '0%', revenue: '$0' },
    { id: 'ST003', location: 'Airport Terminal', status: 'active', load: '90%', revenue: '$780' },
    { id: 'ST004', location: 'City Center', status: 'offline', load: '0%', revenue: '$120' },
    { id: 'ST005', location: 'Shopping Complex', status: 'active', load: '60%', revenue: '$340' }
];

// Populate Station Table
function populateStationTable() {
    const tableBody = document.getElementById('stationTableBody');
    tableBody.innerHTML = '';

    stationData.forEach(station => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${station.id}</td>
            <td>${station.location}</td>
            <td><span class="status-badge status-${station.status}">${station.status}</span></td>
            <td>${station.load}</td>
            <td>${station.revenue}</td>
            <td>
                <button class="action-icon"><i class="fas fa-edit"></i></button>
                <button class="action-icon"><i class="fas fa-trash"></i></button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

// Peak Hours Chart
const peakHoursCtx = document.getElementById('peakHoursChart').getContext('2d');
new Chart(peakHoursCtx, {
    type: 'bar',
    data: {
        labels: ['6AM', '9AM', '12PM', '3PM', '6PM', '9PM'],
        datasets: [{
            label: 'Station Usage',
            data: [30, 65, 45, 55, 85, 40],
            backgroundColor: '#4CAF50',
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Revenue Distribution Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'doughnut',
    data: {
        labels: ['Fast Charging', 'Standard Charging', 'Premium Services'],
        datasets: [{
            data: [45, 35, 20],
            backgroundColor: ['#4CAF50', '#2196F3', '#FFC107']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Populate Alerts
const alerts = [
    {
        title: 'Station ST002 Maintenance Required',
        time: '10 minutes ago',
        icon: 'fa-wrench'
    },
    {
        title: 'High Usage Alert - Downtown Station',
        time: '25 minutes ago',
        icon: 'fa-exclamation-triangle'
    },
    {
        title: 'New User Registration Spike',
        time: '1 hour ago',
        icon: 'fa-user-plus'
    }
];

function populateAlerts() {
    const alertsList = document.getElementById('alertsList');
    alertsList.innerHTML = '';

    alerts.forEach(alert => {
        const alertItem = document.createElement('div');
        alertItem.classList.add('alert-item');
        alertItem.innerHTML = `
            <div class="alert-icon">
                <i class="fas ${alert.icon}"></i>
            </div>
            <div class="alert-content">
                <div class="alert-title">${alert.title}</div>
                <div class="alert-time">${alert.time}</div>
            </div>
        `;
        alertsList.appendChild(alertItem);
    });
}

// Initialize all components
document.addEventListener('DOMContentLoaded', () => {
    populateStationTable();
    populateAlerts();
=======
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

// Station Data
const stationData = [
    { id: 'ST001', location: 'Downtown Mall', status: 'active', load: '75%', revenue: '$450' },
    { id: 'ST002', location: 'Central Park', status: 'maintenance', load: '0%', revenue: '$0' },
    { id: 'ST003', location: 'Airport Terminal', status: 'active', load: '90%', revenue: '$780' },
    { id: 'ST004', location: 'City Center', status: 'offline', load: '0%', revenue: '$120' },
    { id: 'ST005', location: 'Shopping Complex', status: 'active', load: '60%', revenue: '$340' }
];

// Populate Station Table
function populateStationTable() {
    const tableBody = document.getElementById('stationTableBody');
    tableBody.innerHTML = '';

    stationData.forEach(station => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${station.id}</td>
            <td>${station.location}</td>
            <td><span class="status-badge status-${station.status}">${station.status}</span></td>
            <td>${station.load}</td>
            <td>${station.revenue}</td>
            <td>
                <button class="action-icon"><i class="fas fa-edit"></i></button>
                <button class="action-icon"><i class="fas fa-trash"></i></button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

// Peak Hours Chart
const peakHoursCtx = document.getElementById('peakHoursChart').getContext('2d');
new Chart(peakHoursCtx, {
    type: 'bar',
    data: {
        labels: ['6AM', '9AM', '12PM', '3PM', '6PM', '9PM'],
        datasets: [{
            label: 'Station Usage',
            data: [30, 65, 45, 55, 85, 40],
            backgroundColor: '#4CAF50',
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Revenue Distribution Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'doughnut',
    data: {
        labels: ['Fast Charging', 'Standard Charging', 'Premium Services'],
        datasets: [{
            data: [45, 35, 20],
            backgroundColor: ['#4CAF50', '#2196F3', '#FFC107']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Populate Alerts
const alerts = [
    {
        title: 'Station ST002 Maintenance Required',
        time: '10 minutes ago',
        icon: 'fa-wrench'
    },
    {
        title: 'High Usage Alert - Downtown Station',
        time: '25 minutes ago',
        icon: 'fa-exclamation-triangle'
    },
    {
        title: 'New User Registration Spike',
        time: '1 hour ago',
        icon: 'fa-user-plus'
    }
];

function populateAlerts() {
    const alertsList = document.getElementById('alertsList');
    alertsList.innerHTML = '';

    alerts.forEach(alert => {
        const alertItem = document.createElement('div');
        alertItem.classList.add('alert-item');
        alertItem.innerHTML = `
            <div class="alert-icon">
                <i class="fas ${alert.icon}"></i>
            </div>
            <div class="alert-content">
                <div class="alert-title">${alert.title}</div>
                <div class="alert-time">${alert.time}</div>
            </div>
        `;
        alertsList.appendChild(alertItem);
    });
}

// Initialize all components
document.addEventListener('DOMContentLoaded', () => {
    populateStationTable();
    populateAlerts();
>>>>>>> 0c499f78677a34c0d64e35d5565441573d6c2b38
}); 