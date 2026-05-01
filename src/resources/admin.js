var resources = [];

document.addEventListener("DOMContentLoaded", function () {

    loadAndInitialize();

});

function createResourceRow(resource) {

    const row = document.createElement("tr");

    row.innerHTML = `

        <td>${resource.title}</td>

        <td>${resource.description}</td>

        <td><a href="${resource.link}" target="_blank">${resource.link}</a></td>

        <td>

            <button type="button" class="edit-btn" data-id="${resource.id}">Edit</button>

            <button type="button" class="delete-btn" data-id="${resource.id}">Delete</button>

        </td>

    `;

    return row;

}

function renderTable(resourceList) {

    const tbody = document.getElementById("resources-tbody");

    if (!tbody) {

        return;

    }

    tbody.innerHTML = "";

    resourceList.forEach(function (resource) {

        tbody.appendChild(createResourceRow(resource));

    });

}

function handleAddResource(event) {

    event.preventDefault();

    const title = document.getElementById("resource-title").value.trim();

    const description = document.getElementById("resource-description").value.trim();

    const link = document.getElementById("resource-link").value.trim();

    if (title === "" || description === "" || link === "") {

        return;

    }

    fetch("./api/index.php?action=create", {

        method: "POST",

        headers: {

            "Content-Type": "application/json"

        },

        body: JSON.stringify({

            title: title,

            description: description,

            link: link

        })

    })

    .then(function (response) {

        return response.json();

    })

    .then(function (result) {

        if (result.data) {

            resources.push(result.data);

            renderTable(resources);

        }

    });

}

function handleTableClick(event) {

    const target = event.target;

    if (target.classList.contains("delete-btn")) {

        const id = target.dataset.id;

        fetch("./api/index.php?action=delete&id=" + encodeURIComponent(id), {

            method: "DELETE"

        });

        return;

    }

    if (target.classList.contains("edit-btn")) {

        const id = target.dataset.id;

        const selected = resources.find(function (resource) {

            return String(resource.id) === String(id);

        });

        if (!selected) {

            return;

        }

        document.getElementById("resource-title").value = selected.title;

        document.getElementById("resource-description").value = selected.description;

        document.getElementById("resource-link").value = selected.link;

    }

}

async function loadAndInitialize() {

    const response = await fetch("./api/index.php");

    const result = await response.json();

    resources = result.data || result.resources || [];

    renderTable(resources);

    if (!loadAndInitialize._listenersAttached) {

        const form = document.getElementById("resource-form");

        const tbody = document.getElementById("resources-tbody");

        if (form) {

            form.addEventListener("submit", handleAddResource);

        }

        if (tbody) {

            tbody.addEventListener("click", handleTableClick);

        }

        loadAndInitialize._listenersAttached = true;

    }

}
