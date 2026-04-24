/**
 * TurfKick Admin Dashboard JS
 */

const TurfKickAdmin = {
    csrfToken: null,

    async init() {
        await this.fetchToken();
        this.fetchUsers();
    },

    async fetchToken() {
        try {
            const response = await fetch('api/get_token.php');
            const result = await response.json();
            if (result.status === 'success') {
                this.csrfToken = result.data.csrf_token;
            }
        } catch (error) {
            console.error('Error fetching token:', error);
        }
    },

    async fetchUsers() {
        try {
            const response = await fetch('api/admin/get_users.php');
            const result = await response.json();
            const tbody = document.querySelector('#userTable tbody');
            if (result.status === 'success') {
                tbody.innerHTML = result.data.map(u => `
                    <tr>
                        <td>${u.name}</td>
                        <td>${u.email}</td>
                        <td>${u.role}</td>
                        <td>${u.created_at}</td>
                    </tr>
                `).join('');
            }
        } catch (error) {
            console.error('Error fetching users:', error);
        }
    },

    async fetchTurfs() {
        try {
            const response = await fetch('api/admin/get_turfs.php');
            const result = await response.json();
            const tbody = document.querySelector('#turfTable tbody');
            if (result.status === 'success') {
                tbody.innerHTML = result.data.map(t => `
                    <tr>
                        <td>${t.name}</td>
                        <td>${t.owner_name}</td>
                        <td>${t.location}</td>
                        <td><span class="status-pill pill-${t.status}">${t.status}</span></td>
                        <td>
                            ${t.status === 'pending' ? `<button class="btn btn-approve" onclick="TurfKickAdmin.toggleTurf(${t.id}, 'active')">Approve</button>` : ''}
                            ${t.status === 'active' ? `<button class="btn btn-disable" onclick="TurfKickAdmin.toggleTurf(${t.id}, 'inactive')">Disable</button>` : ''}
                            ${t.status === 'inactive' ? `<button class="btn btn-approve" onclick="TurfKickAdmin.toggleTurf(${t.id}, 'active')">Enable</button>` : ''}
                        </td>
                    </tr>
                `).join('');
            }
        } catch (error) {
            console.error('Error fetching turfs:', error);
        }
    },

    async toggleTurf(turfId, status) {
        const formData = new FormData();
        formData.append('turf_id', turfId);
        formData.append('status', status);
        formData.append('csrf_token', this.csrfToken);

        try {
            const response = await fetch('api/admin/toggle_turf.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            alert(result.message);
            this.fetchTurfs();
        } catch (error) {
            console.error('Error toggling turf:', error);
        }
    },

    async fetchBookings() {
        try {
            const response = await fetch('api/admin/get_bookings.php');
            const result = await response.json();
            const tbody = document.querySelector('#bookingTable tbody');
            if (result.status === 'success') {
                tbody.innerHTML = result.data.map(b => `
                    <tr>
                        <td>${b.user_name}</td>
                        <td>${b.turf_name}</td>
                        <td>${b.booking_date}</td>
                        <td>${b.slot_label}</td>
                        <td><span class="status-pill pill-${b.status}">${b.status}</span></td>
                        <td>
                            ${b.status === 'upcoming' ? `<button class="btn btn-cancel" onclick="TurfKickAdmin.cancelBooking(${b.id})">Admin Cancel</button>` : '---'}
                        </td>
                    </tr>
                `).join('');
            }
        } catch (error) {
            console.error('Error fetching bookings:', error);
        }
    },

    async cancelBooking(bookingId) {
        if (!confirm("Are you sure you want to cancel this booking as admin?")) return;

        const formData = new FormData();
        formData.append('booking_id', bookingId);
        formData.append('csrf_token', this.csrfToken);

        try {
            const response = await fetch('api/admin/cancel_booking.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            alert(result.message);
            this.fetchBookings();
        } catch (error) {
            console.error('Error cancelling booking:', error);
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    TurfKickAdmin.init();
});
