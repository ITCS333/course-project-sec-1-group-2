// --- Global Data Store ---
let weeks = [];

// --- Element Selections ---
const weekForm = document.getElementById('week-form');
const weeksTbody = document.getElementById('weeks-tbody');
const addWeekBtn = document.getElementById('add-week');

// --- Functions ---

 
function createWeekRow(week) {
    const tr = document.createElement('tr');

    
    const tdTitle = document.createElement('td');
    tdTitle.textContent = week.title;
    tr.appendChild(tdTitle);

    
    const tdDate = document.createElement('td');
    tdDate.textContent = week.start_date;
    tr.appendChild(tdDate);


    const tdDesc = document.createElement('td');
    tdDesc.textContent = week.description;
    tr.appendChild(tdDesc);

    
    const tdActions = document.createElement('td');

    const editBtn = document.createElement('button');
    editBtn.textContent = 'Edit';
    editBtn.className = 'edit-btn btn btn-sm btn-warning me-2';
    editBtn.dataset.id = week.id;
    tdActions.appendChild(editBtn);

    const deleteBtn = document.createElement('button');
    deleteBtn.textContent = 'Delete';
    deleteBtn.className = 'delete-btn btn btn-sm btn-danger';
    deleteBtn.dataset.id = week.id;
    tdActions.appendChild(deleteBtn);

    tr.appendChild(tdActions);

    return tr;
}

function renderTable() {
    weeksTbody.innerHTML = '';
    weeks.forEach(week => {
        weeksTbody.appendChild(createWeekRow(week));
    });
}

async function handleAddWeek(event) {
    event.preventDefault();

    const title = document.getElementById('week-title').value.trim();
    const start_date = document.getElementById('week-start-date').value;
    const description = document.getElementById('week-description').value.trim();
    const links = document.getElementById('week-links').value
        .split('\n')
        .map(l => l.trim())
        .filter(l => l !== '');

    const editId = addWeekBtn.dataset.editId;

    if (editId) {
       
        await handleUpdateWeek(parseInt(editId), { title, start_date, description, links });
        return;
    }

    
        try {
        const response = await fetch('./api/index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ title, start_date, description, links })
        });
        const result = await response.json();
        if (result.success) {
            
            weeks.push({ id: result.id, title, start_date, description, links });
            renderTable();
            weekForm.reset();
        } else {
            alert('Failed to add week.');
        }
    } catch (err) {
        console.error(err);
        alert('Error adding week.');
    }
}


async function handleUpdateWeek(id, fields) {
    try {
        const response = await fetch('./api/index.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, ...fields })
        });
        const result = await response.json();
        if (result.success) {
            const index = weeks.findIndex(w => w.id === id);
            if (index !== -1) {
                weeks[index] = { id, ...fields };
            }
            renderTable();
            weekForm.reset();
            addWeekBtn.textContent = 'Add Week';
            delete addWeekBtn.dataset.editId;
        } else {
            alert('Failed to update week.');
        }
    } catch (err) {
        console.error(err);
        alert('Error updating week.');
    }
}


async function handleTableClick(event) {
    const target = event.target;
    const id = parseInt(target.dataset.id);

    if (target.classList.contains('delete-btn')) {
        if (!confirm('Are you sure you want to delete this week?')) return;
        try {
            const response = await fetch(`./api/index.php?id=${id}`, { method: 'DELETE' });
            const result = await response.json();
            if (result.success) {
                weeks = weeks.filter(w => w.id !== id);
                renderTable();
            } else {
                alert('Failed to delete week.');
            }
        } catch (err) {
            console.error(err);
            alert('Error deleting week.');
        }
    } else if (target.classList.contains('edit-btn')) {
        const week = weeks.find(w => w.id === id);
        if (!week) return;

        document.getElementById('week-title').value = week.title;
        document.getElementById('week-start-date').value = week.start_date;
        document.getElementById('week-description').value = week.description;
        document.getElementById('week-links').value = week.links.join('\n');

        addWeekBtn.textContent = 'Update Week';
        addWeekBtn.dataset.editId = id;
    }
}


async function loadAndInitialize() {
    try {
        const response = await fetch('./api/index.php');
        const result = await response.json();
        if (result.success) {
            weeks = result.data;
            renderTable();
        } else {
            alert('Failed to load weeks.');
        }
    } catch (err) {
        console.error(err);
        alert('Error loading weeks.');
    }

   
    weekForm.addEventListener('submit', handleAddWeek);
    weeksTbody.addEventListener('click', handleTableClick);
}

// --- Initial Page Load ---
loadAndInitialize();
