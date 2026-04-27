/**
 * TurfKick Owner Dashboard JS - Final Production Engine
 */

const TurfKickOwner = {
    turfs: [],
    currentTurf: null,
    csrfToken: null,
    schedule: {
        'Monday': [], 'Tuesday': [], 'Wednesday': [], 'Thursday': [], 'Friday': [], 'Saturday': [], 'Sunday': []
    },

    async init() {
        await this.fetchToken();
        await this.fetchTurfs();
        await this.fetchEquipment();
        await this.fetchBookings();
        
        // Expose critical UI helpers to window scope
        window.switchManageTab = (tab, btn) => this.switchManageTab(tab, btn);
        window.addSlotToDay = (day) => this.addSlotToDay(day);
        window.saveDaySchedule = (day) => this.saveDaySchedule(day);
        window.openCopyModal = (day) => this.openCopyModal(day);
        window.updateProfile = () => this.updateProfile();
    },

    async fetchToken() {
        try {
            const response = await fetch('api/get_token.php');
            const result = await response.json();
            if (result.status === 'success') this.csrfToken = result.data.csrf_token;
        } catch (error) { console.error('Token Error:', error); }
    },

    async fetchTurfs() {
        try {
            const response = await fetch('api/manage_turfs.php');
            const result = await response.json();
            if (result.status === 'success') {
                this.turfs = result.data;
                this.renderTurfList();
                
                // Populate itemTurfId dropdown
                const turfSelect = document.getElementById('itemTurfId');
                if (turfSelect) {
                    turfSelect.innerHTML = this.turfs.map(t => `<option value="${t.id}">${t.name}</option>`).join('');
                }
            }
        } catch (error) { console.error('Fetch Turfs Error:', error); }
    },

    renderTurfList() {
        const container = document.getElementById('turfListGrid');
        if (!container) return;
        if (this.turfs.length === 0) {
            container.innerHTML = '<p style="opacity:0.5; grid-column: 1/-1; text-align:center;">No properties listed.</p>';
            return;
        }
        container.innerHTML = this.turfs.map(t => `
            <div class="turf-card">
                <img src="${t.image_path || 'https://images.unsplash.com/photo-1508098682722-e99c643e7f0b?auto=format&fit=crop&w=400&q=80'}" alt="${t.name}">
                <div class="turf-card-body">
                    <div class="d-flex justify-content-between">
                        <h4 style="margin:0;">${t.name}</h4>
                        <span class="status-pill" style="font-size:9px; background:rgba(255,255,255,0.1); padding:2px 6px; border-radius:4px;">${t.status}</span>
                    </div>
                    <p style="opacity:0.6; font-size:12px; margin:5px 0;">📍 ${t.location}</p>
                    <div style="margin-top:15px;">
                        <button class="btn btn-primary btn-sm w-100" onclick="window.location.href='manage_turf.php?id=${t.id}'">Manage Property</button>
                    </div>
                </div>
            </div>
        `).join('');
    },

    async manageTurf(id) {
        try {
            const response = await fetch(`api/manage_turfs.php?turf_id=${id}`);
            const result = await response.json();
            if (result.status === 'success') {
                this.currentTurf = result.data;
                this.renderManageView();
                if (typeof showSection === 'function') {
                    showSection('manage-turf');
                }
            }
        } catch (error) { console.error('Manage Turf Error:', error); }
    },

    renderManageView() {
        const t = this.currentTurf;
        document.getElementById('managingTurfName').innerText = t.name;
        document.getElementById('edit_turfId').value = t.id;
        document.getElementById('edit_turfName').value = t.name;
        document.getElementById('edit_turfPrice').value = t.price_per_hour;

        const mStatus = document.getElementById('currentMStatus');
        mStatus.innerText = t.is_under_maintenance == 1 ? "Under Maintenance" : "Active & Live";
        mStatus.style.color = t.is_under_maintenance == 1 ? "var(--danger)" : "var(--secondary)";

        this.renderGallery();
        this.fetchSlots();
        this.fetchMaintenanceRequests(t.id);
    },

    renderGallery() {
        const container = document.getElementById('galleryGrid');
        const gallery = this.currentTurf.gallery || [];
        if (gallery.length === 0) {
            container.innerHTML = '<p style="opacity:0.5; font-size:12px;">No images yet.</p>';
            return;
        }
        container.innerHTML = gallery.map(img => `
            <div style="position:relative; border-radius:10px; overflow:hidden; aspect-ratio:1; border:1px solid var(--border);">
                <img src="${img.image_path}" style="width:100%; height:100%; object-fit:cover;">
            </div>
        `).join('');
    },

    async fetchSlots() {
        if (!this.currentTurf) return;
        try {
            const response = await fetch(`api/manage_slots.php?turf_id=${this.currentTurf.id}`);
            const result = await response.json();
            if (result.status === 'success') {
                Object.keys(this.schedule).forEach(day => this.schedule[day] = []);
                result.data.forEach(s => {
                    if(this.schedule[s.day_of_week]) {
                        this.schedule[s.day_of_week].push({
                            id: s.id, name: s.slot_name || '', start: s.start_time.substring(0,5), end: s.end_time.substring(0,5)
                        });
                    }
                });
                this.renderWeeklySchedule();
            }
        } catch (error) { console.error('Slots Error:', error); }
    },

    renderWeeklySchedule() {
        const container = document.getElementById('manageSlotsList');
        if (!container) return;
        container.innerHTML = Object.keys(this.schedule).map(day => {
            const slots = this.schedule[day];
            const hasSlots = slots.length > 0;
            return `
                <div class="day-card ${!hasSlots ? 'disabled' : ''}" id="card-${day}">
                    <div class="day-header">
                        <div class="day-title">${day}</div>
                        <div style="display: flex; gap: 10px;">
                            ${hasSlots ? `<button class="copy-btn" onclick="openCopyModal('${day}')">Copy</button>` : ''}
                            <button class="btn btn-outline" style="font-size: 11px; padding: 5px 12px;" onclick="addSlotToDay('${day}')">+ Slot</button>
                        </div>
                    </div>
                    <div class="day-slots">
                        ${slots.map((s, idx) => `
                            <div class="slot-row">
                                <input type="text" placeholder="Name" value="${s.name}" class="slot-name">
                                <input type="time" value="${s.start}" class="slot-start">
                                <input type="time" value="${s.end}" class="slot-end">
                                <span style="color:var(--danger); cursor:pointer; font-weight:bold;" onclick="TurfKickOwner.removeSlotUI('${day}', ${idx})">&times;</span>
                            </div>
                        `).join('')}
                    </div>
                    ${hasSlots ? `<div style="text-align:right; margin-top:15px;"><button class="btn btn-primary" style="font-size:10px;" onclick="saveDaySchedule('${day}')">Save Day</button></div>` : ''}
                </div>
            `;
        }).join('');
    },

    addSlotToDay(day) {
        this.schedule[day].push({ name: '', start: '09:00', end: '10:00' });
        this.renderWeeklySchedule();
    },

    removeSlotUI(day, idx) {
        this.schedule[day].splice(idx, 1);
        this.renderWeeklySchedule();
    },

    async saveDaySchedule(day) {
        const card = document.getElementById(`card-${day}`);
        const rows = card.querySelectorAll('.slot-row');
        const slots = Array.from(rows).map(r => ({
            name: r.querySelector('.slot-name').value,
            start: r.querySelector('.slot-start').value,
            end: r.querySelector('.slot-end').value
        }));

        const formData = new FormData();
        formData.append('action', 'save_day');
        formData.append('turf_id', this.currentTurf.id);
        formData.append('day', day);
        formData.append('slots', JSON.stringify(slots));
        formData.append('csrf_token', this.csrfToken);

        const response = await fetch('api/manage_slots.php', { method: 'POST', body: formData });
        const result = await response.json();
        alert(result.message);
        if (result.status === 'success') this.fetchSlots();
    },

    openCopyModal(day) {
        this.copySourceDay = day;
        const container = document.getElementById('targetDaysList');
        if (!container) return;
        
        container.innerHTML = Object.keys(this.schedule).filter(d => d !== day).map(d => `
            <label style="display:flex; align-items:center; gap:10px; cursor:pointer; background:rgba(255,255,255,0.05); padding:10px; border-radius:10px;">
                <input type="checkbox" name="targetDay" value="${d}"> ${d}
            </label>
        `).join('');
        
        if (typeof openModal === 'function') openModal('copyModal');
    },

    async confirmCopy() {
        const selected = Array.from(document.querySelectorAll('input[name="targetDay"]:checked')).map(i => i.value);
        if (selected.length === 0) return alert("Select at least one day.");

        const formData = new FormData();
        formData.append('action', 'copy_to_days');
        formData.append('turf_id', this.currentTurf.id);
        formData.append('source_day', this.copySourceDay);
        formData.append('target_days', JSON.stringify(selected));
        formData.append('csrf_token', this.csrfToken);

        const response = await fetch('api/manage_slots.php', { method: 'POST', body: formData });
        const result = await response.json();
        alert(result.message);
        if (result.status === 'success') {
            if (typeof closeModal === 'function') closeModal('copyModal');
            this.fetchSlots();
        }
    },

    async addNewTurf() {
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('name', document.getElementById('add_turfName').value);
        formData.append('price', document.getElementById('add_turfPrice').value);
        formData.append('location', document.getElementById('add_turfLoc').value);
        formData.append('category', document.getElementById('add_turfSport').value);
        formData.append('description', document.getElementById('add_turfDesc').value);
        formData.append('available_days', document.getElementById('add_turfDays').value);
        const file = document.getElementById('add_turfImage').files[0];
        if (file) formData.append('image', file);
        formData.append('csrf_token', this.csrfToken);

        const response = await fetch('api/manage_turfs.php', { method: 'POST', body: formData });
        const result = await response.json();
        alert(result.message);
        if (result.status === 'success') {
            document.getElementById('addTurfForm').reset();
            this.fetchTurfs();
            showSection('my-turfs');
        }
    },

    async updateTurf() {
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('turf_id', this.currentTurf.id);
        formData.append('name', document.getElementById('edit_turfName').value);
        formData.append('price', document.getElementById('edit_turfPrice').value);
        formData.append('csrf_token', this.csrfToken);
        const response = await fetch('api/manage_turfs.php', { method: 'POST', body: formData });
        const result = await response.json();
        alert(result.message);
        if (result.status === 'success') this.fetchTurfs();
    },

    switchManageTab(tab, btn) {
        document.querySelectorAll('.manage-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        btn.classList.add('active');
    },

    async requestMaintenance() {
        const formData = new FormData();
        formData.append('turf_id', this.currentTurf.id);
        formData.append('start_date', document.getElementById('m_startDate').value);
        formData.append('end_date', document.getElementById('m_endDate').value);
        formData.append('reason', document.getElementById('m_reason').value);
        formData.append('csrf_token', this.csrfToken);
        const response = await fetch('api/maintenance_requests.php', { method: 'POST', body: formData });
        const result = await response.json();
        alert(result.message);
        if (result.status === 'success') this.fetchMaintenanceRequests(this.currentTurf.id);
    },

    async fetchMaintenanceRequests(id) {
        const response = await fetch(`api/maintenance_requests.php?turf_id=${id}`);
        const result = await response.json();
        const list = document.getElementById('m_requestList');
        if (result.status === 'success' && result.data.length > 0) {
            list.innerHTML = result.data.map(r => `
                <div style="padding:10px; margin-bottom:5px; background:rgba(255,255,255,0.02); border:1px solid var(--border); border-radius:8px;">
                    <div class="d-flex justify-content-between"><small>${r.start_date}</small> <span class="status-pill pill-${r.status}" style="font-size:8px;">${r.status}</span></div>
                </div>
            `).join('');
        } else {
            list.innerHTML = '<small style="opacity:0.5;">No history.</small>';
        }
    },

    async fetchEquipment() {
        const response = await fetch('api/manage_equipment.php');
        const result = await response.json();
        const container = document.getElementById('itemList');
        if (result.status === 'success' && result.data.length > 0) {
            container.innerHTML = result.data.map(item => `<div class="item-row"><div><strong>${item.name}</strong> <small style="opacity:0.7">(${item.turf_name || 'All'})</small><br><small>₹${item.price_per_session}</small></div></div>`).join('');
        } else {
            container.innerHTML = '<p style="opacity: 0.5; text-align: center;">No equipment found.</p>';
        }
    },

    async fetchBookings() {
        const response = await fetch('api/owner/get_bookings.php');
        const result = await response.json();
        const container = document.getElementById('bookingBody');
        if (result.status === 'success' && result.data.length > 0) {
            container.innerHTML = result.data.map(b => `<tr><td>${b.user_name}</td><td>${b.booking_date}</td><td>${b.slot_label}</td><td>None</td><td>${b.status}</td><td>---</td></tr>`).join('');
        }
    },

    async addEquipment() {
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('name', document.getElementById('itemName').value);
        formData.append('price', document.getElementById('itemPrice').value);
        formData.append('turf_id', document.getElementById('itemTurfId').value);
        formData.append('csrf_token', this.csrfToken);

        const response = await fetch('api/manage_equipment.php', { method: 'POST', body: formData });
        const result = await response.json();
        alert(result.message);
        if (result.status === 'success') {
            if (typeof closeModal === 'function') closeModal('itemModal');
            document.getElementById('itemName').value = '';
            document.getElementById('itemPrice').value = '';
            this.fetchEquipment();
        }
    }
};

// INITIALIZE AND EXPOSE
document.addEventListener('DOMContentLoaded', () => TurfKickOwner.init());
window.TurfKickOwner = TurfKickOwner;
window.confirmCopy = () => TurfKickOwner.confirmCopy();
window.addEquipment = () => TurfKickOwner.addEquipment();
