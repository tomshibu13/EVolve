document.addEventListener('DOMContentLoaded', function() {
    // Sample booking data
    let mybBookings = [
        {
            id: 1,
            stationName: "Station 1 - Fast Charge",
            date: "2024-03-20",
            time: "09:00",
            vehicleModel: "Tesla Model 3",
            status: "active",
            price: "$25",
            duration: "1 hour"
        },
        {
            id: 2,
            stationName: "Station 2 - Super Charge",
            date: "2024-03-21",
            time: "10:00",
            vehicleModel: "Nissan Leaf",
            status: "completed",
            price: "$30",
            duration: "1 hour"
        }
    ];

    // DOM Elements
    const mybBookingsList = document.querySelector('.myb-bookings-list');
    const mybModal = document.getElementById('mybBookingModal');
    const mybNewBookingBtn = document.querySelector('.myb-new-booking-btn');
    const mybBookingForm = document.getElementById('mybBookingForm');
    const mybCancelBtn = document.querySelector('.myb-cancel-btn');
    const mybSearchInput = document.getElementById('mybStationSearch');
    const mybFilterStatus = document.getElementById('mybFilterStatus');

    // Create booking card
    function createBookingCard(booking) {
        return `
            <div class="myb-booking-card">
                <div class="myb-booking-header">
                    <h3>${booking.stationName}</h3>
                    <span class="myb-booking-status myb-status-${booking.status}">
                        ${booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}
                    </span>
                </div>
                <div class="myb-booking-details">
                    <div class="myb-detail-item">
                        <span class="myb-detail-label">Date</span>
                        <span class="myb-detail-value">${formatDate(booking.date)}</span>
                    </div>
                    <div class="myb-detail-item">
                        <span class="myb-detail-label">Time</span>
                        <span class="myb-detail-value">${booking.time}</span>
                    </div>
                    <div class="myb-detail-item">
                        <span class="myb-detail-label">Vehicle</span>
                        <span class="myb-detail-value">${booking.vehicleModel}</span>
                    </div>
                    <div class="myb-detail-item">
                        <span class="myb-detail-label">Price</span>
                        <span class="myb-detail-value">${booking.price}</span>
                    </div>
                </div>
                ${booking.status === 'active' ? `
                    <button class="myb-cancel-booking" data-id="${booking.id}">
                        Cancel Booking
                    </button>
                ` : ''}
            </div>
        `;
    }

    // Format date
    function formatDate(dateStr) {
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateStr).toLocaleDateString('en-US', options);
    }

    // Render bookings
    function renderBookings(bookings) {
        mybBookingsList.innerHTML = bookings.map(booking => createBookingCard(booking)).join('');
    }

    // Filter bookings
    function filterBookings() {
        const searchTerm = mybSearchInput.value.toLowerCase();
        const statusFilter = mybFilterStatus.value;

        const filtered = mybBookings.filter(booking => {
            const matchesSearch = booking.stationName.toLowerCase().includes(searchTerm);
            const matchesStatus = statusFilter === 'all' || booking.status === statusFilter;
            return matchesSearch && matchesStatus;
        });

        renderBookings(filtered);
    }

    // Event Listeners
    mybNewBookingBtn.addEventListener('click', () => {
        mybModal.style.display = 'block';
    });

    mybCancelBtn.addEventListener('click', () => {
        mybModal.style.display = 'none';
    });

    mybBookingForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const newBooking = {
            id: mybBookings.length + 1,
            stationName: document.getElementById('mybStationSelect').value,
            date: document.getElementById('mybDate').value,
            time: document.getElementById('mybTimeSlot').value,
            vehicleModel: document.getElementById('mybVehicleModel').value,
            status: 'active',
            price: '$25',
            duration: '1 hour'
        };

        mybBookings.unshift(newBooking);
        renderBookings(mybBookings);
        mybModal.style.display = 'none';
        mybBookingForm.reset();
    });

    mybSearchInput.addEventListener('input', filterBookings);
    mybFilterStatus.addEventListener('change', filterBookings);

    // Handle booking cancellation
    mybBookingsList.addEventListener('click', (e) => {
        if (e.target.classList.contains('myb-cancel-booking')) {
            const bookingId = parseInt(e.target.dataset.id);
            const booking = mybBookings.find(b => b.id === bookingId);
            if (booking) {
                booking.status = 'cancelled';
                renderBookings(mybBookings);
            }
        }
    });

    // Initial render
    renderBookings(mybBookings);
}); 